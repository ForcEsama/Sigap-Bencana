import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../services/offline_cache.dart';
import 'sos_request.dart';

class SosApiException implements Exception {
  const SosApiException(this.statusCode, this.message);

  final int statusCode;
  final String message;

  @override
  String toString() => message;
}

class SosSessionExpired implements Exception {
  const SosSessionExpired();
}

class SosQueueException implements Exception {
  const SosQueueException(this.message);

  final String message;
}

class SosRequestPage {
  const SosRequestPage({
    required this.requests,
    required this.currentPage,
    required this.lastPage,
  });

  final List<SosRequest> requests;
  final int currentPage;
  final int lastPage;
}

class SosService {
  bool lastReadFromCache = false;

  static const String _pendingRequestsKey = 'pending_sos_requests';

  Future<SosRequest> createRequest(Map<String, dynamic> payload) async {
    lastReadFromCache = false;
    final response = await _send(
      (token) => http.post(
        Uri.parse(ApiConfig.sos),
        headers: _headers(token),
        body: jsonEncode(payload),
      ),
    );

    final request = _readRequest(response['data']);
    await removePendingRequest(payload['client_request_id']?.toString() ?? '');
    await _cacheRequest(request);
    return request;
  }

  Future<SosRequestPage> fetchRequests({int page = 1}) async {
    lastReadFromCache = false;
    try {
      final uri = Uri.parse(ApiConfig.mySos)
          .replace(queryParameters: {'page': page.toString()});
      final response = await _send(
        (token) => http.get(uri, headers: _headers(token)),
      );
      final rows = response['data'];

      if (rows is! List) {
        throw const SosApiException(500, 'Respons riwayat SOS tidak valid.');
      }

      await OfflineCache.write(
        'sos_page_$page',
        response,
        accountScoped: true,
        maxCharacters: 250000,
      );
      return _sosRequestPageFrom(response, page);
    } on SosApiException catch (error) {
      if (error.statusCode != 0) rethrow;
      final cached = await OfflineCache.read(
        'sos_page_$page',
        accountScoped: true,
      );
      if (cached is! Map<String, dynamic>) rethrow;

      lastReadFromCache = true;
      return _sosRequestPageFrom(cached, page);
    }
  }

  SosRequestPage _sosRequestPageFrom(Map<String, dynamic> response, int page) {
    final rows = response['data'];
    final meta = response['meta'];
    return SosRequestPage(
      requests: rows
          .whereType<Map<String, dynamic>>()
          .map(SosRequest.fromJson)
          .toList(),
      currentPage: _readPage(meta, 'current_page', page),
      lastPage: _readPage(meta, 'last_page', page),
    );
  }

  Future<SosRequest> fetchRequest(int id) async {
    lastReadFromCache = false;
    try {
      final response = await _send(
        (token) => http.get(
          Uri.parse(ApiConfig.sosRequest(id)),
          headers: _headers(token),
        ),
      );

      final request = _readRequest(response['data']);
      await _cacheRequest(request);
      return request;
    } on SosApiException catch (error) {
      if (error.statusCode != 0) rethrow;
      final cached = await OfflineCache.read(
        'sos_request_$id',
        accountScoped: true,
      );
      if (cached is! Map<String, dynamic>) rethrow;

      lastReadFromCache = true;
      return _readRequest(cached);
    }
  }

  Future<void> enqueuePendingRequest(Map<String, dynamic> payload) async {
    final clientRequestId = payload['client_request_id']?.toString();
    if (clientRequestId == null || clientRequestId.isEmpty) {
      throw const SosQueueException('ID permintaan SOS tidak tersedia.');
    }

    final pending = await fetchPendingRequests();
    pending.removeWhere(
      (request) => request['client_request_id'] == clientRequestId,
    );
    pending.add({
      ...payload,
      'queued_at': DateTime.now().toUtc().toIso8601String(),
    });
    final saved = await OfflineCache.write(
      _pendingRequestsKey,
      pending,
      accountScoped: true,
      maxCharacters: 100000,
    );
    if (!saved) {
      throw const SosQueueException(
        'Permintaan belum dapat disimpan di perangkat. Jangan tutup halaman ini; coba kirim ulang.',
      );
    }
  }

  Future<List<Map<String, dynamic>>> fetchPendingRequests() async {
    final pending = await OfflineCache.read(
      _pendingRequestsKey,
      accountScoped: true,
    );
    return pending is List
        ? pending.whereType<Map<String, dynamic>>().toList()
        : <Map<String, dynamic>>[];
  }

  Future<void> removePendingRequest(String clientRequestId) async {
    if (clientRequestId.isEmpty) return;

    final pending = await fetchPendingRequests()
      ..removeWhere(
        (request) => request['client_request_id'] == clientRequestId,
      );
    await OfflineCache.write(
      _pendingRequestsKey,
      pending,
      accountScoped: true,
      maxCharacters: 100000,
    );
  }

  Future<void> _cacheRequest(SosRequest request) async {
    await OfflineCache.write(
      'sos_request_${request.id}',
      request.toJson(),
      accountScoped: true,
    );
  }

  Future<Map<String, dynamic>> _send(
    Future<http.Response> Function(String token) send,
  ) async {
    String? token;
    try {
      token = await AuthService.getToken();
    } on Exception {
      throw const SosApiException(
        0,
        'Sesi belum dapat dibaca dari penyimpanan aman. Coba lagi.',
      );
    }
    if (token == null || token.isEmpty) {
      throw const SosApiException(401, 'Sesi login tidak ditemukan.');
    }

    late final http.Response response;
    try {
      response = await send(token).timeout(const Duration(seconds: 8));
    } on Exception {
      throw const SosApiException(
        0,
        'Server belum mengonfirmasi permintaan. Periksa koneksi lalu coba ulang permintaan ini.',
      );
    }

    Map<String, dynamic> decoded;
    try {
      final payload = jsonDecode(response.body);
      decoded = payload is Map<String, dynamic> ? payload : {};
    } on FormatException {
      decoded = {};
    }

    if (response.statusCode == 401) {
      try {
        await AuthService.clearSession();
      } on Exception {
        // The 401 response still ends this request even if local cleanup fails.
      }
      throw const SosSessionExpired();
    }
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw SosApiException(
        response.statusCode,
        _messageFrom(decoded) ?? 'Permintaan SOS gagal diproses.',
      );
    }

    return decoded;
  }

  SosRequest _readRequest(dynamic value) {
    if (value is! Map<String, dynamic>) {
      throw const SosApiException(500, 'Respons SOS dari server tidak valid.');
    }

    try {
      return SosRequest.fromJson(value);
    } on TypeError {
      throw const SosApiException(500, 'Data SOS dari server tidak lengkap.');
    }
  }

  int _readPage(Map<String, dynamic>? meta, String key, int fallback) {
    final value = meta?[key];
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '') ?? fallback;
  }

  String? _messageFrom(Map<String, dynamic> payload) {
    final errors = payload['errors'];
    if (errors is Map) {
      final messages = errors.values.expand((value) {
        return value is List ? value : [value];
      });
      final message = messages.map((value) => value.toString()).join('\n');
      if (message.isNotEmpty) return message;
    }

    final message = payload['message']?.toString();
    return message?.isNotEmpty == true ? message : null;
  }

  Map<String, String> _headers(String token) => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token',
  };
}

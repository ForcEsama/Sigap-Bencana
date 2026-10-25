import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../services/offline_cache.dart';

class ReportApiException implements Exception {
  const ReportApiException(this.statusCode, this.message);

  final int statusCode;
  final String message;

  @override
  String toString() => message;
}

class ReportPage {
  const ReportPage({
    required this.reports,
    required this.currentPage,
    required this.lastPage,
  });

  final List<Map<String, dynamic>> reports;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

class ReportService {
  bool lastReadFromCache = false;

  Future<ReportPage> fetchReports({int page = 1}) async {
    lastReadFromCache = false;
    try {
      final uri = Uri.parse(ApiConfig.reports)
          .replace(queryParameters: {'page': page.toString()});
      final response = await _send(
        (headers) => http.get(uri, headers: headers),
      );
      await OfflineCache.write(
        'reports_page_$page',
        response,
        accountScoped: true,
        maxCharacters: 250000,
      );

      return _reportPageFrom(response, page);
    } on ReportApiException catch (error) {
      if (error.statusCode != 0) rethrow;
      final cached = await OfflineCache.read(
        'reports_page_$page',
        accountScoped: true,
      );
      if (cached is! Map<String, dynamic>) rethrow;

      lastReadFromCache = true;
      return _reportPageFrom(cached, page);
    }
  }

  ReportPage _reportPageFrom(Map<String, dynamic> response, int page) {
    final rows = response['data'];
    return ReportPage(
      reports: rows is List
          ? rows.whereType<Map<String, dynamic>>().toList()
          : const [],
      currentPage: _readPage(response['current_page'], page),
      lastPage: _readPage(response['last_page'], page),
    );
  }

  Future<Map<String, dynamic>> fetchReport(int id) async {
    lastReadFromCache = false;
    try {
      final response = await _send(
        (headers) =>
            http.get(Uri.parse(ApiConfig.report(id)), headers: headers),
      );
      final data = response['data'];

      if (data is! Map<String, dynamic>) {
        throw const ReportApiException(
          500,
          'Respons detail laporan tidak valid.',
        );
      }
      await OfflineCache.write('report_$id', data, accountScoped: true);

      return data;
    } on ReportApiException catch (error) {
      if (error.statusCode != 0) rethrow;
      final cached = await OfflineCache.read('report_$id', accountScoped: true);
      if (cached is! Map<String, dynamic>) rethrow;

      lastReadFromCache = true;
      return cached;
    }
  }

  Future<Map<String, dynamic>> createReport(
    Map<String, dynamic> payload,
  ) async {
    final response = await _send(
      (headers) => http.post(
        Uri.parse(ApiConfig.reports),
        headers: headers,
        body: jsonEncode(payload),
      ),
    );
    final data = response['data'];

    if (data is! Map<String, dynamic>) {
      throw const ReportApiException(500, 'Respons laporan tidak valid.');
    }

    return data;
  }

  Future<Map<String, dynamic>> _send(
    Future<http.Response> Function(Map<String, String> headers) request,
  ) async {
    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      throw const ReportApiException(401, 'Sesi login tidak ditemukan.');
    }

    late final http.Response response;

    try {
      response = await request({
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      }).timeout(const Duration(seconds: 8));
    } on Exception {
      throw const ReportApiException(
        0,
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }

    Map<String, dynamic> payload;

    try {
      final decoded = jsonDecode(response.body);
      payload = decoded is Map<String, dynamic> ? decoded : {};
    } on FormatException {
      payload = {};
    }

    if (response.statusCode == 401) {
      await AuthService.clearSession();
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ReportApiException(
        response.statusCode,
        _messageFrom(payload) ?? 'Permintaan laporan gagal diproses.',
      );
    }

    return payload;
  }

  int _readPage(dynamic value, int fallback) {
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
}

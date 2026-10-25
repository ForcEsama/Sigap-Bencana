import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import 'disaster_warning.dart';

class WarningService {
  WarningService({this.client, FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final http.Client? client;
  final FlutterSecureStorage _storage;
  static const String _cacheKey = 'public_active_warnings_cache';

  Future<List<DisasterWarning>> fetchActiveWarnings() async {
    final response = await _get(Uri.parse(ApiConfig.warnings));
    final records = _decode(response)['data'];
    if (records is! List) {
      throw const WarningApiException(
        'Format respons peringatan tidak dikenali.',
      );
    }

    final parsed =
        records
            .whereType<Map<String, dynamic>>()
            .map(DisasterWarning.fromJson)
            .toList()
          ..sort(DisasterWarning.comparePriority);
    try {
      await _storage.write(key: _cacheKey, value: jsonEncode(records));
    } catch (_) {
      // Cached public data is optional; a storage failure must not hide live data.
    }
    return parsed;
  }

  Future<List<DisasterWarning>> fetchCachedActiveWarnings() async {
    try {
      final cached = await _storage.read(key: _cacheKey);
      final decoded = cached == null ? null : jsonDecode(cached);
      if (decoded is! List) return const [];

      final now = DateTime.now();
      return decoded
          .whereType<Map<String, dynamic>>()
          .map(DisasterWarning.fromJson)
          .where(
            (warning) =>
                warning.status == 'published' &&
                warning.expiresAt.isAfter(now) &&
                (warning.startsAt == null || !warning.startsAt!.isAfter(now)),
          )
          .toList()
        ..sort(DisasterWarning.comparePriority);
    } catch (_) {
      return const [];
    }
  }

  Future<DisasterWarning> fetchWarning(int id) async {
    final response = await _get(Uri.parse(ApiConfig.warning(id)));
    final record = _decode(response)['data'];
    if (record is! Map<String, dynamic>) {
      throw const WarningApiException(
        'Format detail peringatan tidak dikenali.',
      );
    }

    return DisasterWarning.fromJson(record);
  }

  Future<http.Response> _get(Uri uri) {
    final request = client == null
        ? http.get(uri, headers: const {'Accept': 'application/json'})
        : client!.get(uri, headers: const {'Accept': 'application/json'});
    return request.timeout(const Duration(seconds: 6));
  }

  Map<String, dynamic> _decode(http.Response response) {
    final Object? decoded;
    try {
      decoded = jsonDecode(response.body);
    } on FormatException {
      throw const WarningApiException(
        'Server mengirim respons yang tidak valid.',
      );
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw WarningApiException(
        decoded is Map<String, dynamic> && decoded['message'] is String
            ? decoded['message'] as String
            : 'Peringatan tidak dapat dimuat (HTTP ${response.statusCode}).',
      );
    }
    if (decoded is! Map<String, dynamic>) {
      throw const WarningApiException(
        'Format respons peringatan tidak dikenali.',
      );
    }

    return decoded;
  }
}

class WarningApiException implements Exception {
  const WarningApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

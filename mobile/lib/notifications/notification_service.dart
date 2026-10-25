import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../services/offline_cache.dart';
import 'notification_item.dart';

class NotificationServiceException implements Exception {
  const NotificationServiceException(this.message);

  final String message;
}

class NotificationSessionExpired implements Exception {
  const NotificationSessionExpired();
}

class NotificationService {
  bool lastReadFromCache = false;

  Future<List<NotificationItem>> fetchNotifications({String? category}) async {
    lastReadFromCache = false;
    final cacheKey = 'notifications_${category ?? 'all'}';
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw const NotificationSessionExpired();
    }

    final uri = Uri.parse(ApiConfig.notifications)
        .replace(queryParameters: {'category': ?category, 'per_page': '50'});

    try {
      final response = await http
          .get(uri, headers: _headers(token))
          .timeout(const Duration(seconds: 8));

      if (response.statusCode == 401) {
        await AuthService.clearSession();
        throw const NotificationSessionExpired();
      }
      if (response.statusCode != 200) {
        throw const NotificationServiceException(
          'Notifikasi belum dapat dimuat. Coba lagi.',
        );
      }

      final decoded = jsonDecode(response.body);
      final records = decoded is Map<String, dynamic> ? decoded['data'] : null;
      if (records is! List) {
        throw const NotificationServiceException(
          'Respons notifikasi dari server tidak valid.',
        );
      }

      await OfflineCache.write(
        cacheKey,
        records,
        accountScoped: true,
        maxCharacters: 250000,
      );
      return _itemsFrom(records);
    } on NotificationSessionExpired {
      rethrow;
    } on NotificationServiceException {
      final cached = await OfflineCache.read(cacheKey, accountScoped: true);
      if (cached is List) {
        lastReadFromCache = true;
        return _itemsFrom(cached);
      }
      rethrow;
    } catch (_) {
      final cached = await OfflineCache.read(cacheKey, accountScoped: true);
      if (cached is List) {
        lastReadFromCache = true;
        return _itemsFrom(cached);
      }
      throw const NotificationServiceException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  List<NotificationItem> _itemsFrom(Object? records) => (records as List)
      .whereType<Map<String, dynamic>>()
      .map(NotificationItem.fromJson)
      .toList();

  Future<NotificationItem> markRead(int id) async {
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw const NotificationSessionExpired();
    }

    try {
      final response = await http
          .patch(
            Uri.parse('${ApiConfig.notifications}/$id/read'),
            headers: _headers(token),
          )
          .timeout(const Duration(seconds: 8));

      if (response.statusCode == 401) {
        await AuthService.clearSession();
        throw const NotificationSessionExpired();
      }
      if (response.statusCode != 200) {
        throw const NotificationServiceException(
          'Status notifikasi gagal diperbarui.',
        );
      }

      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return NotificationItem.fromJson(decoded['data'] as Map<String, dynamic>);
    } on NotificationSessionExpired {
      rethrow;
    } on NotificationServiceException {
      rethrow;
    } catch (_) {
      throw const NotificationServiceException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  Map<String, String> _headers(String token) => {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  };
}

import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'auth_service.dart';

class OfflineCache {
  static const FlutterSecureStorage _storage = FlutterSecureStorage();

  static Future<Object?> read(String key, {bool accountScoped = false}) async {
    try {
      final storageKey = await _storageKey(key, accountScoped: accountScoped);
      if (storageKey == null) return null;

      final encoded = await _storage.read(key: storageKey);
      if (encoded == null) return null;
      final envelope = jsonDecode(encoded);
      return envelope is Map<String, dynamic> ? envelope['data'] : null;
    } on Exception {
      return null;
    }
  }

  static Future<bool> write(
    String key,
    Object? value, {
    bool accountScoped = false,
    int maxCharacters = 400000,
  }) async {
    try {
      final storageKey = await _storageKey(key, accountScoped: accountScoped);
      if (storageKey == null) return false;

      final encoded = jsonEncode({
        'cached_at': DateTime.now().toUtc().toIso8601String(),
        'data': value,
      });
      if (encoded.length > maxCharacters) return false;

      await _storage.write(key: storageKey, value: encoded);
      return true;
    } on Exception {
      return false;
    }
  }

  static Future<String?> _storageKey(
    String key, {
    required bool accountScoped,
  }) async {
    if (!accountScoped) return 'offline_public_$key';

    final user = await AuthService.getSavedUser();
    final userId = user?['id']?.toString();
    if (userId == null || userId.isEmpty) return null;

    return 'offline_user_${userId}_$key';
  }
}

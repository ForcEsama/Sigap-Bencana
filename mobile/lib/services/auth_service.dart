
import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../config/api_config.dart';

class AuthService {
  static const FlutterSecureStorage _storage =
      FlutterSecureStorage();

  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';

  static Future<void> saveSession({
    required String token,
    required Map<String, dynamic> user,
  }) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(
      key: _userKey,
      value: jsonEncode(user),
    );
  }

  static Future<String?> getToken() async {
    return _storage.read(key: _tokenKey);
  }

  static Future<Map<String, dynamic>?> getSavedUser() async {
    final value = await _storage.read(key: _userKey);

    if (value == null) return null;

    try {
      final decoded = jsonDecode(value);

      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      return null;
    }

    return null;
  }

  static Future<Map<String, dynamic>?> fetchUser() async {
    final token = await getToken();

    if (token == null || token.isEmpty) return null;

    try {
      final response = await http.get(
        Uri.parse(ApiConfig.user),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);

        if (decoded is Map<String, dynamic>) {
          await _storage.write(
            key: _userKey,
            value: jsonEncode(decoded),
          );
          return decoded;
        }
      }

      if (response.statusCode == 401) {
        await clearSession();
      }
    } catch (_) {
      // Koneksi gagal; jangan langsung menghapus token.
    }

    return null;
  }

  static Future<void> clearSession() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _userKey);
  }
}

import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';

class ProfileSessionExpired implements Exception {}

class ProfileServiceException implements Exception {
  const ProfileServiceException(this.message);

  final String message;
}

class ProfileService {
  static Future<Map<String, dynamic>> fetchProfile() async {
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw ProfileSessionExpired();
    }

    try {
      final response = await http
          .get(Uri.parse(ApiConfig.user), headers: _headers(token))
          .timeout(const Duration(seconds: 15));

      if (response.statusCode == 401) {
        await AuthService.clearSession();
        throw ProfileSessionExpired();
      }
      if (response.statusCode != 200) {
        throw const ProfileServiceException(
          'Profil belum dapat dimuat. Coba lagi.',
        );
      }

      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic>) {
        throw const ProfileServiceException('Respons profil tidak valid.');
      }

      await AuthService.saveSession(token: token, user: decoded);
      return decoded;
    } on ProfileSessionExpired {
      rethrow;
    } on ProfileServiceException {
      rethrow;
    } catch (_) {
      throw const ProfileServiceException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  static Future<Map<String, dynamic>> updateProfile({
    required String name,
    required String email,
    required String phone,
    required String domicileAddress,
    required String province,
    required String regencyCity,
    required String district,
    required String village,
    required String postalCode,
    required String dateOfBirth,
    required String gender,
  }) async {
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw ProfileSessionExpired();
    }

    try {
      final response = await http
          .patch(
            Uri.parse(ApiConfig.updateUser),
            headers: _headers(token),
            body: jsonEncode({
              'name': name,
              'email': email,
              'phone': phone.isEmpty ? null : phone,
              'domicile_address': _nullableValue(domicileAddress),
              'province': _nullableValue(province),
              'regency_city': _nullableValue(regencyCity),
              'district': _nullableValue(district),
              'village': _nullableValue(village),
              'postal_code': _nullableValue(postalCode),
              'date_of_birth': _nullableValue(dateOfBirth),
              'gender': _nullableValue(gender),
            }),
          )
          .timeout(const Duration(seconds: 15));

      if (response.statusCode == 401) {
        await AuthService.clearSession();
        throw ProfileSessionExpired();
      }
      if (response.statusCode == 422) {
        final decoded = jsonDecode(response.body);
        final errors = decoded is Map<String, dynamic>
            ? decoded['errors']
            : null;
        final firstError = errors is Map<String, dynamic>
            ? (errors.isEmpty ? null : errors.values.first)
            : null;
        if (firstError is List && firstError.isNotEmpty) {
          throw ProfileServiceException(firstError.first.toString());
        }
        throw const ProfileServiceException('Periksa kembali data profil.');
      }
      if (response.statusCode == 503) {
        throw const ProfileServiceException(
          'Profil tambahan belum dapat disimpan karena database server belum diperbarui.',
        );
      }
      if (response.statusCode != 200) {
        throw const ProfileServiceException(
          'Profil belum dapat disimpan. Coba lagi.',
        );
      }

      final decoded = jsonDecode(response.body);
      final user = decoded is Map<String, dynamic> ? decoded['user'] : null;
      if (user is! Map<String, dynamic>) {
        throw const ProfileServiceException('Respons profil tidak valid.');
      }

      await AuthService.saveSession(token: token, user: user);
      return user;
    } on ProfileSessionExpired {
      rethrow;
    } on ProfileServiceException {
      rethrow;
    } catch (_) {
      throw const ProfileServiceException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  static Map<String, String> _headers(String token) => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token',
  };

  static String? _nullableValue(String value) {
    final trimmedValue = value.trim();

    return trimmedValue.isEmpty ? null : trimmedValue;
  }
}

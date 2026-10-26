import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/offline_cache.dart';

class EmergencyContact {
  const EmergencyContact({
    required this.id,
    required this.name,
    required this.category,
    required this.phone,
    this.description,
    this.region,
    this.address,
    this.websiteUrl,
  });

  final int id;
  final String name;
  final String category;
  final String phone;
  final String? description;
  final String? region;
  final String? address;
  final String? websiteUrl;

  factory EmergencyContact.fromJson(Map<String, dynamic> json) {
    return EmergencyContact(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? 'Kontak darurat',
      category: json['category']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      description: _optionalString(json['description']),
      region: _optionalString(json['region']),
      address: _optionalString(json['address']),
      websiteUrl: _optionalString(json['website_url']),
    );
  }

  static String? _optionalString(Object? value) {
    final text = value?.toString().trim();
    return text == null || text.isEmpty ? null : text;
  }
}

class EmergencyContactDirectoryResult {
  const EmergencyContactDirectoryResult({
    required this.contacts,
    required this.isOffline,
  });

  final List<EmergencyContact> contacts;
  final bool isOffline;
}

class EmergencyContactDirectoryException implements Exception {
  const EmergencyContactDirectoryException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;
}

class EmergencyContactDirectoryService {
  static const String _cacheKey = 'emergency_contacts_directory';

  Future<EmergencyContactDirectoryResult> fetchContacts() async {
    try {
      final response = await http
          .get(
            Uri.parse(ApiConfig.emergencyContacts),
            headers: {'Accept': 'application/json'},
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 401) {
        throw const EmergencyContactDirectoryException(
          'Sesi masuk telah berakhir. Silakan masuk kembali.',
          statusCode: 401,
        );
      }
      if (response.statusCode != 200) {
        throw EmergencyContactDirectoryException(
          'Kontak darurat belum dapat dimuat (HTTP ${response.statusCode}).',
          statusCode: response.statusCode,
        );
      }

      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic> || decoded['data'] is! List) {
        throw const EmergencyContactDirectoryException(
          'Data kontak darurat dari server tidak valid.',
        );
      }

      final contacts = (decoded['data'] as List)
          .whereType<Map<String, dynamic>>()
          .map(EmergencyContact.fromJson)
          .toList(growable: false);
      await OfflineCache.write(
        _cacheKey,
        decoded,
        accountScoped: false,
        maxCharacters: 100000,
      );

      return EmergencyContactDirectoryResult(
        contacts: contacts,
        isOffline: false,
      );
    } on EmergencyContactDirectoryException catch (error) {
      return _cachedOrThrow(error);
    } catch (_) {
      return _cachedOrThrow(
        const EmergencyContactDirectoryException(
          'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
        ),
      );
    }
  }

  Future<EmergencyContactDirectoryResult> _cachedOrThrow(
    EmergencyContactDirectoryException error,
  ) async {
    final cached = await OfflineCache.read(_cacheKey, accountScoped: false);
    if (cached is Map<String, dynamic> && cached['data'] is List) {
      final contacts = (cached['data'] as List)
          .whereType<Map<String, dynamic>>()
          .map(EmergencyContact.fromJson)
          .toList(growable: false);
      return EmergencyContactDirectoryResult(
        contacts: contacts,
        isOffline: true,
      );
    }

    throw error;
  }
}

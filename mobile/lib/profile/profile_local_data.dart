import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ProfileLocalData {
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  static const String _locationsKey = 'profile_saved_locations';
  static const String _contactsKey = 'profile_emergency_contacts';
  static const String _notificationPreferencesKey =
      'profile_notification_preferences';

  static const Map<String, bool> defaultNotificationPreferences = {
    'Peringatan': true,
    'Informasi': true,
    'Aktivitas': true,
  };

  static Future<List<Map<String, dynamic>>> readSavedLocations() =>
      _readList(_locationsKey);

  static Future<void> saveSavedLocations(
    List<Map<String, dynamic>> locations,
  ) => _writeList(_locationsKey, locations);

  static Future<List<Map<String, dynamic>>> readEmergencyContacts() =>
      _readList(_contactsKey);

  static Future<void> saveEmergencyContacts(
    List<Map<String, dynamic>> contacts,
  ) => _writeList(_contactsKey, contacts);

  static Future<Map<String, bool>> readNotificationPreferences() async {
    try {
      final encoded = await _storage.read(key: _notificationPreferencesKey);
      if (encoded == null) return defaultNotificationPreferences;
      final decoded = jsonDecode(encoded);
      if (decoded is! Map<String, dynamic>) {
        return defaultNotificationPreferences;
      }

      return {
        for (final category in defaultNotificationPreferences.keys)
          category: decoded[category] is bool
              ? decoded[category] as bool
              : true,
      };
    } catch (_) {
      return defaultNotificationPreferences;
    }
  }

  static Future<void> saveNotificationPreferences(
    Map<String, bool> preferences,
  ) async {
    await _storage.write(
      key: _notificationPreferencesKey,
      value: jsonEncode(preferences),
    );
  }

  static Future<List<Map<String, dynamic>>> _readList(String key) async {
    try {
      final encoded = await _storage.read(key: key);
      if (encoded == null) return [];
      final decoded = jsonDecode(encoded);
      if (decoded is! List) return [];

      return decoded
          .whereType<Map<String, dynamic>>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> _writeList(
    String key,
    List<Map<String, dynamic>> values,
  ) => _storage.write(key: key, value: jsonEncode(values));
}

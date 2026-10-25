import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter/services.dart';

class CurrentLocation {
  const CurrentLocation({
    required this.latitude,
    required this.longitude,
    required this.accuracyMeters,
    required this.capturedAt,
    this.address,
  });

  final double latitude;
  final double longitude;
  final double accuracyMeters;
  final DateTime capturedAt;
  final String? address;

  factory CurrentLocation.fromMap(Map<Object?, Object?> value) {
    return CurrentLocation(
      latitude: (value['latitude'] as num).toDouble(),
      longitude: (value['longitude'] as num).toDouble(),
      accuracyMeters: (value['accuracy'] as num).toDouble(),
      capturedAt: DateTime.fromMillisecondsSinceEpoch(
        value['captured_at'] as int,
      ),
      address: (value['address'] as String?)?.trim().isNotEmpty == true
          ? (value['address'] as String).trim()
          : null,
    );
  }

  Map<String, Object?> toMap() => {
    'latitude': latitude,
    'longitude': longitude,
    'accuracy': accuracyMeters,
    'captured_at': capturedAt.millisecondsSinceEpoch,
    'address': address,
  };

  String get formattedCoordinates =>
      '${latitude.toStringAsFixed(6)}, ${longitude.toStringAsFixed(6)}';

  String get displayLabel => address ?? formattedCoordinates;
}

class LocationService {
  static const MethodChannel _channel = MethodChannel('sigap/location');
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  static const String _lastLocationKey = 'last_detected_current_location';

  Future<CurrentLocation?> readLastDetectedLocation() async {
    try {
      final encoded = await _storage.read(key: _lastLocationKey);
      if (encoded == null) return null;

      final decoded = jsonDecode(encoded);
      if (decoded is! Map<String, dynamic> ||
          decoded['latitude'] is! num ||
          decoded['longitude'] is! num ||
          decoded['accuracy'] is! num ||
          decoded['captured_at'] is! int) {
        return null;
      }

      return CurrentLocation.fromMap(decoded);
    } on Exception {
      return null;
    }
  }

  Future<void> saveLastDetectedLocation(CurrentLocation location) async {
    await _storage.write(
      key: _lastLocationKey,
      value: jsonEncode(location.toMap()),
    );
  }

  Future<CurrentLocation> getCurrentLocation() async {
    try {
      final result = await _channel.invokeMapMethod<Object?, Object?>(
        'getCurrentLocation',
      );
      if (result == null ||
          result['latitude'] is! num ||
          result['longitude'] is! num ||
          result['accuracy'] is! num ||
          result['captured_at'] is! int ||
          (result['address'] != null && result['address'] is! String)) {
        throw const LocationServiceException(
          'Perangkat mengirim data lokasi dengan format tidak valid.',
        );
      }
      return CurrentLocation.fromMap(result);
    } on PlatformException catch (error) {
      throw LocationServiceException(
        error.message ?? 'Lokasi saat ini tidak dapat diambil.',
      );
    } on MissingPluginException {
      throw const LocationServiceException(
        'Deteksi lokasi hanya tersedia pada aplikasi Android.',
      );
    }
  }
}

class LocationServiceException implements Exception {
  const LocationServiceException(this.message);

  final String message;
}

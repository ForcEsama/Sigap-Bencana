import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/offline_cache.dart';
import 'evacuation_post.dart';

class EvacuationServiceException implements Exception {
  const EvacuationServiceException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;
}

class EvacuationPlacesResult {
  const EvacuationPlacesResult({required this.posts, required this.isOffline});

  final List<EvacuationPost> posts;
  final bool isOffline;
}

class EvacuationService {
  static const String _cacheKey = 'evacuation_posts';

  Future<EvacuationPlacesResult> fetchPlaces() async {
    try {
      final response = await http
          .get(
            Uri.parse(ApiConfig.evacuationPosts),
            headers: {'Accept': 'application/json'},
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 401) {
        throw const EvacuationServiceException(
          'Sesi masuk telah berakhir. Silakan masuk kembali.',
          statusCode: 401,
        );
      }
      if (response.statusCode != 200) {
        throw EvacuationServiceException(
          'Tempat evakuasi belum dapat dimuat (HTTP ${response.statusCode}).',
          statusCode: response.statusCode,
        );
      }

      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic> || decoded['data'] is! List) {
        throw const EvacuationServiceException(
          'Data tempat evakuasi dari server tidak valid.',
        );
      }

      final posts = _postsFrom(decoded);
      await OfflineCache.write(
        _cacheKey,
        decoded,
        accountScoped: false,
        maxCharacters: 300000,
      );

      return EvacuationPlacesResult(posts: posts, isOffline: false);
    } on EvacuationServiceException catch (error) {
      return _cachedOrThrow(error);
    } catch (_) {
      return _cachedOrThrow(
        const EvacuationServiceException(
          'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
        ),
      );
    }
  }

  Future<EvacuationPlacesResult> _cachedOrThrow(
    EvacuationServiceException error,
  ) async {
    final cached = await OfflineCache.read(_cacheKey, accountScoped: false);
    if (cached is Map<String, dynamic> && cached['data'] is List) {
      final posts = _postsFrom(cached);
      return EvacuationPlacesResult(posts: posts, isOffline: true);
    }

    throw error;
  }

  List<EvacuationPost> _postsFrom(Map<String, dynamic> response) {
    final data = response['data'] as List;
    final candidates = response['planning_candidates'];
    final combined = [
      ...data.whereType<Map<String, dynamic>>(),
      if (candidates is List) ...candidates.whereType<Map<String, dynamic>>(),
    ];

    return combined.map(EvacuationPost.fromJson).toList(growable: false);
  }
}

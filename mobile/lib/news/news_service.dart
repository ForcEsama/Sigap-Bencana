import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/offline_cache.dart';
import 'news_article.dart';

class NewsService {
  NewsService({this.client});

  final http.Client? client;
  bool lastReadFromCache = false;

  Future<List<NewsArticle>> fetchArticles({
    NewsCategory category = NewsCategory.all,
  }) async {
    lastReadFromCache = false;
    final queryParameters = <String, String>{'per_page': '20'};
    final categoryFilter = category.apiValue;
    if (categoryFilter != null) {
      queryParameters['category'] = categoryFilter;
    }

    final uri = Uri.parse(ApiConfig.news)
        .replace(queryParameters: queryParameters);
    try {
      final response = await _get(uri);
      final records = _decodeResponse(response)['data'];
      if (records is! List) {
        throw const NewsApiException('Format respons berita tidak dikenali.');
      }

      await OfflineCache.write('news_list_${category.name}', records);
      return _articlesFrom(records);
    } catch (_) {
      final records = await OfflineCache.read('news_list_${category.name}');
      if (records is! List) rethrow;

      lastReadFromCache = true;
      return _articlesFrom(records);
    }
  }

  List<NewsArticle> _articlesFrom(Object? records) => (records as List)
      .whereType<Map<String, dynamic>>()
      .map(NewsArticle.fromJson)
      .toList(growable: false);

  Future<NewsArticle> fetchArticle(String slug) async {
    lastReadFromCache = false;
    try {
      final uri = Uri.parse(ApiConfig.newsArticle(slug));
      final record = _decodeResponse(await _get(uri))['data'];
      if (record is! Map<String, dynamic>) {
        throw const NewsApiException('Format detail berita tidak dikenali.');
      }

      await OfflineCache.write('news_article_$slug', record);
      return NewsArticle.fromJson(record);
    } catch (_) {
      final record = await OfflineCache.read('news_article_$slug');
      if (record is! Map<String, dynamic>) rethrow;

      lastReadFromCache = true;
      return NewsArticle.fromJson(record);
    }
  }

  Future<http.Response> _get(Uri uri) {
    final client = this.client;
    final request = client == null
        ? http.get(uri, headers: const {'Accept': 'application/json'})
        : client.get(uri, headers: const {'Accept': 'application/json'});

    return request.timeout(const Duration(seconds: 6));
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    final Object? decoded;
    try {
      decoded = jsonDecode(response.body);
    } on FormatException {
      throw const NewsApiException('Server mengirim respons yang tidak valid.');
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      if (decoded is Map<String, dynamic> && decoded['message'] is String) {
        throw NewsApiException(decoded['message'] as String);
      }

      throw NewsApiException(
        'Berita tidak dapat dimuat (HTTP ${response.statusCode}).',
      );
    }

    if (decoded is! Map<String, dynamic>) {
      throw const NewsApiException('Format respons berita tidak dikenali.');
    }

    return decoded;
  }
}

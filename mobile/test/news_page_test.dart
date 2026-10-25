import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/news/news_article.dart';
import 'package:mobile/news/news_page.dart';
import 'package:mobile/news/news_service.dart';

void main() {
  final articles = [
    _article(
      id: 6,
      title: 'Edukasi: Langkah Aman Menghadapi Potensi Tanah Longsor',
      slug: 'edukasi-langkah-aman-tanah-longsor',
      excerpt: 'Kenali tanda yang perlu diwaspadai.',
      source: 'Materi edukasi SIGAP BENCANA; rujukan: BNPB',
    ),
    _article(
      id: 5,
      title: 'Edukasi: Perbedaan Peta Risiko dan Peringatan Dini',
      slug: 'edukasi-peta-risiko-dan-peringatan-dini',
      excerpt: 'Pahami perbedaan peta dan peringatan dini.',
      source: 'Materi edukasi SIGAP BENCANA; rujukan peta risiko: InaRISK BNPB',
    ),
    _article(
      id: 4,
      title: 'Edukasi: Apa Saja Isi Tas Siaga Bencana?',
      slug: 'edukasi-isi-tas-siaga-bencana',
      excerpt: 'Panduan menyiapkan kebutuhan keluarga.',
      source: 'Materi edukasi kesiapsiagaan; rujukan: BNPB',
    ),
  ];
  final latestArticle = _article(
    id: 20,
    title: 'BMKG: Gempa M5.2 - 6 km BaratLaut LEBONG-BENGKULU',
    slug: 'bmkg-gempa-20261022105749',
    excerpt: 'BMKG mencatat gempabumi M5.2 di Bengkulu.',
    source: 'BMKG - Data Terbuka',
    category: 'Berita',
  );

  test(
    'parses Laravel data envelope and requests the selected categories',
    () async {
      final requestedCategories = <String?>[];
      final client = MockClient((request) async {
        final category = request.url.queryParameters['category'];
        requestedCategories.add(category);
        expect(request.url.queryParameters['per_page'], '20');
        expect(request.headers['Accept'], 'application/json');
        final responseArticles = switch (category) {
          'berita' => [latestArticle],
          'edukasi' => articles,
          _ => [latestArticle, ...articles],
        };

        return http.Response(
          jsonEncode({'data': responseArticles}),
          200,
          headers: {'content-type': 'application/json'},
        );
      });
      addTearDown(client.close);

      final service = NewsService(client: client);
      final all = await service.fetchArticles();
      final latest = await service.fetchArticles(category: NewsCategory.latest);
      final education = await service.fetchArticles(
        category: NewsCategory.education,
      );

      expect(requestedCategories, [null, 'berita', 'edukasi']);
      expect(all.map((article) => article.id), [20, 6, 5, 4]);
      expect(education.map((article) => article.id), [6, 5, 4]);
      expect(education.first.category, 'Edukasi');
      expect(education.first.source, articles.first['source']);
      expect(latest.first.title, latestArticle['title']);
    },
  );

  testWidgets('education articles are rendered by the news page', (
    tester,
  ) async {
    final client = MockClient((_) async {
      return http.Response(
        jsonEncode({'data': articles}),
        200,
        headers: {'content-type': 'application/json'},
      );
    });
    addTearDown(client.close);

    await tester.pumpWidget(
      MaterialApp(
        home: NewsPage(
          initialCategory: NewsCategory.education,
          newsService: NewsService(client: client),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.text('Edukasi: Langkah Aman Menghadapi Potensi Tanah Longsor'),
      findsOneWidget,
    );
    expect(find.text('Sumber: ${articles.first['source']}'), findsOneWidget);
    expect(find.text('Edukasi'), findsNWidgets(2));
  });
}

Map<String, Object?> _article({
  required int id,
  required String title,
  required String slug,
  required String excerpt,
  required String source,
  String category = 'Edukasi',
}) {
  return {
    'id': id,
    'title': title,
    'slug': slug,
    'excerpt': excerpt,
    'image_url': null,
    'category': category,
    'source': source,
    'source_url': 'https://www.bnpb.go.id/',
    'is_simulation': false,
    'published_at': '2026-10-24T08:50:29+00:00',
  };
}

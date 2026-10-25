class NewsArticle {
  const NewsArticle({
    required this.id,
    required this.title,
    required this.slug,
    required this.category,
    required this.source,
    required this.sourceUrl,
    required this.publishedAt,
    required this.isSimulation,
    this.excerpt,
    this.imageUrl,
    this.content,
  });

  final int id;
  final String title;
  final String slug;
  final String category;
  final String source;
  final String sourceUrl;
  final DateTime publishedAt;
  final bool isSimulation;
  final String? excerpt;
  final String? imageUrl;
  final String? content;

  factory NewsArticle.fromJson(Map<String, dynamic> json) {
    return NewsArticle(
      id: json['id'] as int,
      title: json['title'] as String,
      slug: json['slug'] as String,
      category: json['category'] as String? ?? 'Berita',
      source: json['source'] as String,
      sourceUrl: json['source_url'] as String,
      publishedAt: DateTime.parse(json['published_at'] as String),
      isSimulation: json['is_simulation'] as bool? ?? false,
      excerpt: json['excerpt'] as String?,
      imageUrl: json['image_url'] as String?,
      content: json['content'] as String?,
    );
  }

  String get formattedDate {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des',
    ];
    final date = publishedAt.toLocal();

    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  String get readableContent {
    final html = content ?? excerpt ?? '';

    final plainText = html
        .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
        .replaceAll(RegExp(r'<li[^>]*>', caseSensitive: false), '• ')
        .replaceAll(
          RegExp(r'</(p|div|h[1-6]|li|ul|ol)>', caseSensitive: false),
          '\n',
        )
        .replaceAll(RegExp(r'<[^>]*>'), '')
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll('&#x27;', "'");

    return plainText
        .split('\n')
        .map((paragraph) => paragraph.trim())
        .join('\n')
        .replaceAll(RegExp(r'\n{3,}'), '\n\n')
        .trim();
  }
}

enum NewsCategory { all, latest, education }

extension NewsCategoryLabel on NewsCategory {
  String get label => switch (this) {
    NewsCategory.all => 'Semua',
    NewsCategory.latest => 'Berita Terkini',
    NewsCategory.education => 'Edukasi',
  };

  String? get apiValue => switch (this) {
    NewsCategory.all => null,
    NewsCategory.latest => 'berita',
    NewsCategory.education => 'edukasi',
  };
}

class NewsApiException implements Exception {
  const NewsApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

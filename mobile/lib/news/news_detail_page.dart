import 'package:flutter/material.dart';

import 'news_article.dart';
import 'news_service.dart';

class NewsDetailPage extends StatefulWidget {
  const NewsDetailPage({
    super.key,
    required this.slug,
    this.sourceType = 'local',
  });

  final String slug;
  final String sourceType;

  @override
  State<NewsDetailPage> createState() => _NewsDetailPageState();
}

class _NewsDetailPageState extends State<NewsDetailPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  final NewsService _newsService = NewsService();
  NewsArticle? _article;
  Object? _loadError;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadArticle();
  }

  Future<void> _loadArticle() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final article = await _newsService.fetchArticle(
        widget.slug,
        sourceType: widget.sourceType,
      );
      if (!mounted) return;
      setState(() {
        _article = article;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loadError = error;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      body: SafeArea(
        child: Column(
          children: [
            Align(
              alignment: Alignment.centerLeft,
              child: IconButton(
                onPressed: () => Navigator.of(context).maybePop(),
                icon: const Icon(Icons.arrow_back),
                tooltip: 'Kembali',
              ),
            ),
            Expanded(child: _buildContent()),
          ],
        ),
      ),
    );
  }

  Widget _buildContent() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_loadError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off, size: 36, color: Colors.black54),
              const SizedBox(height: 12),
              const Text(
                'Artikel tidak dapat dimuat. Periksa koneksi lalu coba lagi.',
                textAlign: TextAlign.center,
              ),
              TextButton(
                onPressed: _loadArticle,
                child: const Text('Coba lagi'),
              ),
            ],
          ),
        ),
      );
    }

    final article = _article;
    if (article == null) {
      return const Center(child: Text('Artikel tidak tersedia.'));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (article.imageUrl != null && article.imageUrl!.isNotEmpty)
              Image.network(
                article.imageUrl!,
                width: double.infinity,
                height: 210,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) =>
                    const SizedBox.shrink(),
              ),
            Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    article.isSimulation
                        ? 'SIMULASI — bukan laporan kejadian aktual'
                        : article.category,
                    style: TextStyle(
                      color: article.isSimulation
                          ? const Color(0xFF9A5200)
                          : const Color(0xFFD2002F),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    article.title,
                    style: const TextStyle(
                      fontSize: 22,
                      height: 1.3,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${article.formattedDate} • Sumber: ${article.source}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF77717A),
                    ),
                  ),
                  const Divider(height: 28),
                  SelectableText(
                    article.readableContent,
                    textAlign: TextAlign.justify,
                    style: const TextStyle(fontSize: 14, height: 1.65),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    'Rujukan sumber',
                    style: TextStyle(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 5),
                  SelectableText(
                    article.sourceUrl,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF555555),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

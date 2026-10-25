import 'package:flutter/material.dart';

import 'news_article.dart';
import 'news_detail_page.dart';
import 'news_service.dart';

class NewsPage extends StatefulWidget {
  const NewsPage({
    super.key,
    this.initialCategory = NewsCategory.all,
    this.newsService,
  });

  final NewsCategory initialCategory;
  final NewsService? newsService;

  @override
  State<NewsPage> createState() => _NewsPageState();
}

class _NewsPageState extends State<NewsPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  final NewsService _newsService = NewsService();
  late NewsCategory _selectedCategory;
  List<NewsArticle> _articles = [];
  Object? _loadError;
  bool _isLoading = true;
  int _loadSequence = 0;

  @override
  void initState() {
    super.initState();
    _selectedCategory = widget.initialCategory;
    _loadArticles();
  }

  Future<void> _loadArticles() async {
    final sequence = ++_loadSequence;
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final articles = await (widget.newsService ?? _newsService).fetchArticles(
        category: _selectedCategory,
      );
      if (!mounted || sequence != _loadSequence) return;
      setState(() {
        _articles = articles;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted || sequence != _loadSequence) return;
      setState(() {
        _loadError = error;
        _isLoading = false;
      });
    }
  }

  void _selectCategory(NewsCategory category) {
    if (_selectedCategory == category) return;
    setState(() => _selectedCategory = category);
    _loadArticles();
  }

  void _openArticle(NewsArticle article) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => NewsDetailPage(slug: article.slug),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      body: SafeArea(
        child: CustomScrollView(
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
              sliver: SliverList.list(
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      IconButton(
                        onPressed: () => Navigator.of(context).maybePop(),
                        icon: const Icon(Icons.arrow_back),
                        tooltip: 'Kembali',
                        padding: EdgeInsets.zero,
                        alignment: Alignment.topLeft,
                      ),
                      const SizedBox(width: 10),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Berita & Edukasi',
                              style: TextStyle(
                                fontSize: 25,
                                fontWeight: FontWeight.w600,
                                height: 1.25,
                              ),
                            ),
                            SizedBox(height: 6),
                            Text(
                              'Dapatkan informasi terbaru seputar bencana dan tingkatkan kesiapsiagaan Anda.',
                              style: TextStyle(fontSize: 15, height: 1.45),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: NewsCategory.values.map((category) {
                      return Expanded(
                        child: Padding(
                          padding: EdgeInsets.only(
                            right: category == NewsCategory.education ? 0 : 8,
                          ),
                          child: _CategoryButton(
                            label: category.label,
                            selected: _selectedCategory == category,
                            onPressed: () => _selectCategory(category),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Artikel Utama',
                    style: TextStyle(fontSize: 21, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 10),
                  _buildFeaturedArticle(),
                  const SizedBox(height: 22),
                  const Text(
                    'Artikel Lainnya',
                    style: TextStyle(fontSize: 21, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 10),
                  _buildOtherArticles(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFeaturedArticle() {
    if (_isLoading) {
      return const NewsFeedStatus(
        icon: Icons.sync,
        message: 'Memuat berita dan materi edukasi…',
        compact: false,
        showProgress: true,
      );
    }

    if (_loadError != null) {
      return NewsFeedStatus(
        icon: Icons.wifi_off,
        message: 'Berita tidak dapat dimuat. Periksa koneksi lalu coba lagi.',
        compact: false,
        actionLabel: 'Coba lagi',
        onAction: _loadArticles,
      );
    }

    if (_articles.isEmpty) {
      return NewsFeedEmptyState(
        message: _emptyMessage,
        compact: false,
        actionLabel: 'Muat ulang',
        onAction: _loadArticles,
      );
    }

    return _FeaturedArticleCard(
      article: _articles.first,
      onTap: () => _openArticle(_articles.first),
    );
  }

  Widget _buildOtherArticles() {
    if (_isLoading) {
      return const SizedBox.shrink();
    }

    if (_loadError != null) {
      return const SizedBox.shrink();
    }

    if (_articles.length < 2) {
      return NewsFeedEmptyState(
        message: _articles.isEmpty
            ? 'Artikel lain akan tampil ketika tersedia.'
            : 'Belum ada artikel lain pada kategori ini.',
      );
    }

    return Column(
      children: _articles.skip(1).map((article) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: _NewsListTile(
            article: article,
            onTap: () => _openArticle(article),
          ),
        );
      }).toList(),
    );
  }

  String get _emptyMessage => switch (_selectedCategory) {
    NewsCategory.all => 'Belum ada konten yang terbit dengan atribusi sumber.',
    NewsCategory.latest =>
      'Belum ada berita terkini yang terbit dengan atribusi sumber.',
    NewsCategory.education =>
      'Belum ada materi edukasi yang terbit dengan atribusi sumber.',
  };
}

class NewsFeedEmptyState extends StatelessWidget {
  const NewsFeedEmptyState({
    super.key,
    required this.message,
    this.compact = true,
    this.actionLabel,
    this.onAction,
  });

  final String message;
  final bool compact;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return NewsFeedStatus(
      icon: Icons.newspaper_outlined,
      message: message,
      compact: compact,
      actionLabel: actionLabel,
      onAction: onAction,
    );
  }
}

class NewsFeedStatus extends StatelessWidget {
  const NewsFeedStatus({
    super.key,
    required this.icon,
    required this.message,
    this.compact = true,
    this.showProgress = false,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String message;
  final bool compact;
  final bool showProgress;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: BoxConstraints(minHeight: compact ? 72 : 190),
      padding: EdgeInsets.all(compact ? 14 : 22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
      ),
      child: compact
          ? Row(
              children: [
                if (showProgress)
                  const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                else
                  Icon(icon, color: const Color(0xFF77717A)),
                const SizedBox(width: 12),
                Expanded(child: _message()),
                if (actionLabel != null && onAction != null)
                  TextButton(onPressed: onAction, child: Text(actionLabel!)),
              ],
            )
          : Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (showProgress)
                  const CircularProgressIndicator()
                else
                  Icon(icon, size: 32, color: const Color(0xFF77717A)),
                const SizedBox(height: 12),
                _message(),
                if (actionLabel != null && onAction != null) ...[
                  const SizedBox(height: 8),
                  TextButton(onPressed: onAction, child: Text(actionLabel!)),
                ],
              ],
            ),
    );
  }

  Widget _message() => Text(
    message,
    textAlign: compact ? TextAlign.start : TextAlign.center,
    style: const TextStyle(
      fontSize: 12,
      height: 1.45,
      color: Color(0xFF77717A),
    ),
  );
}

class _FeaturedArticleCard extends StatelessWidget {
  const _FeaturedArticleCard({required this.article, required this.onTap});

  final NewsArticle article;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _ArticleImage(
              url: article.imageUrl,
              height: 190,
              width: double.infinity,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _ArticleBadge(article: article),
                  const SizedBox(height: 9),
                  Text(
                    article.title,
                    style: const TextStyle(
                      fontSize: 20,
                      height: 1.35,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    article.formattedDate,
                    style: const TextStyle(color: Color(0xFF77717A)),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    article.isSimulation
                        ? 'SIMULASI — bukan laporan kejadian aktual'
                        : 'Sumber: ${article.source}',
                    style: TextStyle(
                      fontSize: 11,
                      color: article.isSimulation
                          ? const Color(0xFF9A5200)
                          : const Color(0xFF77717A),
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Row(
                    children: [
                      Text(
                        'Baca Selengkapnya',
                        style: TextStyle(
                          color: Color(0xFFD2002F),
                          fontSize: 16,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      SizedBox(width: 8),
                      Icon(Icons.arrow_forward, color: Color(0xFFD2002F)),
                    ],
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

class _NewsListTile extends StatelessWidget {
  const _NewsListTile({required this.article, required this.onTap});

  final NewsArticle article;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              _ArticleImage(url: article.imageUrl, height: 82, width: 82),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _ArticleBadge(article: article),
                    const SizedBox(height: 5),
                    Text(
                      article.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        height: 1.3,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      article.formattedDate,
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF77717A),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 6),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}

class _ArticleBadge extends StatelessWidget {
  const _ArticleBadge({required this.article});

  final NewsArticle article;

  @override
  Widget build(BuildContext context) {
    final isEducation = article.category.toLowerCase() == 'edukasi';
    final isSimulation = article.isSimulation;
    final background = isSimulation
        ? const Color(0xFFFFE7BF)
        : isEducation
        ? const Color(0xFFC9F7D9)
        : const Color(0xFFD9ECFF);
    final label = isSimulation
        ? 'Simulasi'
        : isEducation
        ? 'Edukasi'
        : 'Berita Terkini';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(7),
        border: Border.all(color: Colors.black87, width: 1),
      ),
      child: Text(label, style: const TextStyle(fontSize: 11)),
    );
  }
}

class _ArticleImage extends StatelessWidget {
  const _ArticleImage({required this.url, required this.height, this.width});

  final String? url;
  final double height;
  final double? width;

  @override
  Widget build(BuildContext context) {
    final imageUrl = url;
    if (imageUrl == null || imageUrl.isEmpty) {
      return Container(
        width: width ?? double.infinity,
        height: height,
        color: const Color(0xFFE6E2E2),
        alignment: Alignment.center,
        child: const Icon(Icons.newspaper_outlined, color: Colors.black45),
      );
    }

    return Image.network(
      imageUrl,
      width: width ?? double.infinity,
      height: height,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => Container(
        width: width ?? double.infinity,
        height: height,
        color: const Color(0xFFE6E2E2),
        alignment: Alignment.center,
        child: const Icon(Icons.broken_image_outlined, color: Colors.black45),
      ),
    );
  }
}

class _CategoryButton extends StatelessWidget {
  const _CategoryButton({
    required this.label,
    required this.selected,
    required this.onPressed,
  });

  final String label;
  final bool selected;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    const red = Color(0xFFD2002F);
    return SizedBox(
      height: 46,
      child: OutlinedButton(
        onPressed: onPressed,
        style: OutlinedButton.styleFrom(
          backgroundColor: selected ? red : Colors.white,
          foregroundColor: selected ? Colors.white : Colors.black,
          side: BorderSide(color: selected ? red : Colors.transparent),
          padding: const EdgeInsets.symmetric(horizontal: 7),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
        ),
        child: FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(
            label,
            maxLines: 1,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
          ),
        ),
      ),
    );
  }
}

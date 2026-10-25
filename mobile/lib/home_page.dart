import 'package:flutter/material.dart';

import 'news/news_article.dart';
import 'news/news_detail_page.dart';
import 'news/news_service.dart';
import 'profile/location_service.dart';
import 'profile/profile_page.dart';
import 'news/news_page.dart';
import 'notifications/notification_page.dart';
import 'risk_map/risk_map_page.dart';
import 'reports/report_list_page.dart';
import 'sos/sos_activation_page.dart';
import 'services/auth_service.dart';
import 'widgets/app_bottom_navigation.dart';
import 'warnings/disaster_warning.dart';
import 'warnings/warning_detail_page.dart';
import 'warnings/warning_service.dart';
import 'warnings/warnings_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({this.offlineMode = false, this.onRetryConnection, super.key});

  final bool offlineMode;
  final VoidCallback? onRetryConnection;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  static const red = Color(0xFFD50032);
  static const pink = Color(0xFFFFEEF1);

  String userName = 'Pengguna';
  CurrentLocation? _currentLocation;
  bool _isDetectingLocation = false;
  final NewsService _newsService = NewsService();
  final WarningService _warningService = WarningService();
  List<NewsArticle> _latestArticles = [];
  List<DisasterWarning> _activeWarnings = [];
  Object? _newsError;
  Object? _warningError;
  bool _isLoadingNews = true;
  bool _newsFromCache = false;
  bool _isLoadingWarnings = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _restoreLastDetectedLocation();
    _loadLatestNews();
    _loadWarnings();
  }

  Future<void> _restoreLastDetectedLocation() async {
    final location = await LocationService().readLastDetectedLocation();
    if (!mounted || _currentLocation != null || location == null) return;

    setState(() => _currentLocation = location);
  }

  Future<void> _loadUser() async {
    final user = await AuthService.getSavedUser();
    if (!mounted) return;

    setState(() {
      userName = user?['name']?.toString() ?? 'Pengguna';
    });
  }

  Future<void> _loadLatestNews() async {
    setState(() {
      _isLoadingNews = true;
      _newsError = null;
    });

    try {
      final articles = await _newsService.fetchArticles(
        category: NewsCategory.latest,
      );
      if (!mounted) return;
      setState(() {
        _latestArticles = articles;
        _newsFromCache = _newsService.lastReadFromCache;
        _isLoadingNews = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _newsError = error;
        _newsFromCache = false;
        _isLoadingNews = false;
      });
    }
  }

  Future<void> _loadWarnings() async {
    setState(() {
      _isLoadingWarnings = true;
      _warningError = null;
    });
    try {
      final warnings = await _warningService.fetchActiveWarnings();
      if (!mounted) return;
      setState(() {
        _activeWarnings = warnings;
        _isLoadingWarnings = false;
      });
    } catch (error) {
      final cachedWarnings = await _warningService.fetchCachedActiveWarnings();
      if (!mounted) return;
      setState(() {
        _activeWarnings = cachedWarnings;
        _warningError = error;
        _isLoadingWarnings = false;
      });
    }
  }

  Future<void> _detectCurrentLocation() async {
    if (_isDetectingLocation) return;
    setState(() => _isDetectingLocation = true);

    try {
      final location = await LocationService().getCurrentLocation();
      if (!mounted) return;
      setState(() {
        _currentLocation = location;
        _isDetectingLocation = false;
      });
      try {
        await LocationService().saveLastDetectedLocation(location);
      } on Exception {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'Lokasi terdeteksi, tetapi belum dapat disimpan untuk dibuka kembali.',
              ),
            ),
          );
        }
      }
    } on LocationServiceException catch (error) {
      if (!mounted) return;
      setState(() => _isDetectingLocation = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  Widget _currentLocationButton() {
    final location = _currentLocation;
    final label = _isDetectingLocation
        ? 'Mencari…'
        : location?.displayLabel ?? 'Deteksi lokasi';

    return Semantics(
      button: true,
      label: location == null
          ? 'Deteksi lokasi saat ini'
          : 'Perbarui lokasi saat ini',
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: _isDetectingLocation ? null : _detectCurrentLocation,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 2),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.location_on_outlined, size: 15),
              const SizedBox(width: 3),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 145),
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10),
                ),
              ),
              const SizedBox(width: 2),
              if (_isDetectingLocation)
                const SizedBox(
                  width: 12,
                  height: 12,
                  child: CircularProgressIndicator(strokeWidth: 1.5),
                )
              else
                const Icon(Icons.keyboard_arrow_down, size: 16),
            ],
          ),
        ),
      ),
    );
  }

  void _comingSoon(String feature) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Fitur $feature akan dibuat berikutnya.')),
    );
  }

  void _openNews({NewsCategory category = NewsCategory.all}) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => NewsPage(initialCategory: category),
      ),
    );
  }

  void _openArticle(NewsArticle article) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => NewsDetailPage(slug: article.slug),
      ),
    );
  }

  void _openWarnings() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            WarningsPage(locationAddress: _currentLocation?.address),
      ),
    );
  }

  void _openWarningDetail(DisasterWarning warning) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => WarningDetailPage(warningId: warning.id),
      ),
    );
  }

  Widget _warningBanner() {
    final locationAddress = _currentLocation?.address;
    final nearbyWarnings = locationAddress == null
        ? const <DisasterWarning>[]
        : _activeWarnings
              .where(
                (warning) =>
                    warning.affectsAdministrativeAddress(locationAddress),
              )
              .toList(growable: false);
    final warning = nearbyWarnings.isEmpty ? null : nearbyWarnings.first;
    final isStale = _warningError != null;
    late final List<Widget> children;
    if (_isLoadingWarnings) {
      children = const [
        Row(
          children: [
            SizedBox(
              width: 15,
              height: 15,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white,
              ),
            ),
            SizedBox(width: 8),
            Text(
              'Memeriksa peringatan aktif',
              style: TextStyle(color: Colors.white, fontSize: 12),
            ),
          ],
        ),
      ];
    } else if (warning == null) {
      children = [
        Row(
          children: [
            const Icon(Icons.error_outline, size: 17, color: Colors.white),
            const SizedBox(width: 7),
            Expanded(
              child: Text(
                isStale
                    ? 'Peringatan aktif belum dapat diperiksa'
                    : _currentLocation == null
                    ? 'Deteksi lokasi untuk melihat peringatan di sekitar Anda'
                    : locationAddress == null
                    ? 'Wilayah lokasi belum terbaca untuk mencocokkan peringatan'
                    : 'Tidak ada peringatan aktif di wilayah lokasi terakhir',
                style: const TextStyle(color: Colors.white, fontSize: 13),
              ),
            ),
            if (isStale)
              IconButton(
                onPressed: _loadWarnings,
                tooltip: 'Coba lagi',
                icon: const Icon(Icons.refresh, color: Colors.white),
              ),
          ],
        ),
      ];
    } else {
      children = [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(
                        Icons.error_outline,
                        size: 16,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        isStale
                            ? 'Peringatan Aktif · Data tersimpan'
                            : 'Peringatan Aktif',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 7),
                  Text(
                    warning.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      height: 1.2,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Container(
              width: 62,
              height: 62,
              decoration: BoxDecoration(
                color: const Color(0x33FFFFFF),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.thunderstorm_rounded,
                size: 35,
                color: Colors.white,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          warning.affectedArea,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: Colors.white, fontSize: 12),
        ),
        const SizedBox(height: 7),
        Wrap(
          spacing: 6,
          runSpacing: 5,
          children: [
            _warningBadge('Severity ${warning.severity}'),
            if (warning.urgency != null)
              _warningBadge('Urgensi ${warning.urgency}'),
          ],
        ),
        const SizedBox(height: 7),
        Text(
          'Berlaku sampai ${warning.formattedExpiresAt}',
          style: const TextStyle(color: Colors.white, fontSize: 10),
        ),
        if (isStale)
          Text(
            'Pembaruan gagal · terakhir ${warning.formattedUpdatedAt}',
            style: const TextStyle(color: Colors.white70, fontSize: 10),
          ),
        const SizedBox(height: 10),
        Row(
          children: [
            SizedBox(
              height: 32,
              child: ElevatedButton(
                onPressed: () => isStale
                    ? Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => WarningDetailPage(
                            warningId: warning.id,
                            initialWarning: warning,
                          ),
                        ),
                      )
                    : _openWarningDetail(warning),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF970019),
                  padding: const EdgeInsets.symmetric(horizontal: 15),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(5),
                  ),
                ),
                child: Text(
                  isStale ? 'Lihat Detail Tersimpan' : 'Lihat Detail',
                  style: const TextStyle(fontSize: 11),
                ),
              ),
            ),
            if (isStale) ...[
              const SizedBox(width: 6),
              IconButton(
                onPressed: _loadWarnings,
                tooltip: 'Coba lagi',
                icon: const Icon(Icons.refresh, color: Colors.white),
              ),
            ],
          ],
        ),
      ];
    }

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(5),
        gradient: const LinearGradient(
          colors: [Color(0xFFD50032), Color(0xFF970019)],
        ),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: children,
      ),
    );
  }

  Widget _warningBadge(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0x33FFFFFF),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(
        label,
        style: const TextStyle(color: Colors.white, fontSize: 10),
      ),
    );
  }

  Widget _serviceCard(
    String title,
    IconData icon,
    Color background,
    Color foreground, {
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap ?? () => _comingSoon(title),
      borderRadius: BorderRadius.circular(5),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Row(
          children: [
            Container(
              width: 43,
              height: 43,
              decoration: BoxDecoration(
                color: background,
                borderRadius: BorderRadius.circular(5),
              ),
              child: Icon(icon, color: foreground, size: 23),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  height: 1.25,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _bottomNavigation() {
    return AppBottomNavigation(
      selected: AppDestination.home,
      onSelect: (destination) {
        switch (destination) {
          case AppDestination.home:
            break;
          case AppDestination.map:
            Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const RiskMapPage()),
            );
            break;
          case AppDestination.profile:
            Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const ProfilePage()),
            );
            break;
          case AppDestination.sos:
            Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const SosActivationPage(),
              ),
            );
            break;
          case AppDestination.notifications:
            Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const NotificationPage()),
            );
            break;
        }
      },
    );
  }

  Widget _latestNewsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (_newsFromCache)
          const Padding(
            padding: EdgeInsets.only(bottom: 8),
            child: Text(
              'Offline · menampilkan berita yang tersimpan sebelumnya',
              style: TextStyle(fontSize: 11, color: Color(0xFF765500)),
            ),
          ),
        if (_isLoadingNews)
          const NewsFeedStatus(
            icon: Icons.sync,
            message: 'Memuat berita terkini…',
            showProgress: true,
          )
        else if (_newsError != null)
          NewsFeedStatus(
            icon: Icons.wifi_off,
            message: 'Berita tidak dapat dimuat. Periksa koneksi.',
            actionLabel: 'Coba lagi',
            onAction: _loadLatestNews,
          )
        else if (_latestArticles.isEmpty)
          NewsFeedEmptyState(
            message: 'Belum ada berita terkini dengan atribusi sumber.',
            actionLabel: 'Muat ulang',
            onAction: _loadLatestNews,
          )
        else
          ..._latestArticles
              .take(2)
              .map(
                (article) => Padding(
                  padding: const EdgeInsets.only(bottom: 9),
                  child: _HomeNewsCard(
                    article: article,
                    onTap: () => _openArticle(article),
                  ),
                ),
              ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: pink,
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      children: [
                        Image.asset(
                          'assets/images/logo.png',
                          width: 95,
                          height: 44,
                          fit: BoxFit.contain,
                        ),
                        const Spacer(),
                        _currentLocationButton(),
                      ],
                    ),

                    if (widget.offlineMode) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF4D6),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            const Expanded(
                              child: Text(
                                'Mode offline: informasi adalah data terakhir yang tersimpan. SOS/laporan belum dianggap terkirim sampai server mengonfirmasi.',
                                style: TextStyle(fontSize: 11, height: 1.35),
                              ),
                            ),
                            IconButton(
                              onPressed: widget.onRetryConnection,
                              tooltip: 'Coba sambungkan kembali',
                              icon: const Icon(Icons.refresh),
                            ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 12),

                    _warningBanner(),

                    const SizedBox(height: 12),

                    const Text(
                      'Layanan Utama',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w600,
                      ),
                    ),

                    const SizedBox(height: 8),

                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 8,
                      crossAxisSpacing: 8,
                      childAspectRatio: 2.05,
                      children: [
                        _serviceCard(
                          'Peringatan\nBencana',
                          Icons.warning_rounded,
                          const Color(0xFFFFC9CE),
                          red,
                          onTap: _openWarnings,
                        ),
                        _serviceCard(
                          'Lapor\nKejadian',
                          Icons.add_home_work,
                          const Color(0xFFFFEDB7),
                          const Color(0xFFC68B00),
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => const ReportListPage(),
                              ),
                            );
                          },
                        ),
                        _serviceCard(
                          'Berita &\nEdukasi',
                          Icons.message,
                          const Color(0xFFAEF8DB),
                          const Color(0xFF00B88C),
                          onTap: _openNews,
                        ),
                        _serviceCard(
                          'Tempat\nEvakuasi',
                          Icons.campaign,
                          const Color(0xFFF6C6F8),
                          const Color(0xFFC000D4),
                        ),
                      ],
                    ),

                    const SizedBox(height: 12),

                    Row(
                      children: [
                        const Text(
                          'Berita Terkini',
                          style: TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const Spacer(),
                        TextButton(
                          onPressed: () =>
                              _openNews(category: NewsCategory.latest),
                          child: const Text(
                            'Lihat Semua',
                            style: TextStyle(fontSize: 10, color: red),
                          ),
                        ),
                      ],
                    ),

                    _latestNewsSection(),

                    const SizedBox(height: 5),

                    Text(
                      'Halo, $userName!',
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF77717A),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            _bottomNavigation(),
          ],
        ),
      ),
    );
  }
}

class _HomeNewsCard extends StatelessWidget {
  const _HomeNewsCard({required this.article, required this.onTap});

  final NewsArticle article;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(6),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: article.imageUrl == null || article.imageUrl!.isEmpty
                    ? Container(
                        width: 64,
                        height: 72,
                        color: const Color(0xFFE6E2E2),
                        child: const Icon(Icons.newspaper_outlined),
                      )
                    : Image.network(
                        article.imageUrl!,
                        width: 64,
                        height: 72,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          width: 64,
                          height: 72,
                          color: const Color(0xFFE6E2E2),
                          child: const Icon(Icons.broken_image_outlined),
                        ),
                      ),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      article.isSimulation ? 'SIMULASI' : article.category,
                      style: const TextStyle(
                        fontSize: 10,
                        color: Color(0xFFD2002F),
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      article.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 12,
                        height: 1.3,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      article.formattedDate,
                      style: const TextStyle(
                        fontSize: 10,
                        color: Color(0xFF77717A),
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}

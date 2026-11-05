import 'dart:async';

import 'package:flutter/material.dart';

import '../profile/profile_local_data.dart';
import '../profile/profile_page.dart';
import '../profile/profile_tools_pages.dart';
import '../risk_map/risk_map_page.dart';
import '../sos/sos_status_page.dart';
import '../sos/sos_activation_page.dart';
import '../warnings/disaster_warning.dart';
import '../warnings/warning_detail_page.dart';
import '../warnings/warning_service.dart';
import '../widgets/app_bottom_navigation.dart';
import 'notification_item.dart';
import 'notification_service.dart';

class NotificationPage extends StatefulWidget {
  const NotificationPage({
    super.key,
    this.notificationService,
    this.warningService,
  });

  final NotificationService? notificationService;
  final WarningService? warningService;

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> {
  static const Color _red = Color(0xFFD50032);
  static const Color _pink = Color(0xFFFFEEF1);
  static const List<String> _filters = [
    'Semua',
    'Peringatan',
    'Informasi',
    'Aktivitas',
  ];

  List<NotificationItem> _allItems = [];
  List<NotificationItem> _items = [];
  List<DisasterWarning> _officialWarnings = [];
  String _selectedFilter = 'Semua';
  String? _error;
  bool _isLoading = true;
  bool _isOfflineCache = false;
  int _loadVersion = 0;
  Map<String, bool> _notificationPreferences = {
    ...ProfileLocalData.defaultNotificationPreferences,
  };

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    final loadVersion = ++_loadVersion;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final preferences = await ProfileLocalData.readNotificationPreferences();
      final notificationService =
          widget.notificationService ?? NotificationService();
      final items = await notificationService.fetchNotifications();
      final preferenceFilteredItems = items
          .where((item) => preferences[item.type] ?? true)
          .toList();
      final visibleItems = _filterItems(preferenceFilteredItems);
      var officialWarnings = <DisasterWarning>[];
      if (_selectedFilter == 'Semua' || _selectedFilter == 'Peringatan') {
        try {
          officialWarnings = await (widget.warningService ?? WarningService())
              .fetchActiveWarnings()
              .then(
                (items) => items
                    .where((warning) => warning.isOfficialWarning)
                    .toList(),
              );
        } catch (_) {
          // Notifikasi personal tetap dapat ditampilkan saat feed peringatan
          // resmi sedang tidak dapat dihubungi.
        }
      }
      if (!mounted) return;
      if (loadVersion != _loadVersion) return;
      setState(() {
        _notificationPreferences = preferences;
        _allItems = preferenceFilteredItems;
        _items = visibleItems;
        _officialWarnings = officialWarnings;
        _isOfflineCache = notificationService.lastReadFromCache;
        _isLoading = false;
      });
    } on NotificationSessionExpired {
      if (mounted && loadVersion == _loadVersion) {
        Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
      }
    } on NotificationServiceException catch (error) {
      if (!mounted) return;
      if (loadVersion != _loadVersion) return;
      setState(() {
        _error = error.message;
        _allItems = [];
        _items = [];
        _officialWarnings = [];
        _isOfflineCache = false;
        _isLoading = false;
      });
    }
  }

  Future<void> _selectFilter(String filter) async {
    if (_selectedFilter == filter) return;
    setState(() {
      _selectedFilter = filter;
      _items = _filterItems(_allItems);
      if (filter != 'Semua' && filter != 'Peringatan') {
        _officialWarnings = [];
      }
    });
    await _loadNotifications();
  }

  List<NotificationItem> _filterItems(List<NotificationItem> items) {
    if (_selectedFilter == 'Semua') return items;
    return items.where((item) => item.type == _selectedFilter).toList();
  }

  Future<void> _openNotification(NotificationItem item) async {
    if (item.relatedType == 'sos_request' && item.relatedId != null) {
      if (!item.isRead) {
        unawaited(_markReadInBackground(item));
      }
      await Navigator.of(context).push<void>(
        MaterialPageRoute<void>(
          builder: (_) => SosStatusPage(
            sosRequestId: item.relatedId!,
            initialRequest: item.relatedSosRequest,
          ),
        ),
      );
      return;
    }

    if (!item.isRead) {
      try {
        final updated =
            await (widget.notificationService ?? NotificationService())
                .markRead(item.id);
        if (!mounted) return;
        setState(() {
          _items = _items
              .map((current) => current.id == updated.id ? updated : current)
              .toList();
        });
      } on NotificationSessionExpired {
        if (mounted) {
          Navigator.of(context)
              .pushNamedAndRemoveUntil('/welcome', (_) => false);
        }
        return;
      } on NotificationServiceException catch (error) {
        if (!mounted) return;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    }

    if (!mounted) return;
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(22, 14, 22, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.black12,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                _displayType(item.type),
                style: const TextStyle(
                  color: _red,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                item.title,
                style: const TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 10),
              Text(item.message, style: const TextStyle(height: 1.5)),
              const SizedBox(height: 14),
              Text(
                _timeLabel(item.createdAt),
                style: const TextStyle(color: Colors.black54, fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _markReadInBackground(NotificationItem item) async {
    try {
      final updated =
          await (widget.notificationService ?? NotificationService()).markRead(
            item.id,
          );
      if (!mounted) return;
      setState(() {
        _items = _items
            .map((current) => current.id == updated.id ? updated : current)
            .toList();
      });
    } on NotificationServiceException {
      // Opening SOS status is independent of the read receipt request.
    } on NotificationSessionExpired {
      // The SOS detail request will handle an expired session.
    }
  }

  Future<void> _openSettings() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => const NotificationPreferencesPage(),
      ),
    );
    if (mounted) await _loadNotifications();
  }

  void _selectDestination(AppDestination destination) {
    switch (destination) {
      case AppDestination.home:
        Navigator.of(context).popUntil((route) => route.isFirst);
        break;
      case AppDestination.map:
        Navigator.of(context)
            .push(MaterialPageRoute<void>(builder: (_) => const RiskMapPage()));
        break;
      case AppDestination.profile:
        Navigator.of(context)
            .push(MaterialPageRoute<void>(builder: (_) => const ProfilePage()));
        break;
      case AppDestination.sos:
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const SosActivationPage()),
        );
        break;
      case AppDestination.notifications:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        title: const Text(
          'Notifikasi',
          style: TextStyle(fontSize: 23, fontWeight: FontWeight.w600),
        ),
        actions: [
          IconButton(
            onPressed: _openSettings,
            icon: const Icon(Icons.settings_outlined),
            tooltip: 'Pengaturan notifikasi',
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: Column(
        children: [
          SizedBox(
            height: 58,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
              scrollDirection: Axis.horizontal,
              itemCount: _filters.length,
              separatorBuilder: (_, index) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final filter = _filters[index];
                final selected = filter == _selectedFilter;
                return ChoiceChip(
                  label: Text(filter),
                  selected: selected,
                  onSelected: (_) => _selectFilter(filter),
                  selectedColor: _red,
                  backgroundColor: Colors.white,
                  labelStyle: TextStyle(
                    color: selected ? Colors.white : Colors.black87,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                    side: BorderSide(color: selected ? _red : Colors.white),
                  ),
                  showCheckmark: false,
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                );
              },
            ),
          ),
          if (_isOfflineCache)
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Offline · menampilkan notifikasi yang tersimpan sebelumnya',
                  style: TextStyle(fontSize: 11, color: Color(0xFF765500)),
                ),
              ),
            ),
          Expanded(child: _content()),
        ],
      ),
      bottomNavigationBar: AppBottomNavigation(
        selected: AppDestination.notifications,
        onSelect: _selectDestination,
      ),
    );
  }

  Widget _content() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: _red));
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off_outlined, size: 42),
              const SizedBox(height: 12),
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _loadNotifications,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba lagi'),
              ),
            ],
          ),
        ),
      );
    }
    if (_items.isEmpty) {
      if (_officialWarnings.isNotEmpty) return _notificationList();

      final isPreferenceDisabled =
          _selectedFilter != 'Semua' &&
          _notificationPreferences[_selectedFilter] == false;
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(30),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 78,
                height: 78,
                decoration: const BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.notifications_none,
                  size: 40,
                  color: _red,
                ),
              ),
              const SizedBox(height: 15),
              Text(
                isPreferenceDisabled
                    ? 'Kategori disembunyikan'
                    : _selectedFilter == 'Semua'
                    ? 'Belum ada notifikasi'
                    : 'Belum ada notifikasi $_selectedFilter',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 6),
              Text(
                isPreferenceDisabled
                    ? 'Aktifkan kategori ini dari pengaturan notifikasi di Profil Saya.'
                    : 'Notifikasi baru akan muncul di sini.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.black54, fontSize: 13),
              ),
              if (isPreferenceDisabled) ...[
                const SizedBox(height: 10),
                TextButton(
                  onPressed: _openSettings,
                  child: const Text('Buka Pengaturan'),
                ),
              ],
            ],
          ),
        ),
      );
    }

    return _notificationList();
  }

  Widget _notificationList() {
    final cards = <Widget>[
      ..._officialWarnings.map(_warningCard),
      ..._items.map(_notificationCard),
    ];

    return RefreshIndicator(
      onRefresh: _loadNotifications,
      color: _red,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 20),
        itemCount: cards.length,
        separatorBuilder: (_, index) => const SizedBox(height: 10),
        itemBuilder: (context, index) => cards[index],
      ),
    );
  }

  Widget _warningCard(DisasterWarning warning) {
    final style = _styleFor('Peringatan');
    return _buildCard(
      icon: style.$1,
      color: style.$2,
      title: warning.title,
      message:
          'Wilayah: ${warning.affectedArea}\nSumber: ${warning.source} · Berlaku sampai ${warning.formattedExpiresAt}',
      category: 'Peringatan',
      createdAt: warning.publishedAt ?? warning.updatedAt,
      isRead: true,
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => WarningDetailPage(
            warningId: warning.id,
            initialWarning: warning,
            service: widget.warningService,
          ),
        ),
      ),
    );
  }

  Widget _notificationCard(NotificationItem item) {
    final style = _styleFor(item.type);
    return _buildCard(
      icon: style.$1,
      color: style.$2,
      title: item.title,
      message: item.message,
      category: _displayType(item.type),
      createdAt: item.createdAt,
      isRead: item.isRead,
      onTap: () => _openNotification(item),
    );
  }

  Widget _buildCard({
    required IconData icon,
    required Color color,
    required String title,
    required String message,
    required String category,
    required DateTime? createdAt,
    required bool isRead,
    required VoidCallback onTap,
  }) {
    return Material(
      color: isRead ? Colors.white : const Color(0xFFFFE1E6),
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(15),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: color,
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(icon, color: Colors.white, size: 29),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                              height: 1.25,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _timeLabel(createdAt),
                          style: const TextStyle(
                            color: Colors.black87,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      message,
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        color: Colors.black87,
                        height: 1.4,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      category,
                      style: TextStyle(
                        color: color,
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 4),
              const Icon(Icons.chevron_right, size: 26),
            ],
          ),
        ),
      ),
    );
  }

  (IconData, Color) _styleFor(String type) {
    switch (type) {
      case 'Peringatan':
        return (Icons.warning_amber_rounded, const Color(0xFFFF6B00));
      case 'Informasi':
        return (Icons.info, const Color(0xFF16A6E0));
      case 'Aktivitas':
        return (Icons.check, const Color(0xFF00A66C));
      default:
        return (Icons.notifications_none, const Color(0xFF6B7280));
    }
  }

  String _displayType(String type) =>
      _filters.contains(type) && type != 'Semua' ? type : 'Notifikasi';

  String _timeLabel(DateTime? date) {
    if (date == null) return '';
    final elapsed = DateTime.now().difference(date.toLocal());
    if (elapsed.inMinutes < 1) return 'Baru saja';
    if (elapsed.inHours < 1) return '${elapsed.inMinutes} menit lalu';
    if (elapsed.inDays < 1) return '${elapsed.inHours} jam lalu';
    if (elapsed.inDays < 7) return '${elapsed.inDays} hari lalu';
    return '${date.toLocal().day}/${date.toLocal().month}/${date.toLocal().year}';
  }
}

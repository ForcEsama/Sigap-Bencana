import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../profile/location_service.dart';
import 'evacuation_post.dart';
import 'evacuation_service.dart';

class EvacuationPage extends StatefulWidget {
  const EvacuationPage({super.key});

  @override
  State<EvacuationPage> createState() => _EvacuationPageState();
}

class _EvacuationPageState extends State<EvacuationPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  static const Color _red = Color(0xFFD50032);

  final EvacuationService _service = EvacuationService();
  final LocationService _locationService = LocationService();
  final TextEditingController _searchController = TextEditingController();

  List<EvacuationPost> _posts = const [];
  List<EvacuationPost> _mapPosts = const [];
  Map<String, String> _thumbnailDataByPath = const {};
  Timer? _searchDebounce;
  CurrentLocation? _currentLocation;
  String? _error;
  String _search = '';
  String? _areaFilter;
  String? _statusFilter;
  bool _isLoading = true;
  bool _isOffline = false;
  bool _isDetectingLocation = false;

  @override
  void initState() {
    super.initState();
    _restoreLocation();
    _loadPlaces();
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _restoreLocation() async {
    final location = await _locationService.readLastDetectedLocation();
    if (mounted && location != null) {
      setState(() => _currentLocation = location);
    }
  }

  Future<void> _loadPlaces() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await _service.fetchPlaces();
      final thumbnailDataByPath = await _loadThumbnailData(result.posts);
      if (!mounted) return;
      setState(() {
        _posts = result.posts;
        _mapPosts = result.posts;
        _thumbnailDataByPath = thumbnailDataByPath;
        _isOffline = result.isOffline;
        _isLoading = false;
      });
    } on EvacuationServiceException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  Future<Map<String, String>> _loadThumbnailData(
    List<EvacuationPost> posts,
  ) async {
    final thumbnails = <String, String>{};
    for (final post in posts) {
      final imagePath = post.imagePath;
      final assetPath = _evacuationThumbnailAsset(imagePath);
      if (imagePath == null ||
          assetPath == null ||
          thumbnails.containsKey(imagePath)) {
        continue;
      }

      try {
        final imageData = await rootBundle.load(assetPath);
        final bytes = imageData.buffer.asUint8List(
          imageData.offsetInBytes,
          imageData.lengthInBytes,
        );
        thumbnails[imagePath] = base64Encode(bytes);
      } on Exception {
        // A missing optional thumbnail should not prevent the map from loading.
      }
    }

    return thumbnails;
  }

  Future<void> _detectLocation() async {
    setState(() => _isDetectingLocation = true);
    try {
      final location = await _locationService.getCurrentLocation();
      if (!mounted) return;
      setState(() {
        _currentLocation = location;
        _isDetectingLocation = false;
      });
      try {
        await _locationService.saveLastDetectedLocation(location);
      } on Exception {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'Lokasi terdeteksi, tetapi belum dapat disimpan untuk penggunaan berikutnya.',
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

  List<EvacuationPost> get _visiblePosts {
    return _posts
        .where((post) {
          final query = _search.trim().toLowerCase();
          final matchesQuery =
              query.isEmpty ||
              post.name.toLowerCase().contains(query) ||
              post.address.toLowerCase().contains(query);
          final matchesArea =
              _areaFilter == null || post.address == _areaFilter;
          final matchesStatus =
              _statusFilter == null ||
              post.status.toLowerCase() == _statusFilter;
          return matchesQuery && matchesArea && matchesStatus;
        })
        .toList(growable: false)
      ..sort((first, second) {
        final firstDistance = _distanceFromCurrentLocation(first);
        final secondDistance = _distanceFromCurrentLocation(second);
        if (firstDistance == null) return secondDistance == null ? 0 : 1;
        if (secondDistance == null) return -1;
        return firstDistance.compareTo(secondDistance);
      });
  }

  List<String> get _areas =>
      _posts
          .map((post) => post.address.trim())
          .where((address) => address.isNotEmpty)
          .toSet()
          .toList()
        ..sort();

  List<String> get _statuses =>
      _posts
          .map((post) => post.status.toLowerCase())
          .where((status) => status.isNotEmpty)
          .toSet()
          .toList()
        ..sort();

  double? _distanceFromCurrentLocation(EvacuationPost post) {
    final location = _currentLocation;
    if (location == null || !post.hasValidCoordinates) return null;

    const earthRadiusKm = 6371.0;
    final latitudeDifference = _radians(post.latitude! - location.latitude);
    final longitudeDifference = _radians(post.longitude! - location.longitude);
    final startLatitude = _radians(location.latitude);
    final endLatitude = _radians(post.latitude!);
    final haversine =
        math.pow(math.sin(latitudeDifference / 2), 2) +
        math.cos(startLatitude) *
            math.cos(endLatitude) *
            math.pow(math.sin(longitudeDifference / 2), 2);
    return earthRadiusKm * 2 * math.asin(math.sqrt(haversine));
  }

  double _radians(double degrees) => degrees * math.pi / 180;

  void _applyMapFilters() {
    setState(() => _mapPosts = _visiblePosts);
  }

  void _showFilters() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 22),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.black26,
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                const Text(
                  'Filter tempat evakuasi',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 14),
                const Text('Wilayah'),
                const SizedBox(height: 8),
                DropdownButtonFormField<String?>(
                  initialValue: _areaFilter,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    isDense: true,
                  ),
                  items: [
                    const DropdownMenuItem<String?>(
                      value: null,
                      child: Text('Semua wilayah'),
                    ),
                    ..._areas.map(
                      (area) => DropdownMenuItem<String?>(
                        value: area,
                        child: Text(
                          area,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ),
                  ],
                  onChanged: (value) {
                    setSheetState(() => _areaFilter = value);
                    setState(() {});
                    _applyMapFilters();
                  },
                ),
                if (_statuses.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text('Status'),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final status in _statuses)
                        FilterChip(
                          label: Text(_statusLabel(status)),
                          selected: _statusFilter == status,
                          onSelected: (selected) {
                            setSheetState(
                              () => _statusFilter = selected ? status : null,
                            );
                            setState(() {});
                            _applyMapFilters();
                          },
                        ),
                    ],
                  ),
                ],
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: TextButton(
                    onPressed: () {
                      setState(() {
                        _areaFilter = null;
                        _statusFilter = null;
                        _mapPosts = _visiblePosts;
                      });
                      Navigator.of(sheetContext).pop();
                    },
                    child: const Text('Hapus filter'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _statusLabel(String status) {
    switch (status.replaceAll(' ', '_')) {
      case 'available':
        return 'Tersedia';
      case 'almost_full':
        return 'Hampir penuh';
      case 'full':
        return 'Penuh';
      case 'inactive':
        return 'Tidak aktif';
      case 'active':
        return 'Aktif';
      default:
        return status;
    }
  }

  void _showDetails(EvacuationPost post) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _EvacuationDetailsSheet(
        post: post,
        distanceKm: _distanceFromCurrentLocation(post),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final mapPlaces = _mapPosts
        .where((post) => post.hasValidCoordinates)
        .map(
          (post) => {
            'id': post.locationCode ?? post.recordId?.toString(),
            'name': post.name,
            'latitude': post.latitude,
            'longitude': post.longitude,
            'is_planning_candidate': post.isPlanningCandidate,
            'location_code': post.locationCode,
            'image_path': post.imagePath == null
                ? null
                : _thumbnailDataByPath[post.imagePath],
            'recommended_capacity': post.recommendedCapacity,
            'address': post.address,
          },
        )
        .toList(growable: false);
    final mapKey = ValueKey<String>(
      '${mapPlaces.map((place) => place['id']).join(',')}'
      '-${_currentLocation?.latitude}-${_currentLocation?.longitude}',
    );

    return Scaffold(
      backgroundColor: _pink,
      body: Stack(
        children: [
          Positioned.fill(
            child: Platform.isAndroid
                ? AndroidView(
                    key: mapKey,
                    viewType: 'sigap/evacuation-map',
                    layoutDirection: TextDirection.ltr,
                    creationParams: {
                      'places': mapPlaces,
                      'latitude': _currentLocation?.latitude,
                      'longitude': _currentLocation?.longitude,
                    },
                    creationParamsCodec: const StandardMessageCodec(),
                  )
                : const ColoredBox(
                    color: Color(0xFFE8E7E5),
                    child: Center(
                      child: Text('Peta tempat evakuasi tersedia di Android.'),
                    ),
                  ),
          ),
          SafeArea(
            bottom: false,
            child: Column(
              children: [
                _buildHeader(),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                  child: Row(
                    children: [
                      Expanded(child: _buildSearch()),
                      const SizedBox(width: 10),
                      _roundButton(
                        icon: Icons.tune_rounded,
                        label: 'Filter tempat evakuasi',
                        onPressed: _showFilters,
                      ),
                    ],
                  ),
                ),
                if (_isOffline)
                  Container(
                    margin: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 7,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.92),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.cloud_off, size: 15, color: Colors.black54),
                        SizedBox(width: 6),
                        Text(
                          'Offline · menampilkan data tersimpan',
                          style: TextStyle(fontSize: 11),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          Positioned(
            right: 16,
            bottom: MediaQuery.sizeOf(context).height * 0.39 + 18,
            child: _roundButton(
              icon: _isDetectingLocation
                  ? Icons.hourglass_top_rounded
                  : Icons.my_location_rounded,
              label: 'Deteksi lokasi saya',
              onPressed: _isDetectingLocation ? null : _detectLocation,
            ),
          ),
          DraggableScrollableSheet(
            initialChildSize: 0.38,
            minChildSize: 0.20,
            maxChildSize: 0.82,
            builder: (context, scrollController) => Container(
              decoration: const BoxDecoration(
                color: _pink,
                borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
                boxShadow: [
                  BoxShadow(
                    color: Color(0x26000000),
                    blurRadius: 12,
                    offset: Offset(0, -2),
                  ),
                ],
              ),
              child: Column(
                children: [
                  const SizedBox(height: 9),
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFFBDBDBD),
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(18, 10, 18, 6),
                    child: Row(
                      children: [
                        const Expanded(
                          child: Text(
                            'Tempat Evakuasi',
                            style: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        if (!_isLoading && _error == null)
                          Text(
                            '${_visiblePosts.length} lokasi',
                            style: const TextStyle(
                              fontSize: 12,
                              color: Colors.black54,
                            ),
                          ),
                        IconButton(
                          onPressed: _loadPlaces,
                          tooltip: 'Muat ulang',
                          visualDensity: VisualDensity.compact,
                          icon: const Icon(Icons.refresh_rounded),
                        ),
                      ],
                    ),
                  ),
                  if (_posts.any((post) => post.isPlanningCandidate))
                    const Padding(
                      padding: EdgeInsets.fromLTRB(18, 0, 18, 7),
                      child: Row(
                        children: [
                          Icon(
                            Icons.info_outline,
                            size: 14,
                            color: Color(0xFF9B6200),
                          ),
                          SizedBox(width: 5),
                          Expanded(
                            child: Text(
                              'Pin merah: data server · pin kuning: kandidat TEA dengan koordinat dan operasional belum diverifikasi.',
                              style: TextStyle(
                                fontSize: 10,
                                color: Color(0xFF745100),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  Expanded(child: _buildList(scrollController)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      height: 48,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.of(context).maybePop(),
            tooltip: 'Kembali',
            icon: const Icon(Icons.arrow_back),
          ),
          const Expanded(
            child: Text(
              'Tempat Evakuasi',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w500),
            ),
          ),
          if (_isLoading)
            const SizedBox(
              width: 17,
              height: 17,
              child: CircularProgressIndicator(strokeWidth: 2, color: _red),
            ),
        ],
      ),
    );
  }

  Widget _buildSearch() {
    return Container(
      height: 48,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: const [
          BoxShadow(
            color: Color(0x18000000),
            blurRadius: 7,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: TextField(
        controller: _searchController,
        onChanged: (value) {
          setState(() => _search = value);
          _searchDebounce?.cancel();
          _searchDebounce = Timer(const Duration(milliseconds: 350), () {
            if (mounted) _applyMapFilters();
          });
        },
        decoration: InputDecoration(
          hintText: 'Cari tempat evakuasi...',
          prefixIcon: const Icon(Icons.search_rounded),
          suffixIcon: _search.isEmpty
              ? null
              : IconButton(
                  onPressed: () {
                    _searchController.clear();
                    setState(() {
                      _search = '';
                      _mapPosts = _visiblePosts;
                    });
                  },
                  icon: const Icon(Icons.close),
                ),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 12),
        ),
      ),
    );
  }

  Widget _roundButton({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
  }) {
    return Material(
      color: Colors.white,
      shape: const CircleBorder(),
      elevation: 3,
      child: SizedBox(
        width: 48,
        height: 48,
        child: IconButton(
          onPressed: onPressed,
          tooltip: label,
          icon: Icon(icon, color: _red, size: 22),
        ),
      ),
    );
  }

  Widget _buildList(ScrollController scrollController) {
    if (_isLoading && _posts.isEmpty) {
      return const Center(child: CircularProgressIndicator(color: _red));
    }
    if (_error != null && _posts.isEmpty) {
      return ListView(
        controller: scrollController,
        children: [
          const SizedBox(height: 34),
          const Icon(Icons.cloud_off_outlined, size: 42, color: Colors.black38),
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(_error!, textAlign: TextAlign.center),
          ),
          TextButton.icon(
            onPressed: _loadPlaces,
            icon: const Icon(Icons.refresh),
            label: const Text('Coba lagi'),
          ),
        ],
      );
    }
    if (_posts.isEmpty) {
      return ListView(
        controller: scrollController,
        children: const [
          SizedBox(height: 32),
          Icon(Icons.location_off_outlined, size: 42, color: Colors.black38),
          SizedBox(height: 10),
          Text(
            'Belum ada tempat evakuasi yang tersedia.',
            textAlign: TextAlign.center,
          ),
          SizedBox(height: 5),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 25),
            child: Text(
              'Lokasi akan muncul setelah data tempat evakuasi tersedia di server.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: Colors.black54),
            ),
          ),
        ],
      );
    }
    final visiblePosts = _visiblePosts;
    if (visiblePosts.isEmpty) {
      return ListView(
        controller: scrollController,
        children: const [
          SizedBox(height: 28),
          Icon(Icons.search_off_rounded, size: 38, color: Colors.black38),
          SizedBox(height: 8),
          Text(
            'Tidak ada lokasi yang cocok dengan pencarian.',
            textAlign: TextAlign.center,
          ),
        ],
      );
    }

    return ListView.separated(
      controller: scrollController,
      padding: const EdgeInsets.fromLTRB(14, 4, 14, 22),
      itemCount: visiblePosts.length,
      separatorBuilder: (context, index) => const SizedBox(height: 9),
      itemBuilder: (context, index) {
        final post = visiblePosts[index];
        return _EvacuationPostCard(
          post: post,
          distanceKm: _distanceFromCurrentLocation(post),
          onTap: () => _showDetails(post),
        );
      },
    );
  }
}

String? _evacuationThumbnailAsset(String? imagePath) {
  if (imagePath == null) return null;
  final filename = imagePath.split('/').last;
  if (!RegExp(r'^tea-[a-i][12]\.jpg$').hasMatch(filename)) return null;

  return 'assets/images/evacuation/$filename';
}

class _EvacuationPostCard extends StatelessWidget {
  const _EvacuationPostCard({
    required this.post,
    required this.distanceKm,
    required this.onTap,
  });

  final EvacuationPost post;
  final double? distanceKm;
  final VoidCallback onTap;

  String _formatCapacity(int value) {
    return value.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (_) => '.',
    );
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(11),
          child: Row(
            children: [
              Container(
                width: 68,
                height: 68,
                decoration: BoxDecoration(
                  color: const Color(0xFFF0E7E7),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: _evacuationThumbnailAsset(post.imagePath) == null
                    ? const Icon(
                        Icons.location_city_rounded,
                        color: Color(0xFF9B8588),
                        size: 30,
                      )
                    : ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: Image.asset(
                          _evacuationThumbnailAsset(post.imagePath)!,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(
                                Icons.location_city_rounded,
                                color: Color(0xFF9B8588),
                                size: 30,
                              ),
                        ),
                      ),
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            post.isPlanningCandidate
                                ? 'Kandidat TEA'
                                : 'Tempat Evakuasi',
                            style: TextStyle(
                              color: post.isPlanningCandidate
                                  ? Color(0xFF9B6200)
                                  : Color(0xFFD50032),
                              fontSize: 11,
                            ),
                          ),
                        ),
                        if (distanceKm != null)
                          Text(
                            '${post.isPlanningCandidate ? '~' : ''}${distanceKm!.toStringAsFixed(1)} km',
                            style: const TextStyle(
                              color: Colors.black54,
                              fontSize: 11,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Text(
                      post.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                    if (post.address.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        post.address,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: Colors.black54,
                        ),
                      ),
                    ],
                    if (post.recommendedCapacity != null) ...[
                      const SizedBox(height: 3),
                      Text(
                        'Daya tampung rencana: ${_formatCapacity(post.recommendedCapacity!)} jiwa',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 10,
                          color: Color(0xFF745100),
                        ),
                      ),
                    ],
                    const SizedBox(height: 5),
                    _StatusBadge(status: post.statusLabel),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: Colors.black87),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: const Color(0xFFF2F2F2),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        status,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontSize: 9, color: Colors.black87),
      ),
    );
  }
}

class _EvacuationDetailsSheet extends StatelessWidget {
  const _EvacuationDetailsSheet({required this.post, required this.distanceKm});

  final EvacuationPost post;
  final double? distanceKm;

  String _formatCapacity(int value) {
    return value.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (_) => '.',
    );
  }

  @override
  Widget build(BuildContext context) {
    final capacity = post.capacity;
    final occupancy = post.currentOccupancy;
    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.78,
      ),
      decoration: const BoxDecoration(
        color: Color(0xFFFFEEF1),
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      child: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
          shrinkWrap: true,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              post.name,
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
            ),
            if (post.isPlanningCandidate) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF1CF),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Text(
                  'Kandidat TEA dari dokumen perencanaan. Status operasional dan titik koordinat belum diverifikasi. Jangan gunakan marker ini sebagai panduan evakuasi.',
                  style: TextStyle(fontSize: 12, color: Color(0xFF694800)),
                ),
              ),
            ],
            const SizedBox(height: 6),
            Text(post.address, style: const TextStyle(color: Colors.black54)),
            if (distanceKm != null) ...[
              const SizedBox(height: 5),
              Text(
                post.isPlanningCandidate
                    ? 'Perkiraan jarak garis lurus: ~${distanceKm!.toStringAsFixed(1)} km dari titik kandidat yang belum diverifikasi'
                    : 'Jarak garis lurus sekitar ${distanceKm!.toStringAsFixed(1)} km',
                style: const TextStyle(color: Colors.black54),
              ),
            ],
            const SizedBox(height: 12),
            _StatusBadge(status: post.statusLabel),
            if (post.description != null) ...[
              const SizedBox(height: 17),
              const Text(
                'Keterangan',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(post.description!),
            ],
            if (post.isPlanningCandidate &&
                post.recommendedCapacity != null) ...[
              const SizedBox(height: 15),
              const Text(
                'Daya tampung rekomendasi dokumen',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(
                '${_formatCapacity(post.recommendedCapacity!)} jiwa · angka rencana, bukan kapasitas aktif terverifikasi',
              ),
            ] else if (capacity != null) ...[
              const SizedBox(height: 15),
              const Text(
                'Kapasitas',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text('${occupancy ?? 0} dari $capacity orang tercatat'),
            ],
            if (post.facilities != null) ...[
              const SizedBox(height: 15),
              const Text(
                'Fasilitas',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(post.facilities!),
            ],
            if (post.sourceDocument != null) ...[
              const SizedBox(height: 15),
              const Text(
                'Sumber',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(post.sourceDocument!),
              if (post.sourcePage != null) Text('Halaman ${post.sourcePage}'),
              const SizedBox(height: 4),
              const Text(
                'Foto kawasan hanya sebagai referensi visual; bukan bukti operasional lokasi.',
              ),
            ],
            if (post.manualReviewNote != null) ...[
              const SizedBox(height: 12),
              Text(
                post.manualReviewNote!,
                style: const TextStyle(fontSize: 11, color: Colors.black54),
              ),
            ],
            if (post.contactPerson != null || post.contactPhone != null) ...[
              const SizedBox(height: 15),
              const Text(
                'Kontak pengelola',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(
                [
                  post.contactPerson,
                  post.contactPhone,
                ].whereType<String>().join(' · '),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

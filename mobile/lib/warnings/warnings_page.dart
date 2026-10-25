import 'package:flutter/material.dart';

import 'disaster_warning.dart';
import 'warning_detail_page.dart';
import 'warning_service.dart';

class WarningsPage extends StatefulWidget {
  const WarningsPage({super.key, this.service, this.locationAddress});

  final WarningService? service;
  final String? locationAddress;

  @override
  State<WarningsPage> createState() => _WarningsPageState();
}

enum _WarningTab { all, earlyWarning, activeIncident }

class _WarningsPageState extends State<WarningsPage> {
  late Future<List<DisasterWarning>> _warnings = _load();
  final TextEditingController _searchController = TextEditingController();
  _WarningTab _selectedTab = _WarningTab.all;
  String? _selectedDisasterType;
  String _searchQuery = '';

  Future<List<DisasterWarning>> _load() async {
    final warnings = await (widget.service ?? WarningService())
        .fetchActiveWarnings();
    final locationAddress = widget.locationAddress;
    if (locationAddress == null || locationAddress.trim().isEmpty) {
      return warnings;
    }

    return warnings
        .where(
          (warning) => warning.affectsAdministrativeAddress(locationAddress),
        )
        .toList(growable: false);
  }

  void _reload() {
    setState(() {
      _warnings = _load();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFEEF1),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      IconButton(
                        onPressed: () => Navigator.of(context).maybePop(),
                        icon: const Icon(Icons.arrow_back),
                        padding: EdgeInsets.zero,
                        alignment: Alignment.centerLeft,
                        constraints: const BoxConstraints.tightFor(
                          width: 40,
                          height: 44,
                        ),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Text(
                          'Peringatan Bencana',
                          style: TextStyle(
                            fontSize: 25,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const Padding(
                    padding: EdgeInsets.only(left: 52),
                    child: Text(
                      'Bantu sebarkan informasi untuk penanganan lebih cepat.',
                      style: TextStyle(fontSize: 16, height: 1.45),
                    ),
                  ),
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          onChanged: (value) => setState(() {
                            _searchQuery = value.trim().toLowerCase();
                          }),
                          decoration: InputDecoration(
                            hintText: 'Cari wilayah atau jenis bencana...',
                            prefixIcon: const Icon(Icons.search, size: 28),
                            filled: true,
                            fillColor: Colors.white,
                            contentPadding: const EdgeInsets.symmetric(
                              vertical: 14,
                            ),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                color: Color(0xFF999999),
                                width: 1.4,
                              ),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                color: Color(0xFF999999),
                                width: 1.4,
                              ),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                color: Color(0xFFD50032),
                                width: 1.6,
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      _OutlineIconButton(
                        icon: Icons.tune,
                        label: 'Filter jenis bencana',
                        onPressed: _showDisasterTypeFilter,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _TabChip(
                          label: 'Semua',
                          selected: _selectedTab == _WarningTab.all,
                          onTap: () => _selectTab(_WarningTab.all),
                        ),
                        const SizedBox(width: 10),
                        _TabChip(
                          label: 'Peringatan Dini',
                          selected: _selectedTab == _WarningTab.earlyWarning,
                          onTap: () => _selectTab(_WarningTab.earlyWarning),
                        ),
                        const SizedBox(width: 10),
                        _TabChip(
                          label: 'Kejadian Aktif',
                          selected: _selectedTab == _WarningTab.activeIncident,
                          onTap: () => _selectTab(_WarningTab.activeIncident),
                        ),
                      ],
                    ),
                  ),
                  if (_selectedDisasterType != null) ...[
                    const SizedBox(height: 10),
                    InputChip(
                      label: Text(_selectedDisasterType!),
                      onDeleted: () => setState(() {
                        _selectedDisasterType = null;
                      }),
                      deleteIconColor: const Color(0xFFD50032),
                      backgroundColor: Colors.white,
                      side: BorderSide.none,
                    ),
                  ],
                ],
              ),
            ),
            Expanded(
              child: FutureBuilder<List<DisasterWarning>>(
                future: _warnings,
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) {
                    return const Center(
                      child: CircularProgressIndicator(
                        color: Color(0xFFD50032),
                      ),
                    );
                  }
                  if (snapshot.hasError) {
                    return _Status(
                      icon: Icons.cloud_off_outlined,
                      text: 'Peringatan tidak dapat dimuat. Periksa koneksi.',
                      action: _reload,
                    );
                  }

                  final warnings = _filterWarnings(snapshot.data ?? []);
                  if (warnings.isEmpty) {
                    final isIncidentTab =
                        _selectedTab == _WarningTab.activeIncident;
                    return _Status(
                      icon: isIncidentTab
                          ? Icons.article_outlined
                          : Icons.notifications_none,
                      text: isIncidentTab
                          ? 'Belum ada sumber laporan kejadian aktif yang tersedia.'
                          : _searchQuery.isNotEmpty ||
                                _selectedDisasterType != null
                          ? 'Tidak ada peringatan yang cocok dengan pencarian atau filter.'
                          : 'Tidak ada peringatan resmi yang aktif.',
                    );
                  }

                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    itemCount: warnings.length,
                    separatorBuilder: (context, index) =>
                        const SizedBox(height: 12),
                    itemBuilder: (context, index) => _WarningCard(
                      warning: warnings[index],
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => WarningDetailPage(
                            warningId: warnings[index].id,
                            initialWarning: warnings[index],
                            service: widget.service,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<DisasterWarning> _filterWarnings(List<DisasterWarning> warnings) {
    if (_selectedTab == _WarningTab.activeIncident) {
      return const [];
    }

    return warnings.where((warning) {
      final matchesType =
          _selectedDisasterType == null ||
          warning.disasterType.toLowerCase() ==
              _selectedDisasterType!.toLowerCase();
      final searchableText =
          '${warning.title} ${warning.disasterType} ${warning.affectedArea} ${warning.source}'
              .toLowerCase();
      return matchesType && searchableText.contains(_searchQuery);
    }).toList();
  }

  void _selectTab(_WarningTab tab) {
    setState(() {
      _selectedTab = tab;
    });
  }

  Future<void> _showDisasterTypeFilter() async {
    List<DisasterWarning> warnings;
    try {
      warnings = await _warnings;
    } catch (_) {
      return;
    }
    if (!mounted) return;

    final types =
        warnings
            .map((warning) => warning.disasterType)
            .where((type) => type.trim().isNotEmpty)
            .toSet()
            .toList()
          ..sort();

    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Jenis bencana',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 12),
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Semua jenis'),
                trailing: _selectedDisasterType == null
                    ? const Icon(Icons.check, color: Color(0xFFD50032))
                    : null,
                onTap: () {
                  setState(() => _selectedDisasterType = null);
                  Navigator.pop(context);
                },
              ),
              ...types.map(
                (type) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(type),
                  trailing: _selectedDisasterType == type
                      ? const Icon(Icons.check, color: Color(0xFFD50032))
                      : null,
                  onTap: () {
                    setState(() => _selectedDisasterType = type);
                    Navigator.pop(context);
                  },
                ),
              ),
              if (types.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: Text('Jenis bencana belum tersedia.'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _WarningCard extends StatelessWidget {
  const _WarningCard({required this.warning, required this.onTap});

  final DisasterWarning warning;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 86,
                height: 110,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFE2E6),
                  borderRadius: BorderRadius.circular(7),
                ),
                child: const Icon(
                  Icons.warning_amber_rounded,
                  color: Color(0xFFD50032),
                  size: 38,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        _Badge(
                          label: 'Peringatan Dini',
                          background: const Color(0xFFFFCDD2),
                          foreground: const Color(0xFFC00020),
                        ),
                        _Badge(
                          label: warning.disasterType,
                          background: const Color(0xFFFFF1F2),
                          foreground: const Color(0xFF9E001A),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      warning.title,
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        height: 1.35,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      warning.affectedArea,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.black54,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(
                          Icons.schedule,
                          color: Colors.black45,
                          size: 15,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            warning.formattedPublishedAt,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.black54,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 7),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            warning.source,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.black54,
                              fontSize: 11,
                            ),
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Text(
                          'Lihat Detail  →',
                          style: TextStyle(
                            color: Color(0xFFC00020),
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 6),
              _Badge(
                label: 'Aktif',
                background: const Color(0xFFE4F7E8),
                foreground: const Color(0xFF237A38),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({
    required this.label,
    required this.background,
    required this.foreground,
  });

  final String label;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          color: foreground,
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _TabChip extends StatelessWidget {
  const _TabChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      showCheckmark: false,
      onSelected: (_) => onTap(),
      backgroundColor: Colors.white,
      selectedColor: const Color(0xFFC90024),
      side: BorderSide.none,
      labelStyle: TextStyle(
        color: selected ? Colors.white : Colors.black,
        fontWeight: FontWeight.w500,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    );
  }
}

class _OutlineIconButton extends StatelessWidget {
  const _OutlineIconButton({
    required this.icon,
    required this.label,
    required this.onPressed,
  });

  final IconData icon;
  final String label;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: label,
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(9),
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(9),
          child: Container(
            width: 48,
            height: 52,
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xFF999999), width: 1.4),
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(icon, size: 25),
          ),
        ),
      ),
    );
  }
}

class _Status extends StatelessWidget {
  const _Status({required this.text, required this.icon, this.action});

  final String text;
  final IconData icon;
  final VoidCallback? action;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: Colors.black38, size: 42),
            const SizedBox(height: 12),
            Text(
              text,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.black54, height: 1.4),
            ),
            if (action != null) ...[
              const SizedBox(height: 12),
              TextButton(
                onPressed: action,
                child: const Text(
                  'Coba lagi',
                  style: TextStyle(color: Color(0xFFD50032)),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

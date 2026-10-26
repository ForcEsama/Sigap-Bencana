import 'dart:io';

import 'package:flutter/material.dart';

import '../notifications/notification_page.dart';
import '../profile/profile_page.dart';
import '../sos/sos_activation_page.dart';
import '../widgets/app_bottom_navigation.dart';

class RiskMapPage extends StatelessWidget {
  const RiskMapPage({this.guestMode = false, super.key});

  final bool guestMode;

  static const Color _pink = Color(0xFFFFEEF1);

  void _selectDestination(BuildContext context, AppDestination destination) {
    if (guestMode &&
        destination != AppDestination.home &&
        destination != AppDestination.map) {
      showDialog<void>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Masuk untuk melanjutkan'),
          content: const Text('Fitur ini memerlukan akun SIGAP BENCANA.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Nanti'),
            ),
            FilledButton(
              onPressed: () {
                Navigator.pop(dialogContext);
                Navigator.of(context).pushNamed('/login');
              },
              child: const Text('Masuk'),
            ),
          ],
        ),
      );
      return;
    }

    switch (destination) {
      case AppDestination.home:
        Navigator.of(context).popUntil((route) => route.isFirst);
        break;
      case AppDestination.map:
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
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const NotificationPage()),
        );
        break;
    }
  }

  void _showLayerInfo(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      builder: (sheetContext) => const SafeArea(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Sumber peta risiko',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
              ),
              SizedBox(height: 8),
              Text(
                'Basemap memakai OpenStreetMap. Layer risiko dan legenda berasal dari layanan ImageServer InaRISK/BNPB yang sama dengan website.',
              ),
              SizedBox(height: 12),
              Text(
                'Peta memerlukan koneksi internet. Tile OpenStreetMap dan layer InaRISK belum disimpan untuk penggunaan offline.',
                style: TextStyle(color: Colors.black54),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: Platform.isAndroid
                  ? const AndroidView(
                      viewType: 'sigap/risk-map',
                      layoutDirection: TextDirection.ltr,
                    )
                  : const ColoredBox(
                      color: Color(0xFFE8E7E5),
                      child: Center(
                        child: Text(
                          'Peta risiko tersedia pada aplikasi Android.',
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
            ),
            Positioned(
              top: 12,
              left: 16,
              right: 16,
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      height: 54,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        border: Border.all(color: const Color(0xFF9E9E9E)),
                        borderRadius: BorderRadius.circular(10),
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x22000000),
                            blurRadius: 5,
                            offset: Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const TextField(
                        enabled: false,
                        decoration: InputDecoration(
                          hintText: 'Cari Lokasi...',
                          hintStyle: TextStyle(
                            fontSize: 18,
                            color: Colors.black,
                          ),
                          prefixIcon: Icon(
                            Icons.location_on_outlined,
                            size: 28,
                            color: Colors.black,
                          ),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(vertical: 14),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Material(
                    color: Colors.white,
                    shape: const CircleBorder(),
                    elevation: 2,
                    child: SizedBox(
                      width: 56,
                      height: 56,
                      child: IconButton(
                        onPressed: () => _showLayerInfo(context),
                        tooltip: 'Sumber layer peta',
                        icon: const Icon(
                          Icons.layers_rounded,
                          size: 29,
                          color: Colors.black,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Positioned(
              left: 12,
              right: 12,
              bottom: 12,
              child: _riskLegendCard(),
            ),
          ],
        ),
      ),
      bottomNavigationBar: AppBottomNavigation(
        selected: AppDestination.map,
        onSelect: (destination) => _selectDestination(context, destination),
      ),
    );
  }

  Widget _riskLegendCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1F000000),
            blurRadius: 8,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text(
            'Legenda Zona Risiko SIGAP BENCANA',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 16,
            runSpacing: 10,
            children: const [
              _RiskLegendItem(color: Color(0xFFDC2626), label: 'Risiko tinggi'),
              _RiskLegendItem(color: Color(0xFFF59E0B), label: 'Risiko sedang'),
              _RiskLegendItem(color: Color(0xFF059669), label: 'Risiko rendah'),
              _RiskLegendItem(
                color: Color(0xFF64748B),
                label: 'Tingkat lain/tidak dikenali',
              ),
              _RiskLegendItem(
                color: Color(0xFF64748B),
                label: 'Radius perkiraan bila tersedia',
                outlined: true,
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'Simbol dan warna membantu membedakan kategori; radius bukan batas wilayah resmi.',
            style: TextStyle(fontSize: 10, color: Color(0xFF6B7280)),
          ),
        ],
      ),
    );
  }
}

class _RiskLegendItem extends StatelessWidget {
  const _RiskLegendItem({
    required this.color,
    required this.label,
    this.outlined = false,
  });

  final Color color;
  final String label;
  final bool outlined;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: outlined ? Colors.white : color,
            shape: BoxShape.circle,
            border: outlined ? Border.all(color: color, width: 2) : null,
          ),
        ),
        const SizedBox(width: 7),
        Flexible(
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Color(0xFF374151)),
          ),
        ),
      ],
    );
  }
}

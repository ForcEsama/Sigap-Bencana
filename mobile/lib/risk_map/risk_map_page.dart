import 'dart:io';

import 'package:flutter/material.dart';

import '../notifications/notification_page.dart';
import '../profile/profile_page.dart';
import '../sos/sos_activation_page.dart';
import '../widgets/app_bottom_navigation.dart';

class RiskMapPage extends StatelessWidget {
  const RiskMapPage({super.key});

  static const Color _pink = Color(0xFFFFEEF1);

  void _selectDestination(BuildContext context, AppDestination destination) {
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
          ],
        ),
      ),
      bottomNavigationBar: AppBottomNavigation(
        selected: AppDestination.map,
        onSelect: (destination) => _selectDestination(context, destination),
      ),
    );
  }
}

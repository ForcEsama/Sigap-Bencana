import 'package:flutter/material.dart';

import 'evacuation/evacuation_page.dart';
import 'news/news_page.dart';
import 'profile/emergency_contact_directory_page.dart';
import 'profile/profile_tools_pages.dart';
import 'risk_map/risk_map_page.dart';
import 'warnings/warnings_page.dart';
import 'widgets/app_bottom_navigation.dart';

class GuestAccessPage extends StatelessWidget {
  const GuestAccessPage({
    required this.title,
    required this.description,
    required this.icon,
    required this.selectedDestination,
    super.key,
  });

  final String title;
  final String description;
  final IconData icon;
  final AppDestination selectedDestination;

  static const Color _pink = Color(0xFFFFEEF1);
  static const Color _red = Color(0xFFD50032);

  void _openDestination(BuildContext context, AppDestination destination) {
    if (destination == selectedDestination) return;

    switch (destination) {
      case AppDestination.home:
        Navigator.of(context).popUntil((route) => route.isFirst);
        break;
      case AppDestination.map:
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const RiskMapPage(guestMode: true),
          ),
        );
        break;
      case AppDestination.sos:
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const GuestAccessPage(
              title: 'SOS',
              description:
                  'Mode guest dapat mempelajari alur bantuan dan membuka kontak darurat. Login diperlukan untuk mengirim permintaan SOS dan melihat status akun.',
              icon: Icons.sos,
              selectedDestination: AppDestination.sos,
            ),
          ),
        );
        break;
      case AppDestination.notifications:
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const GuestAccessPage(
              title: 'Notifikasi',
              description:
                  'Notifikasi aktivitas dan status laporan bersifat pribadi. Login untuk melihat notifikasi akun Anda.',
              icon: Icons.notifications_outlined,
              selectedDestination: AppDestination.notifications,
            ),
          ),
        );
        break;
      case AppDestination.profile:
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const GuestAccessPage(
              title: 'Profil',
              description:
                  'Data pribadi, lokasi tersimpan, dan preferensi hanya tersedia setelah login. Sebagai guest, Anda tetap dapat membuka informasi publik di bawah.',
              icon: Icons.person_outline,
              selectedDestination: AppDestination.profile,
            ),
          ),
        );
        break;
    }
  }

  void _openLogin(BuildContext context) {
    Navigator.of(context).pushNamed('/login');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        title: Text(title),
      ),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFFFD0D8)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, color: _red, size: 28),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(description, style: const TextStyle(height: 1.4)),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: () => _openLogin(context),
                        style: FilledButton.styleFrom(
                          backgroundColor: _red,
                          foregroundColor: Colors.white,
                        ),
                        icon: const Icon(Icons.login),
                        label: const Text('Masuk untuk fitur akun'),
                      ),
                      TextButton(
                        onPressed: () => Navigator.of(context).pushNamed(
                          '/register',
                        ),
                        child: const Text('Belum punya akun? Daftar'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),
          if (title == 'Profil') ...[
            const Text(
              'Fitur khusus akun',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            _privateLink(context, 'Data Pribadi'),
            _privateLink(context, 'Lokasi Tersimpan'),
            _privateLink(context, 'Preferensi Notifikasi'),
            _privateLink(context, 'Riwayat Laporan Saya'),
            _privateLink(context, 'Dashboard Operasional (Admin/Petugas)'),
            const SizedBox(height: 14),
          ],
          if (title == 'Notifikasi') ...[
            _privateLink(context, 'Notifikasi Peringatan'),
            _privateLink(context, 'Notifikasi Informasi'),
            _privateLink(context, 'Notifikasi Aktivitas Akun'),
            const SizedBox(height: 14),
          ],
          if (title == 'SOS') ...[
            _privateLink(context, 'Aktifkan SOS dan lacak status bantuan'),
            const SizedBox(height: 14),
          ],
          const Text(
            'Informasi yang tetap bisa dibuka sebagai guest',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          _publicLink(
            context,
            'Peringatan Bencana',
            Icons.warning_amber_rounded,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const WarningsPage()),
            ),
          ),
          _publicLink(
            context,
            'Berita & Edukasi',
            Icons.newspaper_outlined,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const NewsPage()),
            ),
          ),
          _publicLink(
            context,
            'Tempat Evakuasi',
            Icons.house_outlined,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const EvacuationPage()),
            ),
          ),
          _publicLink(
            context,
            'Kontak Darurat',
            Icons.call_outlined,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const EmergencyContactDirectoryPage(),
              ),
            ),
          ),
          _publicLink(
            context,
            'Bantuan',
            Icons.help_outline,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const HelpCenterPage()),
            ),
          ),
          _publicLink(
            context,
            'Tentang SIGAP Bencana',
            Icons.info_outline,
            () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const AboutAppPage()),
            ),
          ),
        ],
      ),
      bottomNavigationBar: AppBottomNavigation(
        selected: selectedDestination,
        onSelect: (destination) => _openDestination(context, destination),
      ),
    );
  }

  Widget _publicLink(
    BuildContext context,
    String label,
    IconData icon,
    VoidCallback onTap,
  ) {
    return Card(
      color: Colors.white,
      child: ListTile(
        leading: Icon(icon, color: _red),
        title: Text(label),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }

  Widget _privateLink(BuildContext context, String label) {
    return Card(
      color: Colors.white,
      child: ListTile(
        leading: const Icon(Icons.lock_outline, color: _red),
        title: Text(label),
        subtitle: const Text('Login diperlukan'),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => _openLogin(context),
      ),
    );
  }
}

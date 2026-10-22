import 'package:flutter/material.dart';

import 'services/auth_service.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  static const red = Color(0xFFD50032);
  static const pink = Color(0xFFFFEEF1);

  String userName = 'Pengguna';

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await AuthService.getSavedUser();
    if (!mounted) return;

    setState(() {
      userName = user?['name']?.toString() ?? 'Pengguna';
    });
  }

  void _comingSoon(String feature) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Fitur $feature akan dibuat berikutnya.')),
    );
  }

  Widget _serviceCard(
    String title,
    IconData icon,
    Color background,
    Color foreground,
  ) {
    return InkWell(
      onTap: () => _comingSoon(title),
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

  Widget _newsCard({
    required String category,
    required String title,
    required String date,
    required IconData icon,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 7),
      padding: const EdgeInsets.all(7),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(5),
      ),
      child: Row(
        children: [
          Container(
            width: 56,
            height: 55,
            decoration: BoxDecoration(
              color: const Color(0xFFE6D7D9),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Icon(icon, size: 28, color: red),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 5,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFCDD5),
                    borderRadius: BorderRadius.circular(3),
                  ),
                  child: Text(
                    category,
                    style: const TextStyle(fontSize: 9, color: red),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    height: 1.2,
                  ),
                ),
                Text(
                  date,
                  style: const TextStyle(fontSize: 9, color: Colors.grey),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _navItem(
    String label,
    IconData icon, {
    bool selected = false,
    VoidCallback? onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap:
            onTap ??
            () {
              if (!selected) _comingSoon(label);
            },
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 21, color: selected ? red : Colors.black),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 9,
                color: selected ? red : Colors.black,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showAccountMenu() async {
    await showModalBottomSheet<void>(
      context: context,
      builder: (sheetContext) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.person_outline),
                title: Text(userName),
                subtitle: const Text('Akun SIGAP Bencana'),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.logout, color: red),
                title: const Text('Keluar', style: TextStyle(color: red)),
                onTap: () {
                  Navigator.pop(sheetContext);
                  _confirmLogout();
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _confirmLogout() async {
    final shouldLogout = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Keluar dari akun?'),
          content: const Text(
            'Sesi SIGAP Bencana di perangkat ini akan dihapus.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Batal'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: const Text('Keluar'),
            ),
          ],
        );
      },
    );

    if (shouldLogout != true) return;

    final serverRevoked = await AuthService.logout();

    if (!mounted) return;

    if (!serverRevoked) {
      await showDialog<void>(
        context: context,
        builder: (dialogContext) {
          return AlertDialog(
            title: const Text('Sesi perangkat dihapus'),
            content: const Text(
              'Server belum mengonfirmasi pencabutan token. Token server mungkin masih aktif sampai dicabut saat koneksi tersedia.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dialogContext),
                child: const Text('Mengerti'),
              ),
            ],
          );
        },
      );
    }

    if (!mounted) return;

    Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (route) => false);
  }

  Widget _bottomNavigation() {
    return Container(
      height: 68,
      padding: const EdgeInsets.symmetric(horizontal: 6),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFF1DDE1))),
      ),
      child: Row(
        children: [
          _navItem('Home', Icons.home, selected: true),
          _navItem('Map', Icons.map),
          Expanded(
            child: Center(
              child: Transform.translate(
                offset: const Offset(0, -12),
                child: InkWell(
                  onTap: () => _comingSoon('SOS'),
                  customBorder: const CircleBorder(),
                  child: Container(
                    width: 58,
                    height: 58,
                    decoration: const BoxDecoration(
                      color: red,
                      shape: BoxShape.circle,
                    ),
                    alignment: Alignment.center,
                    child: const Text(
                      'SOS',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          _navItem('Notification', Icons.notifications),
          _navItem(
            'Profile',
            Icons.person,
            onTap: () {
              _showAccountMenu();
            },
          ),
        ],
      ),
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
                        InkWell(
                          onTap: () => _comingSoon('Lokasi'),
                          child: const Row(
                            children: [
                              Icon(Icons.location_on_outlined, size: 15),
                              SizedBox(width: 3),
                              Text(
                                'Padang, Sumatera Barat',
                                style: TextStyle(fontSize: 10),
                              ),
                              Icon(Icons.keyboard_arrow_down, size: 16),
                            ],
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 12),

                    // Peringatan: data contoh untuk desain
                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(5),
                        gradient: const LinearGradient(
                          colors: [Color(0xFFD50032), Color(0xFF970019)],
                        ),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Icon(
                                  Icons.error,
                                  size: 14,
                                  color: Colors.white,
                                ),
                                SizedBox(width: 5),
                                Text(
                                  'Contoh Peringatan Bencana',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            const Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    'Hujan Lebat',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 21,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                                Icon(
                                  Icons.thunderstorm,
                                  size: 45,
                                  color: Colors.white,
                                ),
                              ],
                            ),
                            const SizedBox(height: 7),
                            const Text(
                              'Tingkat Risiko: Tinggi',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                              ),
                            ),
                            const SizedBox(height: 4),
                            const Text(
                              'Berpotensi menyebabkan banjir dan longsor.',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                              ),
                            ),
                            const SizedBox(height: 10),
                            SizedBox(
                              height: 30,
                              child: ElevatedButton(
                                onPressed: () =>
                                    _comingSoon('Detail Peringatan'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  foregroundColor: Colors.black,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 15,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                ),
                                child: const Text(
                                  'Lihat Detail',
                                  style: TextStyle(fontSize: 10),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

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
                        ),
                        _serviceCard(
                          'Lapor\nKejadian',
                          Icons.add_home_work,
                          const Color(0xFFFFEDB7),
                          const Color(0xFFC68B00),
                        ),
                        _serviceCard(
                          'Berita &\nEdukasi',
                          Icons.message,
                          const Color(0xFFAEF8DB),
                          const Color(0xFF00B88C),
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
                          onPressed: () => _comingSoon('Semua Berita'),
                          child: const Text(
                            'Lihat Semua',
                            style: TextStyle(fontSize: 10, color: red),
                          ),
                        ),
                      ],
                    ),

                    _newsCard(
                      category: 'Banjir',
                      title: 'Waspada Banjir di Beberapa Wilayah Jawa Tengah',
                      date: '12 Sep 2025',
                      icon: Icons.water,
                    ),

                    _newsCard(
                      category: 'Gempa',
                      title: 'Gempa Magnitudo 5.2 Guncang Maluku',
                      date: '12 Nov 2026',
                      icon: Icons.landscape,
                    ),

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

import 'package:flutter/material.dart';

import '../reports/report_list_page.dart';
import '../notifications/notification_page.dart';
import '../services/auth_service.dart';
import '../sos/sos_activation_page.dart';
import '../widgets/app_bottom_navigation.dart';
import 'profile_tools_pages.dart';
import 'profile_service.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  static const Color _red = Color(0xFFD50032);
  static const Color _pink = Color(0xFFFFEEF1);

  Map<String, dynamic>? _profile;
  String? _error;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final profile = await ProfileService.fetchProfile();
      if (!mounted) return;
      setState(() {
        _profile = profile;
        _isLoading = false;
      });
    } on ProfileSessionExpired {
      if (mounted) _goToWelcome();
    } on ProfileServiceException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  void _goToWelcome() {
    Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
  }

  void _selectDestination(AppDestination destination) {
    switch (destination) {
      case AppDestination.home:
        Navigator.of(context).popUntil((route) => route.isFirst);
        break;
      case AppDestination.map:
        Navigator.of(context).popUntil((route) => route.isFirst);
        Navigator.of(context).pushNamed('/risk-map');
        break;
      case AppDestination.profile:
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

  Future<void> _openPersonalInfo() async {
    final profile = _profile;
    if (profile == null) return;

    final updatedProfile = await Navigator.of(context)
        .push<Map<String, dynamic>>(
          MaterialPageRoute(
            builder: (_) => ProfileDetailsPage(profile: profile),
          ),
        );

    if (updatedProfile != null && mounted) {
      setState(() => _profile = updatedProfile);
    }
  }

  Future<void> _openReportHistory() async {
    await Navigator.of(context)
        .push<void>(MaterialPageRoute(builder: (_) => const ReportListPage()));
  }

  void _openProfilePage(Widget page) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => page));
  }

  Future<void> _logout() async {
    final shouldLogout = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
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
      ),
    );

    if (shouldLogout != true) return;
    final serverRevoked = await AuthService.logout();
    if (!mounted) return;

    if (!serverRevoked) {
      await showDialog<void>(
        context: context,
        builder: (dialogContext) => AlertDialog(
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
        ),
      );
    }

    if (mounted) _goToWelcome();
  }

  @override
  Widget build(BuildContext context) {
    final profile = _profile;

    return Scaffold(
      backgroundColor: _pink,
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: _red))
            : _error != null
            ? _errorState()
            : SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(18, 18, 18, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      children: [
                        const Expanded(
                          child: Text(
                            'Profil Saya',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        IconButton(
                          onPressed: () => _openProfilePage(
                            const NotificationPreferencesPage(),
                          ),
                          icon: const Icon(Icons.settings_outlined),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _identityCard(profile!),
                    const SizedBox(height: 12),
                    _profileItem(
                      icon: Icons.person,
                      color: const Color(0xFF22A6E8),
                      title: 'Data Pribadi',
                      subtitle: 'Lihat dan edit informasi akun',
                      onTap: _openPersonalInfo,
                    ),
                    _profileItem(
                      icon: Icons.location_on,
                      color: const Color(0xFFFF777F),
                      title: 'Lokasi Tersimpan',
                      subtitle: 'Kelola alamat domisili dan lokasi penting',
                      onTap: () => _openProfilePage(const SavedLocationsPage()),
                    ),
                    _profileItem(
                      icon: Icons.notifications,
                      color: const Color(0xFF00C88A),
                      title: 'Preferensi Notifikasi',
                      subtitle: 'Atur jenis notifikasi yang diterima',
                      onTap: () =>
                          _openProfilePage(const NotificationPreferencesPage()),
                    ),
                    _profileItem(
                      icon: Icons.call,
                      color: const Color(0xFFFF1744),
                      title: 'Kontak Darurat Saya',
                      subtitle: 'Kelola kontak darurat pribadi',
                      onTap: () =>
                          _openProfilePage(const EmergencyContactsPage()),
                    ),
                    _profileItem(
                      icon: Icons.folder,
                      color: const Color(0xFF9C4DDF),
                      title: 'Riwayat Laporan',
                      subtitle: 'Lihat laporan yang pernah Anda kirim',
                      onTap: _openReportHistory,
                    ),
                    const Divider(height: 22),
                    _profileItem(
                      icon: Icons.help,
                      color: const Color(0xFF078ABD),
                      title: 'Pusat Bantuan',
                      subtitle: 'Pertanyaan umum dan panduan',
                      onTap: () => _openProfilePage(const HelpCenterPage()),
                    ),
                    _profileItem(
                      icon: Icons.info,
                      color: const Color(0xFF078ABD),
                      title: 'Tentang Aplikasi',
                      subtitle: 'Versi 1.0.0',
                      onTap: () => _openProfilePage(const AboutAppPage()),
                    ),
                    const SizedBox(height: 8),
                    ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                      ),
                      leading: const Icon(Icons.logout, color: _red),
                      title: const Text(
                        'Keluar',
                        style: TextStyle(color: _red),
                      ),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: _logout,
                    ),
                  ],
                ),
              ),
      ),
      bottomNavigationBar: AppBottomNavigation(
        selected: AppDestination.profile,
        onSelect: _selectDestination,
      ),
    );
  }

  Widget _errorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.cloud_off_outlined,
              size: 42,
              color: Colors.black45,
            ),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _loadProfile,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _identityCard(Map<String, dynamic> profile) {
    final name = profile['name']?.toString() ?? 'Pengguna';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFC3CA),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          const CircleAvatar(
            radius: 38,
            backgroundColor: Colors.white,
            child: Icon(Icons.person_outline, size: 48, color: Colors.black54),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  profile['email']?.toString() ?? 'Email belum tersedia',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12),
                ),
                if ((profile['phone']?.toString() ?? '').isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    profile['phone'].toString(),
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _profileItem({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 7),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        clipBehavior: Clip.antiAlias,
        child: ListTile(
          onTap: onTap,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 10,
            vertical: 3,
          ),
          leading: Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: Colors.white, size: 23),
          ),
          title: Text(
            title,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
          ),
          subtitle: Text(subtitle, style: const TextStyle(fontSize: 11)),
          trailing: const Icon(Icons.chevron_right, size: 21),
        ),
      ),
    );
  }
}

class ProfileDetailsPage extends StatefulWidget {
  const ProfileDetailsPage({required this.profile, super.key});

  final Map<String, dynamic> profile;

  @override
  State<ProfileDetailsPage> createState() => _ProfileDetailsPageState();
}

class _ProfileDetailsPageState extends State<ProfileDetailsPage> {
  static const Color _red = Color(0xFFD50032);
  late Map<String, dynamic> _profile;

  @override
  void initState() {
    super.initState();
    _profile = Map<String, dynamic>.from(widget.profile);
  }

  Future<void> _editProfile() async {
    final updated = await Navigator.of(context).push<Map<String, dynamic>>(
      MaterialPageRoute(builder: (_) => EditProfilePage(profile: _profile)),
    );

    if (updated != null && mounted) {
      setState(() => _profile = updated);
    }
  }

  Widget _dataRow(IconData icon, String label, String value) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFE8EB),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Icon(icon, color: _red, size: 21),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(fontSize: 12, color: Colors.black54),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFEEF1),
      appBar: AppBar(
        backgroundColor: const Color(0xFFFFEEF1),
        title: const Text('Profil Saya'),
        actions: [
          TextButton(
            onPressed: _editProfile,
            child: const Text(
              'Edit',
              style: TextStyle(color: _red, fontSize: 15),
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 24),
        children: [
          const Center(
            child: CircleAvatar(
              radius: 52,
              backgroundColor: Colors.white,
              child: Icon(
                Icons.person_outline,
                size: 64,
                color: Colors.black54,
              ),
            ),
          ),
          const SizedBox(height: 22),
          const Text(
            'Informasi Pribadi',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 10),
          _dataRow(
            Icons.person,
            'Nama Lengkap',
            _profile['name']?.toString() ?? '—',
          ),
          _dataRow(
            Icons.email_outlined,
            'Email',
            _profile['email']?.toString() ?? '—',
          ),
          _dataRow(
            Icons.phone_outlined,
            'Nomor Telepon',
            _profile['phone']?.toString() ?? 'Belum tersedia',
          ),
          const SizedBox(height: 8),
          const Text(
            'Informasi Tambahan',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          _dataRow(
            Icons.location_on_outlined,
            'Lokasi Domisili',
            'Belum tersedia pada data akun',
          ),
          _dataRow(
            Icons.calendar_month_outlined,
            'Tanggal Lahir',
            'Belum tersedia pada data akun',
          ),
          _dataRow(
            Icons.person_outline,
            'Jenis Kelamin',
            'Belum tersedia pada data akun',
          ),
        ],
      ),
    );
  }
}

class EditProfilePage extends StatefulWidget {
  const EditProfilePage({required this.profile, super.key});

  final Map<String, dynamic> profile;

  @override
  State<EditProfilePage> createState() => _EditProfilePageState();
}

class _EditProfilePageState extends State<EditProfilePage> {
  static const Color _red = Color(0xFFD50032);
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _emailController;
  late final TextEditingController _phoneController;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.profile['name']?.toString(),
    );
    _emailController = TextEditingController(
      text: widget.profile['email']?.toString(),
    );
    _phoneController = TextEditingController(
      text: widget.profile['phone']?.toString(),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isSaving = true);

    try {
      final updated = await ProfileService.updateProfile(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
      );
      if (mounted) Navigator.of(context).pop(updated);
    } on ProfileSessionExpired {
      if (mounted) {
        Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
      }
    } on ProfileServiceException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  InputDecoration _decoration(String label, IconData icon) => InputDecoration(
    labelText: label,
    prefixIcon: Icon(icon, color: _red),
    filled: true,
    fillColor: Colors.white,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
  );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFEEF1),
      appBar: AppBar(
        backgroundColor: const Color(0xFFFFEEF1),
        title: const Text('Edit Profil'),
        actions: [
          TextButton(
            onPressed: _isSaving ? null : _save,
            child: _isSaving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text(
                    'Simpan',
                    style: TextStyle(color: _red, fontSize: 15),
                  ),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 24),
          children: [
            const Center(
              child: CircleAvatar(
                radius: 54,
                backgroundColor: Colors.white,
                child: Icon(
                  Icons.person_outline,
                  size: 66,
                  color: Colors.black54,
                ),
              ),
            ),
            const SizedBox(height: 8),
            const Center(
              child: Text(
                'Foto profil belum didukung',
                style: TextStyle(fontSize: 11, color: Colors.black54),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Informasi Pribadi',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _nameController,
              decoration: _decoration('Nama Lengkap', Icons.person_outline),
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Nama wajib diisi.'
                  : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: _decoration('Email', Icons.email_outlined),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Email wajib diisi.';
                }
                if (!value.contains('@')) {
                  return 'Format email tidak valid.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: _decoration('Nomor Telepon', Icons.phone_outlined),
            ),
            const SizedBox(height: 22),
            const Text(
              'Informasi Tambahan',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Text(
                'Alamat domisili, tanggal lahir, jenis kelamin, dan foto profil belum tersedia pada skema akun/API saat ini.',
                style: TextStyle(fontSize: 12, color: Colors.black54),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'location_service.dart';
import 'profile_local_data.dart';

const Color _profilePink = Color(0xFFFFEEF1);
const Color _profileRed = Color(0xFFD50032);

class SavedLocationsPage extends StatefulWidget {
  const SavedLocationsPage({super.key});

  @override
  State<SavedLocationsPage> createState() => _SavedLocationsPageState();
}

class _SavedLocationsPageState extends State<SavedLocationsPage> {
  List<Map<String, dynamic>> _locations = [];
  CurrentLocation? _currentLocation;
  String? _locationError;
  bool _isLoading = true;
  bool _isDetectingLocation = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final locations = await ProfileLocalData.readSavedLocations();
    if (!mounted) return;
    setState(() {
      _locations = locations;
      _isLoading = false;
    });
  }

  Future<void> _editLocation([Map<String, dynamic>? existing]) async {
    final isExistingLocation =
        existing != null &&
        _locations.any((item) => item['id'] == existing['id']);
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (_) => _LocationEditDialog(
        existing: existing,
        isEditing: isExistingLocation,
        isPrimaryByDefault: _locations.isEmpty,
      ),
    );
    if (result == null || !mounted) return;
    setState(() {
      final index = _locations.indexWhere((item) => item['id'] == result['id']);
      if (index < 0) {
        _locations.add(result);
      } else {
        _locations[index] = result;
      }
      if (result['is_primary'] == true) {
        for (final item in _locations) {
          item['is_primary'] = item['id'] == result['id'];
        }
      }
    });
    await _persistLocations();
  }

  Future<void> _setPrimary(Map<String, dynamic> location) async {
    setState(() {
      for (final item in _locations) {
        item['is_primary'] = item['id'] == location['id'];
      }
    });
    await _persistLocations();
  }

  Future<void> _detectCurrentLocation() async {
    setState(() {
      _isDetectingLocation = true;
      _locationError = null;
    });

    try {
      final location = await LocationService().getCurrentLocation();
      if (!mounted) return;
      setState(() {
        _currentLocation = location;
        _isDetectingLocation = false;
      });
    } on LocationServiceException catch (error) {
      if (!mounted) return;
      setState(() {
        _locationError = error.message;
        _isDetectingLocation = false;
      });
    }
  }

  Future<void> _saveCurrentLocationAsDomicile() async {
    final location = _currentLocation;
    if (location == null) return;

    await _editLocation({
      'id': DateTime.now().microsecondsSinceEpoch,
      'name': 'Domisili',
      'address': location.address ?? '',
      'is_primary': true,
      'latitude': location.latitude,
      'longitude': location.longitude,
      'accuracy': location.accuracyMeters,
      'captured_at': location.capturedAt.toIso8601String(),
    });
  }

  Future<void> _delete(Map<String, dynamic> location) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Hapus lokasi?'),
        content: Text(
          'Lokasi ${location['name']} akan dihapus dari perangkat.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Batal'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(
      () => _locations.removeWhere((item) => item['id'] == location['id']),
    );
    if (_locations.isNotEmpty &&
        !_locations.any((item) => item['is_primary'] == true)) {
      _locations.first['is_primary'] = true;
    }
    await _persistLocations();
  }

  Future<void> _persistLocations() async {
    try {
      await ProfileLocalData.saveSavedLocations(_locations);
    } catch (error) {
      debugPrint('Gagal menyimpan lokasi lokal: $error');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Lokasi belum dapat disimpan di perangkat.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _profilePink,
      appBar: AppBar(
        backgroundColor: _profilePink,
        title: const Text('Lokasi Tersimpan'),
        actions: [
          IconButton(
            onPressed: _editLocation,
            tooltip: 'Tambah lokasi',
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _profileRed))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _currentLocationCard(),
                const SizedBox(height: 16),
                if (_locations.isEmpty)
                  _emptyState(
                    Icons.location_on_outlined,
                    'Belum ada lokasi domisili',
                    'Tambahkan alamat domisili agar titik ini dapat dipakai sebagai acuan saat menyiapkan SOS.',
                    action: _editLocation,
                  )
                else
                  ..._locations.map(_savedLocationCard),
              ],
            ),
    );
  }

  Widget _currentLocationCard() {
    final location = _currentLocation;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.my_location, color: _profileRed),
              SizedBox(width: 9),
              Text(
                'Lokasi saat ini',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            location == null
                ? 'Ambil titik GPS hanya saat Anda menekan tombol. Izin lokasi akan diminta oleh Android.'
                : 'Koordinat: ${location.formattedCoordinates}\nAkurasi ±${location.accuracyMeters.round()} m · Diambil ${_formatTime(location.capturedAt)}',
            style: const TextStyle(
              color: Colors.black54,
              fontSize: 13,
              height: 1.45,
            ),
          ),
          if (location != null && location.address != null) ...[
            const SizedBox(height: 4),
            Text(
              'Perkiraan alamat: ${location.address}',
              style: const TextStyle(fontSize: 13, height: 1.4),
            ),
          ],
          if (_locationError != null) ...[
            const SizedBox(height: 8),
            Text(
              _locationError!,
              style: const TextStyle(color: _profileRed, fontSize: 12),
            ),
          ],
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              FilledButton.icon(
                onPressed: _isDetectingLocation ? null : _detectCurrentLocation,
                icon: _isDetectingLocation
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.my_location),
                label: Text(
                  _isDetectingLocation
                      ? 'Mencari lokasi…'
                      : 'Deteksi lokasi saat ini',
                ),
                style: FilledButton.styleFrom(backgroundColor: _profileRed),
              ),
              if (location != null)
                OutlinedButton.icon(
                  onPressed: _saveCurrentLocationAsDomicile,
                  icon: const Icon(Icons.home_outlined),
                  label: const Text('Simpan sebagai domisili'),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _savedLocationCard(Map<String, dynamic> location) {
    final isPrimary = location['is_primary'] == true;
    final address = location['address']?.toString() ?? '';
    final latitude = location['latitude'];
    final longitude = location['longitude'];
    final coordinateText = latitude is num && longitude is num
        ? 'Koordinat: ${_formatCoordinate(latitude)}, ${_formatCoordinate(longitude)}'
        : null;

    return Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Card(
        color: Colors.white,
        child: ListTile(
          leading: Icon(
            isPrimary ? Icons.home_filled : Icons.location_on_outlined,
            color: _profileRed,
          ),
          title: Text(location['name']?.toString() ?? 'Lokasi'),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (address.isNotEmpty) Text(address),
              if (coordinateText != null) Text(coordinateText),
              if (isPrimary)
                const Text(
                  'Domisili utama',
                  style: TextStyle(
                    color: _profileRed,
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ),
          isThreeLine: true,
          trailing: PopupMenuButton<String>(
            onSelected: (action) {
              if (action == 'primary') _setPrimary(location);
              if (action == 'edit') _editLocation(location);
              if (action == 'delete') _delete(location);
            },
            itemBuilder: (context) => [
              if (!isPrimary)
                const PopupMenuItem(
                  value: 'primary',
                  child: Text('Jadikan domisili utama'),
                ),
              const PopupMenuItem(value: 'edit', child: Text('Edit')),
              const PopupMenuItem(value: 'delete', child: Text('Hapus')),
            ],
          ),
        ),
      ),
    );
  }
}

String _formatCoordinate(Object? value) =>
    (value as num).toDouble().toStringAsFixed(6);

String _formatTime(DateTime value) {
  final local = value.toLocal();
  final hour = local.hour.toString().padLeft(2, '0');
  final minute = local.minute.toString().padLeft(2, '0');
  return '${local.day}/${local.month}/${local.year} $hour:$minute';
}

class NotificationPreferencesPage extends StatefulWidget {
  const NotificationPreferencesPage({super.key});

  @override
  State<NotificationPreferencesPage> createState() =>
      _NotificationPreferencesPageState();
}

class _NotificationPreferencesPageState
    extends State<NotificationPreferencesPage> {
  Map<String, bool> _preferences = {
    ...ProfileLocalData.defaultNotificationPreferences,
  };
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    ProfileLocalData.readNotificationPreferences().then((preferences) {
      if (!mounted) return;
      setState(() {
        _preferences = preferences;
        _isLoading = false;
      });
    });
  }

  Future<void> _setPreference(String category, bool enabled) async {
    final previous = _preferences;
    final updated = {..._preferences, category: enabled};
    setState(() => _preferences = updated);
    try {
      await ProfileLocalData.saveNotificationPreferences(updated);
    } catch (_) {
      if (!mounted) return;
      setState(() => _preferences = previous);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Preferensi belum dapat disimpan di perangkat.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _profilePink,
      appBar: AppBar(
        backgroundColor: _profilePink,
        title: const Text('Preferensi Notifikasi'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _profileRed))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const Card(
                  color: Colors.white,
                  child: Padding(
                    padding: EdgeInsets.all(14),
                    child: Text(
                      'Pilihan ini mengatur kategori yang ditampilkan pada halaman Notifikasi di perangkat ini. Pengiriman push notification belum tersedia.',
                      style: TextStyle(fontSize: 12, height: 1.45),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                for (final category
                    in ProfileLocalData.defaultNotificationPreferences.keys)
                  SwitchListTile(
                    value: _preferences[category] ?? true,
                    onChanged: (value) => _setPreference(category, value),
                    title: Text(category),
                    subtitle: Text(
                      'Tampilkan notifikasi $category di aplikasi',
                    ),
                    activeThumbColor: _profileRed,
                    tileColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
              ],
            ),
    );
  }
}

class HelpCenterPage extends StatelessWidget {
  const HelpCenterPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _profilePink,
      appBar: AppBar(
        backgroundColor: _profilePink,
        title: const Text('Pusat Bantuan'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          _HelpQuestion(
            question: 'Bagaimana melihat peringatan resmi?',
            answer: 'Buka Beranda atau menu Peringatan Bencana. Peringatan menampilkan sumber, wilayah, waktu, dan masa berlaku yang tersedia dari sumber resmi.',
          ),
          _HelpQuestion(
            question: 'Bagaimana mengirim laporan kejadian?',
            answer: 'Pilih Lapor Kejadian, isi lokasi dan keterangan, lalu kirim. Laporan akan tampil pada Riwayat Laporan dan statusnya mengikuti proses verifikasi.',
          ),
          _HelpQuestion(
            question: 'Di mana melihat laporan yang sudah dikirim?',
            answer: 'Buka Profil Saya lalu pilih Riwayat Laporan. Daftar memerlukan koneksi ke server dan sesi akun yang masih berlaku.',
          ),
          _HelpQuestion(
            question: 'Apa yang dilakukan jika data tidak termuat?',
            answer: 'Periksa koneksi internet lalu gunakan tombol Coba lagi. Data yang tidak berhasil diverifikasi akan ditandai atau tidak ditampilkan sebagai data baru.',
          ),
        ],
      ),
    );
  }
}

class _HelpQuestion extends StatelessWidget {
  const _HelpQuestion({required this.question, required this.answer});

  final String question;
  final String answer;

  @override
  Widget build(BuildContext context) => Card(
    color: Colors.white,
    child: ExpansionTile(
      title: Text(
        question,
        style: const TextStyle(fontWeight: FontWeight.w500),
      ),
      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      children: [Text(answer, style: const TextStyle(height: 1.45))],
    ),
  );
}

class AboutAppPage extends StatelessWidget {
  const AboutAppPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _profilePink,
      appBar: AppBar(
        backgroundColor: _profilePink,
        title: const Text('Tentang SIGAP Bencana'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          const Text(
            'Platform informasi kebencanaan untuk membantu masyarakat tetap mendapat informasi dan lebih siap menghadapi bencana.',
            style: TextStyle(fontSize: 16, height: 1.5),
          ),
          const SizedBox(height: 16),
          const _AboutCard(
            title: 'Visi',
            color: Color(0xFFD2E9FF),
            content: 'Terwujudnya masyarakat Indonesia yang tangguh dan siap menghadapi bencana.',
          ),
          const SizedBox(height: 10),
          const _AboutCard(
            title: 'Misi',
            color: Color(0xFFD4F8DB),
            content: '1. Menyediakan informasi bencana yang akurat dan terkini.\n\n2. Memudahkan masyarakat dalam melaporkan kejadian bencana.\n\n3. Meningkatkan edukasi dan kesadaran akan mitigasi bencana.',
          ),
          const SizedBox(height: 12),
          const Center(
            child: Text('Versi 1.0.0', style: TextStyle(color: Colors.black54)),
          ),
        ],
      ),
    );
  }
}

class _AboutCard extends StatelessWidget {
  const _AboutCard({
    required this.title,
    required this.content,
    required this.color,
  });

  final String title;
  final String content;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(14),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Center(
          child: Text(
            title,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
          ),
        ),
        const SizedBox(height: 12),
        Text(content, style: const TextStyle(fontSize: 14, height: 1.5)),
      ],
    ),
  );
}

Widget _emptyState(
  IconData icon,
  String title,
  String description, {
  VoidCallback? action,
}) => Center(
  child: Padding(
    padding: const EdgeInsets.all(28),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 48, color: _profileRed),
        const SizedBox(height: 12),
        Text(
          title,
          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
        ),
        const SizedBox(height: 6),
        Text(
          description,
          textAlign: TextAlign.center,
          style: const TextStyle(color: Colors.black54, height: 1.4),
        ),
        if (action != null) ...[
          const SizedBox(height: 14),
          FilledButton.icon(
            onPressed: action,
            icon: const Icon(Icons.add),
            label: const Text('Tambah'),
          ),
        ],
      ],
    ),
  ),
);

class _LocationEditDialog extends StatefulWidget {
  const _LocationEditDialog({
    required this.existing,
    required this.isEditing,
    required this.isPrimaryByDefault,
  });

  final Map<String, dynamic>? existing;
  final bool isEditing;
  final bool isPrimaryByDefault;

  @override
  State<_LocationEditDialog> createState() => _LocationEditDialogState();
}

class _LocationEditDialogState extends State<_LocationEditDialog> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _addressController;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.existing?['name']?.toString() ?? '',
    );
    _addressController = TextEditingController(
      text: widget.existing?['address']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  void _save() {
    if (!_formKey.currentState!.validate()) return;
    final existing = widget.existing;

    Navigator.of(context).pop({
      'id': existing?['id'] ?? DateTime.now().microsecondsSinceEpoch,
      'name': _nameController.text.trim(),
      'address': _addressController.text.trim(),
      'is_primary': existing?['is_primary'] ?? widget.isPrimaryByDefault,
      'latitude': existing?['latitude'],
      'longitude': existing?['longitude'],
      'accuracy': existing?['accuracy'],
      'captured_at': existing?['captured_at'],
    });
  }

  @override
  Widget build(BuildContext context) {
    final existing = widget.existing;

    return AlertDialog(
      title: Text(widget.isEditing ? 'Edit lokasi' : 'Tambah lokasi'),
      content: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Nama lokasi'),
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Nama lokasi wajib diisi.'
                  : null,
            ),
            TextFormField(
              controller: _addressController,
              decoration: const InputDecoration(labelText: 'Alamat'),
              minLines: 1,
              maxLines: 3,
              validator: (value) =>
                  (value == null || value.trim().isEmpty) &&
                      existing?['latitude'] == null
                  ? 'Alamat wajib diisi.'
                  : null,
            ),
            if (existing?['latitude'] is num &&
                existing?['longitude'] is num) ...[
              const SizedBox(height: 8),
              Text(
                'Titik GPS: ${_formatCoordinate(existing!['latitude'])}, ${_formatCoordinate(existing['longitude'])}',
                style: const TextStyle(fontSize: 12, color: Colors.black54),
              ),
              if (existing['accuracy'] is num)
                Text(
                  'Akurasi sekitar ±${(existing['accuracy'] as num).round()} m',
                  style: const TextStyle(fontSize: 12, color: Colors.black54),
                ),
            ],
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Batal'),
        ),
        FilledButton(onPressed: _save, child: const Text('Simpan')),
      ],
    );
  }
}


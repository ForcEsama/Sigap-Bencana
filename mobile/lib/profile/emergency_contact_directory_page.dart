import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'emergency_contact_directory_service.dart';

class EmergencyContactDirectoryPage extends StatefulWidget {
  const EmergencyContactDirectoryPage({super.key});

  @override
  State<EmergencyContactDirectoryPage> createState() =>
      _EmergencyContactDirectoryPageState();
}

class _EmergencyContactDirectoryPageState
    extends State<EmergencyContactDirectoryPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  static const Color _red = Color(0xFFD50032);
  static const MethodChannel _channel = MethodChannel('sigap/emergency-contacts');

  final EmergencyContactDirectoryService _service =
      EmergencyContactDirectoryService();
  List<EmergencyContact> _contacts = const [];
  String? _error;
  bool _isLoading = true;
  bool _isOffline = false;

  @override
  void initState() {
    super.initState();
    _loadContacts();
  }

  Future<void> _loadContacts() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await _service.fetchContacts();
      if (!mounted) return;
      setState(() {
        _contacts = result.contacts;
        _isOffline = result.isOffline;
        _isLoading = false;
      });
    } on EmergencyContactDirectoryException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  Future<void> _dial(EmergencyContact contact) async {
    final phone = contact.phone.replaceAll(RegExp(r'[^0-9+]'), '');
    if (!RegExp(r'^\+?[0-9]{3,30}$').hasMatch(phone)) return;

    try {
      await _channel.invokeMethod<void>('dial', {'phone': phone});
    } on PlatformException {
      _showActionError('Aplikasi telepon tidak dapat dibuka.');
    } on MissingPluginException {
      _showActionError('Fitur telepon belum tersedia pada platform ini.');
    }
  }

  Future<void> _openWebsite(EmergencyContact contact) async {
    final value = contact.websiteUrl;
    final uri = value == null ? null : Uri.tryParse(value);
    if (uri == null ||
        !uri.hasAuthority ||
        !{'http', 'https'}.contains(uri.scheme.toLowerCase())) {
      return;
    }

    try {
      await _channel.invokeMethod<void>('openWebsite', {'url': uri.toString()});
    } on PlatformException {
      _showActionError('Situs layanan tidak dapat dibuka.');
    } on MissingPluginException {
      _showActionError('Fitur tautan belum tersedia pada platform ini.');
    }
  }

  void _showActionError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  IconData _iconFor(String category) {
    final normalized = category.toLowerCase();
    if (normalized.contains('medis') || normalized.contains('kesehatan')) {
      return Icons.medical_services_outlined;
    }
    if (normalized.contains('polisi') || normalized.contains('keamanan')) {
      return Icons.shield_outlined;
    }
    if (normalized.contains('pemadam') || normalized.contains('kebakaran')) {
      return Icons.local_fire_department_outlined;
    }
    if (normalized.contains('sar') || normalized.contains('pertolongan')) {
      return Icons.support_outlined;
    }
    if (normalized.contains('bencana') || normalized.contains('evakuasi')) {
      return Icons.flood_outlined;
    }
    return Icons.call_outlined;
  }

  String? _logoAssetFor(String name) {
    return switch (name.trim().toLowerCase()) {
      'bpbd kota padang' =>
        'assets/images/emergency-contacts/bpbd-kota-padang.png',
      'basarnas' => 'assets/images/emergency-contacts/basarnas.png',
      'polri' => 'assets/images/emergency-contacts/polri.png',
      _ => null,
    };
  }

  String? _phoneNumber(String value) {
    final sanitized = value.replaceAll(RegExp(r'[^0-9+]'), '');
    return RegExp(r'^\+?[0-9]{3,30}$').hasMatch(sanitized)
        ? sanitized
        : null;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        title: const Text('Kontak Darurat'),
        actions: [
          IconButton(
            onPressed: _isLoading ? null : _loadContacts,
            icon: const Icon(Icons.refresh),
            tooltip: 'Muat ulang',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _red))
          : _error != null
          ? _errorState()
          : RefreshIndicator(
              onRefresh: _loadContacts,
              color: _red,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(18, 8, 18, 24),
                children: [
                  if (_isOffline) _offlineBanner(),
                  _guidanceCard(),
                  const SizedBox(height: 18),
                  const Text(
                    'Kontak yang Tersedia',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Informasi mengikuti daftar layanan aktif yang sama dengan website.',
                    style: TextStyle(color: Colors.black54),
                  ),
                  const SizedBox(height: 12),
                  if (_contacts.isEmpty)
                    _emptyState()
                  else
                    ..._contacts.map(_contactCard),
                  const SizedBox(height: 8),
                  _tipsCard(),
                ],
              ),
            ),
    );
  }

  Widget _offlineBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFE6B8),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Row(
        children: [
          Icon(Icons.cloud_off_outlined),
          SizedBox(width: 8),
          Expanded(child: Text('Offline · menampilkan data terakhir tersimpan')),
        ],
      ),
    );
  }

  Widget _guidanceCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.warning_amber_rounded, color: _red),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Utamakan keselamatan. Hubungi layanan yang sesuai dan tersedia di wilayah Anda, lalu ikuti arahan petugas setempat.',
              style: TextStyle(height: 1.4),
            ),
          ),
        ],
      ),
    );
  }

  Widget _contactCard(EmergencyContact contact) {
    final logoAsset = _logoAssetFor(contact.name);
    final phone = _phoneNumber(contact.phone);
    final location = [contact.region, contact.address]
        .whereType<String>()
        .where((part) => part.isNotEmpty)
        .join(' · ');
    final website = contact.websiteUrl == null
        ? null
        : Uri.tryParse(contact.websiteUrl!);
    final websiteIsValid = website != null &&
        website.hasAuthority &&
        {'http', 'https'}.contains(website.scheme.toLowerCase());

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFFD5DC)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFECED),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: logoAsset == null
                    ? Icon(_iconFor(contact.category), color: _red)
                    : Padding(
                        padding: const EdgeInsets.all(4),
                        child: Image.asset(
                          logoAsset,
                          fit: BoxFit.contain,
                          errorBuilder: (context, error, stackTrace) =>
                              Icon(_iconFor(contact.category), color: _red),
                        ),
                      ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(contact.name,
                        style: const TextStyle(fontWeight: FontWeight.w600)),
                    if (contact.category.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(contact.category,
                          style: const TextStyle(color: Colors.black54)),
                    ],
                  ],
                ),
              ),
            ],
          ),
          if (contact.description != null) ...[
            const SizedBox(height: 10),
            Text(contact.description!, style: const TextStyle(height: 1.35)),
          ],
          if (location.isNotEmpty) ...[
            const SizedBox(height: 9),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.location_on_outlined, size: 18, color: _red),
                const SizedBox(width: 5),
                Expanded(child: Text(location, style: const TextStyle(color: Colors.black54))),
              ],
            ),
          ],
          const Divider(height: 22),
          Row(
            children: [
              Expanded(
                child: phone == null
                    ? Text(contact.phone.isEmpty
                        ? 'Nomor telepon belum dicantumkan'
                        : contact.phone)
                    : TextButton.icon(
                        onPressed: () => _dial(contact),
                        style: TextButton.styleFrom(foregroundColor: _red),
                        icon: const Icon(Icons.call, size: 18),
                        label: Text(contact.phone),
                      ),
              ),
              if (websiteIsValid)
                IconButton(
                  onPressed: () => _openWebsite(contact),
                  icon: const Icon(Icons.open_in_new, color: _red),
                  tooltip: 'Situs layanan',
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _tipsCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Tips Saat Menghubungi',
              style: TextStyle(fontWeight: FontWeight.w600)),
          SizedBox(height: 10),
          Text('• Sampaikan lokasi kejadian sejelas mungkin.'),
          SizedBox(height: 6),
          Text('• Jelaskan kejadian dan kebutuhan bantuan secara singkat.'),
          SizedBox(height: 6),
          Text('• Dengarkan dan ikuti instruksi petugas.'),
        ],
      ),
    );
  }

  Widget _emptyState() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: const Column(
        children: [
          Icon(Icons.call_end_outlined, color: Colors.black45, size: 36),
          SizedBox(height: 8),
          Text('Kontak belum tersedia',
              style: TextStyle(fontWeight: FontWeight.w600)),
          SizedBox(height: 4),
          Text(
            'Belum ada layanan aktif yang dapat ditampilkan saat ini.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.black54),
          ),
        ],
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
            const Icon(Icons.cloud_off_outlined, size: 42, color: Colors.black45),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _loadContacts,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }
}

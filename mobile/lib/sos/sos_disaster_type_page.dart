import 'package:flutter/material.dart';

import 'sos_location_page.dart';

class SosDisasterTypePage extends StatefulWidget {
  const SosDisasterTypePage({super.key});

  @override
  State<SosDisasterTypePage> createState() => _SosDisasterTypePageState();
}

class _SosDisasterTypePageState extends State<SosDisasterTypePage> {
  static const Color _red = Color(0xFFD2002F);
  static const Color _pink = Color(0xFFFFECED);
  static const List<({String key, String label, IconData icon, Color color})>
  _types = [
    (
      key: 'gempa',
      label: 'Gempa Bumi',
      icon: Icons.cyclone,
      color: Color(0xFFD50032),
    ),
    (
      key: 'banjir',
      label: 'Banjir',
      icon: Icons.flood,
      color: Color(0xFF1688D4),
    ),
    (
      key: 'longsor',
      label: 'Longsor',
      icon: Icons.landscape,
      color: Color(0xFFB8660B),
    ),
    (
      key: 'kebakaran',
      label: 'Kebakaran',
      icon: Icons.local_fire_department,
      color: Color(0xFFE75D2A),
    ),
    (
      key: 'tsunami',
      label: 'Tsunami',
      icon: Icons.waves,
      color: Color(0xFF087BC3),
    ),
  ];

  String? _selectedType;

  Future<void> _continue() async {
    final selectedType = _selectedType;
    if (selectedType == null) return;

    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => SosLocationPage(disasterType: selectedType),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(backgroundColor: _pink),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(24, 8, 24, 25),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Pilih Jenis Keadaan Darurat',
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.w600),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Pilih jenis bencana atau kondisi yang sedang Anda alami.',
                    style: TextStyle(fontSize: 16, height: 1.4),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                itemCount: _types.length,
                separatorBuilder: (_, index) => const SizedBox(height: 12),
                itemBuilder: (context, index) {
                  final type = _types[index];
                  final selected = _selectedType == type.key;
                  return Material(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(10),
                      onTap: () => setState(() => _selectedType = type.key),
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          border: Border.all(
                            color: selected ? _red : const Color(0xFF999999),
                            width: selected ? 2 : 1,
                          ),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                color: type.color.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(9),
                              ),
                              child: Icon(
                                type.icon,
                                color: type.color,
                                size: 29,
                              ),
                            ),
                            const SizedBox(width: 15),
                            Expanded(
                              child: Text(
                                type.label,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                            if (selected)
                              const Icon(Icons.check_circle, color: _red),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 12, 24, 20),
              child: FilledButton(
                onPressed: _selectedType == null ? null : _continue,
                style: FilledButton.styleFrom(
                  backgroundColor: _red,
                  minimumSize: const Size.fromHeight(54),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text('Lanjutkan', style: TextStyle(fontSize: 18)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

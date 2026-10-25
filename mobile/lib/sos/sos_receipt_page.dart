import 'package:flutter/material.dart';

import 'sos_request.dart';
import 'sos_status_page.dart';

class SosReceiptPage extends StatelessWidget {
  const SosReceiptPage({required this.request, super.key});

  final SosRequest request;

  static const Color _pink = Color(0xFFFFECED);
  static const Color _green = Color(0xFF0BC58B);
  static const Color _red = Color(0xFFD2002F);

  void _openStatus(BuildContext context) {
    Navigator.of(context).pushReplacement<void, void>(
      MaterialPageRoute<void>(
        builder: (_) =>
            SosStatusPage(sosRequestId: request.id, initialRequest: request),
      ),
    );
  }

  void _returnHome(BuildContext context) {
    Navigator.of(context).popUntil((route) => route.isFirst);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 20, 24, 22),
          child: Column(
            children: [
              const Spacer(),
              const CircleAvatar(
                radius: 64,
                backgroundColor: _green,
                child: Icon(Icons.check, size: 76, color: Colors.white),
              ),
              const SizedBox(height: 28),
              const Text(
                'Permintaan Bantuan\nBerhasil Dikirim',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 25, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 12),
              const Text(
                'Permintaan sudah tersimpan di server dan menunggu tindak lanjut petugas.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.black54,
                  fontSize: 16,
                  height: 1.4,
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.all(17),
                decoration: BoxDecoration(
                  color: Colors.white,
                  border: Border.all(color: const Color(0xFF999999)),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.info, color: _red),
                        SizedBox(width: 10),
                        Text(
                          'Informasi yang Dikirim',
                          style: TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    _summary('Nomor Permintaan', request.reference),
                    _summary(
                      'Jenis Darurat',
                      sosDisasterTypeLabel(request.disasterType),
                    ),
                    _summary(
                      'Lokasi',
                      request.address ??
                          '${request.latitude}, ${request.longitude}',
                    ),
                    _summary('Waktu', _dateLabel(request.requestedAt)),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () => _openStatus(context),
                  child: const Text('Pantau Status Bantuan'),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => _returnHome(context),
                  style: FilledButton.styleFrom(
                    backgroundColor: _red,
                    minimumSize: const Size.fromHeight(50),
                  ),
                  child: const Text('Kembali ke Beranda'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _summary(String label, String value) => Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 112,
          child: Text(label, style: const TextStyle(color: Colors.black54)),
        ),
        Expanded(child: Text(value)),
      ],
    ),
  );

  String _dateLabel(DateTime? value) {
    if (value == null) return 'Waktu belum tersedia';
    final date = value.toLocal();
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/${date.year}, '
        '${date.hour.toString().padLeft(2, '0')}:'
        '${date.minute.toString().padLeft(2, '0')}';
  }
}

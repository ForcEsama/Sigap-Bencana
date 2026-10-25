import 'package:flutter/material.dart';

import 'report_formatters.dart';
import 'report_service.dart';

class ReportDetailPage extends StatefulWidget {
  const ReportDetailPage({required this.reportId, super.key});

  final int reportId;

  @override
  State<ReportDetailPage> createState() => _ReportDetailPageState();
}

class _ReportDetailPageState extends State<ReportDetailPage> {
  static const Color red = Color(0xFFD2002F);
  static const Color blush = Color(0xFFFFECED);

  final ReportService _reportService = ReportService();
  late Future<Map<String, dynamic>> _reportFuture;

  @override
  void initState() {
    super.initState();
    _reportFuture = _reportService.fetchReport(widget.reportId);
  }

  void _retry() {
    setState(() {
      _reportFuture = _reportService.fetchReport(widget.reportId);
    });
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.black54),
          ),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontSize: 14, height: 1.4)),
        ],
      ),
    );
  }

  Widget _status(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(label, style: const TextStyle(fontSize: 11)),
    );
  }

  Widget _errorState(Object error) {
    final message = error is ReportApiException
        ? error.message
        : 'Detail laporan belum dapat dimuat. Periksa koneksi lalu coba lagi.';

    if (error is ReportApiException && error.statusCode == 401) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          Navigator.of(context)
              .pushNamedAndRemoveUntil('/welcome', (route) => false);
        }
      });
    }

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_outlined, size: 48, color: Colors.grey),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            TextButton.icon(
              onPressed: _retry,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _loadedReport(Map<String, dynamic> report) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          color: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: const BorderSide(color: Color(0xFFE9DADD)),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Laporan #${report['id']}',
                  style: const TextStyle(color: red, fontSize: 12),
                ),
                const SizedBox(height: 6),
                Text(
                  report['title']?.toString() ?? 'Laporan kejadian',
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 18,
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 7,
                  runSpacing: 7,
                  children: [
                    _status(
                      verificationStatusLabel(report['verification_status']),
                      const Color(0xFFFFE5E9),
                    ),
                    _status(
                      handlingStatusLabel(report['handling_status']),
                      const Color(0xFFF0EFF0),
                    ),
                  ],
                ),
                const Divider(height: 28),
                _detailRow(
                  'Jenis bencana',
                  reportTypeLabel(report['disaster_type']),
                ),
                _detailRow(
                  'Lokasi kejadian',
                  report['address']?.toString().trim().isNotEmpty == true
                      ? report['address'].toString()
                      : 'Alamat tidak tersedia',
                ),
                _detailRow(
                  'Deskripsi kejadian',
                  report['description']?.toString().trim().isNotEmpty == true
                      ? report['description'].toString()
                      : 'Deskripsi tidak tersedia.',
                ),
                _detailRow(
                  'Waktu kejadian',
                  reportDateLabel(
                    report['occurred_at'],
                    fallback: 'Tidak dicantumkan',
                  ),
                ),
                _detailRow(
                  'Koordinat',
                  '${report['latitude'] ?? '-'}, ${report['longitude'] ?? '-'}',
                ),
                _detailRow('Dibuat', reportDateLabel(report['created_at'])),
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: blush,
      appBar: AppBar(
        backgroundColor: blush,
        title: const Text('Detail Laporan'),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _reportFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator(color: red));
          }

          if (snapshot.hasError) return _errorState(snapshot.error!);
          if (snapshot.data == null) {
            return _errorState(
              const ReportApiException(500, 'Respons detail laporan kosong.'),
            );
          }

          return _loadedReport(snapshot.data!);
        },
      ),
    );
  }
}

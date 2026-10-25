import 'package:flutter/material.dart';

import 'report_detail_page.dart';
import 'report_formatters.dart';
import 'report_service.dart';
import 'report_form_page.dart';

class ReportListPage extends StatefulWidget {
  const ReportListPage({super.key});

  @override
  State<ReportListPage> createState() => _ReportListPageState();
}

class _ReportListPageState extends State<ReportListPage> {
  static const Color red = Color(0xFFD2002F);
  static const Color blush = Color(0xFFFFECED);

  final ReportService _reportService = ReportService();
  final List<Map<String, dynamic>> _reports = [];

  bool _isLoading = true;
  bool _isLoadingMore = false;
  String? _error;
  int _currentPage = 1;
  int _lastPage = 1;

  @override
  void initState() {
    super.initState();
    _loadReports();
  }

  Future<void> _loadReports({bool loadMore = false}) async {
    if (loadMore && (_isLoadingMore || _currentPage >= _lastPage)) return;

    setState(() {
      if (loadMore) {
        _isLoadingMore = true;
      } else {
        _isLoading = true;
        _error = null;
      }
    });

    try {
      final page = await _reportService.fetchReports(
        page: loadMore ? _currentPage + 1 : 1,
      );

      if (!mounted) return;

      setState(() {
        if (loadMore) {
          _reports.addAll(page.reports);
        } else {
          _reports
            ..clear()
            ..addAll(page.reports);
        }
        _currentPage = page.currentPage;
        _lastPage = page.lastPage;
        _error = null;
      });
    } on ReportApiException catch (exception) {
      if (!mounted) return;

      if (exception.statusCode == 401) {
        Navigator.of(context)
            .pushNamedAndRemoveUntil('/welcome', (route) => false);
        return;
      }

      setState(() => _error = exception.message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Laporan belum dapat dimuat. Periksa koneksi lalu coba lagi.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _isLoadingMore = false;
        });
      }
    }
  }

  Future<void> _openCreateForm() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(builder: (_) => const ReportFormPage()),
    );

    if (created == true && mounted) {
      await _loadReports();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Laporan berhasil dikirim dan menunggu verifikasi.'),
          ),
        );
      }
    }
  }

  Future<void> _openReport(Map<String, dynamic> report) async {
    final id = int.tryParse(report['id']?.toString() ?? '');
    if (id == null) return;

    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(builder: (_) => ReportDetailPage(reportId: id)),
    );
  }

  Widget _reportCard(Map<String, dynamic> report) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE9DADD)),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _openReport(report),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                reportTypeLabel(report['disaster_type']),
                style: const TextStyle(
                  color: red,
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                report['title']?.toString() ?? 'Laporan kejadian',
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.location_on_outlined, size: 16, color: red),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      report['address']?.toString().trim().isNotEmpty == true
                          ? report['address'].toString()
                          : 'Alamat tidak tersedia',
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  _statusChip(
                    verificationStatusLabel(report['verification_status']),
                    const Color(0xFFFFE5E9),
                  ),
                  _statusChip(
                    handlingStatusLabel(report['handling_status']),
                    const Color(0xFFF0EFF0),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Dilaporkan ${reportDateLabel(report['created_at'])}',
                style: const TextStyle(fontSize: 11, color: Colors.black54),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statusChip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(label, style: const TextStyle(fontSize: 10)),
    );
  }

  Widget _body() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: red));
    }

    if (_error != null && _reports.isEmpty) {
      return _messageState(
        icon: Icons.cloud_off_outlined,
        title: 'Laporan belum dapat dimuat',
        message: _error!,
        action: TextButton.icon(
          onPressed: _loadReports,
          icon: const Icon(Icons.refresh),
          label: const Text('Coba lagi'),
        ),
      );
    }

    if (_reports.isEmpty) {
      return _messageState(
        icon: Icons.assignment_outlined,
        title: 'Belum ada laporan',
        message: 'Laporan yang kamu kirim akan muncul di sini.',
        action: FilledButton.icon(
          onPressed: _openCreateForm,
          icon: const Icon(Icons.add),
          label: const Text('Buat laporan'),
          style: FilledButton.styleFrom(backgroundColor: red),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadReports,
      color: red,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
        children: [
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Text(
                'Pembaruan laporan gagal: $_error',
                style: const TextStyle(color: red, fontSize: 12),
              ),
            ),
          ..._reports.map(_reportCard),
          if (_currentPage < _lastPage)
            Center(
              child: TextButton(
                onPressed: _isLoadingMore
                    ? null
                    : () => _loadReports(loadMore: true),
                child: _isLoadingMore
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Muat laporan lainnya'),
              ),
            ),
        ],
      ),
    );
  }

  Widget _messageState({
    required IconData icon,
    required String title,
    required String message,
    required Widget action,
  }) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 52, color: Colors.black38),
            const SizedBox(height: 12),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
            ),
            const SizedBox(height: 6),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 12, color: Colors.black54),
            ),
            const SizedBox(height: 12),
            action,
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: blush,
      appBar: AppBar(
        backgroundColor: blush,
        title: const Text('Riwayat Laporan Saya'),
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(16, 4, 16, 0),
            child: Text(
              'Pantau laporan kejadian yang sudah kamu kirim.',
              style: TextStyle(fontSize: 12, color: Colors.black54),
            ),
          ),
          Expanded(child: _body()),
        ],
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 12),
        child: FilledButton.icon(
          onPressed: _openCreateForm,
          icon: const Icon(Icons.add),
          label: const Text('Lapor Kejadian'),
          style: FilledButton.styleFrom(
            backgroundColor: red,
            minimumSize: const Size.fromHeight(48),
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import 'report_detail_page.dart';
import 'report_formatters.dart';
import 'report_service.dart';
import 'report_form_page.dart';
import '../sos/sos_request.dart';
import '../sos/sos_sending_page.dart';
import '../sos/sos_service.dart';
import '../sos/sos_status_page.dart';

class _ReportLoadResult {
  const _ReportLoadResult({this.page, this.error, this.sessionExpired = false});

  final ReportPage? page;
  final String? error;
  final bool sessionExpired;
}

class _SosLoadResult {
  const _SosLoadResult({this.page, this.error, this.sessionExpired = false});

  final SosRequestPage? page;
  final String? error;
  final bool sessionExpired;
}

class ReportListPage extends StatefulWidget {
  const ReportListPage({this.reportService, this.sosService, super.key});

  final ReportService? reportService;
  final SosService? sosService;

  @override
  State<ReportListPage> createState() => _ReportListPageState();
}

class _ReportListPageState extends State<ReportListPage> {
  static const Color red = Color(0xFFD2002F);
  static const Color blush = Color(0xFFFFECED);

  late final ReportService _reportService =
      widget.reportService ?? ReportService();
  late final SosService _sosService = widget.sosService ?? SosService();
  final List<Map<String, dynamic>> _reports = [];
  List<SosRequest> _sosRequests = [];
  List<Map<String, dynamic>> _pendingSosRequests = [];

  bool _isLoading = true;
  bool _isLoadingMore = false;
  bool _reportsFromCache = false;
  bool _sosFromCache = false;
  String? _error;
  String? _sosError;
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
      if (!loadMore) {
        try {
          final pending = await _sosService.fetchPendingRequests();
          if (mounted) setState(() => _pendingSosRequests = pending);
        } catch (error) {
          debugPrint('Gagal membaca antrean SOS lokal: $error');
        }
      }

      final results = await Future.wait([
        _loadReportPage(page: loadMore ? _currentPage + 1 : 1),
        if (!loadMore) _loadSosPage(),
      ]);
      final reportResult = results.first as _ReportLoadResult;
      final sosResult = loadMore ? null : results[1] as _SosLoadResult;

      if (reportResult.sessionExpired || sosResult?.sessionExpired == true) {
        if (mounted) {
          Navigator.of(context)
              .pushNamedAndRemoveUntil('/welcome', (route) => false);
        }
        return;
      }

      if (!mounted) return;

      setState(() {
        if (reportResult.page != null) {
          if (loadMore) {
            _reports.addAll(reportResult.page!.reports);
          } else {
            _reports
              ..clear()
              ..addAll(reportResult.page!.reports);
          }
          _currentPage = reportResult.page!.currentPage;
          _lastPage = reportResult.page!.lastPage;
          _reportsFromCache = _reportService.lastReadFromCache;
        }
        _error = reportResult.error;
        if (sosResult?.page != null) {
          _sosRequests = sosResult!.page!.requests;
          _sosFromCache = _sosService.lastReadFromCache;
        }
        _sosError = sosResult?.error;
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

  Future<_ReportLoadResult> _loadReportPage({required int page}) async {
    try {
      return _ReportLoadResult(
        page: await _reportService.fetchReports(page: page),
      );
    } on ReportApiException catch (exception) {
      return _ReportLoadResult(
        error: exception.message,
        sessionExpired: exception.statusCode == 401,
      );
    } catch (error) {
      debugPrint('Gagal memuat laporan kejadian: $error');
      return const _ReportLoadResult(
        error: 'Laporan belum dapat dimuat. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  Future<_SosLoadResult> _loadSosPage() async {
    try {
      return _SosLoadResult(page: await _sosService.fetchRequests());
    } on SosSessionExpired {
      return const _SosLoadResult(sessionExpired: true);
    } on SosApiException catch (exception) {
      return _SosLoadResult(error: exception.message);
    } catch (error) {
      debugPrint('Gagal memuat riwayat SOS: $error');
      return _SosLoadResult(
        error: 'Riwayat SOS gagal diproses (${error.runtimeType}).',
      );
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

    if (_error != null &&
        _reports.isEmpty &&
        _sosRequests.isEmpty &&
        _pendingSosRequests.isEmpty) {
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

    if (_reports.isEmpty &&
        _sosRequests.isEmpty &&
        _pendingSosRequests.isEmpty &&
        _sosError == null) {
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
          if (_reportsFromCache || _sosFromCache)
            const Padding(
              padding: EdgeInsets.only(bottom: 10),
              child: Text(
                'Offline · riwayat berasal dari data terakhir yang tersimpan.',
                style: TextStyle(fontSize: 12, color: Color(0xFF765500)),
              ),
            ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Text(
                'Pembaruan laporan gagal: $_error',
                style: const TextStyle(color: red, fontSize: 12),
              ),
            ),
          if (_sosError != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Text(
                'Riwayat SOS belum dapat dimuat: $_sosError',
                style: const TextStyle(color: red, fontSize: 12),
              ),
            ),
          if (_sosRequests.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.only(bottom: 9),
              child: Text(
                'Permintaan SOS',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
              ),
            ),
            ..._sosRequests.map(_sosCard),
          ],
          if (_pendingSosRequests.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.only(top: 4, bottom: 9),
              child: Text(
                'Belum Terkirim dari Perangkat',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
              ),
            ),
            ..._pendingSosRequests.map(_pendingSosCard),
          ],
          if (_reports.isNotEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 4, bottom: 9),
              child: Text(
                'Laporan Kejadian',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
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

  Widget _sosCard(SosRequest request) => Card(
    margin: const EdgeInsets.only(bottom: 12),
    color: Colors.white,
    elevation: 0,
    shape: RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(12),
      side: const BorderSide(color: Color(0xFFE9DADD)),
    ),
    child: InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => Navigator.of(context).push<void>(
        MaterialPageRoute<void>(
          builder: (_) =>
              SosStatusPage(sosRequestId: request.id, initialRequest: request),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.sos, color: red, size: 20),
                const SizedBox(width: 7),
                Text(
                  request.reference,
                  style: const TextStyle(
                    color: red,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
                const Spacer(),
                const Icon(Icons.chevron_right),
              ],
            ),
            const SizedBox(height: 5),
            Text(
              sosDisasterTypeLabel(request.disasterType),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 6),
            Text(
              request.statusLabel,
              style: const TextStyle(color: Colors.black54, fontSize: 12),
            ),
            if (request.address?.isNotEmpty == true) ...[
              const SizedBox(height: 5),
              Text(
                request.address!,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12),
              ),
            ],
          ],
        ),
      ),
    ),
  );

  Widget _pendingSosCard(Map<String, dynamic> request) {
    final disasterType = request['disaster_type']?.toString() ?? '';
    final clientRequestId = request['client_request_id']?.toString() ?? '';
    final latitude = (request['latitude'] as num?)?.toDouble();
    final longitude = (request['longitude'] as num?)?.toDouble();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: const Color(0xFFFFF4D6),
      elevation: 0,
      child: ListTile(
        leading: const Icon(Icons.cloud_off_outlined, color: red),
        title: Text(sosDisasterTypeLabel(disasterType)),
        subtitle: const Text(
          'Tersimpan di perangkat. Belum terkirim ke server atau diterima petugas.',
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: clientRequestId.isEmpty || latitude == null || longitude == null
            ? null
            : () => Navigator.of(context).push<void>(
                MaterialPageRoute<void>(
                  builder: (_) => SosSendingPage(
                    disasterType: disasterType,
                    latitude: latitude,
                    longitude: longitude,
                    address: request['address']?.toString() ?? '',
                    clientRequestId: clientRequestId,
                  ),
                ),
              ),
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

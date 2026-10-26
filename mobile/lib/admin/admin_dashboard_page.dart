import 'package:flutter/material.dart';

import '../services/auth_service.dart';
import 'admin_operations_service.dart';

class AdminDashboardPage extends StatefulWidget {
  const AdminDashboardPage({this.offlineMode = false, super.key});

  final bool offlineMode;

  @override
  State<AdminDashboardPage> createState() => _AdminDashboardPageState();
}

class _AdminDashboardPageState extends State<AdminDashboardPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  static const Color _red = Color(0xFFD50032);

  final AdminOperationsService _service = AdminOperationsService();
  final Map<int, int?> _reportAssignees = {};
  final Map<int, int?> _sosAssignees = {};

  AdminQueue? _queue;
  String? _error;
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    if (widget.offlineMode) {
      _loading = false;
      _error = 'Server sedang offline. Antrean operasional perlu koneksi untuk menampilkan status terbaru.';
    } else {
      _loadQueue();
    }
  }

  Future<void> _loadQueue() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final queue = await _service.fetchQueue();
      if (!mounted) return;
      setState(() {
        _queue = queue;
        for (final report in queue.reports) {
          _reportAssignees[report.id] = report.assignedTo;
        }
        for (final sos in queue.sosRequests) {
          _sosAssignees[sos.id] = sos.assignedTo;
        }
        _loading = false;
      });
    } on AdminOperationsException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _loading = false;
      });
      if (error.statusCode == 401) {
        await AuthService.clearSession();
        if (mounted) {
          Navigator.of(context)
              .pushNamedAndRemoveUntil('/welcome', (_) => false);
        }
      }
    }
  }

  Future<void> _perform(Future<void> Function() action) async {
    if (_saving) return;
    setState(() => _saving = true);
    try {
      await action();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Perubahan berhasil disimpan.')),
      );
      await _loadQueue();
    } on AdminOperationsException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<String?> _requestNotes({
    required String title,
    required String hint,
  }) async {
    final controller = TextEditingController();
    final notes = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLength: 2000,
          maxLines: 3,
          decoration: InputDecoration(hintText: hint),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              final value = controller.text.trim();
              if (value.isEmpty) return;
              Navigator.pop(dialogContext, value);
            },
            child: const Text('Lanjutkan'),
          ),
        ],
      ),
    );
    controller.dispose();
    return notes;
  }

  Future<void> _rejectReport(AdminReport report) async {
    final notes = await _requestNotes(
      title: 'Alasan penolakan laporan',
      hint: 'Tuliskan alasan yang jelas',
    );
    if (notes == null || !mounted) return;
    await _perform(
      () =>
          _service.verifyReport(report.id, decision: 'rejected', notes: notes),
    );
  }

  Future<void> _rejectSos(AdminSosRequest sos) async {
    final notes = await _requestNotes(
      title: 'Alasan SOS tidak dapat ditindaklanjuti',
      hint: 'Tuliskan alasan yang jelas',
    );
    if (notes == null || !mounted) return;
    await _perform(() => _service.rejectSos(sos.id, notes));
  }

  Future<void> _logout() async {
    await AuthService.logout();
    if (mounted) {
      Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        title: const Text('Dashboard Admin'),
        actions: [
          IconButton(
            onPressed: _loadQueue,
            tooltip: 'Muat ulang antrean',
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            onPressed: () => Navigator.of(context).pushNamed('/profile'),
            tooltip: 'Profil',
            icon: const Icon(Icons.person_outline),
          ),
          IconButton(
            onPressed: _logout,
            tooltip: 'Keluar',
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _red))
          : _error != null
          ? _messageState()
          : _queueView(),
    );
  }

  Widget _messageState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.cloud_off_outlined,
              size: 44,
              color: Colors.black45,
            ),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _loadQueue,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _queueView() {
    final queue = _queue!;
    return RefreshIndicator(
      onRefresh: _loadQueue,
      color: _red,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              'Pantau laporan dan permintaan SOS. Penugasan dicatat di SIGAP BENCANA dan tidak otomatis mengirim bantuan ke instansi eksternal. Petugas aktif: ${queue.fieldOfficers.length}.',
              style: const TextStyle(height: 1.4),
            ),
          ),
          const SizedBox(height: 18),
          _sectionTitle('Laporan kejadian', queue.reports.length),
          if (queue.reports.isEmpty)
            _emptyCard('Belum ada laporan yang memerlukan tindakan.')
          else
            ...queue.reports.map(
              (report) => _reportCard(report, queue.fieldOfficers),
            ),
          const SizedBox(height: 12),
          _sectionTitle('Permintaan SOS', queue.sosRequests.length),
          if (queue.sosRequests.isEmpty)
            _emptyCard('Belum ada permintaan SOS aktif.')
          else
            ...queue.sosRequests.map(
              (sos) => _sosCard(sos, queue.fieldOfficers),
            ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title, int count) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        '$title · $count',
        style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _emptyCard(String message) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(message, textAlign: TextAlign.center),
    );
  }

  Widget _reportCard(AdminReport report, List<AdminFieldOfficer> officers) {
    final isPending = report.verificationStatus == 'pending';
    return _card(
      children: [
        Text(report.title, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 5),
        Text('${report.disasterType} · ${report.reporter ?? 'Pelapor'}'),
        if (report.address?.isNotEmpty == true)
          Text('Lokasi: ${report.address}'),
        const SizedBox(height: 6),
        Text(
          'Verifikasi: ${report.verificationStatus} · Penanganan: ${report.handlingStatus}',
        ),
        if (isPending) ...[
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: FilledButton(
                  onPressed: _saving
                      ? null
                      : () => _perform(
                          () => _service.verifyReport(
                            report.id,
                            decision: 'verified',
                          ),
                        ),
                  style: FilledButton.styleFrom(backgroundColor: _red),
                  child: const Text('Verifikasi'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: _saving ? null : () => _rejectReport(report),
                  child: const Text('Tolak'),
                ),
              ),
            ],
          ),
        ] else if (report.verificationStatus == 'verified') ...[
          const SizedBox(height: 10),
          _officerPicker(
            selectedId: _reportAssignees[report.id],
            officers: officers,
            onChanged: (value) =>
                setState(() => _reportAssignees[report.id] = value),
          ),
          const SizedBox(height: 8),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: _saving || _reportAssignees[report.id] == null
                  ? null
                  : () => _perform(
                      () => _service.assignReport(
                        report.id,
                        _reportAssignees[report.id]!,
                      ),
                    ),
              style: FilledButton.styleFrom(backgroundColor: _red),
              child: Text(
                report.assignedTo == null
                    ? 'Tugaskan petugas'
                    : 'Perbarui penugasan',
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _sosCard(AdminSosRequest sos, List<AdminFieldOfficer> officers) {
    final canAssign = sos.status == 'sent' || sos.status == 'assigned';
    return _card(
      children: [
        Text(
          sos.reference,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 5),
        Text('${sos.disasterType} · ${sos.requester ?? 'Pemohon'}'),
        if (sos.address?.isNotEmpty == true) Text('Lokasi: ${sos.address}'),
        const SizedBox(height: 6),
        Text(
          'Status: ${sos.status} · Petugas: ${sos.assignedOfficer ?? 'Belum ditugaskan'}',
        ),
        if (canAssign) ...[
          const SizedBox(height: 10),
          _officerPicker(
            selectedId: _sosAssignees[sos.id],
            officers: officers,
            onChanged: (value) => setState(() => _sosAssignees[sos.id] = value),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: FilledButton(
                  onPressed: _saving || _sosAssignees[sos.id] == null
                      ? null
                      : () => _perform(
                          () => _service.assignSos(
                            sos.id,
                            _sosAssignees[sos.id]!,
                          ),
                        ),
                  style: FilledButton.styleFrom(backgroundColor: _red),
                  child: const Text('Tugaskan'),
                ),
              ),
              if (sos.status == 'sent') ...[
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => _rejectSos(sos),
                    child: const Text('Tolak SOS'),
                  ),
                ),
              ],
            ],
          ),
        ],
      ],
    );
  }

  Widget _officerPicker({
    required int? selectedId,
    required List<AdminFieldOfficer> officers,
    required ValueChanged<int?> onChanged,
  }) {
    return DropdownButtonFormField<int>(
      initialValue: officers.any((officer) => officer.id == selectedId)
          ? selectedId
          : null,
      decoration: const InputDecoration(
        labelText: 'Petugas lapangan',
        border: OutlineInputBorder(),
        isDense: true,
      ),
      items: officers
          .map(
            (officer) => DropdownMenuItem<int>(
              value: officer.id,
              child: Text(officer.name, overflow: TextOverflow.ellipsis),
            ),
          )
          .toList(growable: false),
      onChanged: _saving || officers.isEmpty ? null : onChanged,
    );
  }

  Widget _card({required List<Widget> children}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFFD5DC)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: children,
      ),
    );
  }
}

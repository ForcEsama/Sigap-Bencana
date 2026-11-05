import 'package:flutter/material.dart';

import '../services/auth_service.dart';
import 'field_task_service.dart';

class FieldDashboardPage extends StatefulWidget {
  const FieldDashboardPage({this.offlineMode = false, super.key});

  final bool offlineMode;

  @override
  State<FieldDashboardPage> createState() => _FieldDashboardPageState();
}

class _FieldDashboardPageState extends State<FieldDashboardPage> {
  static const Color _pink = Color(0xFFFFEEF1);
  static const Color _red = Color(0xFFD50032);
  final FieldTaskService _service = FieldTaskService();
  FieldTasks? _tasks;
  String? _error;
  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    if (widget.offlineMode) {
      _isLoading = false;
      _error = 'Koneksi server belum tersedia. Tugas lapangan tidak ditampilkan dari cache agar statusnya tidak keliru.';
    } else {
      _loadTasks();
    }
  }

  Future<void> _loadTasks() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final tasks = await _service.fetchTasks();
      if (!mounted) return;
      setState(() {
        _tasks = tasks;
        _isLoading = false;
      });
    } on FieldTaskServiceException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
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

  Future<void> _updateTask(FieldTask task, {required bool isSos}) async {
    final status = _nextStatus(task, isSos: isSos);
    if (status == null || _isSaving) return;
    final notes = await _requestNotes(
      title: isSos ? 'Catatan progres SOS' : 'Catatan penanganan laporan',
      required: status == 'resolved' || status == 'completed',
    );
    if (notes == null || !mounted) return;

    setState(() => _isSaving = true);
    try {
      if (isSos) {
        await _service.updateSos(task.id, status: status, notes: notes);
      } else {
        await _service.updateReport(task.id, status: status, notes: notes);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Progres tugas berhasil diperbarui.')),
      );
      await _loadTasks();
    } on FieldTaskServiceException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  String? _nextStatus(FieldTask task, {required bool isSos}) {
    if (isSos) {
      return switch (task.status) {
        'assigned' => 'en_route',
        'en_route' => 'arrived',
        'arrived' => 'completed',
        _ => null,
      };
    }
    return switch (task.status) {
      'unhandled' => 'in_progress',
      'in_progress' => 'resolved',
      _ => null,
    };
  }

  String _actionLabel(String status) => switch (status) {
    'in_progress' => 'Mulai ditangani',
    'resolved' => 'Selesaikan laporan',
    'en_route' => 'Menuju lokasi',
    'arrived' => 'Tiba di lokasi',
    'completed' => 'Selesaikan tugas',
    _ => 'Perbarui status',
  };

  Future<String?> _requestNotes({
    required String title,
    required bool required,
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
          decoration: InputDecoration(
            hintText: required
                ? 'Catatan hasil wajib diisi'
                : 'Catatan opsional',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Batal'),
          ),
          TextButton(
            onPressed: () {
              final value = controller.text.trim();
              if (required && value.isEmpty) return;
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
        title: const Text('Tugas Lapangan'),
        actions: [
          IconButton(
            onPressed: _loadTasks,
            icon: const Icon(Icons.refresh),
            tooltip: 'Muat ulang',
          ),
          IconButton(
            onPressed: () => Navigator.of(context).pushNamed('/profile'),
            icon: const Icon(Icons.person_outline),
            tooltip: 'Profil',
          ),
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout),
            tooltip: 'Keluar',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _red))
          : _error != null
          ? _messageState()
          : _taskList(),
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
              size: 42,
              color: Colors.black45,
            ),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _loadTasks,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _taskList() {
    final tasks = _tasks!;
    final allTasks = [
      ...tasks.reports.map((task) => (task: task, isSos: false)),
      ...tasks.sosRequests.map((task) => (task: task, isSos: true)),
    ];
    final activeTasks = allTasks
        .where((entry) => _nextStatus(entry.task, isSos: entry.isSos) != null)
        .toList(growable: false);
    final completedTasks = allTasks
        .where((entry) => _nextStatus(entry.task, isSos: entry.isSos) == null)
        .toList(growable: false);

    return RefreshIndicator(
      onRefresh: _loadTasks,
      color: _red,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 28),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Text(
              'Hanya tugas yang ditugaskan kepada akun petugas ini yang ditampilkan. Perubahan progres tercatat di sistem dan tidak berarti bantuan telah dikirim ke instansi eksternal.',
              style: TextStyle(height: 1.4),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Tugas aktif · ${activeTasks.length}',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 10),
          if (activeTasks.isEmpty)
            Container(
              padding: const EdgeInsets.all(26),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Column(
                children: [
                  Icon(Icons.task_alt, size: 40, color: Colors.black45),
                  SizedBox(height: 8),
                  Text(
                    'Belum ada tugas aktif',
                    style: TextStyle(fontWeight: FontWeight.w600),
                  ),
                  Text('Tugas baru akan tampil setelah admin menugaskannya.'),
                ],
              ),
            )
          else
            ...activeTasks.map(
              (entry) => _taskCard(entry.task, isSos: entry.isSos),
            ),
          if (completedTasks.isNotEmpty) ...[
            const SizedBox(height: 14),
            Text(
              'Riwayat tugas · ${completedTasks.length}',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 10),
            ...completedTasks.map(
              (entry) => _taskCard(entry.task, isSos: entry.isSos),
            ),
          ],
        ],
      ),
    );
  }

  Widget _taskCard(FieldTask task, {required bool isSos}) {
    final next = _nextStatus(task, isSos: isSos);
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
            children: [
              Icon(isSos ? Icons.sos : Icons.report_outlined, color: _red),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  task.title,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
              Text(
                task.kind.toUpperCase(),
                style: const TextStyle(fontSize: 11, color: Colors.black54),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text('Status: ${task.status}'),
          if (task.reporter != null) Text('Pemohon: ${task.reporter}'),
          if (task.address.isNotEmpty) Text('Lokasi: ${task.address}'),
          if (task.latitude != null && task.longitude != null)
            Text(
              'Koordinat: ${task.latitude}, ${task.longitude}',
              style: const TextStyle(color: Colors.black54),
            ),
          if (task.description?.isNotEmpty == true) ...[
            const SizedBox(height: 6),
            Text(
              task.description!,
              maxLines: 4,
              overflow: TextOverflow.ellipsis,
            ),
          ],
          if (next != null) ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _isSaving
                    ? null
                    : () => _updateTask(task, isSos: isSos),
                style: FilledButton.styleFrom(backgroundColor: _red),
                child: Text(_actionLabel(next)),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

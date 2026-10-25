import 'dart:async';

import 'package:flutter/material.dart';

import 'sos_request.dart';
import 'sos_service.dart';

class SosStatusPage extends StatefulWidget {
  const SosStatusPage({
    required this.sosRequestId,
    this.initialRequest,
    super.key,
  });

  final int sosRequestId;
  final SosRequest? initialRequest;

  @override
  State<SosStatusPage> createState() => _SosStatusPageState();
}

class _SosStatusPageState extends State<SosStatusPage> {
  static const Color _pink = Color(0xFFFFECED);
  static const Color _green = Color(0xFF0BC58B);
  static const Color _amber = Color(0xFFFFB21A);
  static const Color _blue = Color(0xFF2563EB);
  static const Color _red = Color(0xFFD2002F);

  final SosService _sosService = SosService();
  SosRequest? _request;
  String? _error;
  bool _isLoading = true;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _request = widget.initialRequest;
    _isLoading = _request == null;
    _loadStatus();
    _refreshTimer = Timer.periodic(
      const Duration(seconds: 30),
      (_) => _loadStatus(silent: true),
    );
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadStatus({bool silent = false}) async {
    if (_isLoading && !silent) return;
    if (!silent && mounted) {
      setState(() {
        if (_request == null) _isLoading = true;
        _error = null;
      });
    }

    try {
      final request = await _sosService.fetchRequest(widget.sosRequestId);
      if (!mounted) return;
      setState(() {
        _request = request;
        _error = null;
        _isLoading = false;
      });
    } on SosSessionExpired {
      if (mounted) {
        Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
      }
    } on SosApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final request = _request;

    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        title: const Text('Status Bantuan SOS'),
        actions: [
          IconButton(
            onPressed: () => _loadStatus(),
            tooltip: 'Perbarui status',
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _isLoading && request == null
          ? const Center(child: CircularProgressIndicator(color: _red))
          : request == null
          ? _errorState()
          : RefreshIndicator(
              onRefresh: _loadStatus,
              color: _red,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(18, 12, 18, 26),
                children: [
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Text(
                        'Pembaruan status gagal: $_error',
                        style: const TextStyle(color: _red, fontSize: 12),
                      ),
                    ),
                  _statusHeader(request),
                  const SizedBox(height: 16),
                  _timeline(request),
                  const SizedBox(height: 14),
                  _locationCard(request),
                  if (request.status == 'arrived' ||
                      request.status == 'completed') ...[
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFD4F8DB),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text(
                        'Tetap waspada dan ikuti arahan petugas di lokasi.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: Color(0xFF147A39),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
    );
  }

  Widget _statusHeader(SosRequest request) {
    final color = switch (request.status) {
      'sent' => _amber,
      'assigned' => _amber,
      'en_route' => _blue,
      'arrived' || 'completed' => _green,
      'rejected' => _red,
      _ => _amber,
    };
    final message = switch (request.status) {
      'sent' =>
        'Server menyimpan permintaan. Saat ini menunggu tindak lanjut petugas.',
      'assigned' => 'Petugas sudah ditugaskan. Perkembangan berikutnya akan tampil di sini.',
      'en_route' => 'Petugas mengonfirmasi sedang menuju lokasi.',
      'arrived' => 'Petugas mengonfirmasi telah tiba di lokasi.',
      'completed' => 'Penanganan telah ditutup oleh petugas.',
      'rejected' => 'Permintaan tidak dapat ditindaklanjuti. Lihat catatan status di bawah.',
      _ => 'Status bantuan mengikuti pembaruan resmi dari petugas.',
    };

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(13),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 34,
            backgroundColor: color,
            child: const Text(
              'SOS',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  request.statusLabel,
                  style: TextStyle(
                    color: color,
                    fontSize: 17,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(request.reference),
                Text(_dateLabel(request.requestedAt)),
                const SizedBox(height: 5),
                Text(
                  message,
                  style: const TextStyle(fontSize: 12, height: 1.4),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _timeline(SosRequest request) {
    final currentIndex = switch (request.status) {
      'sent' => 1,
      'assigned' => 2,
      'en_route' => 3,
      'arrived' => 4,
      'completed' => 5,
      'rejected' => -1,
      _ => 1,
    };
    final eventsByStatus = {
      for (final event in request.events) event.status: event,
    };
    final steps = <_SosTimelineStep>[
      _SosTimelineStep('sent', 'Permintaan Dikirim'),
      _SosTimelineStep('waiting', 'Menunggu Konfirmasi Petugas'),
      _SosTimelineStep('assigned', 'Petugas Ditugaskan'),
      _SosTimelineStep('en_route', 'Petugas Menuju Lokasi'),
      _SosTimelineStep('arrived', 'Bantuan Tiba'),
      _SosTimelineStep('completed', 'Penanganan Selesai'),
    ];
    final rejectedEvent = eventsByStatus['rejected'];

    return Container(
      padding: const EdgeInsets.fromLTRB(17, 19, 17, 10),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFF999999)),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Perkembangan Bantuan',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 12),
          for (var index = 0; index < steps.length; index++)
            _timelineRow(
              step: steps[index],
              index: index,
              currentIndex: currentIndex,
              request: request,
              event: steps[index].key == 'waiting'
                  ? null
                  : eventsByStatus[steps[index].key],
              isLast: index == steps.length - 1 && rejectedEvent == null,
            ),
          if (rejectedEvent != null)
            _timelineRow(
              step: const _SosTimelineStep('rejected', 'Permintaan Ditolak'),
              index: steps.length,
              currentIndex: steps.length,
              request: request,
              event: rejectedEvent,
              isLast: true,
              failure: true,
            ),
        ],
      ),
    );
  }

  Widget _timelineRow({
    required _SosTimelineStep step,
    required int index,
    required int currentIndex,
    required SosRequest request,
    required SosEvent? event,
    required bool isLast,
    bool failure = false,
  }) {
    final complete = failure ? false : index < currentIndex;
    final active = failure || index == currentIndex;
    final color = failure
        ? _red
        : complete
        ? _green
        : active
        ? (index == 1 ? _amber : _blue)
        : Colors.grey.shade400;
    final timestamp = step.key == 'waiting'
        ? request.acknowledgedAt
        : event?.occurredAt ??
              (step.key == 'sent' ? request.requestedAt : null);
    final detail =
        event?.notes ??
        (step.key == 'waiting'
            ? active
                  ? 'Permintaan tersimpan dan menunggu respons petugas.'
                  : 'Respons petugas tercatat.'
            : active && !complete
            ? 'Sedang menunggu pembaruan berikutnya.'
            : 'Belum ada informasi.');

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            width: 42,
            child: Column(
              children: [
                Container(
                  width: 30,
                  height: 30,
                  decoration: BoxDecoration(
                    color: color,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    complete ? Icons.check : Icons.circle,
                    size: complete ? 20 : 12,
                    color: Colors.white,
                  ),
                ),
                if (!isLast) Expanded(child: Container(width: 3, color: color)),
              ],
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(8, 0, 0, 22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    step.label,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    timestamp == null ? detail : _dateLabel(timestamp),
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                  ),
                  if (event?.actor != null)
                    Text(
                      'Pembaruan oleh ${event!.actor}',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 11,
                      ),
                    ),
                  if (event?.notes?.isNotEmpty == true && step.key != 'sent')
                    Padding(
                      padding: const EdgeInsets.only(top: 3),
                      child: Text(
                        event!.notes!,
                        style: const TextStyle(fontSize: 12, height: 1.3),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _locationCard(SosRequest request) => Container(
    padding: const EdgeInsets.all(15),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          sosDisasterTypeLabel(request.disasterType),
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 5),
        Text(request.address ?? 'Alamat tidak dicantumkan.'),
      ],
    ),
  );

  Widget _errorState() => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.cloud_off_outlined, size: 46, color: Colors.black45),
          const SizedBox(height: 12),
          Text(_error ?? 'Status SOS belum dapat dimuat.'),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: _loadStatus,
            icon: const Icon(Icons.refresh),
            label: const Text('Coba lagi'),
          ),
        ],
      ),
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

class _SosTimelineStep {
  const _SosTimelineStep(this.key, this.label);

  final String key;
  final String label;
}

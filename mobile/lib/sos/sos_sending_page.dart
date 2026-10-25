import 'package:flutter/material.dart';

import 'sos_receipt_page.dart';
import 'sos_service.dart';

class SosSendingPage extends StatefulWidget {
  const SosSendingPage({
    required this.disasterType,
    required this.latitude,
    required this.longitude,
    required this.address,
    required this.clientRequestId,
    super.key,
  });

  final String disasterType;
  final double latitude;
  final double longitude;
  final String address;
  final String clientRequestId;

  @override
  State<SosSendingPage> createState() => _SosSendingPageState();
}

class _SosSendingPageState extends State<SosSendingPage> {
  static const Color _pink = Color(0xFFFFECED);
  static const Color _red = Color(0xFFFF0520);
  final SosService _sosService = SosService();
  bool _isSending = false;
  bool _isSavedOffline = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _send());
  }

  Future<void> _send() async {
    if (_isSending) return;
    setState(() {
      _isSending = true;
      _error = null;
    });

    try {
      final payload = {
        'client_request_id': widget.clientRequestId,
        'disaster_type': widget.disasterType,
        'latitude': widget.latitude,
        'longitude': widget.longitude,
        'address': widget.address,
      };
      await _sosService.enqueuePendingRequest(payload);
      final request = await _sosService.createRequest(payload);
      if (!mounted) return;
      await Navigator.of(context).pushReplacement<void, void>(
        MaterialPageRoute<void>(
          builder: (_) => SosReceiptPage(request: request),
        ),
      );
    } on SosSessionExpired {
      if (mounted) {
        Navigator.of(context).pushNamedAndRemoveUntil('/welcome', (_) => false);
      }
    } on SosQueueException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isSavedOffline = false;
      });
    } on SosApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isSavedOffline = error.statusCode == 0;
      });
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasError = _error != null;

    return PopScope(
      canPop: !_isSending,
      child: Scaffold(
        backgroundColor: _pink,
        body: SafeArea(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return SingleChildScrollView(
                child: ConstrainedBox(
                  constraints: BoxConstraints(minHeight: constraints.maxHeight),
                  child: Padding(
                    padding: const EdgeInsets.all(26),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          hasError
                              ? 'Permintaan Belum Terkonfirmasi'
                              : 'Mengirim Permintaan Bantuan...',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 23,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 35),
                        SizedBox(
                          width: 230,
                          height: 230,
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              _circle(230, const Color(0xFFFFDDE1)),
                              _circle(184, const Color(0xFFFFAAB5)),
                              _circle(144, const Color(0xFFFF6478)),
                              _circle(100, _red),
                              if (_isSending)
                                const SizedBox(
                                  width: 110,
                                  height: 110,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 4,
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white,
                                    ),
                                  ),
                                ),
                              const Text(
                                'SOS',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 25,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (hasError) ...[
                          const SizedBox(height: 26),
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(height: 1.4),
                          ),
                          const SizedBox(height: 14),
                          const Text(
                            'Jangan menganggap bantuan sudah diterima. Coba ulangi dengan permintaan yang sama.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.black54,
                            ),
                          ),
                          const SizedBox(height: 16),
                          if (_isSavedOffline) ...[
                            const Text(
                              'Permintaan tersimpan aman di perangkat, tetapi BELUM terkirim ke server atau diterima petugas. Saat koneksi tersedia, buka Riwayat Laporan lalu kirim ulang.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF970019),
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 16),
                          ],
                          FilledButton.icon(
                            onPressed: _send,
                            icon: const Icon(Icons.refresh),
                            label: const Text('Coba Kirim Ulang'),
                            style: FilledButton.styleFrom(
                              backgroundColor: _red,
                            ),
                          ),
                          TextButton(
                            onPressed: () =>
                                Navigator.of(context)
                                    .popUntil((route) => route.isFirst),
                            child: const Text('Kembali ke Beranda'),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _circle(double diameter, Color color) => Container(
    width: diameter,
    height: diameter,
    decoration: BoxDecoration(color: color, shape: BoxShape.circle),
  );
}

import 'package:flutter/material.dart';

import 'disaster_warning.dart';
import 'warning_service.dart';

class WarningDetailPage extends StatefulWidget {
  const WarningDetailPage({
    super.key,
    required this.warningId,
    this.initialWarning,
    this.service,
  });

  final int warningId;
  final DisasterWarning? initialWarning;
  final WarningService? service;

  @override
  State<WarningDetailPage> createState() => _WarningDetailPageState();
}

class _WarningDetailPageState extends State<WarningDetailPage> {
  late final Future<DisasterWarning> _warning = _loadWarning();

  Future<DisasterWarning> _loadWarning() {
    final cached = widget.initialWarning;
    if (cached != null) {
      if (cached.expiresAt.isAfter(DateTime.now())) return Future.value(cached);
      return Future.error(
        const WarningApiException('Masa berlaku peringatan telah berakhir.'),
      );
    }
    return (widget.service ?? WarningService()).fetchWarning(widget.warningId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFEEF1),
      appBar: AppBar(
        backgroundColor: const Color(0xFFFFEEF1),
        title: const Text('Detail Peringatan'),
      ),
      body: FutureBuilder<DisasterWarning>(
        future: _warning,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text('Peringatan tidak dapat dimuat: ${snapshot.error}'),
              ),
            );
          }

          final warning = snapshot.requireData;
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                warning.title,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 16),
              _detail('Wilayah', warning.affectedArea),
              if (warning.urgency != null) _detail('Urgensi', warning.urgency!),
              _detail('Severity dari sumber', warning.severity),
              _detail('Diterbitkan', warning.formattedPublishedAt),
              if (warning.startsAt != null)
                _detail('Mulai berlaku', warning.formattedStartsAt),
              _detail('Berlaku sampai', warning.formattedExpiresAt),
              _detail('Sumber', warning.source),
              _detail('Tautan sumber', warning.sourceUrl),
              const SizedBox(height: 12),
              Text(
                warning.description,
                style: const TextStyle(fontSize: 15, height: 1.5),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _detail(String label, String value) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(color: Colors.black54, fontSize: 12),
        ),
        const SizedBox(height: 3),
        SelectableText(value, style: const TextStyle(fontSize: 14)),
      ],
    ),
  );
}

import 'package:flutter/material.dart';

import 'report_formatters.dart';
import 'report_service.dart';

class ReportFormPage extends StatefulWidget {
  const ReportFormPage({super.key});

  @override
  State<ReportFormPage> createState() => _ReportFormPageState();
}

class _ReportFormPageState extends State<ReportFormPage> {
  static const Color red = Color(0xFFD2002F);
  static const Color blush = Color(0xFFFFECED);

  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _latitudeController = TextEditingController();
  final TextEditingController _longitudeController = TextEditingController();
  final ReportService _reportService = ReportService();

  String? _disasterType;
  DateTime? _occurredAt;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _addressController.dispose();
    _descriptionController.dispose();
    _latitudeController.dispose();
    _longitudeController.dispose();
    super.dispose();
  }

  String? _coordinateValidator(String? value, {required bool latitude}) {
    final parsed = double.tryParse((value ?? '').trim().replaceAll(',', '.'));
    final min = latitude ? -90.0 : -180.0;
    final max = latitude ? 90.0 : 180.0;

    if (parsed == null) return 'Koordinat wajib berupa angka.';
    if (parsed < min || parsed > max) {
      return latitude
          ? 'Latitude harus berada antara -90 dan 90.'
          : 'Longitude harus berada antara -180 dan 180.';
    }

    return null;
  }

  Future<void> _chooseOccurredAt() async {
    final now = DateTime.now();
    final selectedDate = await showDatePicker(
      context: context,
      initialDate: _occurredAt ?? now,
      firstDate: DateTime(2000),
      lastDate: now,
      locale: const Locale('id'),
    );
    if (selectedDate == null || !mounted) return;

    final selectedTime = await showTimePicker(
      context: context,
      initialTime: _occurredAt == null
          ? TimeOfDay.fromDateTime(now)
          : TimeOfDay.fromDateTime(_occurredAt!),
    );
    if (selectedTime == null || !mounted) return;

    setState(() {
      _occurredAt = DateTime(
        selectedDate.year,
        selectedDate.month,
        selectedDate.day,
        selectedTime.hour,
        selectedTime.minute,
      );
    });
  }

  String _titleDate(DateTime date) {
    const months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ];
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');

    return '${date.day} ${months[date.month - 1]} ${date.year} $hour:$minute';
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    if (_disasterType == null) {
      _showMessage('Pilih jenis bencana.');
      return;
    }

    if (_occurredAt?.isAfter(DateTime.now()) == true) {
      _showMessage('Waktu kejadian tidak boleh berada di masa depan.');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final reportTime = _occurredAt ?? DateTime.now();
      final report = await _reportService.createReport({
        'title':
            'Laporan ${reportTypeLabel(_disasterType)} - ${_titleDate(reportTime)}',
        'disaster_type': _disasterType,
        'description': _descriptionController.text.trim(),
        'address': _addressController.text.trim(),
        'latitude': double.parse(
          _latitudeController.text.trim().replaceAll(',', '.'),
        ),
        'longitude': double.parse(
          _longitudeController.text.trim().replaceAll(',', '.'),
        ),
        'occurred_at': _occurredAt?.toUtc().toIso8601String(),
      });

      if (!mounted) return;

      if (report['id'] == null) {
        _showMessage('Laporan dikirim, tetapi nomor laporan tidak diterima.');
        return;
      }

      Navigator.of(context).pop(true);
    } on ReportApiException catch (exception) {
      if (!mounted) return;

      if (exception.statusCode == 401) {
        Navigator.of(context)
            .pushNamedAndRemoveUntil('/welcome', (route) => false);
      } else {
        _showMessage(exception.message);
      }
    } catch (_) {
      if (mounted) {
        _showMessage('Laporan belum dapat dikirim. Coba lagi nanti.');
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message), backgroundColor: red));
  }

  Widget _textField({
    required String label,
    required TextEditingController controller,
    required String hint,
    int maxLines = 1,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 7),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          keyboardType: keyboardType,
          validator: validator,
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(color: Color(0xFF999999)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: const BorderSide(color: red),
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
        title: const Text('Lapor Kejadian'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(18, 8, 18, 20),
          children: [
            const Text(
              'Bantu sebarkan informasi untuk penanganan lebih cepat.',
              style: TextStyle(fontSize: 14, height: 1.4),
            ),
            const SizedBox(height: 22),
            const Text(
              'Jenis Bencana',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 7),
            DropdownButtonFormField<String>(
              initialValue: _disasterType,
              decoration: InputDecoration(
                hintText: 'Pilih jenis bencana',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              items: disasterTypeLabels.entries
                  .map(
                    (entry) => DropdownMenuItem<String>(
                      value: entry.key,
                      child: Text(entry.value),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _disasterType = value),
            ),
            const SizedBox(height: 18),
            _textField(
              label: 'Lokasi Kejadian',
              controller: _addressController,
              hint: 'Alamat atau keterangan lokasi',
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Lokasi kejadian wajib diisi.'
                  : null,
            ),
            const SizedBox(height: 12),
            const Text(
              'Koordinat lokasi diperlukan agar laporan dapat dipetakan.',
              style: TextStyle(fontSize: 11, color: Colors.black54),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: _textField(
                    label: 'Latitude',
                    controller: _latitudeController,
                    hint: 'Contoh: -6.2000000',
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                      signed: true,
                    ),
                    validator: (value) =>
                        _coordinateValidator(value, latitude: true),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _textField(
                    label: 'Longitude',
                    controller: _longitudeController,
                    hint: 'Contoh: 106.8166660',
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                      signed: true,
                    ),
                    validator: (value) =>
                        _coordinateValidator(value, latitude: false),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            _textField(
              label: 'Deskripsi Kejadian',
              controller: _descriptionController,
              hint: 'Jelaskan kejadian yang terjadi...',
              maxLines: 5,
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Deskripsi kejadian wajib diisi.'
                  : null,
            ),
            const SizedBox(height: 18),
            const Text(
              'Waktu Kejadian (Opsional)',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 7),
            OutlinedButton.icon(
              onPressed: _chooseOccurredAt,
              icon: const Icon(Icons.schedule),
              label: Text(
                _occurredAt == null
                    ? 'Pilih tanggal dan waktu'
                    : reportDateLabel(_occurredAt!.toIso8601String()),
              ),
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.black87,
                backgroundColor: Colors.white,
                minimumSize: const Size.fromHeight(48),
                side: const BorderSide(color: Color(0xFF999999)),
                alignment: Alignment.centerLeft,
              ),
            ),
            if (_occurredAt != null)
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () => setState(() => _occurredAt = null),
                  child: const Text('Hapus waktu'),
                ),
              ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.65),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFE7C8CD)),
              ),
              child: const Text(
                'Lampiran foto/video belum didukung oleh endpoint laporan mobile. Laporan akan dikirim tanpa lampiran.',
                style: TextStyle(fontSize: 11, height: 1.4),
              ),
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: _isSubmitting ? null : _submit,
              style: FilledButton.styleFrom(
                backgroundColor: red,
                minimumSize: const Size.fromHeight(50),
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text('Kirim Laporan'),
            ),
          ],
        ),
      ),
    );
  }
}

import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../profile/location_service.dart';
import 'sos_request.dart';
import 'sos_sending_page.dart';

class SosLocationPage extends StatefulWidget {
  const SosLocationPage({required this.disasterType, super.key});

  final String disasterType;

  @override
  State<SosLocationPage> createState() => _SosLocationPageState();
}

class _SosLocationPageState extends State<SosLocationPage> {
  static const Color _red = Color(0xFFD2002F);
  static const Color _pink = Color(0xFFFFECED);
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _latitudeController = TextEditingController();
  final TextEditingController _longitudeController = TextEditingController();
  late final String _clientRequestId;

  CurrentLocation? _location;
  String? _locationError;
  bool _isRefreshing = false;

  @override
  void initState() {
    super.initState();
    _clientRequestId = createSosClientRequestId();
    _refreshLocation();
  }

  @override
  void dispose() {
    _addressController.dispose();
    _latitudeController.dispose();
    _longitudeController.dispose();
    super.dispose();
  }

  Future<void> _refreshLocation() async {
    setState(() {
      _isRefreshing = true;
      _locationError = null;
    });

    try {
      final location = await LocationService().getCurrentLocation();
      if (!mounted) return;
      setState(() {
        _location = location;
        _addressController.text = location.address ?? '';
        _latitudeController.text = location.latitude.toStringAsFixed(7);
        _longitudeController.text = location.longitude.toStringAsFixed(7);
        _isRefreshing = false;
      });
    } on LocationServiceException catch (error) {
      if (!mounted) return;
      setState(() {
        _location = null;
        _locationError = error.message;
        _isRefreshing = false;
      });
    }
  }

  Future<void> _sendRequest() async {
    if (!_formKey.currentState!.validate()) return;

    final latitude =
        _location?.latitude ?? double.tryParse(_latitudeController.text.trim());
    final longitude =
        _location?.longitude ??
        double.tryParse(_longitudeController.text.trim());
    if (latitude == null || longitude == null) return;

    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => SosSendingPage(
          disasterType: widget.disasterType,
          latitude: latitude,
          longitude: longitude,
          address: _addressController.text.trim(),
          clientRequestId: _clientRequestId,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final location = _location;

    return Scaffold(
      backgroundColor: _pink,
      appBar: AppBar(
        backgroundColor: _pink,
        leading: IconButton(
          onPressed: () => Navigator.of(context).pop(),
          icon: const Icon(Icons.arrow_back),
          tooltip: 'Kembali',
        ),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(24, 0, 24, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Konfirmasi Lokasi',
                      style: TextStyle(
                        fontSize: 25,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      'Pastikan lokasi Anda sesuai sebelum mengirim bantuan.',
                      style: TextStyle(fontSize: 16, height: 1.4),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          border: Border.all(color: const Color(0xFF999999)),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Column(
                          children: [
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 14,
                                vertical: 12,
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.warning_amber_rounded),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      sosDisasterTypeLabel(widget.disasterType),
                                      style: const TextStyle(
                                        fontSize: 17,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                  TextButton(
                                    onPressed: () =>
                                        Navigator.of(context).pop(),
                                    child: const Text('Ubah jenis'),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(
                              height: 230,
                              child: location == null
                                  ? const ColoredBox(
                                      color: Color(0xFFE8E7E5),
                                      child: Center(
                                        child: Icon(
                                          Icons.location_searching,
                                          size: 42,
                                          color: _red,
                                        ),
                                      ),
                                    )
                                  : Platform.isAndroid
                                  ? AndroidView(
                                      key: ValueKey(
                                        '${location.latitude},${location.longitude},${location.capturedAt.microsecondsSinceEpoch}',
                                      ),
                                      viewType: 'sigap/sos-map',
                                      layoutDirection: TextDirection.ltr,
                                      creationParams: {
                                        'latitude': location.latitude,
                                        'longitude': location.longitude,
                                        'accuracy': location.accuracyMeters,
                                      },
                                      creationParamsCodec:
                                          const StandardMessageCodec(),
                                    )
                                  : const ColoredBox(
                                      color: Color(0xFFE8E7E5),
                                      child: Center(
                                        child: Text(
                                          'Peta titik SOS tersedia pada Android.',
                                        ),
                                      ),
                                    ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(14),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  TextButton.icon(
                                    onPressed: _isRefreshing
                                        ? null
                                        : _refreshLocation,
                                    icon: _isRefreshing
                                        ? const SizedBox(
                                            width: 18,
                                            height: 18,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                            ),
                                          )
                                        : const Icon(Icons.refresh),
                                    label: const Text('Perbarui lokasi'),
                                  ),
                                  if (_locationError != null) ...[
                                    Text(
                                      'GPS belum tersedia: $_locationError',
                                      style: const TextStyle(
                                        color: Color(0xFF9D2434),
                                        fontSize: 12,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    const Text(
                                      'Masukkan alamat dan koordinat secara manual untuk melanjutkan.',
                                      style: TextStyle(
                                        color: Colors.black54,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ],
                                  const SizedBox(height: 8),
                                  TextFormField(
                                    controller: _addressController,
                                    minLines: 1,
                                    maxLines: 3,
                                    decoration: const InputDecoration(
                                      labelText: 'Lokasi Anda Saat Ini',
                                      border: OutlineInputBorder(),
                                      prefixIcon: Icon(Icons.location_on),
                                    ),
                                    validator: (value) =>
                                        value == null || value.trim().isEmpty
                                        ? 'Alamat wajib diisi.'
                                        : null,
                                  ),
                                  if (location == null) ...[
                                    const SizedBox(height: 10),
                                    Row(
                                      children: [
                                        Expanded(
                                          child: _coordinateField(
                                            _latitudeController,
                                            'Latitude',
                                            -90,
                                            90,
                                          ),
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: _coordinateField(
                                            _longitudeController,
                                            'Longitude',
                                            -180,
                                            180,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ] else ...[
                                    const SizedBox(height: 8),
                                    Text(
                                      'Koordinat ${location.formattedCoordinates} · akurasi ±${location.accuracyMeters.round()} m',
                                      style: const TextStyle(
                                        color: Colors.black54,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                child: FilledButton(
                  onPressed: _isRefreshing ? null : _sendRequest,
                  style: FilledButton.styleFrom(
                    backgroundColor: _red,
                    minimumSize: const Size.fromHeight(54),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: const Text(
                    'Kirim Permintaan Bantuan',
                    style: TextStyle(fontSize: 17),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _coordinateField(
    TextEditingController controller,
    String label,
    double min,
    double max,
  ) => TextFormField(
    controller: controller,
    keyboardType: const TextInputType.numberWithOptions(
      decimal: true,
      signed: true,
    ),
    decoration: InputDecoration(
      labelText: label,
      border: const OutlineInputBorder(),
    ),
    validator: (value) {
      final coordinate = double.tryParse(value?.trim() ?? '');
      if (coordinate == null || coordinate < min || coordinate > max) {
        return 'Koordinat tidak valid';
      }
      return null;
    },
  );
}

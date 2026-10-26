import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';

class FieldTask {
  const FieldTask({
    required this.id,
    required this.title,
    required this.kind,
    required this.status,
    required this.address,
    required this.latitude,
    required this.longitude,
    this.description,
    this.reporter,
  });

  final int id;
  final String title;
  final String kind;
  final String status;
  final String address;
  final double? latitude;
  final double? longitude;
  final String? description;
  final String? reporter;

  factory FieldTask.fromJson(Map<String, dynamic> json, {required bool isSos}) {
    return FieldTask(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      title: isSos
          ? json['reference']?.toString() ?? 'Permintaan SOS'
          : json['title']?.toString() ?? 'Laporan kejadian',
      kind: json['disaster_type']?.toString() ?? '',
      status:
          (isSos ? json['status'] : json['handling_status'])?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      latitude: _double(json['latitude']),
      longitude: _double(json['longitude']),
      description: json['description']?.toString(),
      reporter:
          (json['reporter'] as Map<String, dynamic>?)?['name']?.toString() ??
          (json['requester'] as Map<String, dynamic>?)?['name']?.toString(),
    );
  }

  static double? _double(Object? value) => value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '');
}

class FieldTasks {
  const FieldTasks({required this.reports, required this.sosRequests});

  final List<FieldTask> reports;
  final List<FieldTask> sosRequests;
}

class FieldTaskServiceException implements Exception {
  const FieldTaskServiceException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;
}

class FieldTaskService {
  Future<FieldTasks> fetchTasks() async {
    final response = await _request('GET', ApiConfig.fieldTasks);
    final decoded = _decode(response);
    final data = decoded['data'];
    if (data is! Map<String, dynamic> ||
        data['reports'] is! List ||
        data['sos'] is! List) {
      throw const FieldTaskServiceException(
        'Data tugas dari server tidak valid.',
      );
    }

    return FieldTasks(
      reports: (data['reports'] as List)
          .whereType<Map<String, dynamic>>()
          .map((item) => FieldTask.fromJson(item, isSos: false))
          .toList(growable: false),
      sosRequests: (data['sos'] as List)
          .whereType<Map<String, dynamic>>()
          .map((item) => FieldTask.fromJson(item, isSos: true))
          .toList(growable: false),
    );
  }

  Future<void> updateReport(
    int id, {
    required String status,
    required String notes,
  }) async {
    final response = await _request(
      'PATCH',
      ApiConfig.reportHandling(id),
      body: {'status': status, 'notes': notes},
    );
    _decode(response);
  }

  Future<void> updateSos(
    int id, {
    required String status,
    required String notes,
  }) async {
    final response = await _request(
      'PATCH',
      ApiConfig.sosOperationStatus(id),
      body: {'status': status, 'notes': notes},
    );
    _decode(response);
  }

  Future<http.Response> _request(
    String method,
    String url, {
    Map<String, String>? body,
  }) async {
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw const FieldTaskServiceException(
        'Sesi masuk tidak ditemukan. Silakan masuk kembali.',
        statusCode: 401,
      );
    }

    try {
      final headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };
      final response = method == 'GET'
          ? await http
                .get(Uri.parse(url), headers: headers)
                .timeout(const Duration(seconds: 20))
          : await http
                .patch(
                  Uri.parse(url),
                  headers: {...headers, 'Content-Type': 'application/json'},
                  body: jsonEncode(body),
                )
                .timeout(const Duration(seconds: 20));
      return response;
    } on Exception {
      throw const FieldTaskServiceException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }

  Map<String, dynamic> _decode(http.Response response) {
    if (response.statusCode == 401) {
      throw const FieldTaskServiceException(
        'Sesi masuk telah berakhir. Silakan masuk kembali.',
        statusCode: 401,
      );
    }
    if (response.statusCode == 403) {
      throw const FieldTaskServiceException(
        'Akses ini hanya tersedia bagi petugas lapangan yang ditugaskan.',
        statusCode: 403,
      );
    }
    if (response.statusCode < 200 || response.statusCode >= 300) {
      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } on FormatException {
        decoded = null;
      }
      final message = decoded is Map<String, dynamic>
          ? decoded['message']?.toString()
          : null;
      throw FieldTaskServiceException(
        message ?? 'Permintaan tugas gagal (HTTP ${response.statusCode}).',
        statusCode: response.statusCode,
      );
    }

    dynamic decoded;
    try {
      decoded = jsonDecode(response.body);
    } on FormatException {
      throw const FieldTaskServiceException('Respons server tidak valid.');
    }
    if (decoded is! Map<String, dynamic>) {
      throw const FieldTaskServiceException('Respons server tidak valid.');
    }
    return decoded;
  }
}

import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../services/auth_service.dart';

class AdminFieldOfficer {
  const AdminFieldOfficer({required this.id, required this.name});

  final int id;
  final String name;

  factory AdminFieldOfficer.fromJson(Map<String, dynamic> json) {
    return AdminFieldOfficer(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? 'Petugas lapangan',
    );
  }
}

class AdminReport {
  const AdminReport({
    required this.id,
    required this.title,
    required this.disasterType,
    required this.verificationStatus,
    required this.handlingStatus,
    this.address,
    this.reporter,
    this.assignedTo,
    this.assignedOfficer,
  });

  final int id;
  final String title;
  final String disasterType;
  final String verificationStatus;
  final String handlingStatus;
  final String? address;
  final String? reporter;
  final int? assignedTo;
  final String? assignedOfficer;

  factory AdminReport.fromJson(Map<String, dynamic> json) {
    final reporter = json['reporter'];
    final assignedOfficer = json['assigned_officer'];
    return AdminReport(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      title: json['title']?.toString() ?? 'Laporan kejadian',
      disasterType: json['disaster_type']?.toString() ?? '',
      verificationStatus: json['verification_status']?.toString() ?? '',
      handlingStatus: json['handling_status']?.toString() ?? '',
      address: json['address']?.toString(),
      reporter: reporter is Map<String, dynamic>
          ? reporter['name']?.toString()
          : null,
      assignedTo: int.tryParse(json['assigned_to']?.toString() ?? ''),
      assignedOfficer: assignedOfficer is Map<String, dynamic>
          ? assignedOfficer['name']?.toString()
          : null,
    );
  }
}

class AdminSosRequest {
  const AdminSosRequest({
    required this.id,
    required this.reference,
    required this.disasterType,
    required this.status,
    this.address,
    this.requester,
    this.assignedTo,
    this.assignedOfficer,
  });

  final int id;
  final String reference;
  final String disasterType;
  final String status;
  final String? address;
  final String? requester;
  final int? assignedTo;
  final String? assignedOfficer;

  factory AdminSosRequest.fromJson(Map<String, dynamic> json) {
    final requester = json['requester'];
    final assignedOfficer = json['assigned_officer'];
    return AdminSosRequest(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      reference: json['reference']?.toString() ?? 'Permintaan SOS',
      disasterType: json['disaster_type']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      address: json['address']?.toString(),
      requester: requester is Map<String, dynamic>
          ? requester['name']?.toString()
          : null,
      assignedTo: int.tryParse(json['assigned_to']?.toString() ?? ''),
      assignedOfficer: assignedOfficer is Map<String, dynamic>
          ? assignedOfficer['name']?.toString()
          : null,
    );
  }
}

class AdminQueue {
  const AdminQueue({
    required this.reports,
    required this.sosRequests,
    required this.fieldOfficers,
  });

  final List<AdminReport> reports;
  final List<AdminSosRequest> sosRequests;
  final List<AdminFieldOfficer> fieldOfficers;
}

class AdminOperationsException implements Exception {
  const AdminOperationsException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;
}

class AdminOperationsService {
  Future<AdminQueue> fetchQueue() async {
    final decoded = await _request('GET', ApiConfig.adminQueue);
    final data = decoded['data'];
    if (data is! Map<String, dynamic> ||
        data['reports'] is! List ||
        data['sos'] is! List ||
        data['field_officers'] is! List) {
      throw const AdminOperationsException(
        'Data antrean operasional dari server tidak valid.',
      );
    }

    return AdminQueue(
      reports: (data['reports'] as List)
          .whereType<Map<String, dynamic>>()
          .map(AdminReport.fromJson)
          .toList(growable: false),
      sosRequests: (data['sos'] as List)
          .whereType<Map<String, dynamic>>()
          .map(AdminSosRequest.fromJson)
          .toList(growable: false),
      fieldOfficers: (data['field_officers'] as List)
          .whereType<Map<String, dynamic>>()
          .map(AdminFieldOfficer.fromJson)
          .toList(growable: false),
    );
  }

  Future<void> verifyReport(
    int reportId, {
    required String decision,
    String? notes,
  }) async {
    await _request(
      'PATCH',
      ApiConfig.reportVerification(reportId),
      body: {'decision': decision, 'notes': notes},
    );
  }

  Future<void> assignReport(int reportId, int officerId) async {
    await _request(
      'PATCH',
      ApiConfig.reportAssignment(reportId),
      body: {'assigned_to': officerId},
    );
  }

  Future<void> assignSos(int sosId, int officerId) async {
    await _request(
      'PATCH',
      ApiConfig.sosOperationStatus(sosId),
      body: {'status': 'assigned', 'assigned_to': officerId},
    );
  }

  Future<void> rejectSos(int sosId, String notes) async {
    await _request(
      'PATCH',
      ApiConfig.sosOperationStatus(sosId),
      body: {'status': 'rejected', 'notes': notes},
    );
  }

  Future<Map<String, dynamic>> _request(
    String method,
    String url, {
    Map<String, Object?>? body,
  }) async {
    final token = await AuthService.getToken();
    if (token == null || token.isEmpty) {
      throw const AdminOperationsException(
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

      if (response.statusCode == 401) {
        throw const AdminOperationsException(
          'Sesi masuk telah berakhir. Silakan masuk kembali.',
          statusCode: 401,
        );
      }
      if (response.statusCode == 403) {
        throw const AdminOperationsException(
          'Akun ini tidak memiliki akses admin.',
          statusCode: 403,
        );
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } on FormatException {
        decoded = null;
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        final message = decoded is Map<String, dynamic>
            ? decoded['message']?.toString()
            : null;
        throw AdminOperationsException(
          message ?? 'Permintaan admin gagal (HTTP ${response.statusCode}).',
          statusCode: response.statusCode,
        );
      }
      if (decoded is! Map<String, dynamic>) {
        throw const AdminOperationsException('Respons server tidak valid.');
      }
      return decoded;
    } on AdminOperationsException {
      rethrow;
    } on Exception {
      throw const AdminOperationsException(
        'Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
      );
    }
  }
}

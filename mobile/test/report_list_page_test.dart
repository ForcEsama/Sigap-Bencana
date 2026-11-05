import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/reports/report_list_page.dart';
import 'package:mobile/reports/report_service.dart';
import 'package:mobile/sos/sos_request.dart';
import 'package:mobile/sos/sos_service.dart';

void main() {
  testWidgets('shows a normal empty state when there are no SOS requests', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: ReportListPage(
          reportService: _ReportWithOneReportService(),
          sosService: _EmptySosHistoryService(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Laporan Banjir'), findsOneWidget);
    expect(find.text('Permintaan SOS'), findsOneWidget);
    expect(find.text('Belum ada permintaan SOS yang dikirim.'), findsOneWidget);
    expect(find.textContaining('TypeError'), findsNothing);
  });

  testWidgets('shows SOS history even when disaster report loading fails', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: ReportListPage(
          reportService: _FailingReportService(),
          sosService: _SosHistoryService(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Permintaan SOS'), findsOneWidget);
    expect(find.text('SOS-000042'), findsOneWidget);
    expect(find.text('Gempa Bumi'), findsOneWidget);
    expect(find.text('Laporan belum dapat dimuat.'), findsOneWidget);
  });
}

class _ReportWithOneReportService extends ReportService {
  @override
  Future<ReportPage> fetchReports({int page = 1}) async {
    return const ReportPage(
      reports: [
        {
          'id': 1,
          'title': 'Laporan Banjir',
          'disaster_type': 'banjir',
          'address': 'Alamat uji',
          'verification_status': 'pending',
          'handling_status': 'unhandled',
          'created_at': '2026-10-26T00:00:00+07:00',
        },
      ],
      currentPage: 1,
      lastPage: 1,
    );
  }
}

class _EmptySosHistoryService extends SosService {
  @override
  Future<List<Map<String, dynamic>>> fetchPendingRequests() async => [];

  @override
  Future<SosRequestPage> fetchRequests({int page = 1}) async {
    return SosRequestPage(requests: const [], currentPage: 1, lastPage: 1);
  }
}

class _FailingReportService extends ReportService {
  @override
  Future<ReportPage> fetchReports({int page = 1}) async {
    throw const ReportApiException(500, 'Laporan belum dapat dimuat.');
  }
}

class _SosHistoryService extends SosService {
  @override
  Future<List<Map<String, dynamic>>> fetchPendingRequests() async => [];

  @override
  Future<SosRequestPage> fetchRequests({int page = 1}) async {
    return SosRequestPage(
      requests: [
        SosRequest(
          id: 42,
          reference: 'SOS-000042',
          disasterType: 'gempa',
          latitude: -0.95,
          longitude: 100.35,
          status: 'sent',
          requestedAt: DateTime(2026, 10, 26),
          address: 'Kota Padang',
          events: const [],
        ),
      ],
      currentPage: 1,
      lastPage: 1,
    );
  }
}

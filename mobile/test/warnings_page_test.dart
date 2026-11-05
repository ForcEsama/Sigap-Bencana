import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/warnings/disaster_warning.dart';
import 'package:mobile/warnings/warning_service.dart';
import 'package:mobile/warnings/warnings_page.dart';

void main() {
  testWidgets(
    'keeps active warnings visible and prioritizes the saved location',
    (tester) async {
      final service = _FakeWarningService([
        _warning(id: 1, area: 'Jawa Barat'),
        _warning(id: 2, area: 'Sumatera Barat'),
      ]);

      await tester.pumpWidget(
        MaterialApp(
          home: WarningsPage(
            service: service,
            locationAddress: 'Padang, Sumatera Barat, Indonesia',
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Peringatan Sumatera Barat'), findsOneWidget);
      expect(find.text('Peringatan Jawa Barat'), findsOneWidget);
      expect(
        tester.getTopLeft(find.text('Peringatan Sumatera Barat')).dy,
        lessThan(tester.getTopLeft(find.text('Peringatan Jawa Barat')).dy),
      );
    },
  );
}

DisasterWarning _warning({required int id, required String area}) {
  return DisasterWarning(
    id: id,
    title: 'Peringatan $area',
    disasterType: 'Hujan Lebat dan Petir',
    description: 'Peringatan resmi BMKG.',
    severity: 'Moderate',
    urgency: 'Immediate',
    affectedArea: area,
    source: 'BMKG',
    sourceUrl: 'https://www.bmkg.go.id/alerts/nowcast/id/test_alert.xml',
    status: 'published',
    publishedAt: DateTime.now(),
    startsAt: DateTime.now().subtract(const Duration(minutes: 1)),
    expiresAt: DateTime.now().add(const Duration(hours: 1)),
  );
}

class _FakeWarningService extends WarningService {
  _FakeWarningService(this.warnings);

  final List<DisasterWarning> warnings;

  @override
  Future<List<DisasterWarning>> fetchActiveWarnings() async => warnings;
}

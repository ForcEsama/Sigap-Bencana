import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/warnings/warning_service.dart';

void main() {
  test(
    'loads only the warning API contract and parses official metadata',
    () async {
      late Uri requestedUri;
      final client = MockClient((request) async {
        requestedUri = request.url;
        return http.Response(
          jsonEncode({
            'data': [
              {
                'id': 7,
                'title': 'Hujan Lebat Kepulauan Riau',
                'disaster_type': 'cuaca',
                'description': 'Peringatan resmi BMKG.',
                'severity': 'Severe',
                'urgency': 'Immediate',
                'affected_area': 'Tanjungpinang; Bintan',
                'source': 'BMKG - Peringatan Dini Cuaca',
                'source_url':
                    'https://www.bmkg.go.id/alerts/nowcast/id/test_alert.xml',
                'published_at': '2026-10-25T00:53:00+07:00',
                'starts_at': '2026-10-25T00:53:00+07:00',
                'expires_at': '2026-10-25T02:53:00+07:00',
                'status': 'published',
                'updated_at': '2026-10-25T00:55:00+07:00',
              },
            ],
          }),
          200,
          headers: {'content-type': 'application/json'},
        );
      });
      addTearDown(client.close);

      final warnings = await WarningService(client: client)
          .fetchActiveWarnings();

      expect(requestedUri.path, endsWith('/api/v1/warnings'));
      expect(warnings.single.title, 'Hujan Lebat Kepulauan Riau');
      expect(warnings.single.urgency, 'Immediate');
      expect(warnings.single.severity, 'Severe');
      expect(warnings.single.affectedArea, 'Tanjungpinang; Bintan');
      expect(warnings.single.source, 'BMKG - Peringatan Dini Cuaca');
      expect(warnings.single.updatedAt, isNotNull);
    },
  );
}

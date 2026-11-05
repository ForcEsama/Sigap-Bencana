import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/home_page.dart';
import 'package:mobile/services/role_home_page.dart';

void main() {
  test('all supported roles open the shared mobile home page', () {
    expect(signedInPageForRole('masyarakat'), isA<HomePage>());
    expect(signedInPageForRole('admin'), isA<HomePage>());
    expect(signedInPageForRole('petugas_lapangan'), isA<HomePage>());
  });

  test('unknown role does not receive a signed-in page', () {
    expect(signedInPageForRole('operator'), isNull);
    expect(signedInPageForRole(null), isNull);
  });

  test('offline session also opens the shared home page for every role', () {
    expect(signedInPageForRole('admin', offlineMode: true), isA<HomePage>());
    expect(
      signedInPageForRole('petugas_lapangan', offlineMode: true),
      isA<HomePage>(),
    );
    expect(
      signedInPageForRole('masyarakat', offlineMode: true),
      isA<HomePage>(),
    );
  });
}

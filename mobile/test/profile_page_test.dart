import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/profile/profile_page.dart';

void main() {
  testWidgets('profile edit form includes shared web and mobile profile fields', (
    tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: EditProfilePage(
          profile: {
            'name': 'Dina Warga',
            'email': 'dina@example.test',
            'phone': '081234567890',
            'domicile_address': 'Jl. Merdeka 10',
            'province': 'Sumatera Barat',
            'regency_city': 'Kota Padang',
            'district': 'Padang Barat',
            'village': 'Belakang Tangsi',
            'postal_code': '25111',
            'date_of_birth': '1999-04-12T00:00:00.000000Z',
            'gender': 'perempuan',
          },
        ),
      ),
    );

    expect(find.text('Alamat Domisili'), findsOneWidget);
    expect(find.text('Jl. Merdeka 10'), findsOneWidget);
    expect(find.text('Provinsi'), findsOneWidget);
    expect(find.text('Tanggal Lahir'), findsOneWidget);

    await tester.scrollUntilVisible(
      find.byType(DropdownButtonFormField<String>),
      300,
      scrollable: find.byType(Scrollable).first,
    );

    expect(find.byType(DropdownButtonFormField<String>), findsOneWidget);
    expect(find.text('perempuan'), findsOneWidget);
  });
}

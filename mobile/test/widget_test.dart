import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/main.dart';

void main() {
  testWidgets('welcome page offers login and registration', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const MaterialApp(home: WelcomePage()));

    expect(find.text('Masuk'), findsOneWidget);
    expect(find.text('Daftar Akun'), findsOneWidget);
  });
}

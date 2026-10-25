import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/notifications/notification_item.dart';
import 'package:mobile/notifications/notification_page.dart';
import 'package:mobile/notifications/notification_service.dart';
import 'package:mobile/warnings/disaster_warning.dart';
import 'package:mobile/warnings/warning_service.dart';

void main() {
  testWidgets('notification filters show only their exact category', (
    WidgetTester tester,
  ) async {
    final notificationService = _FakeNotificationService([
      _item(1, 'Peringatan hujan', 'Peringatan'),
      _item(2, 'Informasi layanan', 'Informasi'),
      _item(3, 'Laporan diperbarui', 'Aktivitas'),
    ]);

    await tester.pumpWidget(
      MaterialApp(
        home: NotificationPage(
          notificationService: notificationService,
          warningService: _EmptyWarningService(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Peringatan hujan'), findsOneWidget);
    expect(find.text('Informasi layanan'), findsOneWidget);
    expect(find.text('Laporan diperbarui'), findsOneWidget);

    await tester.tap(find.widgetWithText(ChoiceChip, 'Peringatan'));
    await tester.pumpAndSettle();
    expect(find.text('Peringatan hujan'), findsOneWidget);
    expect(find.text('Informasi layanan'), findsNothing);
    expect(find.text('Laporan diperbarui'), findsNothing);

    await tester.tap(find.widgetWithText(ChoiceChip, 'Informasi'));
    await tester.pumpAndSettle();
    expect(find.text('Peringatan hujan'), findsNothing);
    expect(find.text('Informasi layanan'), findsOneWidget);
    expect(find.text('Laporan diperbarui'), findsNothing);

    await tester.tap(find.widgetWithText(ChoiceChip, 'Aktivitas'));
    await tester.pumpAndSettle();
    expect(find.text('Peringatan hujan'), findsNothing);
    expect(find.text('Informasi layanan'), findsNothing);
    expect(find.text('Laporan diperbarui'), findsOneWidget);

    await tester.tap(find.widgetWithText(ChoiceChip, 'Semua'));
    await tester.pumpAndSettle();
    expect(find.text('Peringatan hujan'), findsOneWidget);
    expect(find.text('Informasi layanan'), findsOneWidget);
    expect(find.text('Laporan diperbarui'), findsOneWidget);
    expect(notificationService.requestedCategories, everyElement(isNull));
  });
}

NotificationItem _item(int id, String title, String type) => NotificationItem(
  id: id,
  title: title,
  message: 'Ringkasan $title',
  type: type,
  createdAt: DateTime(2026, 10, 25, 9),
  isRead: true,
);

class _FakeNotificationService extends NotificationService {
  _FakeNotificationService(this.items);

  final List<NotificationItem> items;
  final List<String?> requestedCategories = [];

  @override
  Future<List<NotificationItem>> fetchNotifications({String? category}) async {
    requestedCategories.add(category);
    return items;
  }
}

class _EmptyWarningService extends WarningService {
  @override
  Future<List<DisasterWarning>> fetchActiveWarnings() async => const [];
}

# Laporan Pengujian Integrasi SIGAP BENCANA

Tanggal: 26 Oktober 2026

## Hasil otomatis

| Command | Hasil aktual | Catatan |
|---|---|---|
| `php artisan test --compact` | **144 passed, 748 assertions** | PHPUnit berjalan memakai database pengujian terisolasi; tidak memverifikasi skema MySQL aktif |
| `php artisan test --compact tests/Feature/MobileNotificationApiTest.php tests/Feature/MobileSosApiTest.php tests/Feature/MobileProfileApiTest.php` | **9 passed, 46 assertions** | Filter/read notification, ownership SOS, idempotensi SOS, update profil dan schema guard |
| `flutter analyze` dari `mobile/` | **No issues found** | Analisis statis, bukan eksekusi di emulator/HP |
| `vendor/bin/pint --dirty --format agent` | **Passed** | Format PHP workspace |
| `git diff --check` | **Passed** | Tidak menemukan whitespace/error patch |
| `php artisan migrate:status --no-interaction` | 15 migration inti `Ran`; 4 migration `Pending` | Schema MySQL tidak sinkron sepenuhnya dengan ledger |

## Tes Flutter widget

Widget test profil ditambahkan di `mobile/test/profile_page_test.dart`, namun belum mendapat hasil assertion. Perintah `flutter test --reporter expanded --timeout 30s test/profile_page_test.dart` tidak menghasilkan keluaran lebih dari 30 detik; proses dihentikan manual. Perintah baseline `flutter test --reporter expanded --timeout 20s test/widget_test.dart` juga menggantung tanpa keluaran. Percobaan verbose untuk test profil pun tidak menghasilkan keluaran selama lebih dari 30 detik. Maka status widget tests adalah **belum terverifikasi karena test runner menggantung**, bukan lulus dan bukan bukti regression aplikasi.

## Pemeriksaan database aktif (read-only)

Laravel Boost membaca schema dan jumlah records pada MySQL `sigap_bencana`. Saat diperiksa: users 2, disaster reports 2, early warnings 7, early warning source metadata 7, notifications 1, SOS requests 4, idempotency keys 1, emergency contacts 6; risk zones, news, evacuation posts dan safety guides masing-masing 0. Tidak ada row database yang diubah dalam audit ini.

Empat migration pending: repair `risk_zones`, create/guard `early_warning_sources`, create/guard `sos_request_idempotency_keys`, dan repair field profil. Tabel early-warning metadata dan SOS idempotency sudah ada; risk_zones belum memiliki business columns, field profil tambahan belum ada. Migrasi aktif tidak dijalankan sesuai instruksi tugas dan karena percobaan DDL sebelumnya menemukan MySQL 8.4.3 error 3958 `Failed to delete sdi ... due to missing record`. Error itu perlu pemeriksaan operator/backup, bukan bypass dengan drop/recreate.

## Manual yang belum dilakukan

- Tidak ada browser desktop yang digunakan untuk memeriksa tampilan/klik pada semua halaman web.
- Tidak ada Android emulator atau HP fisik yang digunakan; lokasi GPS, push notification, secure storage perangkat, navigasi dan profil perangkat belum diuji manual.
- Feed BMKG/BNPB, geocoding, dan tiles InaRISK belum diuji secara live pada browser/perangkat selama tahap ini.
- Tidak ada migration/import terhadap database aktif, dan isi records dari dump `.ibd` tidak diperiksa.

## Perintah yang tidak dijalankan

- Tidak menjalankan `migrate:fresh`, `db:wipe`, `DatabaseSeeder`, atau import `.ibd`.
- Tidak mengubah `.env` atau credential.
- Tidak menjalankan `flutter pub get`; dependency tidak diubah.

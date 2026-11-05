# Changelog Rekonsiliasi SIGAP BENCANA

Tanggal pembaruan: 26 Oktober 2026

## Integrasi website

- Mengintegrasikan halaman web untuk notifikasi, berita dan edukasi, peringatan aktif, SOS, tempat evakuasi, kontak darurat, bantuan, dan tentang aplikasi beserta routes, controller, services, dan pengujian.
- Memperbaiki navigasi Home supaya kartu dan menu fitur menuju halaman yang benar; landing, login, registrasi, bantuan, dan tentang tetap publik, sedangkan Home dan fitur pengguna memakai middleware `auth`.
- Menghubungkan detail profil ke controller dan form yang memuat data kontak/domilisi, tanggal lahir, jenis kelamin, serta perubahan kata sandi. Field profil tambahan dibuat nullable dan menggunakan pemeriksaan skema untuk mencegah error SQL jika database belum siap.
- Menambahkan migration repair profil yang idempotent, serta membuat migration tabel warning-source dan SOS-idempotency aman bila tabelnya sudah ada. Tidak ada migration destruktif.
- Menambahkan gambar kontak darurat dan foto rencana titik evakuasi dari arsip. CSV evakuasi tetap berupa kandidat perencanaan dengan status koordinat belum terverifikasi; tidak diimpor sebagai lokasi operasional.
- Menambahkan `EmergencyContactSeeder` idempotent dan menjalankannya secara khusus. Enam kontak resmi terlihat pada database aktif; tidak menjalankan `DatabaseSeeder` yang juga membuat akun uji.
- Halaman Peringatan Bencana membedakan CAP BMKG aktif dari berita dan kejadian; kode SOS web tetap menggunakan nilai bencana yang cocok dengan API/mobile.
- Mengaudit empat arsip Dirga/Duta. `.env` Duta dikecualikan dari ekstraksi; dua arsip database hanya berisi `.ibd` dan diperiksa metadata SDI-nya tanpa import records.
- Menyamakan form Data Pribadi Flutter dengan field profil web melalui `PATCH /api/user` yang additive. Kode klien lama tetap mengirim field dasar; klien baru menerima 503 yang eksplisit bila database belum memiliki kolom tambahan.
- Menambahkan widget test Flutter untuk field profil dan API feature tests untuk update extended profile serta schema yang belum siap.

## Kondisi database aktual

- Database MySQL `sigap_bencana` berhasil diperiksa melalui Laravel Boost. Sebelum migrasi, `users` berisi 2 akun; `risk_zones`, `news`, `emergency_contacts`, dan `evacuation_posts` masing-masing kosong. Tabel `early_warning_sources` dan `sos_request_idempotency_keys` sudah ada dengan struktur yang sesuai.
- Seeder kontak berhasil dijalankan dan melakukan upsert enam kontak tanpa menghapus data.
- Migrasi skema belum berhasil diterapkan. MySQL 8.4.3 menolak `ALTER TABLE` pada `risk_zones` dan `users` dengan error 3958: `Failed to delete sdi ... due to missing record`. Setelah gagal, pemeriksaan menunjukkan kolom/tabel belum berubah dan migrations tetap berstatus pending. Perlu backup dan pemeriksaan/recovery MySQL oleh operator sebelum mencoba DDL lagi.
- Karena itu kolom domisili profil belum aktif di MySQL, dan tabel peta risiko masih hanya memiliki kolom `id`, `created_at`, dan `updated_at`. Halaman peta memberi state kosong/log ketika skema tak lengkap; penyimpanan profil tambahan memberi pesan skema belum siap. Jangan menjalankan reset, drop, atau membuat ulang tabel untuk melewati error ini.

## Yang dipertahankan

- Tidak mengganti kontrak API/mobile atau menyelaraskan tampilan mobile pada tahap ini.
- Tidak menghapus sembilan peringatan atau data pengguna, mengubah `.env`, menjalankan reset database, atau menghapus tabel.
- Berita, edukasi, kejadian, dan peringatan aktif tetap diklasifikasikan terpisah; tidak dibuat konten bencana palsu.
- Kandidat evakuasi dari CSV tidak diberi status resmi/terverifikasi dan tidak dimasukkan ke tabel operasional.

## Verifikasi

- Pengujian terarah: `php artisan test --compact tests/Feature/ProfilePageTest.php tests/Feature/AuthenticationTest.php tests/Feature/PublicPagesTest.php tests/Feature/DisasterAlertFeedsTest.php tests/Feature/NewsEducationTest.php tests/Feature/NotificationCenterTest.php tests/Feature/SosFlowTest.php tests/Feature/ValidateEvacuationPostsCommandTest.php` — 60 tes lulus, 357 assertions.
- `vendor/bin/pint --dirty --format agent` — lulus.
- `php artisan migrate --pretend --force --no-interaction` — menampilkan rencana DDL; tidak mengubah skema.
- `php artisan migrate --force --no-interaction` — gagal pada error MySQL 3958, tidak ada migration tercatat lulus.
- `php artisan db:seed --class=EmergencyContactSeeder --no-interaction` — berhasil.
- `php artisan test --compact`: **144 tests passed, 748 assertions**.
- `php artisan test --compact tests/Feature/MobileNotificationApiTest.php tests/Feature/MobileSosApiTest.php tests/Feature/MobileProfileApiTest.php`: **9 passed, 46 assertions**.
- `flutter analyze` — No issues found.
- `flutter test` pada widget profil dan test bawaan lama tidak mengeluarkan output lebih dari 30 detik dan dihentikan manual; widget assertion belum terverifikasi.
- `vendor/bin/pint --dirty --format agent` dan `git diff --check` — lulus.
- Hasil terperinci termasuk status MySQL, tes manual dan keterbatasan ada di [`INTEGRATION_TEST_REPORT.md`](INTEGRATION_TEST_REPORT.md).
- Pengujian visual browser/HP belum dilakukan. Sinkronisasi profil lintas web/API/mobile terpasang pada kode, tetapi menunggu schema MySQL yang belum bisa dimigrasikan.

# Rencana Integrasi SIGAP BENCANA

Baseline adalah project Dirga yang sudah aktif di workspace. Rencana berikut memisahkan integrasi kode yang sudah siap dari rekonsiliasi database yang memerlukan perbaikan lingkungan.

| Tahap | Status | Keputusan / tindakan |
|---|---|---|
| 1. Identifikasi project dan arsip | Selesai | Memastikan workspace cocok dengan baseline Dirga; mengekstrak source ke folder terpisah dan mengecualikan `.env` Duta |
| 2. Audit source dan schema arsip | Selesai | Membaca route, controller, service, view, dependency, test, serta SDI tabel; tidak menyalin `.ibd` |
| 3. Integrasi web Duta ke Laravel Dirga | Terpasang dan diuji di workspace | Pertahankan route/contract Dirga; web Duta ditambahkan secara selektif untuk news/education, alerts, notifications, SOS, evacuation, contacts, help/about |
| 4. Menautkan Home dan akses privat | Terpasang dan diuji | Semua tautan fitur memakai route; `/home` dan fitur pengguna memakai `auth`, landing/auth/help/about tetap publik |
| 5. Sinkronisasi profil web/API/mobile | Kode diselaraskan | Form/API Flutter berbagi kolom profil; klien lama tetap kompatibel. Schema guard menghasilkan pesan 503 selama migration belum tersedia |
| 6. Validasi database terisolasi | Lulus di tes Laravel SQLite | Feature suite menjalankan migrations dan alur profil pada SQLite terisolasi; tidak membuktikan DDL MySQL aktif |
| 7. Rekonsiliasi database arsip | Batasan tercatat | `.ibd` hanya dapat dipakai untuk metadata SDI; tidak ada data rows yang diimpor. Minta dump SQL sebelum mapping data |
| 8. Perbaikan MySQL aktif | Terblokir untuk sementara | Backup database, periksa MySQL error log dan SDI oleh operator. Sesudah perbaikan, validasi di salinan terisolasi, lalu minta persetujuan sebelum migration aktif |
| 9. Verifikasi UI dan perangkat | Belum dilakukan | Jalankan web pada browser desktop/mobile, dan Flutter pada Android. Jangan menganggap `flutter analyze` sebagai tes perangkat |

## Urutan aman untuk aktivasi skema nanti

1. Buat backup database `sigap_bencana` dengan cara backup resmi MySQL/Laragon dan uji bahwa file backup dapat dibaca. Jangan menyalin `.ibd` antar database.
2. Periksa error log MySQL untuk error 3958/SDI dan status file tablespace. Jangan menjalankan `DROP`, `REPAIR`, `OPTIMIZE`, atau import tablespace tanpa rencana recovery dan backup tervalidasi.
3. Buat salinan database untuk uji. Jalankan migration pending pada salinan dan cocokkan schema hasilnya dengan migrations/model dan dokumen database.
4. Pastikan migration conditional tidak membuat ulang tabel `early_warning_sources` dan `sos_request_idempotency_keys` yang sudah ada. Pastikan `risk_zones` kosong sebelum menambah kolom `NOT NULL` atau rancang repair sesuai records yang ditemukan.
5. Minta persetujuan eksplisit untuk penerapan ke database aktif setelah hasil uji dan backup dapat ditinjau.
6. Setelah penerapan, verifikasi `migrate:status`, schema, jumlah records sebelum/sesudah, profil API/web, dan peta risiko. Tidak perlu menjalankan ulang seeder bila kontak sudah ada karena seeder memakai upsert.

## Tidak termasuk dalam rencana aktif

- Tidak mengganti project Dirga dengan arsip Duta.
- Tidak melakukan merge rows dari file `.ibd`.
- Tidak menambah endpoint evakuasi/kontak/peta mobile yang tidak ada; penambahan endpoint membutuhkan desain kontrak dan konsumen yang teruji.
- Tidak mengisi data berita, peringatan, atau shelter palsu untuk presentasi.

# Rekonsiliasi Database SIGAP BENCANA

## Sumber dan metode

`database punya dirga.zip` memuat 22 file `.ibd`; `database punya duta.zip` memuat 11 file `.ibd`. Tidak ada file `.sql`, `.dump`, migration snapshot, atau file `.cfg`. `ibd2sdi` MySQL 8.4.3 digunakan untuk membaca definisi kolom, index, dan foreign-key metadata yang tertanam pada SDI. Pemeriksaan ini tidak mengakses atau menghitung isi records.

## Perbedaan himpunan tabel

| Kategori | Tabel |
|---|---|
| Ada pada Dirga dan Duta (11) | `jobs`, `migrations`, `news`, `notifications`, `password_reset_tokens`, `personal_access_tokens`, `risk_zones`, `safety_guides`, `sessions`, `sos_requests`, `users` |
| Hanya pada Dirga (11) | `cache`, `cache_locks`, `disaster_reports`, `early_warning_sources`, `early_warnings`, `emergency_contacts`, `evacuation_posts`, `failed_jobs`, `incident_histories`, `job_batches`, `sos_request_idempotency_keys` |
| Hanya pada Duta | Tidak ada |

Untuk 10 tabel bersama selain `users`, kolom terlihat, tipe/nullability, index, dan foreign-key metadata dari SDI cocok. Pada `users`, kedua dump memiliki field inti `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, timestamps, `role`, `phone`, `is_active`; dump Duta juga memuat:

| Kolom tambahan Duta | Tipe | Nullable | Pemakaian |
|---|---|---:|---|
| `domicile_address` | varchar(255) | Ya | Alamat domisili |
| `province` | varchar(100) | Ya | Provinsi |
| `regency_city` | varchar(100) | Ya | Kabupaten/kota |
| `district` | varchar(100) | Ya | Kecamatan |
| `village` | varchar(100) | Ya | Kelurahan/desa |
| `postal_code` | varchar(10) | Ya | Kode pos |
| `date_of_birth` | date | Ya | Tanggal lahir |
| `gender` | varchar(20) | Ya | Jenis kelamin |

Keputusan: skema Dirga tetap superset. Field Duta dipetakan sebagai penambahan nullable melalui migration baru; tidak ada kolom Dirga yang dihapus.

## Skema aktif workspace

Database aktif `sigap_bencana` dibaca read-only via Laravel Boost pada 26 Oktober 2026. Schema yang teramati:

- `users`: field inti Dirga; delapan kolom profil tambahan belum ada.
- `risk_zones`: hanya `id`, `created_at`, `updated_at`, berbeda dari dua metadata dump yang memuat nama, jenis bencana, tingkat, wilayah, koordinat, sumber, tanggal data, active flag dan index.
- `early_warning_sources` dan `sos_request_idempotency_keys`: tabel sudah ada di MySQL dengan struktur/index/FK yang cocok dengan definisi migration di codebase.
- Field/tabel lain pada schema aktif mengikuti himpunan tabel Dirga.

Row count hasil query read-only:

| Tabel | Records aktif |
|---|---:|
| `users` | 2 |
| `disaster_reports` | 2 |
| `risk_zones` | 0 |
| `early_warnings` | 7 |
| `early_warning_sources` | 7 |
| `news` | 0 |
| `notifications` | 1 |
| `sos_requests` | 4 |
| `sos_request_idempotency_keys` | 1 |
| `emergency_contacts` | 6 |
| `evacuation_posts` | 0 |
| `safety_guides` | 0 |

## Migration ledger dan risiko

`php artisan migrate:status --no-interaction` menunjukkan migration inti sudah `Ran`, sementara yang berikut `Pending`:

- `2026_10_24_070712_restore_missing_columns_on_risk_zones_table`
- `2026_10_24_190235_create_early_warning_sources_table`
- `2026_10_25_084741_create_sos_request_idempotency_keys_table`
- `2026_10_25_171313_repair_missing_profile_fields_on_users_table`

Ini menunjukkan drift antara migration ledger dan schema aktif: dua tabel pending sudah ada, sedangkan kolom peta risiko/profil belum lengkap. Pada percobaan DDL sebelumnya MySQL 8.4.3 mengembalikan error 3958 `Failed to delete sdi ... due to missing record` saat `ALTER TABLE risk_zones` dan `ALTER TABLE users`. Pemeriksaan pasca-gagal menunjukkan schema tidak berubah dan migration masih pending.

Tindakan: migration untuk warning metadata dan SOS idempotency memakai `Schema::hasTable` sebelum membuat tabel, dan down tidak menghapus tabel/records yang mungkin telah ada. Migration peta risiko memperbaiki kolom yang hilang tanpa menghapus data. Migration profil menambah field satu per satu jika belum tersedia; `down()` tidak menghapus nilai profil. Meski code migration diuji oleh SQLite, migration MySQL belum diterapkan karena masalah SDI dan aturan tugas melarang perubahan aktif tanpa backup, validasi salinan, dan persetujuan.

## Data arsip dan larangan import langsung

File `.ibd` mengandung tablespace InnoDB; arsip tidak menyertakan ekspor SQL atau file transport yang cukup untuk pemetaan row yang aman. Metadata SDI hanya dipakai untuk perbandingan struktur, bukan data. Jangan menimpa database aktif dengan file `.ibd` dan jangan menganggap ID/foreign key antar-database bebas bentrok. Jika records Duta perlu dipindahkan, minta dump SQL terstruktur, lakukan mapping key/foreign key/kategori/status dan deduplikasi pada salinan terisolasi, lalu tinjau hasil sebelum persetujuan import.

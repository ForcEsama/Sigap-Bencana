# Desain Database SIGAP BENCANA

Status: inventaris skema terpasang dan usulan tahap berikutnya, 23 Oktober 2026. Skema runtime diperiksa melalui `php artisan migrate:status`; seluruh 13 migration aplikasi/bawaan yang terdaftar sudah dijalankan. Dokumen ini tidak mengubah database.

## Entitas yang tersedia

| Tabel | Kolom penting / tipe | Key, relasi, indeks, aturan hapus | Status bisnis saat ini |
|---|---|---|---|
| `users` | `id`, `name`, `email` unik, `email_verified_at`, `password`, `phone`, `role`, `is_active`, timestamps | PK `id`; profil dasar; laporan/SOS/notifikasi/histori memiliki FK ke user. | Role string default `masyarakat`; belum ada katalog role/permission. |
| `personal_access_tokens` | morph key, `name`, token unik, abilities, `last_used_at`, `expires_at` | Tabel polymorphic Sanctum; indeks expiry. | Token bearer; kebijakan expiry aplikasi belum ditetapkan. |
| `disaster_reports` | user, judul, jenis, deskripsi/alamat, decimal koordinat, `occurred_at`, `photo_path`, status verifikasi/penanganan, verifier/waktu | FK user cascade; verifier `nullOnDelete`; indeks status. | `pending`/`verified`/`rejected`; `unhandled`/`in_progress`/`resolved`; aturan status masih string. |
| `sos_requests` | user, jenis, deskripsi, koordinat, alamat, urgency/status, penugas, waktu permintaan/ack/selesai | FK user cascade; assigned user `nullOnDelete`; indeks status+urgensi, koordinat, waktu. | Default `high`/`sent`; belum ada ID eksternal stabil, idempotency key, atau riwayat tindakan SOS teruji. |
| `incident_histories` | dua FK objek nullable, `actor_id` nullable, status lama/baru, catatan, timestamps | FK laporan dan SOS cascade delete; actor `nullOnDelete`; indeks created_at. | Dapat menyimpan histori salah satu objek, tetapi tidak mengharuskan tepat satu FK terisi. |
| `early_warnings` | pembuat, judul/jenis, uraian, severity, area, koordinat nullable, source, starts/expires, status | pembuat `nullOnDelete`; indeks status/severity dan jenis/start. | Draft/status string; informasi penerbit dan timestamp terbit/pembaruan sumber masih kurang. |
| `risk_zones` | nama/jenis/risk level, region, titik decimal, radius, source, data_date, active | Indeks jenis+level dan active. | Titik/radius saja, tidak menyimpan poligon; harus dilabeli sumber dan tanggal data. |
| `evacuation_posts` | nama/alamat, koordinat, capacity/occupancy, fasilitas, kontak, status | Indeks status+nama dan koordinat. | Belum ada `capacity_updated_at`/pemilik data; ambang status belum diputuskan (OD-06). |
| `news` | author, title, slug unik, excerpt/content, image path, category, source/URL, status, publish time | FK author restrict delete; indeks status+published. | Draft/published belum dibatasi enum; kategori artikel masih string. |
| `safety_guides` | title, disaster type, phase, summary/content, source/URL, status, sort order | Indeks disaster_type+phase+status. | Konten edukasi; nilai status/fase belum dibatasi. |
| `emergency_contacts` | name/category/phone, description, region/address/website, active, order | Indeks category+active. | Belum tanggal atau bukti verifikasi nomor; kontak perlu diverifikasi sebelum publikasi. |
| `notifications` | user, title/message/type, referensi polymorphic, read_at | FK user cascade; indeks user+read dan related type/id. | Kategori string; referensi tidak memakai relasi morph Eloquent formal. |
| `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | tabel infrastruktur framework | indeks bawaan; sessions `user_id` nullable indexed. | Bukan data fitur bencana. |

Semua tabel fitur menggunakan bigint auto-increment sebagai PK dan timestamps kecuali personal access token mengikuti skema Sanctum. FK di migration yang disebut di atas adalah relasi nyata; kolom `related_type/related_id` notifikasi adalah pasangan referensi tanpa FK.

## Relasi

- User 1:N disaster reports (pelapor), SOS requests (pemohon), notifications, incident histories (actor), early warnings (creator), dan news (author).
- User 1:N disaster reports terverifikasi dan SOS yang ditugaskan melalui FK terpisah.
- Disaster report 1:N incident histories; SOS request 1:N incident histories secara konseptual, tetapi struktur migration sekarang membuat dua FK wajib sehingga satu histori harus menunjuk kedua objek.
- Entitas informasi lain berdiri sendiri pada skema saat ini.

## Temuan skema yang perlu ditangani

1. `incident_histories.disaster_report_id` dan `sos_request_id` sama-sama nullable. Ini mengizinkan histori tanpa objek atau dengan dua objek sekaligus; aturan tepat-satu perlu validasi aplikasi atau constraint yang cocok dengan versi MySQL.
2. Kolom FK `disaster_reports.verified_by`, `sos_requests.assigned_to`, dan `early_warnings.created_by` secara eksplisit nullable sebelum `nullOnDelete()`.
3. `notifications.related_id` adalah nullable unsigned bigint tanpa FK, bersama `related_type`; indeks gabungan ada.
4. Laporan hanya menyediakan satu `photo_path`, tidak mendukung beberapa foto/video dan metadata media. Jangan menambahkan kolom/table sampai desain upload, retensi, akses privat, serta batas 10 MB difinalkan.
5. `evacuation_posts` tidak mencatat waktu update kapasitas khusus; `updated_at` tabel tidak menjelaskan kapan occupancy diverifikasi.
6. `early_warnings` tidak memiliki URL sumber, `published_at`, dan `source_updated_at`; `source` bebas teks.
7. Tidak ada entitas lokasi tersimpan, preferensi notifikasi, kontak darurat pribadi, atau audit log umum. Keberadaan tabel tersebut bergantung pada keputusan/lingkup BRD, khususnya OD-08, OD-14, dan OD-15.

## Status dan aturan data

| Entitas | Nilai yang terlihat di kode/skema | Catatan |
|---|---|---|
| User role | `masyarakat`, `petugas`, `admin` digunakan kode | Operator/editor/pengelola evakuasi belum dimodelkan; jangan menganggap role awal final. |
| Report verification | `pending`, `verified`, `rejected` | UI harus melabeli laporan pending sebagai belum terverifikasi. |
| Report handling | `unhandled`, `in_progress`, `resolved` | Transisi dan siapa yang boleh mengubah perlu diuji. |
| SOS | default `sent`, urgency `high` | Tahap BRD mencakup diterima, menunggu konfirmasi, ditugaskan, menuju, tiba, selesai, batal/ditolak; enum/transisi belum disepakati. |
| Early warning/news/guide/post | status string, default tersedia di migration | Standar publikasi, masa berlaku, dan kategori harus konsisten pada API. |
| Risk/evacuation | tingkat risiko/status string | Ambang, koordinat dan freshness perlu pemilik data; tidak ada data resmi terpasang dari audit. |

## Tabel yang mungkin diperlukan setelah keputusan

Tidak ada tabel baru yang direkomendasikan untuk dibuat tanpa keputusan dan pemeriksaan duplikasi. Kandidat yang harus ditinjau terhadap kebutuhan final:

- `report_media`: jika lampiran jamak dan metadata media dibutuhkan.
- `user_saved_locations`, `user_notification_preferences`, `user_emergency_contacts`: jika kemampuan profil tersebut disepakati dan tiap data dimiliki pengguna.
- Katalog `roles`/`permissions` atau paket RBAC internal: jika jumlah role dan izin operasional difinalkan.
- Audit log terpisah: bila histori domain saat ini tidak memenuhi keterlacakan aktivitas administratif/security.

Semua tabel kandidat harus memiliki ownership FK, indeks untuk pola baca, aturan cascade/retensi, penyimpanan path media alih-alih blob, dan migration tambahan yang tidak menghapus data. Hindari duplikasi `incident_histories` dengan tabel audit generik sebelum batas tanggung jawabnya jelas.

## Rencana koreksi aman

1. Ambil skema aktual secara read-only (`SHOW CREATE TABLE`/Laravel schema inspection) tanpa menampilkan data akun atau konfigurasi rahasia.
2. Cocokkan migration dan periksa apakah data histori yang ada melanggar aturan tepat-satu.
3. Putuskan apakah integritas tepat-satu ditegakkan oleh validasi aplikasi atau constraint MySQL.
4. Jika skema perlu berubah, buat migration tambahan yang tidak menghapus data; jangan edit migration yang sudah dijalankan.
5. Jalankan tes migrasi pada database sementara; terapkan ke data lokal hanya setelah dampaknya jelas dan backup tersedia.

Pada audit ini migration status dibaca, tetapi skema kolom runtime belum diambil satu per satu. Tidak ada perubahan atau penghapusan data.

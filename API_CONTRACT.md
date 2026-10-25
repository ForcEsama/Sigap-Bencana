# Kontrak API SIGAP BENCANA yang Ditemukan

Dokumen ini mencatat route aktif dari `routes/api.php`, controller/resource, dan tests. Semua path di bawah memakai prefix Laravel `/api`. Tidak ada kontrak endpoint yang direkomendasikan dicatat sebagai endpoint aktif.

## Format umum

- Klien mobile mengambil base URL dari `mobile/lib/config/api_config.dart`; nilai saat ini merupakan IP LAN lokal dan harus disesuaikan per lingkungan, bukan ditulis ulang dari arsip.
- Endpoint privat menggunakan header `Authorization: Bearer <Sanctum token>` dan `Accept: application/json`.
- Validasi Laravel mengembalikan HTTP 422 dengan `message` dan `errors`. Token tidak valid/tidak ada mengembalikan 401. Error business conflict dapat mengembalikan 403, 404, atau 409 sesuai route.
- Resource collections menggunakan envelope `data`, `links`, dan `meta` pagination Laravel.

## Endpoint

| Method & path | Auth | Request/filter | Respons yang diimplementasikan | Konsumen / tes |
|---|---|---|---|---|
| `POST /register` | Publik | `name`, `email`, `password`, `password_confirmation`; optional `phone` | 201: `message`, `user{id,name,email,phone,role}`; registrasi menetapkan role masyarakat, akun aktif, email belum verified | Flutter register; `AuthenticationTest` |
| `POST /login` | Publik | `email`, `password` | 200: `message`, `token`, `token_type=Bearer`, `user{id,name,email,role}`; salah kredensial 401, akun nonaktif 403 | Flutter login; `AuthenticationTest` |
| `POST /logout` | Sanctum | Tidak ada body | 200 `message`; menghapus token yang sedang dipakai | Flutter logout; `AuthenticationTest` |
| `GET /user` | Sanctum | — | JSON User aktif, field rahasia disembunyikan oleh model | Flutter pemulihan profil/sesi; autentikasi mobile |
| `PATCH /user` | Sanctum | Wajib `name`, `email`; optional `phone`; additive optional `domicile_address`, `province`, `regency_city`, `district`, `village`, `postal_code`, `date_of_birth`, `gender` | 200 `message`, `user` dengan field profil tersedia; 422 validasi; 503 bila field tambahan diminta tetapi migration belum tersedia | Flutter Data Pribadi dan web memakai data users; `MobileProfileApiTest`, `ProfilePageTest` |
| `GET /reports` | Sanctum | Pagination default 10 | Paginator Laravel berisi laporan user yang sedang login | Flutter riwayat; `DisasterReportApiTest` |
| `POST /reports` | Sanctum | `title`, `disaster_type` dalam `gempa,banjir,longsor,kebakaran,tsunami,lainnya`, optional `description,address,occurred_at`, wajib `latitude,longitude` | 201 `message,data`; status server ditetapkan `verification_status=pending`, `handling_status=unhandled` | Flutter/web laporan; `DisasterReportApiTest`, `WebDisasterReportTest` |
| `GET /reports/{id}` | Sanctum | ID numerik | `data` laporan yang dimiliki user; laporan user lain 404 | Flutter detail laporan; `DisasterReportApiTest` |
| `PATCH /reports/{id}/verification` | Sanctum + officer | `decision=verified|rejected`, optional `notes` | `message,data`; menulis riwayat; perubahan status kedua 409 | Petugas web/API; `DisasterReportApiTest` |
| `PATCH /reports/{id}/handling` | Sanctum + officer | `status=unhandled|in_progress|resolved`, optional `notes` | `message,data`; hanya laporan verified dapat ditangani | Petugas; `DisasterReportApiTest` |
| `GET /v1/news` | Publik | Optional `category=berita|edukasi`, `per_page=1..20` (default 10) | Paginated `NewsResource`; item harus published, sudah terbit, memiliki sumber dan URL sumber. Category berita tidak menyertakan simulasi; kategori Peringatan dikecualikan | Flutter Home/berita; `NewsApiTest` |
| `GET /v1/news/{slug}` | Publik | Slug | `data` detail NewsResource + `content`; draft/tidak memenuhi publikasi 404 | Flutter detail berita; `NewsApiTest` |
| `GET /v1/warnings` | Publik | Pagination default 20 | Paginated warning; hanya published, sudah mulai, belum kedaluwarsa, memiliki sumber metadata dan URL sumber | Flutter Home/peringatan; `EarlyWarningApiTest`, `SyncBmkgWeatherAlertsTest` |
| `GET /v1/warnings/{id}` | Publik | ID numerik | `EarlyWarningResource`; warning yang tidak aktif 404 | Flutter detail warning; `EarlyWarningApiTest` |
| `GET /v1/me/notifications` | Sanctum | Optional `category=Peringatan|Informasi|Aktivitas`, `per_page=1..50` default 20 | Paginator `NotificationResource`, user-scoped; notifikasi SOS dapat menyertakan snapshot `related_sos_request` | Flutter notifications; `MobileNotificationApiTest` |
| `PATCH /v1/me/notifications/{id}/read` | Sanctum | ID numerik | `NotificationResource` yang sudah ditandai dibaca; foreign user 404 | Flutter; `MobileNotificationApiTest` |
| `POST /v1/sos` | Sanctum, throttle 10/menit | `client_request_id` UUID, `disaster_type=gempa|banjir|longsor|kebakaran|tsunami`, koordinat wajib, description/address optional | 201 saat baru dibuat atau 200 idempotent bila request ID sama; `message,data` termasuk reference/status/events. Urgency ditetapkan backend `high`, status awal `sent` | Flutter SOS; `MobileSosApiTest` |
| `GET /v1/me/sos` | Sanctum | Pagination default 20 | Daftar SOS milik pemohon dengan riwayat | Flutter riwayat/notifikasi; `MobileSosApiTest` |
| `GET /v1/me/sos/{id}` | Sanctum | ID numerik | Detail hanya milik pemohon, termasuk `events`; user lain 404 | Flutter progress; `MobileSosApiTest` |
| `PATCH /v1/operations/sos/{id}/status` | Sanctum + officer | `status=assigned|en_route|arrived|completed|rejected`; `assigned_to` wajib saat assigned; `notes` wajib saat rejected | `message,data`; transisi berurutan, pemohon diberi notifikasi | Petugas; SOS tests |

## Konten kontrak yang belum tersedia

- Tidak ditemukan API mobile untuk `emergency_contacts`, `evacuation_posts`, `risk_zones`, atau konten keselamatan `safety_guides`. Halaman web kontak/evakuasi memakai Laravel/database; jangan membuat klien mobile mengakses tabel langsung.
- Peta risiko web/mobile mengambil layanan layer InaRISK/ArcGIS secara langsung dari konfigurasi map yang sudah ada, bukan endpoint JSON Laravel untuk polygon zona lokal. Layer InaRISK dibedakan dari kejadian aktif.
- API profile mengembalikan field alamat hanya ketika schema users memiliki kolomnya; selama migration belum berjalan, Flutter yang mengirim field baru menerima 503 dan tidak menampilkan sukses palsu.
- Login API menerima email, bukan nomor telepon.
- `GET /reports` membatasi hasil ke pemilik yang login. Controller saat ini mengembalikan model Eloquent terpaginasikan secara langsung; gunakan Resource eksplisit pada perubahan kontrak berikutnya untuk mengunci daftar field publik. Ini catatan minimisasi field, bukan indikasi query lintas-pengguna.

## Bukti tes kontrak

Kontrak berita, warning, autentikasi, laporan, profile, notification, dan SOS ditopang tests feature di `tests/Feature`. Ringkasan jumlah/hasil aktual ada pada [`INTEGRATION_TEST_REPORT.md`](INTEGRATION_TEST_REPORT.md).

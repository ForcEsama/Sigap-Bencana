# Desain API SIGAP BENCANA

Status: kontrak yang benar-benar terdaftar dan rancangan endpoint lanjutan, 23 Oktober 2026. Endpoint rencana bukan endpoint yang sudah tersedia. Bentuk JSON akhir perlu dikunci bersama kebijakan privasi, status, dan keputusan terbuka BRD.

## Konvensi yang disarankan

- Prefix versi `/api/v1` untuk endpoint baru. Pertahankan `/api/*` lama selama mobile menggunakannya; migrasikan klien bertahap agar kompatibel.
- Autentikasi Sanctum menggunakan `Authorization: Bearer <token>`; informasi publik tetap bisa diakses tanpa login sesuai BRD.
- JSON sukses menggunakan `data` (objek/daftar), dan daftar memakai metadata pagination Laravel. JSON gagal memakai `message` dan, untuk validasi, `errors` berisi daftar pesan per field.
- Status penting: `200` baca/perubahan, `201` pembuatan, `401` belum autentikasi, `403` izin/akun ditolak, `404` data tidak ditemukan atau bukan milik pemanggil, `409` transisi konflik, `422` validasi, `429` rate limit.
- Jangan mengembalikan koordinat/identitas pelapor publik tanpa aturan publikasi; jangan memasukkan token/kode verifikasi ke log.

## Endpoint yang terdaftar sekarang

| Method / path | Auth / peran | Request utama | Response / error aktual dan batasan |
|---|---|---|---|
| `POST /api/register` | Publik | `name`, `email`, `phone?`, `password`, `password_confirmation` | `201 {message,user}`. Email unik, password min 8. Akun langsung aktif; belum consent, email verification, throttling. |
| `POST /api/login` | Publik | `email`, `password` | `200 {message,token,token_type,user}`; `401` kredensial salah; `403` akun nonaktif; validasi `422`. Email saja, belum ingat-saya atau rate limit yang terlihat. |
| `GET /api/user` | Sanctum | Bearer token | `200` user model; `401` tanpa token. Gunakan resource agar field output eksplisit. |
| `POST /api/logout` | Sanctum | Bearer token | `200 {message}`; menghapus token saat ini. |
| `GET /api/reports` | Sanctum; pemilik | query pagination Laravel | `200` paginator; hanya milik user aktif. Batas page size belum dikendalikan dari controller. |
| `POST /api/reports` | Sanctum | title, disaster_type, description?, address?, latitude, longitude, occurred_at? | `201 {message,data}`; status awal `pending`/`unhandled`; belum upload media, ID bisnis publik, atau transaction dibutuhkan. |
| `GET /api/reports/{id}` | Sanctum; pemilik | ID | `200 {data}`; laporan milik user lain tidak ditemukan (`404`). |
| `PATCH /api/reports/{id}/verification` | Sanctum + `officer` (`petugas`/`admin`) | `decision: verified|rejected`, `notes?` | `200 {message,data}`; `409` jika bukan pending; menulis incident history. FK histori untuk laporan dan SOS nullable; skema belum menjamin tepat satu relasi terisi. |
| `PATCH /api/reports/{id}/handling` | Sanctum + `officer` (`petugas`/`admin`) | `status: unhandled|in_progress|resolved`, `notes?` | `200 {message,data}`; `409` jika laporan belum verified; histori saat status berubah. |

Endpoint API lama tidak diberi prefix versi dan harus dipertahankan kompatibel sampai klien mobile beralih. Route laporan sekarang seluruhnya perlu Sanctum, sehingga tidak ada daftar laporan publik.

Autentikasi yang saat ini terdaftar juga tidak menyediakan endpoint Home, peringatan, atau berita publik. `GET /api/v1/home` pada tabel rencana di bawah adalah usulan kontrak saja. Jangan menganggapnya tersedia atau menghubungkan klien sebelum sumber data, kewenangan publikasi, dan kebijakan kesegaran disetujui.

## Rencana endpoint

| Modul | Method / path rencana | Akses | Request / response inti | Validasi dan aturan |
|---|---|---|---|---|
| Akun | `POST /api/v1/auth/register` | Publik | Data profil, persetujuan; `201 {data:user, message}` | Email/telepon unik; belum aktif sampai email diverifikasi. |
| Verifikasi | `POST /api/v1/auth/email/verify`, `POST .../resend` | Publik terbatas | Email + kode 6 digit; resend tanpa payload sensitif | Masa berlaku, batas percobaan, cooldown [OD-13], delivery provider [OD-07], hashing code. |
| Login | `POST /api/v1/auth/login` | Publik | `identity`, `password`, optional remember; token + safe user resource | Identity email/phone; throttle; hanya akun verified/aktif. |
| Reset | `POST /api/v1/auth/password/forgot`, `POST .../reset` | Publik terbatas | Email, token/kode, sandi baru | Throttle, respons tidak mengungkap keberadaan email; mail provider [OD-07]. |
| Profil | `GET/PATCH /api/v1/me`; `DELETE /api/v1/me` | Sanctum; pemilik | Field yang boleh diedit; safe user resource | Perubahan email/telepon perlu verifikasi ulang; delete account mengikuti retensi [OD-08]. |
| Home | `GET /api/v1/home` | Publik atau Sanctum untuk personalisasi lokasi opsional | Peringatan publik, berita, layanan/metadata sumber | Lokasi hanya jika user memberi izin; data stale ditandai. |
| Peringatan | `GET /api/v1/warnings` dan `GET /api/v1/warnings/{id}` | Publik | Filter type/region/severity; daftar paginated | Hanya published dan `expires_at > now`; sertakan source, published_at, expires_at. |
| Zona peta | `GET /api/v1/map/risk-zones`, `GET /api/v1/map/incidents`, `GET /api/v1/map/evacuation-posts` | Publik untuk data layak publik | bbox/filter type/risk/status; GeoJSON atau resource koordinat | Perlu provider/sumber [OD-02/03]; laporan tampil sesuai verifikasi dan kebijakan privasi. Tidak menyebut prediksi real-time. |
| Laporan | `POST /api/v1/reports`; `GET /api/v1/me/reports`; `GET /api/v1/me/reports/{id}` | Sanctum/pemilik | jenis, lokasi, uraian, attachments; daftar history | File MIME/isi dan maksimal 10 MB tiap file; storage privat; status awal pending; idempotensi pembuatan dipertimbangkan. |
| Moderasi laporan | `PATCH /api/v1/operations/reports/{id}/verification`, `PATCH .../handling` | Peran operator/petugas sesuai izin | decision/status, reason, optional duplicate ID | Policy server, transisi yang sah, transaction, actor/time/reason history, notifikasi. Workflow final perlu SOP. |
| SOS | `POST /api/v1/sos`; `GET /api/v1/me/sos`; `GET /api/v1/me/sos/{id}` | Sanctum/pemilik | disaster type, lokasi, accuracy/address, idempotency key | Simpan lalu kembalikan ID dan status server-received. Tidak mengklaim dispatch otomatis. Jenis final [OD-16]. |
| Operasi SOS | `PATCH /api/v1/operations/sos/{id}/status`, `POST .../assign` | Operator/petugas berwenang | status, reason, assigned officer | SOP, penugasan, pembatalan/eskalasi dan SLA [OD-04/05]; seluruh perubahan diaudit. |
| Evakuasi | `GET /api/v1/evacuation-posts`, `GET /api/v1/evacuation-posts/{id}` | Publik | search, bbox, status, pagination | Sertakan `capacity_updated_at`; status usang “Belum Diperbarui”; ambang [OD-06]. Jarak dihitung hanya jika titik pencari disediakan. |
| Berita/edukasi | `GET /api/v1/news`, `GET /api/v1/news/{slug}`, `GET /api/v1/safety-guides` | Publik | kategori, jenis, fase, pagination | Hanya konten published; sumber/atribusi; tidak mengirim draft ke publik. |
| Kontak darurat | `GET /api/v1/emergency-contacts` | Publik | region/category | Hanya kontak aktif yang sudah diverifikasi; phone action adalah panggilan perangkat, bukan SOS internal. |
| Notifikasi | `GET /api/v1/me/notifications`, `PATCH /api/v1/me/notifications/{id}/read`, `POST .../read-all` | Sanctum/pemilik | filter category/read, pagination | Scope selalu pada user terautentikasi; kategori konsisten Peringatan/Informasi/Aktivitas. |
| Preferensi | `GET/PATCH /api/v1/me/preferences`, saved locations, personal emergency contacts | Sanctum/pemilik | pilihan notifikasi/lokasi/kontak | Dibangun setelah fitur/paritas diputuskan (OD-14/15); jangan kirim notifikasi ke kontak pribadi tanpa persetujuan. |

## Akses dan pembatasan data

- Pengguna masyarakat hanya membaca/mengubah profil, laporan, SOS, notifikasi miliknya sendiri.
- Informasi publik hanya berisi konten terbit/terverifikasi dan field yang aman. Filter status di UI tidak menggantikan scope dan policy server.
- Operator, petugas, editor, pengelola evakuasi, dan admin memerlukan izin tindakan spesifik dan batas wilayah/penugasan. Role saat ini baru diperiksa oleh middleware officer untuk dua route laporan.
- Pembaruan status dilakukan dalam transaksi dengan histori actor, waktu, nilai lama/baru dan alasan. Notifikasi dikirim setelah commit.
- Endpoint daftar memakai pagination terbatas, validasi filter, rate limiting yang dikonfigurasi, dan resource eksplisit.

## Kontrak contoh (rencana)

Berhasil kirim laporan: `201 {"data":{"id":123,"status":"pending","verification_status":"pending","created_at":"..."},"message":"Laporan diterima dan menunggu verifikasi."}`

Validasi: `422 {"message":"Data yang diberikan tidak valid.","errors":{"latitude":["Koordinat wajib diisi."]}}`

Unauthenticated: `401 {"message":"Unauthenticated."}`. Status 401 dari endpoint terlindungi berarti kredensial tidak tersedia/tidak valid dan tidak menjadi bukti server tidak dapat dijangkau.

Kontrak rencana di atas bukan klaim implementasi. Sebelum modul dibangun, putuskan sumber data, role/izin, bentuk status, privacy fields, upload/storage, provider email, dan OD terkait.

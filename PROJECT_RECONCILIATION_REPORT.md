# SIGAP BENCANA — Audit dan Rekonsiliasi Project

Status: bagian 1-7 adalah snapshot audit/integrasi terdahulu. Snapshot ini diperbarui oleh `INTEGRATION_AUDIT.md`, `DATABASE_RECONCILIATION.md`, `INTEGRATION_PLAN.md`, `API_CONTRACT.md`, dan `INTEGRATION_TEST_REPORT.md` per 26 Oktober 2026; status MySQL dan sinkronisasi profil pada dokumen tersebut lebih baru.

## 1. Identitas sumber

| Label | Direktori | Teknologi/identitas |
|---|---|---|
| Project utama (baseline) | `C:\laragon\www\sigap-bencana` | Laravel 13, PHP 8.3, MySQL, Flutter; Git branch `dirga-21/10`, commit `92379ba8be2de01c1acad77c3ef3975e929b95d3`. |
| Project teman (arsip) | `C:\Users\Acer Nitro\Downloads\progress 25-10\Sigap-Bencana.rar` | Source Laravel dan Flutter; metadata Git dalam arsip menunjukkan branch `main`, commit `06d450839f0a19ea40e851935c39ea17cc31c116`. |
| Salinan audit sementara | `_audit_peer_20261025\Sigap-Bencana` | Source hasil ekstraksi terpisah untuk perbandingan. `.git`, `vendor`, `node_modules`, `.env*`, cache, dan storage runtime tidak diekstrak. Tidak digunakan sebagai project hasil akhir. |

Kedua versi memiliki commit berbeda. Arsip berisi project penuh, bukan sekadar patch. Arsip sumber tidak diubah. Tidak ada kredensial dari arsip yang disalin.

## 2. Ringkasan arsitektur

Kedua versi memakai Laravel 13 dan PHP `^8.3`; manifest Composer yang diperiksa memiliki deklarasi dependensi inti yang sama. Struktur inti Eloquent, migrations, routes, dan Flutter juga berasal dari baseline yang sama.

Project utama lebih maju pada integrasi mobile/API: ditemukan 39 file Dart di `mobile/lib`, termasuk SOS, notifikasi, berita, peringatan, peta, profil, laporan, dan cache offline. API utamanya memiliki endpoint berita, peringatan, profil, notifikasi, laporan, dan SOS.

Arsip teman memiliki 7 file Dart saja, tetapi website jauh lebih lengkap: 27 Blade view dibanding 9 pada baseline. Arsip menambahkan alur web notifikasi, berita/edukasi, SOS, peringatan, kontak darurat, tempat evakuasi, pusat bantuan, dan tentang aplikasi. Struktur backend-nya menambahkan service feed, validasi data evakuasi, serta feature tests untuk halaman-halaman tersebut.

## 3. Database dan API

Kedua versi mempunyai 15 migration inti bernama sama untuk pengguna, laporan, SOS, riwayat, peringatan, peta risiko, evakuasi, berita, edukasi, kontak darurat, dan notifikasi.

Perbedaannya:

- Baseline memiliki migration tambahan untuk memulihkan kolom peta risiko, tabel metadata sumber peringatan resmi, dan idempotency key SOS. Ketiganya mendukung fitur yang sudah berjalan di mobile dan dipertahankan.
- Arsip memiliki migration tambahan berisi delapan kolom profil domisili/tanggal lahir/gender. Migration ini tidak diadopsi pada tahap audit: kode profil baseline tidak memakai kolom tersebut, dan koneksi MySQL aktual saat audit tidak tersedia untuk memastikan apakah skema database sudah memilikinya.
- API baseline menyediakan berita/peringatan/notifikasi/SOS/profil untuk mobile. API pada arsip pada dasarnya hanya autentikasi, pengguna, dan laporan. Tidak ada kontrak API mobile baru di arsip yang lebih lengkap daripada baseline.

Koneksi MySQL `sigap_bencana` tidak tersedia pada saat pemeriksaan schema/migration aktual (koneksi `127.0.0.1:3306` ditolak). Karena itu kesamaan skema database aktif tidak diklaim. Sebelumnya query read-only berhasil melihat dua laporan milik `user_id=1`, tetapi pemeriksaan itu bukan pengganti verifikasi seluruh schema. Tidak ada migration atau query tulis dijalankan.

## 4. Perbandingan modul

| Modul | Project utama | Project teman | Keputusan awal | Alasan / tindakan |
|---|---|---|---|---|
| Autentikasi web/API | Login/register, Sanctum, sesi, mobile token; sejumlah route dan tests aktif | Alur web auth serupa; fitur tambahan tidak menunjukkan peningkatan kontrak | Pertahankan baseline | Tidak mengganti auth yang sudah terhubung ke mobile. Tes baseline saat ini: 72/73 lulus; satu tes logout mengharapkan redirect `/login`, aktual `/`. Dicatat sebagai kegagalan baseline. |
| Home dan peringatan | Home mobile dan API warning aktif, peringkat dan cache di Flutter | Sumber BMKG CAP aktif dengan expiry, cache stale, batas host dan timeout | Adopsi service sumber; rapikan pemisahan halaman | CAP BMKG memberi data resmi dengan masa berlaku. Controller arsip mencampur peringatan, gempa sebagai kejadian, dan berita dalam satu daftar; controller itu tidak diadopsi apa adanya. Berita/kejadian tetap dipisahkan. |
| Berita dan edukasi | API Laravel + halaman Flutter, sinkronisasi BMKG berita gempa | Web feed BNPB/BMKG, cache, kategori, halaman detail, pemeriksaan gambar/sumber | Adopsi service dan halaman web secara selektif | Memperluas web tanpa mengganti kontrak API mobile atau menyamakan berita dengan peringatan. Konten eksternal tetap berlabel sumber/status cache. |
| Peta risiko | Implementasi web dan Flutter memakai InaRISK ImageServer, filter, metadata dan legenda | Halaman peta risiko web serupa namun lebih awal | Pertahankan baseline | Baseline punya konfigurasi multi-layer dan perbaikan kompatibilitas yang lebih baru. |
| SOS | Alur mobile lengkap; API memakai idempotency key dan respons terstruktur | Alur web berhalaman, sesi CSRF, penguncian submit dan notifikasi; tipe `gempa_bumi` berbeda dari API mobile `gempa` | Adopsi UI/controller web dengan penyelarasan tipe | Tidak mengganti mekanisme API mobile. Samakan nilai tipe gempa agar laporan web/mobile tidak berbeda. Pertahankan kehati-hatian agar “tercatat” tidak berarti petugas sudah menerima. |
| Laporan kejadian | API mobile, detail, form, riwayat dan web dasar | Web laporan dan tes serupa | Pertahankan baseline | Tidak ada peningkatan backend yang cukup untuk mengganti kontrak mobile atau skema. |
| Notifikasi | API dan UI mobile aktif | Web feed menggabungkan notifikasi tersimpan dengan item sumber eksternal/aktivitas, kategori dan status sumber | Adopsi modul web/service tanpa mengganti API mobile | Menggunakan relasi pengguna yang ada dan guard tabel; UI membedakan item persisten dan feed eksternal. Perlu tes kepemilikan/status baca. |
| Tempat evakuasi/kontak darurat | Model dan migration tersedia; tidak ada halaman web lengkap | Halaman web list/detail dan filter; validator CSV evakuasi | Adopsi halaman/controller web | Tabel dan model inti sama. Dataset koordinat berstatus belum terverifikasi tidak akan diperlakukan sebagai data lokasi resmi. |
| Profil | Web dan API baseline menyimpan nama/email/telepon; informasi tambahan mobile bersifat lokal | Profil web menulis delapan kolom domisili/tanggal lahir/gender | Pertahankan profil baseline dahulu | Migration tambahan tidak boleh dijalankan sebelum schema aktual dapat diperiksa dan dampaknya disetujui/ditetapkan. |
| Website publik | Landing dan halaman privat inti | Landing serta halaman bantuan/tentang, sidebar responsif dan navigasi modul | Adopsi halaman bantuan/tentang dan navigasi yang kompatibel | Fitur unik yang relevan; route tetap mengikuti auth publik/privat baseline. |

## 5. Tes dan kualitas pada audit awal

- `flutter analyze` pada project utama: **No issues found**.
- `php artisan test --compact` pada baseline utama: **73 tests, 72 passed, 1 failed, 325 assertions**. Kegagalan: `AuthenticationTest::test_web_logout_ends_the_session_and_protects_home_again`, redirect aktual `http://localhost:8000` sedangkan harapan test `http://localhost:8000/login`.
- Tes arsip belum dijalankan sebelum integrasi karena `vendor` dan dependensi runtime sengaja tidak diekstrak. Kode test arsip dibaca sebagai cakupan yang diharapkan; hasil cached/log arsip tidak dianggap sebagai hasil test baru.
- Bootstrap kedua versi mendaftarkan middleware `officer` dan merender exception API sebagai JSON. Service arsip memakai timeout, cache file, pembatasan host URL untuk feed tertentu, dan fallback ketika sumber eksternal gagal. Feed BNPB mengurai markup HTML yang dapat berubah; perlu fallback stale dan pesan sumber unavailable.
- `dom` tersedia pada PHP lokal. Feed eksternal tidak diandalkan sebagai syarat render halaman; pengujian akan memalsukan respons HTTP.

## 6. Risiko dan strategi integrasi

1. **Risiko migration profil:** jangan menyalin atau menjalankan migration arsip tanpa pemeriksaan schema MySQL aktual. Modul profil itu dikecualikan dari integrasi awal.
2. **Pemisahan konten:** controller alert arsip menampilkan berita dan kejadian gempa pada halaman peringatan. Integrasi akan menggunakan feed resmi tetapi mempertahankan pemisahan “peringatan aktif” dari “kejadian/berita”.
3. **Kontrak API:** route `/api/*`, resource, dan services mobile baseline dipertahankan; integrasi web menambah named routes tersendiri.
4. **Data evakuasi:** hanya data tabel yang ada ditampilkan sebagai data operasional. Kandidat dari dokumen perencanaan tidak dipromosikan sebagai koordinat terverifikasi.
5. **Perubahan lokal:** workspace utama memiliki banyak perubahan staged/unstaged sebelum audit. Integrasi dilakukan dengan menambah file dan mengedit route/view yang relevan saja; tidak ada reset, overwrite menyeluruh, perubahan `.env`, penghapusan data, atau push.
6. **Verifikasi:** tes backend menggunakan PHPUnit dengan SQLite in-memory sesuai `phpunit.xml`; tes terhadap MySQL aktual dan perangkat/browser nyata tetap dibedakan sebagai belum dilakukan jika lingkungannya tidak tersedia.

## 7. Keputusan akhir setelah integrasi

Integrasi selektif telah selesai dengan keputusan berikut:

- Lapisan web arsip diadopsi untuk notifikasi, berita/edukasi, peringatan, SOS, tempat evakuasi, kontak darurat, bantuan, dan tentang aplikasi. Tidak ada route API atau widget mobile dari arsip yang menggantikan baseline.
- Beranda awalnya masih menyisakan anchor mati dari baseline. Setelah membandingkan ulang view RAR, tautan navigasi/kartu diselaraskan ke named routes, menu dibuat dapat dibuka dengan klik, dan ikon notifikasi dihubungkan ke route serta jumlah notifikasi belum dibaca.
- Halaman peringatan hanya menyajikan CAP BMKG yang aktif dan masa berlakunya belum lewat. Gempa dan berita BNPB tidak dicampur ke daftar itu; keduanya tetap pada kanal informasi/berita yang sesuai.
- Guest diarahkan ke `/login` untuk route privat. Landing dan autentikasi publik tidak berubah; logout tetap mengakhiri sesi dan menuju landing.
- Jenis SOS gempa web memakai nilai `gempa`, sama dengan API/mobile. Data lama dengan nilai `gempa_bumi` tetap mendapat label tampilan yang benar.
- Laporan web yang tersimpan menghasilkan notifikasi aktivitas bagi pemiliknya dalam transaksi yang sama.
- Zona waktu tampilan WIB ditetapkan lewat konfigurasi `app.display_timezone` Asia/Jakarta; zona waktu proses aplikasi tetap UTC.
- Migration profil arsip tidak disalin. Tidak ada migration, reset, query tulis, perubahan `.env`, penghapusan data, atau push Git.

Verifikasi sesudah integrasi:

- `php artisan test --compact`: **135 passed, 699 assertions**.
- Tes integrasi fitur baru + autentikasi yang ditargetkan: **72 passed, 397 assertions**.
- `flutter analyze` dari `mobile/`: **No issues found**.
- `vendor/bin/pint --dirty --format agent`: berhasil.
- Tes memakai SQLite in-memory. MySQL aktual tidak tersedia saat pemeriksaan schema; browser dan perangkat Android tidak diuji manual. Halaman dan feed eksternal masih perlu verifikasi visual/operasional pada lingkungan yang menjalankan aplikasi.

Daftar perubahan file dan keputusan integrasi rinci ada di `INTEGRATION_CHANGELOG.md`.

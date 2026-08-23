# Catatan Fitur, Cara Kerja, dan Deploy RCI ke Render

Dokumen ini adalah catatan handoff untuk pemilik akun Render dan operator RCI. Ikuti urutannya dari atas ke bawah. Jangan mengirim file `.env`, password, token, atau API key melalui Git/WhatsApp.

Terakhir diperbarui: 23 Agustus 2026.

## 1. Ringkasan sistem

RCI adalah aplikasi konsultasi hukum multi-peran yang menjalankan frontend Blade/Vite dan REST API dalam satu aplikasi Laravel.

- Runtime: PHP 8.3, Laravel 12, Nginx, PHP-FPM, dan queue worker.
- Database: PostgreSQL.
- Autentikasi: Laravel Sanctum, verifikasi email, dan Google OAuth.
- Admin: Filament pada `/admin`.
- Deployment: satu Docker web service melalui `Dockerfile` dan `render.yaml`.
- File rahasia: private object storage S3-compatible direkomendasikan.
- Proses container: Supervisor menjalankan Nginx, PHP-FPM, dan queue worker bersama-sama.
- Health check: `GET /up`.

## 2. Fitur dan cara kerja

### Autentikasi dan kontrol akses

- Pengguna dapat mendaftar dengan email/password atau Google.
- Role yang tersedia: `client`, `paralegal`, `lawyer`, dan `admin`.
- Endpoint bisnis memerlukan token Sanctum, akun aktif, dan email terverifikasi.
- Lawyer/paralegal belum dapat menangani kasus sebelum dokumennya disetujui admin.
- Saat akun dinonaktifkan, token aktif dicabut dan login biasa maupun Google ditolak.

### Klien

- Membuat dan memantau kasus.
- Mengunggah dokumen kasus secara privat.
- Menerima, menyetujui, atau menolak quotation.
- Berkomunikasi dengan expert di ruang chat kasus.
- Membayar melalui wallet/escrow dan melihat status pembayaran.
- Mengonfirmasi penyelesaian, mengajukan sengketa, membatalkan kasus, serta memberi ulasan.
- Melaporkan pesan expert yang mengarahkan pembayaran keluar RCI.

### Paralegal

- Mengunggah KTP dan ijazah untuk verifikasi admin.
- Melihat dashboard/Kanban dan job marketplace.
- Melamar pekerjaan, memperbarui status kasus, dan mengunggah dokumen.
- Mengeskalasi kasus yang membutuhkan lawyer.
- Menggunakan chat kasus setelah terverifikasi.

### Lawyer

- Mengunggah KTP, ijazah, kartu advokat, selfie, dan CV opsional.
- Menangani kasus yang dieskalasi.
- Mengirim quotation dan melihat informasi pendapatan.
- Menggunakan chat kasus setelah terverifikasi.

### Pembayaran

- Top-up membuat invoice Xendit.
- Xendit mengirim callback ke `/api/v1/xendit/webhook/invoice`.
- Header `x-callback-token` diverifikasi sebelum callback diproses.
- Callback bersifat idempotent: pembayaran yang sudah diproses tidak dikreditkan dua kali.
- Pembayaran berhasil mengisi wallet dan membuat riwayat transaksi.
- Escrow menahan dana sampai alur kasus diselesaikan sesuai aturan aplikasi.

### AI dan RAG

- Chat AI publik dibatasi dengan rate limit.
- Model dapat memakai Gemini atau OpenRouter melalui environment variable.
- Dokumen pengetahuan `.txt`/`.md` dapat dimasukkan memakai perintah `rag:import`.
- Respons AI bukan pengganti pendapat hukum profesional; konten dan batas pemakaian tetap harus diawasi.

### Admin

Admin masuk melalui `/admin` untuk:

- mengelola pengguna dan status akun;
- memverifikasi dokumen lawyer/paralegal;
- memantau kasus;
- meninjau laporan pembayaran di luar RCI; dan
- mengonfirmasi pelanggaran atau menutup laporan sebagai tidak terbukti.

## 3. Proteksi pembayaran di luar RCI

Fitur ini adalah mekanisme triase dan audit, bukan pengganti pemeriksaan manusia.

### Alur otomatis

1. Lawyer/paralegal mengirim pesan di chat kasus.
2. Server menormalisasi teks dan mencari kombinasi sinyal pembayaran, bypass platform, rekening pribadi, nomor rekening/e-wallet, serta kontak eksternal.
3. Server menghitung skor risiko 0–100.
4. Skor di bawah 45: pesan dikirim tanpa flag.
5. Skor 45–69: pesan tetap dikirim, tetapi laporan otomatis dibuat untuk admin.
6. Skor 70 atau lebih: pesan diblokir, tidak masuk chat, dan bukti tetap dicatat untuk admin.

Bobot sinyal saat ini:

| Sinyal | Bobot |
| --- | ---: |
| Permintaan pembayaran/transfer/fee | 25 |
| Ajakan bypass atau membayar langsung | 45 |
| Rekening/e-wallet pribadi | 25 |
| Detail rekening atau nomor tujuan pembayaran | 35 |
| WhatsApp/Telegram/nomor telepon eksternal | 10 |

Kalimat perlindungan seperti “jangan transfer di luar RCI” dikecualikan agar tidak memicu flag palsu.

### Laporan manual klien

- Tombol laporan hanya tampil pada pesan lawyer/paralegal.
- Hanya klien pemilik kasus yang boleh melapor.
- Satu pesan tidak dapat dilaporkan berulang selama laporan masih aktif.
- Identitas pelapor tidak diberitahukan kepada expert melalui antarmuka klien.
- Isi pesan disalin sebagai bukti dan dienkripsi menggunakan `APP_KEY`.

### Tinjauan admin

Laporan masuk ke `/admin/compliance-flags` dengan status:

`pending` → `reviewing` → `confirmed` atau `dismissed`.

- `Terbukti & suspend`: laporan dikonfirmasi, akun expert dinonaktifkan, dan seluruh tokennya dicabut.
- `Tidak terbukti`: laporan ditutup dengan alasan reviewer.
- Catatan keputusan wajib diisi untuk menyimpan audit trail.

### Batasan yang harus diketahui

- Detektor saat ini hanya membaca teks chat, belum OCR gambar/PDF, audio, telepon, atau aplikasi eksternal.
- Skor dapat menghasilkan false positive/false negative; admin wajib memeriksa konteks kasus.
- Sistem tidak dapat mencegah komunikasi di luar platform setelah pengguna saling mengetahui kontak.
- Alur banding, pemberitahuan legal formal, dan retensi bukti terjadwal belum diotomatisasi.
- Hindari menulis isi chat ke log. `BROADCAST_CONNECTION=null` adalah konfigurasi aman saat chat masih memakai polling.

## 4. Urutan implementasi/deploy paling efektif

### Tahap A — Serah terima repository dan ownership

1. Gunakan repository privat.
2. Pastikan `.env`, credential JSON Google, dump database, dokumen KTP, dan secret tidak masuk commit.
3. Teman yang memiliki workspace Render memasukkan semua secret langsung di Render Dashboard.
4. Credential admin lama yang pernah tertulis di source code harus dianggap bocor dan tidak boleh dipakai kembali.
5. Tentukan siapa yang menjadi pemilik Google Cloud, Xendit, object storage, email, dan database. Hindari akun pribadi tanpa akses pemulihan tim.

### Tahap B — Siapkan layanan eksternal

Siapkan sebelum deploy:

1. Render PostgreSQL di workspace dan region yang sama dengan web service.
2. Bucket S3-compatible privat untuk KTP, ijazah, dan dokumen kasus.
3. OAuth Client bertipe Web Application di Google Cloud.
4. SMTP/Resend untuk verifikasi email dan reset password.
5. Xendit untuk pembayaran, jika pembayaran akan diuji.
6. Gemini/OpenRouter, jika fitur AI akan diaktifkan.

Render merekomendasikan URL PostgreSQL internal untuk service yang berada pada account dan region yang sama karena latensinya lebih rendah. Blueprint proyek menghubungkannya ke `DB_URL`, yaitu nama variabel yang benar-benar dibaca oleh konfigurasi Laravel. Lihat [dokumentasi Render Postgres](https://render.com/docs/postgresql-creating-connecting).

### Tahap C — Buat `APP_KEY` sekali

Jalankan lokal:

```bash
docker compose exec app php artisan key:generate --show
```

Salin output lengkap yang diawali `base64:` ke environment variable `APP_KEY` di Render.

Jangan mengganti atau menghapus `APP_KEY` setelah data produksi dibuat. Jika berubah, sesi dan bukti kepatuhan yang terenkripsi tidak dapat didekripsi lagi. Simpan salinan key dalam password manager tim, bukan di repository.

### Tahap D — Deploy Blueprint

1. Push commit ke GitHub/GitLab milik teman.
2. Di Render pilih **New Blueprint Instance** dan hubungkan repository.
3. Render membaca [`render.yaml`](../render.yaml) dari root repository.
4. Isi semua variable bertanda `sync: false` sebelum menyetujui deploy.
5. Pastikan database dan web service dibuat pada region yang sama.
6. Tunggu build Docker dan startup selesai.

Blueprint membuat web service, PostgreSQL, koneksi `DB_URL`, dan health check `/up`. Secret dengan `sync: false` disimpan sebagai environment variable Render dan tidak ditulis ke repository. Lihat [Render Blueprints](https://render.com/docs/infrastructure-as-code), [environment variables](https://render.com/docs/configure-environment-variables), dan [health checks](https://render.com/docs/health-checks).

`render.yaml` masih memakai paket database `free` untuk demo. Menurut dokumentasi Render saat dokumen ini dibuat, database gratis berakhir setelah 30 hari. Gunakan paket berbayar dengan backup/PITR sebelum memproses data sungguhan. Lihat [jenis layanan Render](https://render.com/docs/service-types).

## 5. Environment variable Render

### Wajib agar aplikasi dapat hidup

| Key | Nilai/cara mengisi |
| --- | --- |
| `APP_ENV` | `production` (sudah dari blueprint) |
| `APP_DEBUG` | `false` (sudah dari blueprint) |
| `APP_URL` | `https://nama-service.onrender.com`, tanpa slash akhir |
| `FRONTEND_URL` | Sama dengan `APP_URL` karena frontend dan API satu service |
| `APP_KEY` | Output `php artisan key:generate --show`; jangan diganti |
| `DB_CONNECTION` | `pgsql` (sudah dari blueprint) |
| `DB_URL` | Otomatis dari database pada blueprint; jangan ganti menjadi `DATABASE_URL` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `database` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` atau `info` saat diagnosis |
| `BROADCAST_CONNECTION` | `null` selama Reverb belum dijalankan |
| `ADMIN_EMAIL` | Email admin pertama |
| `ADMIN_PASSWORD` | Password acak minimal 12 karakter, idealnya 20+ |

`ADMIN_PASSWORD` hanya dipakai saat membuat admin pertama. Seeder tidak lagi menimpa password admin setiap container restart. Perubahan password berikutnya dilakukan melalui alur reset password, bukan melalui Git.

Disarankan juga menambah:

```dotenv
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Google OAuth

| Key | Nilai |
| --- | --- |
| `GOOGLE_CLIENT_ID` | Client ID dari Google Cloud |
| `GOOGLE_CLIENT_SECRET` | Client secret dari Google Cloud |
| `GOOGLE_REDIRECT_URI` | `https://nama-service.onrender.com/api/v1/auth/google/callback` |

Pada Google Auth Platform → Clients → Web Application, tambahkan:

- Authorized JavaScript origin: `https://nama-service.onrender.com`
- Authorized redirect URI: `https://nama-service.onrender.com/api/v1/auth/google/callback`

Redirect URI harus sama persis, termasuk `https`, hostname, path, huruf besar/kecil, dan slash. Google menjelaskan aturan ini pada [OAuth 2.0 untuk web server](https://developers.google.com/identity/protocols/oauth2/web-server).

Jika consent screen masih berstatus Testing, tambahkan akun penguji pada Audience. Untuk produksi publik, siapkan homepage, privacy policy, terms, authorized domain, dan proses verifikasi Google yang relevan.

### Penyimpanan dokumen

Konfigurasi yang direkomendasikan:

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=rci-documents
```

Untuk penyedia S3-compatible seperti Supabase, tambahkan sesuai provider:

```dotenv
AWS_ENDPOINT=https://<project-id>.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
```

- Bucket untuk KTP/ijazah/dokumen kasus harus privat.
- Jangan memasukkan secret service-role ke JavaScript/browser.
- Uji upload dan download admin setelah deploy.
- Dokumen expert dan kasus mengikuti `FILESYSTEM_DISK` yang dikonfigurasi.
- Avatar profil yang diunggah manual saat ini masih menggunakan disk `public` lokal; gunakan persistent disk atau lanjutkan implementasi disk publik terpisah jika avatar harus bertahan.

Alternatif demo adalah `FILESYSTEM_DISK=local` dengan Render Persistent Disk yang di-mount ke `/var/www/html/storage/app`. Tanpa persistent disk, filesystem Render bersifat sementara dan file hilang saat restart/redeploy. Persistent disk juga membatasi scaling ke satu instance dan menonaktifkan zero-downtime deploy. Untuk dokumen sensitif, object storage lebih fleksibel. Lihat [dokumentasi persistent disk Render](https://render.com/docs/disks).

### Email

Isi `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, dan `MAIL_FROM_NAME` sesuai provider. Tanpa mail provider, registrasi email/password tidak dapat menyelesaikan verifikasi dan reset password tidak terkirim.

Jangan memakai password utama Gmail. Gunakan app password atau provider transactional email milik organisasi.

### Xendit

```dotenv
XENDIT_SECRET_KEY=...
XENDIT_WEBHOOK_TOKEN=...
XENDIT_INVOICE_DURATION=86400
XENDIT_SUCCESS_REDIRECT_URL=https://nama-service.onrender.com/payment/success
XENDIT_FAILURE_REDIRECT_URL=https://nama-service.onrender.com/payment/failed
```

Daftarkan callback invoice di dashboard Xendit:

```text
https://nama-service.onrender.com/api/v1/xendit/webhook/invoice
```

Mulai dengan mode development/sandbox. Cocokkan token callback dengan `XENDIT_WEBHOOK_TOKEN`, lalu uji satu invoice berhasil dan satu invoice expired.

### AI

Aktifkan salah satu atau keduanya:

```dotenv
GEMINI_API_KEY=...
GEMINI_MODEL=...
OPENROUTER_API_KEY=...
OPENROUTER_MODEL=...
```

Terapkan limit biaya dan monitoring pada dashboard provider. Jangan menyimpan API key di frontend.

## 6. Apa yang terjadi saat container menyala

[`docker/start.sh`](../docker/start.sh) menjalankan urutan berikut:

1. Memastikan `APP_KEY` tersedia; production gagal start bila key kosong.
2. Membuat cache config, route, dan view.
3. Menjalankan `php artisan migrate --force`.
4. Membuat admin pertama jika environment admin lengkap.
5. Memasukkan/memperbarui setting SOP.
6. Membuat storage link.
7. Menjalankan Nginx, PHP-FPM, dan queue worker melalui Supervisor.

Karena migrasi dijalankan otomatis, deploy baru harus ditinjau agar migrasinya backward-compatible. Ambil backup database sebelum perubahan skema berisiko.

## 7. Checklist setelah deploy

Jangan menganggap deploy selesai hanya karena halaman utama tampil.

- [ ] `https://domain/up` mengembalikan HTTP 200.
- [ ] Log startup menunjukkan migration dan setting seed selesai.
- [ ] `/admin` dapat dimasuki dengan admin baru.
- [ ] Google login berhasil dan kembali ke `/auth/google/callback`.
- [ ] Registrasi email/password menerima email verifikasi.
- [ ] Admin dapat menyetujui akun lawyer/paralegal.
- [ ] Upload/download KTP dan dokumen kasus tetap ada setelah manual redeploy.
- [ ] Klien dapat membuat kasus dan expert dapat membuka kasus yang sah.
- [ ] Pesan chat normal terkirim.
- [ ] Ajakan pembayaran langsung berisiko tinggi diblokir.
- [ ] Klien dapat melaporkan pesan expert.
- [ ] Admin dapat membuka bukti, memilih “Tidak terbukti”, dan mengisi catatan.
- [ ] Dengan akun test, admin dapat memilih “Terbukti & suspend” lalu memastikan login/token ditolak.
- [ ] Xendit webhook valid diterima dan token salah ditolak.
- [ ] Wallet tidak dikreditkan dua kali saat callback yang sama dikirim ulang.
- [ ] `APP_DEBUG=false` dan tidak ada secret/isi chat pada log.

Gunakan akun dan transaksi sandbox untuk seluruh uji suspend/pembayaran.

## 8. Operasional dan keamanan

- Batasi akses Render, Google Cloud, Xendit, dan object storage dengan MFA.
- Gunakan password manager organisasi dan pisahkan secret staging/production.
- Jangan mengirim database production kepada teman melalui file chat.
- Aktifkan backup PostgreSQL dan uji pemulihannya.
- Tetapkan jadwal admin memeriksa laporan `pending`/`reviewing`.
- Buat SOP banding dan retensi bukti sesuai nasihat hukum serta kebijakan privasi RCI.
- Catat siapa yang menyetujui suspend dan alasannya.
- Rotasi secret segera jika pernah muncul pada commit, screenshot, log, atau chat.
- Lakukan uji keamanan, privasi, pembayaran, dan legal compliance sebelum menerima pengguna nyata.

## 9. Troubleshooting cepat

### Deploy gagal dengan `APP_KEY is required`

Isi `APP_KEY` menggunakan output `php artisan key:generate --show`. Jangan menggunakan string acak Render tanpa prefix/format key Laravel.

### Database connection refused atau tabel tidak ada

Pastikan variable bernama `DB_URL`, database dan service berada pada region/workspace yang sama, lalu periksa log migration. Blueprint proyek sengaja tidak memakai `DATABASE_URL` karena konfigurasi aplikasi membaca `DB_URL`.

### Google menampilkan `missing client_id`

Pastikan `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` terisi pada service yang aktif, lalu lakukan manual deploy/restart agar config cache dibuat ulang.

### Google menampilkan `redirect_uri_mismatch`

Samakan `GOOGLE_REDIRECT_URI` di Render dengan Authorized redirect URI Google secara persis. Jangan memakai callback frontend `/auth/google/callback` sebagai callback Google; callback Google adalah `/api/v1/auth/google/callback`.

### Login Google kembali dengan `google_auth_failed`

Set sementara `LOG_LEVEL=info`, baca log callback, periksa client secret, redirect URI, consent screen, dan test users. Kembalikan log ke `warning` setelah diagnosis.

### File hilang setelah redeploy

Filesystem service masih ephemeral. Gunakan private S3-compatible storage atau persistent disk pada mount path yang benar.

### Chat tampil tetapi tidak real-time

Implementasi antarmuka saat ini melakukan polling. `BROADCAST_CONNECTION=null` memang tidak menjalankan WebSocket. Reverb membutuhkan service dan konfigurasi terpisah sebelum diaktifkan.

## 10. File penting untuk handoff

- [`render.yaml`](../render.yaml): blueprint Render.
- [`Dockerfile`](../Dockerfile): image production.
- [`docker/start.sh`](../docker/start.sh): migration/seed/startup.
- [`.env.example`](../.env.example): daftar variable tanpa nilai rahasia.
- [`routes/api.php`](../routes/api.php): daftar endpoint API.
- [`docs/API_DOCUMENTATION.md`](API_DOCUMENTATION.md): catatan endpoint lama; cocokkan kembali dengan route terkini saat mengembangkan integrasi baru.

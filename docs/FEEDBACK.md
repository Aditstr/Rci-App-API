# Kritik & Saran

Pengunjung membuka `/kritik-saran` melalui footer dan mengirim kategori, pesan,
serta email opsional. Pengiriman tidak memerlukan login. Jika pengunjung memiliki
token Sanctum yang valid, masukan dikaitkan dengan akun tersebut.

Kategori tersedia: `kritik`, `saran`, dan `kendala_teknis`. Pesan harus berisi
10–5.000 karakter. Endpoint `POST /api/v1/feedback` dibatasi 5 permintaan per jam
per IP dan hanya menerima kolom masukan publik. Tidak ada endpoint pembacaan
masukan untuk publik.

Admin aktif dapat membuka **Layanan Pengguna → Kritik & Saran** pada
`/admin/kritik-saran`, membaca pesan, memfilter kategori/status, dan mengubah
status menjadi **Baru**, **Ditinjau**, atau **Selesai**. Catatan internal, admin
peninjau, dan waktu peninjauan dicatat. Isi pesan serta email pengirim tidak dapat
diubah dari formulir tindak lanjut. Fitur ini tidak mengirim email otomatis.

## Deployment

Fitur ini mencakup frontend, backend, dan tabel database baru. ZIP aset frontend
saja tidak cukup untuk mengaktifkannya.

1. Perbarui kode aplikasi dari GitHub.
2. Jalankan `php artisan migrate --force` untuk menambahkan tabel `feedback`.
3. Jalankan `npm ci` dan `npm run build`, atau unggah isi paket build yang sesuai
   ke `public_html/public/build` pada Hostinger.
4. Jika menggunakan cache konfigurasi/rute/view, perbarui dengan
   `php artisan config:cache`, `php artisan route:cache`, dan
   `php artisan view:cache`.

Dockerfile Render menjalankan build frontend. Skrip `docker/start.sh` menjalankan
migrasi pada saat startup deployment, sehingga deployment baru akan menambahkan
tabel ini otomatis.

## Pengujian

Jalankan `php artisan test --filter=Feedback` dengan database pengujian PostgreSQL
yang terpisah. Migrasi pembayaran lama proyek menggunakan sintaks PostgreSQL
yang tidak kompatibel dengan SQLite. Pengujian memakai `RefreshDatabase` dan
akan membangun ulang skema database pengujian.

Cakupan: pengiriman tamu/akun, validasi, batas pengiriman, perlindungan kolom
internal, akses admin, pembaruan status/catatan, tampilan teks pengguna, dan akses
langsung ke halaman frontend. Jalankan `npm run build` untuk memeriksa kompilasi
komponen Vue.

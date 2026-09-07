# Deployment Hostinger

Panduan ini berlaku jika seluruh proyek ditempatkan di `public_html`, seperti
instalasi royscounselindo.com. File `.htaccess` pada akar repo meneruskan permintaan
ke folder `public`; `public/.htaccess` kemudian menangani rute Laravel.
Kedua file tersebut harus ikut dalam deployment, termasuk ketika mengunggah ZIP.
Hostinger menjelaskan konfigurasi ini pada panduan
[403 untuk Laravel](https://www.hostinger.com/support/1583304-how-to-fix-a-403-forbidden-error-at-hostinger/).

Struktur yang diperlukan:

```text
public_html/
├── .htaccess
├── .env
├── artisan
├── app/
├── vendor/
└── public/
    ├── .htaccess
    ├── index.php
    └── build/
        ├── manifest.json
        └── assets/
```

## Pembaruan kode

1. Jalankan `git pull origin main` dari folder proyek `public_html`, atau gunakan
   deployment Git Hostinger dengan direktori tujuan tersebut. Jika Git menolak
   karena `.htaccess` manual belum dilacak, cadangkan file itu di luar
   `public_html`, pindahkan file manual tersebut, lalu ulangi pull. File dari
   repo akan menggantikannya.
2. Jalankan `composer install --no-dev --prefer-dist --optimize-autoloader`.
3. Jalankan `npm ci` dan `npm run build`. Jika Node.js tidak tersedia di hosting,
   build dari revisi kode yang sama di komputer lokal, lalu unggah seluruh
   `public/build` ke `public_html/public/build`. Folder ini tidak disimpan di Git.
4. Jalankan `php artisan migrate --force` untuk menerapkan migrasi baru, termasuk
   tabel kritik dan saran.
5. Perbarui cache aplikasi dengan `php artisan config:cache`,
   `php artisan route:cache`, dan `php artisan view:cache`.
6. Periksa beranda dan `/kritik-saran` setelah deployment selesai.

Pertahankan `.env`, file unggahan pengguna, dan data `storage` saat memperbarui
kode. Gunakan konfigurasi produksi ketika membangun aset, karena nilai `VITE_*`
dimasukkan ke hasil build. Dockerfile Render tidak otomatis dijalankan oleh
deployment Git pada hosting PHP Hostinger.

## Jika error muncul kembali

- **403 pada beranda:** pastikan `public_html/.htaccess` dan
  `public_html/public/index.php` ada, lalu periksa direktori tujuan dan log
  deployment Hostinger. Commit Git sendiri tidak mengubah file server; perubahan
  di server terjadi saat pull atau deployment. Pull biasa tidak menghapus file
  yang tidak dilacak, sehingga hilangnya konfigurasi perlu ditelusuri dari proses
  deployment yang digunakan.
- **500 dengan `Vite manifest not found`:** pastikan hasil build berada tepat di
  `public_html/public/build/manifest.json`, beserta semua asetnya.
- **500 lainnya:** baca entri error terbaru di `storage/logs/laravel.log` atau log
  PHP hosting sebelum mengubah konfigurasi.

Lihat [fitur kritik dan saran](FEEDBACK.md) untuk rincian fitur dan pengujiannya.

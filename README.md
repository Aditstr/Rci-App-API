<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# RCI App API backend

Ini adalah repositori antarmuka pemprograman aplikasi (API) backend berbasis Laravel untuk **RCI App** (ROYS Council Indonesia), sebuah ekosistem konsultasi hukum cerdas. API ini memfasilitasi integrasi dan pengelolaan data antar Klien, Paralegal, Pengacara (Lawyer), serta layanan dukungan Kecerdasan Buatan (AI).

## 💡 Fitur Utama

Sistem ini memfasilitasi alur manajemen kasus secara *end-to-end* dengan peran ganda (*Multi-Role*):
- **Otentikasi & Verifikasi Keamanan:** Mengamankan setiap *endpoint* melalui autentikasi token **Laravel Sanctum** dengan prasyarat *Email Verification*.
- **Integrasi Konsultasi Hukum AI:** *Engine* AI berjenjang dengan pembatasan API per hari (*throttle*) untuk kelas *Freemium* dan akses instan berbasis pengetahuan luas untuk kelas *Pro Member*.
- **Sistem Pembayaran Terpadu (Escrow & Wallet):** Pengelolaan deposit virtual (dompet) bagi klien, sistem pembekuan / pelepasan *escrow* dana layanan, dan rekam riwayat saldo pengguna.
- **Modul Manajemen Klien:** Pendaftaran kasus baru, pemantauan *timeline* secara real-time.
- **Modul Workspace Paralegal:** Pengelolaan daftar penugasan berformat Kanban-board, serta mekanisme *Job Marketplace* guna melamar kasus publik.
- **Modul Dashboard Ahli Hukum (Lawyer):** Pendelegasian (*Escalation*), fitur pengajuan penawaran harga layanan hukum (*Quotation* & *Fee Split*), serta riwayat royalti *Professional Revenue*.

## 🚀 Teknologi yang Digunakan
* **Framework:** [Laravel 11.x](https://laravel.com/) (PHP)
* **Database:** MySQL / PostgreSQL (terkoneksi Eloquent ORM)
* **Autentikasi:** Laravel Sanctum
* **Arsitektur:** RESTful API

## 📋 Prasyarat Instalasi (Lokasi)
Pastikan sistem komputer Anda telah dipasang beberapa komponen vital:
- PHP >= 8.2
- Composer
- Database Engine (misal: MySQL, Laragon, XAMPP, dsb)

## 🛠️ Cara Instalasi & Menjalankan

Langkah-langkah untuk menyalin (clone) repositori dan menjalankannya pada *server* pengembangan lokal:

1. **Jalankan *Clone* Repositori**
```bash
git clone https://github.com/Aditstr/Rci-App-API.git
cd Rci-App-API
```

2. **Instal Dependensi Composer**
```bash
composer install
```

3. **Konfigurasi Lingkungan (`.env`)**
Salin *template* konfigurasi *environment*:
```bash
cp .env.example .env
```
Lalu buka file `.env` di teks editor, sesuaikan pengaturan kredensial basis data (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) Anda.

4. **Hasilkan Kunci Aplikasi Laravel (*App Key*)**
```bash
php artisan key:generate
```

5. **Jalankan Migrasi Database**
```bash
php artisan migrate
```
*(Opsional)* Jika Anda memiliki data Dummy awal di Seeder, jalankan `php artisan migrate --seed`.

6. **Hidupkan Server Lokal Laravel**
```bash
php artisan serve
```
Secara otomatis, REST API akan beroperasi mencakup URL Basis (seperti `http://localhost:8000/api`).

## 📚 Dokumentasi API
Anda dapat menjelajahi titik-akhir API (*Endpoints*) serta parameternya dengan mengacu pada rancangan kontroler yang ada pada direktori:
* `routes/api.php`
* *Documentation Markdown yang menyertai proyek pada internal tim.*

## 🔒 Lisensi
RCI App dan repositori API ini bersifat privat / hak kepemilikan terbatas (*Proprietary software*).

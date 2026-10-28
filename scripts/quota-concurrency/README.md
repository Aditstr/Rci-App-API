# Quota Concurrency Check (PostgreSQL lokal)

Membuktikan `AiQuotaService::consume()` menegakkan limit 3 pertanyaan ketika
10 worker memanggilnya bersamaan.

## Prasyarat

- PostgreSQL lokal di `127.0.0.1:5432`.
- Database `rci_quota_test` sudah ada. Harness tidak membuat atau menghapus database.
- Ekstensi PHP `pdo_pgsql`.

Kredensial lokal default: user `postgres`, password kosong. Jika berbeda, set
environment variable (jangan tulis password ke file):

```powershell
$env:QUOTA_PG_USER = "postgres"
$env:QUOTA_PG_PASSWORD = "..."
```

## Menjalankan

```powershell
php scripts\quota-concurrency\run.php --check
php scripts\quota-concurrency\run.php
```

`--check` hanya memeriksa target dan tidak menulis apa pun. Run penuh menjalankan
migration pending pada `rci_quota_test`, membuat user sintetis, menjalankan 10
worker, lalu membersihkan data yang dibuatnya.

## Pengaman

- Guard menolak jika driver bukan `pgsql`, database aktif bukan persis
  `rci_quota_test`, server bukan loopback, atau cache bukan database pada
  koneksi `quota_test`.
- Koneksi diarahkan ke target sebelum Laravel memuat `.env`; harness tidak
  membaca atau mencetak kredensial dari `.env`.
- Tidak memakai `migrate:fresh`, `db:wipe`, seeder, atau menyentuh `rci_app`,
  `rci_db`, maupun Supabase.
- Worker hanya memanggil `AiQuotaService::consume()`, tidak memanggil provider AI.
- Cleanup hanya menghapus user/subscription/cache key yang dibuat run ini.

## Hasil yang diharapkan

```
Accepted: 3
Denied:   7 (quota_exhausted: 7)
Counter:  3
RESULT: PASS
```

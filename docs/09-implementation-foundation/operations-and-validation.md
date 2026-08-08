# Operasi dan Bukti Validasi

Tanggal validasi akhir: 2026-08-07. Semua command aplikasi dijalankan dari root repository melalui Docker Compose.

## Migration

Migration biasa berhasil pada database development dan test:

- `2026_08_07_000001_create_organizational_foundation` — status `Ran`;
- `2026_08_07_000002_widen_activity_log_morph_keys` — status `Ran`.

Percobaan development pertama menemukan urutan DDL self-foreign-key PostgreSQL; transaksi gagal dan rollback. Migration diperbaiki dengan menambahkan FK parent setelah tabel/primary key terbentuk, kemudian berhasil tanpa `migrate:fresh`, wipe, reset, penghapusan volume, atau kehilangan data.

## Quality gate

| Pemeriksaan | Command | Hasil |
|---|---|---|
| PHP format | `docker compose exec -T app ./vendor/bin/pint` | PASS; 3 style issue diperbaiki mekanis |
| Backend test | `docker compose exec -T app php artisan test` | PASS; 10 test, 48 assertion |
| Frontend lint | `docker compose exec -T frontend npm run lint` | PASS; 0 warning/error |
| Frontend type-check | `docker compose exec -T frontend npm run type-check` | PASS |
| Frontend unit | `docker compose exec -T frontend npm run test:unit -- --run` | PASS; 3 file, 6 test |
| Frontend production build | `docker compose exec -T frontend npm run build` | PASS |
| Route API/Filament | `php artisan route:list --path=api/v1` dan `--path=admin` melalui app container | PASS; Fortify/API dan 13 route panel termuat |

## Service dan endpoint

| Komponen | Hasil akhir |
|---|---|
| app | running, healthy |
| frontend | running; `/login` HTTP 200 |
| nginx | running; backend tersedia pada port 8000 |
| postgres | running, healthy |
| redis | running, healthy |
| mailpit | running, healthy |
| horizon | running; `horizon:status` menyatakan aktif |
| scheduler | running; `horizon:snapshot` terdaftar setiap 5 menit |

- `GET /api/v1/health/live`: `status=ok`.
- `GET /api/v1/health/ready`: database, Redis, dan queue `ok`.
- `GET /sanctum/csrf-cookie`: HTTP 204.
- `GET /api/v1/me` tanpa sesi: HTTP 401 `application/problem+json` dengan code dan request ID.
- `/admin/login`: HTTP 200.
- 150 baris log terbaru Horizon dan scheduler: tidak ditemukan ERROR, FAIL, exception, fatal, atau SQLSTATE.

## URL lokal

- SPA production foundation: `http://localhost:5173/login`
- Filament: `http://localhost:8000/admin`
- Health: `http://localhost:8000/api/v1/health/ready`
- Mailpit: `http://localhost:8025`

## Catatan kesiapan

Status **READY WITH NOTES**. Runtime dan quality gate siap; provisioning credential admin dan keputusan organisasi/identity production harus diselesaikan sebelum akses bersama. Tidak ada dasar teknis yang memblokir Phase berikutnya, tetapi Phase 10 belum dimulai.

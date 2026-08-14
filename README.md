# Sistem Survei Mutu LPMPP

Monorepo Laravel, Filament, dan Vue untuk Sistem Informasi Survei Mutu, Analitik, dan Tindak Lanjut LPMPP.

Seluruh runtime aplikasi dijalankan melalui Docker Compose. Status onboarding ada di `docs/00-project-control/progress.md`.

## Login lokal

Semua role menggunakan satu halaman login: <http://localhost:5173/login>. Pengguna dengan permission `admin.panel.access` diarahkan ke Filament; pengguna lain tetap di aplikasi Vue.

Pada mode development lokal, setiap kali URL login di atas dibuka, aplikasi membuat hostname `session-*.localhost` yang unik. Cookie tetap HttpOnly tetapi terpisah per hostname, sehingga dua tab dapat login dengan dua akun berbeda. Untuk membuat sesi baru, buka kembali URL `http://localhost:5173/login`; jangan menduplikasi URL `session-*.localhost` dari tab yang sudah login.

Jalankan akun demo lokal dengan:

```bash
docker compose exec -T app php artisan db:seed
```

Perintah tersebut membuat empat akun demo, campaign lintas periode, snapshot analitik agregat, temuan, dan tindak lanjut untuk mengisi Dashboard Mutu. Dataset tidak membuat jawaban responden individual.

| Role | Email | Password |
|---|---|---|
| `super_admin` | `superadmin@example.test` | `password` |
| `admin_lpmpp` | `admin.lpmpp@example.test` | `password` |
| `leader` | `pimpinan@example.test` | `password` |
| `respondent` | `responden@example.test` | `password` |

Seeder akun demo ditolak pada environment `production`. Ganti password default sebelum memakai akun di luar lingkungan lokal/test.

# Ringkasan Fondasi Implementasi

## Backend

| Area | Implementasi Phase 09 |
|---|---|
| Konfigurasi | Environment development/test terpisah; CORS credentialed berbasis allowlist origin; Sanctum stateful API; cookie aman dapat dikonfigurasi. |
| Identitas | `users` mempertahankan bigint internal untuk kompatibilitas package dan menambah UUIDv7 `public_id` untuk kontrak eksternal. Akun mempunyai status aktif dan waktu login terakhir. |
| Organisasi | `organizational_units` berhierarki, pivot user–unit, unit utama, dan mode scope `self`/`subtree`. |
| Otorisasi | Spatie role/permission, `UserPolicy`, `OrganizationalUnitPolicy`, middleware akun aktif dan organizational scope. Query resource ikut dibatasi scope. |
| Autentikasi | Fortify + Sanctum stateful session cookie, CSRF cookie, rate limit login, pesan gagal generik, logout dan invalidasi sesi. Registrasi publik/passkey tidak diaktifkan. |
| API | Namespace `/api/v1`, Laravel API Resource, request ID, dan `application/problem+json` untuk 401/403/404/422/429. |
| Audit | Spatie Activitylog untuk login/logout, perubahan user dan unit; atribut password/secret tidak dicatat. Morph key dilebarkan agar mendukung subjek UUID. |
| Operasi | Redis queue, Horizon gate, `horizon:snapshot` setiap lima menit, liveness/readiness untuk database/Redis/queue. |
| Data awal | Role, permission, unit organisasi, dan user `.example.test` fiktif; tidak ada data mahasiswa asli. |
| Test | Autentikasi sesi, akun nonaktif, Problem Details, policy/panel access, health/audit, dan organizational scope. |

## Filament

Panel `/admin` hanya dapat diakses user aktif dengan permission `admin.panel.access`. Resource berikut dibuat dengan query dan aksi sesuai permission/scope:

- user, termasuk role dan keanggotaan unit; password bersifat write-only dan tidak dapat direveal;
- role dan permission; role `super_admin` dilindungi dari perubahan oleh user tanpa scope global;
- unit organisasi dengan parent yang berada dalam scope;
- widget status environment, database, Redis, dan queue tanpa host, credential, key, atau secret.

## Frontend

- shell aplikasi responsif dengan skip link, sidebar/drawer, logout nyata, dan navigasi berdasarkan permission efektif;
- router guard untuk authenticated, guest-only, dan permission route;
- Pinia auth store yang mengambil `/api/v1/me` dan tidak menyimpan token di local/session storage;
- Axios `withCredentials` dan `withXSRFToken`; login selalu diawali `/sanctum/csrf-cookie`;
- normalisasi Problem Details termasuk field pointer dan request ID;
- primitive `BaseButton`, `BaseAlert`, dan `FormField` memakai design token Phase 08;
- login, dashboard fondasi, status sistem, dan halaman 403 production-ready pada batas Phase 09.

Rute fixture Phase 08 tetap tersedia sebagai referensi UI dan tidak dianggap fitur survei production.

## Batas yang sengaja dipertahankan

- Tidak ada model/migration/endpoint untuk survei, instrumen, respons, analisis, AI, report, finding, atau action.
- Tidak ada integrasi SSO/SIAKAD/provider AI/email production.
- Tidak ada API key atau secret di database, response, widget, seed, test output, maupun dokumentasi.
- Tidak ada dependency baru pada Phase 09; seluruh implementasi menggunakan package baseline yang sudah terpasang.

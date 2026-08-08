# Authentication dan Authorization Foundation

## Alur SPA session-cookie

1. Browser memanggil `GET /sanctum/csrf-cookie` dengan credentials.
2. Browser mengirim `POST /api/v1/auth/login` dengan email, password, remember, cookie XSRF, dan header XSRF otomatis dari Axios.
3. Fortify menormalkan email, memverifikasi hash dan `is_active`, lalu meregenerasi sesi.
4. SPA memanggil `GET /api/v1/me`; response Laravel API Resource memuat public ID, role, permission efektif, dan unit yang boleh diketahui user.
5. Backend tetap menjadi enforcement point; menu dan guard frontend hanya lapisan UX.
6. Logout melalui `POST /api/v1/auth/logout`, sesi diinvalidasi, token CSRF diregenerasi, dan state Pinia dibersihkan.

Login gagal selalu memakai pesan generik agar status email tidak dapat dienumerasi. Rate limit awal adalah lima percobaan per menit per kombinasi email dan IP. Nilai final masih memerlukan persetujuan keamanan/operasi.

## Role baseline

| Role | Tujuan awal |
|---|---|
| `super_admin` | Administrasi platform dengan scope global yang diberikan eksplisit. |
| `admin_lpmpp` | Administrasi operasional LPMPP pada unit yang ditugaskan. |
| `leader` | Akses pimpinan sesuai scope; belum ada dashboard bisnis Phase 09. |
| `reviewer` | Review metodologi/instrumen pada phase berikutnya. |
| `pic` | Pelaksana tindak lanjut pada phase berikutnya. |
| `verifier` | Verifikasi bukti pada phase berikutnya. |
| `respondent` | Akses responden pada phase berikutnya. |

Role tidak otomatis memperluas data scope. Efektivitas akses adalah irisan: akun aktif, permission, organizational scope, policy object, dan state object bila kelak tersedia.

## Organizational scope

- `organization.scope.all`: semua unit aktif.
- membership `self`: unit yang ditugaskan saja.
- membership `subtree`: unit yang ditugaskan beserta turunannya.
- Query Filament dan API mengambil unit yang diizinkan dari service yang sama.
- User hanya dapat dilihat/diubah bila berbagi sekurang-kurangnya satu unit yang berada dalam scope aktor, kecuali aktor mempunyai scope global.

Traversal hierarchy in-memory cukup untuk struktur awal yang kecil. Ganti dengan recursive CTE/materialized path hanya bila ukuran hierarchy atau profiling menunjukkan kebutuhan.

## Guardrail

- Public API menggunakan UUIDv7 `public_id`; bigint internal tidak diekspos sebagai identifier kontrak.
- Password hanya di-hash, write-only pada form, tersembunyi dari serialisasi, dan dikecualikan dari audit.
- Panel, route, dan resource memeriksa permission di server.
- Endpoint readiness tidak mengungkap hostname, database name, Redis address, atau credential.
- CORS production harus memakai daftar origin eksplisit; wildcard credentialed tidak diizinkan.

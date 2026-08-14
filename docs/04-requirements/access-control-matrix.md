# Role, Permission, and Data-Scope Matrix

Versi: **2.0 — 2026-08-08**
Model: **empat role tetap + organizational scope + object state; deny-by-default**

## Role aktif

| Role | Fungsi | Scope dan batas utama |
|---|---|---|
| `super_admin` | Konfigurasi platform, role/permission, status sistem, secret AI, dan seluruh operasi aplikasi. | Scope global; kebijakan privasi tetap melarang jawaban individual tampil pada pimpinan dan dashboard agregat. |
| `admin_lpmpp` | Mengelola unit/user, instrumen, survei, populasi, analisis, report, AI, temuan, action, review, approval, release, dan verification. | Hanya unit yang ditugaskan. Operasi yang mensyaratkan pemisahan tugas tetap harus dilakukan akun Admin LPMPP lain. |
| `leader` | Membaca campaign, dashboard/report agregat, AI yang sudah boleh ditampilkan, notifikasi, temuan, action, dan dashboard tindak lanjut. | Hierarki organisasi yang dipimpin; read-only dan tidak boleh melihat jawaban individual. |
| `respondent` | Mengisi survei yang eligible, melanjutkan draft, submit sekali, menerima receipt, dan melihat riwayat yang diizinkan. | Hanya undangan/sesi/respons miliknya; tidak dapat membaca hasil agregat maupun respons pihak lain. |

Istilah PIC dan verifier pada fitur tindak lanjut adalah **tanggung jawab assignment**, bukan role akun. Dua akun `admin_lpmpp` yang berbeda dapat ditetapkan sebagai pelaksana dan pemeriksa agar self-verification tetap ditolak.

## Ringkasan permission efektif

| Kelompok permission | `super_admin` | `admin_lpmpp` | `leader` | `respondent` |
|---|:---:|:---:|:---:|:---:|
| Panel administrasi | ✓ | ✓ | — | — |
| Status sistem | ✓ | ✓ | read | — |
| Horizon, global organization scope | ✓ | — | — | — |
| Unit dan user | penuh | sesuai scope | unit read | — |
| Role dan permission | penuh | role read | — | — |
| Instrumen dan validasi | penuh | penuh termasuk approval | — | — |
| Campaign dan populasi | penuh | penuh termasuk approval/publish | campaign read | eligible sendiri |
| Analisis dan release | penuh | penuh | released read | — |
| Report dan export | penuh | penuh | released read/export | — |
| Konfigurasi AI | penuh | penuh kecuali secret governance global | — | — |
| Eksekusi/review AI | penuh | penuh | approved read | — |
| Temuan dan tindak lanjut | penuh | penuh termasuk verification | read-only | — |
| Notifikasi | ✓ | ✓ | ✓ | ✓ |

Jumlah permission hasil seeder: `super_admin` 52, `admin_lpmpp` 47, `leader` 10, dan `respondent` 1. Alur respons responden juga diperiksa oleh autentikasi, eligibility, invitation/token, ownership, dan state, bukan hanya permission bernama.

## Urutan evaluasi akses

1. Akun aktif dan sesi terautentikasi.
2. Permission operasi sesuai.
3. Organizational scope mencakup resource.
4. Assignment dan object state mengizinkan operasi.
5. Privacy mode, reporting threshold, dan suppression diterapkan.
6. Audit dicatat untuk operasi sensitif.

Menu Vue/Filament hanya lapisan UX. Backend policy dan middleware tetap menjadi enforcement point.

## Pemisahan tugas dalam empat role

- Pembuat instrumen tidak boleh menjadi satu-satunya approver instrumen yang sama.
- Peminta analysis/report tidak boleh me-release hasilnya sendiri ketika policy melarang.
- Pelaksana action tidak boleh memverifikasi action yang sama.
- Pembuat hasil AI tidak boleh menyetujui hasilnya sendiri.
- `leader` hanya menerima hasil agregat yang released dan tidak suppressed.
- `super_admin` tidak otomatis membolehkan jawaban individual masuk ke dashboard pimpinan.

# Laporan Validasi Phase 12

Tanggal: 2026-08-08

## Golden dataset

Dataset Likert fiktif `[1,2,3,4,5]` menghasilkan mean/median 3, sample SD 1,58, top-two box 40%, dan skor normalisasi 50. Dataset 30 baris tiga item identik menghasilkan Cronbach alpha 1,000. Golden test juga mengunci hasil SERVQUAL gap 0, CSI internal 70, IKM 75/C, serta aturan IPA.

## Skenario keamanan dan lifecycle

- cache berdasarkan input hash dan formula version;
- separation of duties pada release;
- organizational scope menolak survey dari unit lain;
- small sample tidak dapat dirilis;
- leadership endpoint hanya mengembalikan agregat released;
- export idempotent, private, tercatat audit, memiliki expiry, dan tiket download sekali pakai.

## Gerbang kualitas

- Laravel Pint: 181 file lulus.
- Backend: 35 test, 197 assertion lulus.
- Frontend unit: 4 test file, 9 test lulus.
- Playwright: 15 test lulus pada Chromium, Firefox, dan WebKit.
- ESLint/Oxlint, Vue TypeScript type-check, dan Vite production build: lulus.
- Migrasi `000002` dan `000003` diterapkan pada PostgreSQL development: lulus.

Catatan environment: binary Firefox/WebKit dipasang ke cache Playwright. Karena akun shell tidak memiliki sudo untuk library host WebKit, tiga library runtime paket distro diekstrak sementara ke bundle browser saat validasi; tidak ada binary library yang dimasukkan ke source tree.

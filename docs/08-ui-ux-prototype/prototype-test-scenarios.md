# Prototype Test Scenarios

## 1. Scope dan metode

Target: Chrome/Firefox/WebKit modern, desktop 1280 px dan mobile 320–390 px, keyboard-only, dan basic semantic inspection. Semua test memakai fixture; tidak ada akun, API, database, email, export, atau provider AI nyata.

| ID | Aktor | Skenario | Langkah inti | Expected |
|---|---|---|---|---|
| PT-001 | semua | login fixture | buka `/login`, pilih role, masuk | route sesuai role; banner prototype terlihat |
| PT-002 | responden | lihat dashboard | login responden | KPI dan survei prioritas tampil |
| PT-003 | responden | cari survei | buka daftar, isi search | hasil/filter count berubah |
| PT-004 | responden | detail privasi | buka detail | tujuan, estimasi, mode rahasia, penjelasan terlihat |
| PT-005 | responden | autosave | pilih jawaban | status saving lalu saved lokal diumumkan |
| PT-006 | responden | validasi | lanjut/submit tanpa item wajib | error inline + summary; tidak submit |
| PT-007 | responden | konfirmasi submit | lengkapi item, submit, cancel | dialog tertutup, jawaban tetap ada |
| PT-008 | responden | final submit | confirm dialog | receipt fixture muncul; tidak ada network write |
| PT-009 | Admin LPMPP | overview | login admin | label mock/reference Filament dan agregat terlihat |
| PT-010 | Admin LPMPP | builder | edit teks item, terapkan | card item berubah lokal; status unsaved/saved mock |
| PT-011 | Admin LPMPP | monitoring | filter unit/status | tabel fixture berubah; no respondent content |
| PT-012 | Admin LPMPP | hasil | buka results | snapshot, N, metode, threshold, suppression terlihat |
| PT-013 | pimpinan | scope filter | ganti unit | KPI fixture berubah; unit di luar opsi tidak tersedia |
| PT-014 | Admin LPMPP | AI simulation | jalankan AI | state queued/running/review; label draft tetap terlihat |
| PT-015 | Admin LPMPP | secret config | buka dialog, isi, simpan | masked placeholder; nilai tidak ditampilkan/reload hilang |
| PT-016 | PIC/verifikator | follow-up | ubah status mock | timeline/state lokal berubah; catatan wajib pada verifikasi |
| PT-017 | Admin/pimpinan | report | generate report mock | job berubah processing→ready; file nyata tidak dibuat |
| PT-018 | semua | deep link | buka setiap route langsung | view tersedia atau dialihkan aman ke login |

## 2. Accessibility scenarios

| ID | Pemeriksaan | Expected | WCAG 2.2 mapping |
|---|---|---|---|
| A11Y-001 | Tab dari awal halaman | skip link pertama; semua aksi terjangkau; focus terlihat | 2.1.1, 2.4.7, 2.4.11 |
| A11Y-002 | heading/landmark inspection | satu H1, hierarchy tanpa loncat, `main/nav/header` | 1.3.1, 2.4.6 |
| A11Y-003 | form labels/errors | label/legend ada; error terhubung dan announced | 1.3.1, 3.3.1, 3.3.2, 4.1.3 |
| A11Y-004 | dialog | focus masuk, Escape menutup, focus kembali | 2.1.2, 2.4.3, 4.1.2 |
| A11Y-005 | 320 px reflow | tanpa horizontal page scroll/loss | 1.4.10 |
| A11Y-006 | zoom 200% | content/action tidak hilang | 1.4.4 |
| A11Y-007 | status/graph without color | label/teks tetap menjelaskan | 1.4.1, 1.1.1 |
| A11Y-008 | reduced motion | tidak ada motion esensial/loop; transisi dinonaktifkan | 2.3.3 |
| A11Y-009 | contrast token check | normal text ≥4.5:1; component/focus ≥3:1 | 1.4.3, 1.4.11 |
| A11Y-010 | page title/route focus | title berubah dan H1 menerima focus | 2.4.2, 2.4.3 |

## 3. Privacy/security negative scenarios

| ID | Percobaan | Expected |
|---|---|---|
| NEG-001 | cari nama/email di dashboard hasil/pimpinan | tidak ada fixture identitas respons |
| NEG-002 | pilih small-cell unit | UI menampilkan suppression, bukan angka 0/raw |
| NEG-003 | lihat secret AI | hanya masked suffix; tidak ada reveal control |
| NEG-004 | isi custom AI URL | field tidak tersedia |
| NEG-005 | klik AI run | tidak ada request network; output tetap berlabel draft |
| NEG-006 | refresh setelah perubahan | perubahan memory boleh hilang; UI menjelaskan prototype |
| NEG-007 | report ready | tidak mengunduh data production |

## 4. Quality gates

Perintah dijalankan dari root repository melalui Compose:

```bash
docker compose run --rm frontend npm run lint
docker compose run --rm frontend npm run type-check
docker compose run --rm frontend npm run test:unit -- --run
docker compose run --rm frontend npm run build
```

Image frontend slim tidak menyertakan browser/library Playwright. E2E reproducible memakai container ephemeral; browser disimpan di cache `node_modules` dan tidak ditambahkan ke dependency/image aplikasi:

```bash
docker compose run --rm --user root -e CI=1 -e PLAYWRIGHT_BROWSERS_PATH=/app/node_modules/.cache/ms-playwright frontend sh -lc 'npx playwright install --with-deps chromium && npm run test:e2e -- --project=chromium'
```

Automated axe/pa11y bukan dependency yang tersedia. Pemeriksaan aksesibilitas Phase 08 menggunakan semantic assertions pada Playwright, keyboard/reflow checks, token contrast calculation, dan checklist manual. Screen reader lintas perangkat tetap menjadi gate sebelum production.

## 5. Exit criteria Phase 08

- Seluruh 12 alur prioritas dapat dinavigasi dengan fixture.
- Tidak ada network integration selain asset lokal Vite.
- Autosave/submit/export/AI/config jelas berlabel simulasi.
- Lint, type-check, unit test, E2E Chromium, dan build lulus, atau error spesifik dicatat.
- A11y critical blocker pada jalur prioritas tidak tersisa dari cakupan tes yang tersedia.
- Dokumen kontrol menyatakan Phase 09 belum dimulai.

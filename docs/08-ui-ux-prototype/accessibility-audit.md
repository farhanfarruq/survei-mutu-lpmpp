# Accessibility Audit Evidence

Tanggal: 2026-08-07  
Target desain: WCAG 2.2 AA untuk jalur prioritas (PROPOSED; perlu persetujuan pemilik sistem).  
Status: **PASS WITH TOOL AND DEVICE LIMITATIONS**.

## 1. Scope

Layar: login, dashboard responden, daftar/detail/pengisian survei, admin overview, builder, monitoring, hasil, pimpinan, AI, konfigurasi AI, tindak lanjut, dan laporan. Pemeriksaan mencakup Chromium headless desktop/mobile, semantic assertions, keyboard-ready primitives, error/live status, reflow 320 px, reduced motion, dan token contrast.

## 2. Automated evidence

| Pemeriksaan | Hasil | Bukti |
|---|---|---|
| Lint | PASS | Oxlint 0 warning/0 error; ESLint exit 0 |
| Type-check | PASS | `vue-tsc --build` exit 0 |
| Unit | PASS | 2/2 test: required-answer validation dan fixture filtering |
| E2E Chromium | PASS | 4/4 test, 5,1 detik pada final run |
| Landmark/H1 | PASS | 11 priority routes memiliki satu `main` dan satu H1 |
| Mobile reflow | PASS | 11 priority routes tidak overflow pada viewport 320×800 |
| Respondent flow | PASS | validation → answer → autosave → native dialog → receipt fixture |
| Leadership scope | PASS | filter mengubah aggregate fixture; tidak ada raw response pada view |
| AI/secret labels | PASS | AI tetap simulation/draft; secret masked dan dialog native |
| Integration scan | PASS | tidak ada `axios`, `fetch`, `XMLHttpRequest`, API key, atau provider nyata di `frontend/src` |

Playwright awal gagal karena executable Chromium tidak ada pada image `node:24-bookworm-slim`; instalasi `--with-deps` sebagai UID host juga gagal dengan `su: Authentication failure`. Browser Chromium 151 dipasang sebagai root hanya pada container test ephemeral dengan cache di `node_modules/.cache`, lalu test dijalankan headless. Tidak ada dependency aplikasi atau image yang diubah.

## 3. Contrast evidence

Perhitungan mengikuti WCAG relative luminance:

| Pair | Ratio | AA normal |
|---|---:|---|
| `ink` / surface | 15,63:1 | PASS |
| secondary text / surface | 9,11:1 | PASS |
| muted text / surface | 4,76:1 | PASS |
| white / brand | 7,03:1 | PASS |
| success / success-soft | 5,84:1 | PASS |
| warning / warning-soft | 6,23:1 | PASS |
| danger / danger-soft | 5,93:1 | PASS |

## 4. Manual/code inspection mapping

| WCAG 2.2 | Temuan | Severity | Status/remediasi |
|---|---|---|---|
| 1.3.1 Info and Relationships | landmark, H1, label, fieldset/legend, table header tersedia | — | PASS within inspected routes |
| 1.4.1 Use of Color | badge selalu memiliki teks; chart memiliki label dan accessible summary | — | PASS |
| 1.4.3 Contrast | seluruh token teks kritis ≥4.5:1 | — | PASS |
| 1.4.10 Reflow | browser assertion 320 px tanpa page overflow | — | PASS |
| 2.1.1 Keyboard | aksi memakai element native; radio/select/button/link dapat dioperasikan keyboard | — | PASS by implementation; physical keyboard session pending |
| 2.1.2 No Keyboard Trap | native dialog mendukung Escape dan focus management browser | — | PASS by implementation; AT matrix pending |
| 2.4.1 Bypass Blocks | skip link tersedia | — | PASS |
| 2.4.2 Page Titled | title berubah berdasarkan route | — | PASS |
| 2.4.3/2.4.7 Focus | route memfokuskan H1; focus-visible 3 px | — | PASS by implementation |
| 3.3.1/3.3.2 Errors/Labels | error summary + inline error; field berlabel | — | PASS respondent flow |
| 3.3.4 Error Prevention | submit final memakai confirmation dialog | — | PASS prototype |
| 4.1.3 Status Messages | autosave/result count memakai live region | — | PASS by implementation |

## 5. Coverage gaps

| Gap | Risiko | Tindakan sebelum production |
|---|---|---|
| axe-core/pa11y tidak tersedia | rule otomatis tertentu belum diperiksa | tambahkan satu tool setelah tooling QA disetujui; jalankan pada route dan state dinamis |
| NVDA/JAWS/VoiceOver belum diuji | announcement/focus dapat berbeda per AT/browser | uji matrix perangkat nyata dengan pengguna keyboard/screen reader |
| Browser hanya Chromium | perbedaan Firefox/WebKit belum tervalidasi | sediakan browser binaries pada image CI dan jalankan tiga project |
| Zoom 200% belum automation | layout dapat memiliki edge case pada ukuran sistem tertentu | manual zoom/reflow session pada desktop dan mobile |
| Copy/consent belum disahkan | bahasa privacy bisa tidak sesuai policy institusi | review LPMPP, fungsi PDP/hukum, dan pengguna nyata |

Tidak ada klaim sertifikasi atau full WCAG conformance. Hasil hanya bukti Phase 08 sesuai scope/tool yang tersedia.

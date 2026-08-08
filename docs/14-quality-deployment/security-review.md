# Phase 14 Security Review

## Ringkasan

Tidak ditemukan bukti kebocoran secret pada source yang dipindai, advisory Composer/npm, akses individual answer oleh pimpinan, atau pengiriman data test ke AI eksternal. Perbaikan Phase 14 menambahkan limiter API, header browser, penyamaran versi server, retry delivery yang aman, serta regression test privacy/security.

## Kontrol terverifikasi

| Area | Implementasi dan bukti | Status |
|---|---|---|
| Authentication/session | Fortify + Sanctum cookie; login 5/menit; active-user middleware; session HttpOnly dan SameSite=Lax | PASS |
| CSRF | `/sanctum/csrf-cookie`, stateful session, X-XSRF-TOKEN client | PASS; production wajib HTTPS |
| CORS | exact `FRONTEND_ORIGINS`, credentialed origin, header allowlist; origin asing tidak pernah mendapat origin yang sama dengan request | PASS |
| Authorization | permission middleware, policies, organizational scope intersection, reviewer SoD | PASS |
| Anonymous/confidential | content session tanpa `user_id`/participation key; confidential mapping pada tabel terpisah | PASS |
| Small-cell | suppression sebelum release/dashboard/export; suppressed bukan nol | PASS |
| Input | FormRequest/controller validation, enum/allowlist, optimistic version, hashed token/idempotency key | PASS |
| Output | Problem Details tanpa stack, API Resource/projection, aggregate-only leader payload | PASS |
| Rate limit | authenticated 60/menit per user/IP; external response 30/menit per IP; auth 5/menit | PASS |
| Browser header | nosniff, SAMEORIGIN, strict-origin referrer, restrictive permissions policy | PASS |
| Secret | provider secret encrypted, masked, tidak dapat dibaca kembali; `.env` di-ignore; scan pola secret source = 0 | PASS |
| AI safety | provider allowlist, timeout/token/cost/rate gate, aggregate projection/redaction, injection sanitizer, JSON schema/leakage validation, review | PASS |
| Dependency | Composer locked audit dan npm all/prod audit | 0 advisory/vulnerability |

Live check mengonfirmasi Nginx tidak lagi mengirim `X-Powered-By` dan hanya mengirim nama server tanpa versi. Preflight origin `http://localhost:5173` diterima. Untuk origin asing, library mengirim nilai origin allowlist yang statis; karena nilainya tidak cocok dengan `Origin` peminta, browser tetap menolak akses credentialed.

## Temuan dan tindakan

| ID | Temuan | Tindakan | Residual |
|---|---|---|---|
| SEC-14-01 | API authenticated belum memiliki limiter umum | named limiter `api` 60/menit dan middleware route | rendah |
| SEC-14-02 | Header hardening dan versi runtime terekspos | header Nginx, `server_tokens off`, sembunyikan `X-Powered-By` | rendah |
| SEC-14-03 | Failed notification dianggap terminal karena record dedupe sudah ada | retry failed-only, attempt metadata, 3 tries/backoff, regression test | rendah |
| SEC-14-04 | Kontras secondary text 4.11–4.43:1 | token warna digelapkan; axe ulang 0 violation | rendah |
| SEC-14-05 | PHPStan level 5 mempunyai 352 temuan existing | baseline dibuat; error baru diblokir | sedang, wajib burn-down |

## Conditions sebelum production

- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, cookie domain/origin exact, rotate `APP_KEY` melalui secret manager, dan larang secret di image/log.
- Terminasi TLS 1.2+ di edge; aktifkan HSTS hanya setelah seluruh domain HTTPS; tambah CSP berdasarkan inventory asset/inline script yang tervalidasi.
- Jalankan SAST/DAST CI, image/SBOM scan, pentest terotorisasi, dan review baseline PHPStan.
- Konfirmasi retention, lawful basis/privacy notice, reporting threshold, role assignment, dan break-glass procedure oleh pemilik kebijakan.


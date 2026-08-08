# Phase 14 Test Plan and Results

Tanggal eksekusi: 8 Agustus 2026. Lingkungan: Docker Compose lokal, PHP 8.5, PostgreSQL 17, Redis 7, Node 24. Seluruh fixture menggunakan identitas `.example.test`, unit fiktif, dan respons sintetis; tidak ada data pribadi asli atau provider AI eksternal.

## Scope dan traceability

Canonical requirement tetap berada pada `docs/04-requirements/`: 80 FR dan 41 NFR. Trace tingkat fase diperbarui pada `docs/00-project-control/traceability-matrix.md`, termasuk Phase 12 dan Phase 14. Bukti implementasi berikut menutup area yang diminta:

| Area | Bukti utama | Hasil |
|---|---|---|
| Unit | golden statistics, navigation, autosave | PASS |
| Feature/integration | auth, lifecycle, response, analytics/export, AI/notification/follow-up, Phase 14 | PASS |
| Policy/scope | Filament access, organizational subtree, survey policy, leader/read-only, reviewer SoD | PASS |
| Privacy | anonymous content-session schema, confidential link, aggregate-only leader view, small-cell suppression | PASS |
| Security | Problem Details, validation, rate limits 60/30, CSRF/CORS/session config, AI injection/output quarantine | PASS |
| Queue | export/analysis failure state, notification partial failure and idempotent retry | PASS |
| Performance | 100 released snapshots, maksimal 10 query, budget lokal di bawah 1 detik | PASS |
| Accessibility/E2E | axe WCAG A/AA, focus/skip link, zoom/reflow, workflow lintas Chromium/Firefox/WebKit | PASS WITH MANUAL AT CONDITION |

## Hasil otomatis

| Gate | Perintah | Hasil |
|---|---|---|
| Backend | `docker compose exec -T app php artisan test --compact` | 43 test, 384 assertion, PASS |
| PHP format | `docker compose exec -T app vendor/bin/pint --test` | 210 file, PASS |
| PHP static | `docker compose exec -T app composer analyse -- --no-progress` | level 5 terhadap baseline, 0 error baru, PASS |
| Composer audit | `docker compose exec -T app composer audit --locked` | 0 advisory, PASS |
| Frontend unit | `npm run test:unit -- --run` | 4 file, 10 test, PASS |
| Frontend lint | `npm run lint` | 0 warning/error, PASS |
| Type-check | `npm run type-check` | PASS |
| Build | `npm run build` | PASS |
| npm audit | `npm audit` dan `npm audit --omit=dev` | 0 vulnerability, PASS |
| E2E | `PLAYWRIGHT_SYSTEM_CHROME=1 npm run test:e2e` | 36 test pada 3 browser, PASS |
| Accessibility subset | `npm run test:a11y` | 6 Chromium; seluruh axe violations = 0, PASS |
| Compose | `docker compose config --quiet` | PASS |
| Runtime readiness | `GET /api/v1/health/ready` | database/Redis/queue `ok` |
| Restore drill | custom-format dump ke PostgreSQL sementara | 54 tabel, 16 baris migration ledger, PASS |

## Negative/regression coverage

- Duplicate final submit, one-response rule, expired survey, expired/revoked invitation, unauthorized survey, missing required answer, autosave conflict, dan network recovery: `ResponseCollectionTest` serta frontend autosave/E2E.
- Scope dan privacy: sibling unit tidak terlihat, leader hanya agregat released, nilai small-cell disembunyikan, individual answer tidak ada pada payload pimpinan.
- AI: base URL di luar allowlist ditolak; untrusted text diperlakukan sebagai data; malformed/leaking structured output dikarantina; fake provider gagal menghasilkan deterministic fallback.
- Rate limiting: request ke-61 API terautentikasi dan request ke-31 flow external menghasilkan 429 Problem Details tanpa exception detail.
- Queue: channel database yang sudah terkirim tidak diulang; channel email gagal diulang dan attempt naik 1 ke 2.

## Batas pengujian

Belum dilakukan uji screen reader nyata (NVDA/JAWS/VoiceOver), UAT manusia, soak/load test multi-node, SMTP nyata, object storage, KMS, TLS edge, failover infra, dan restore dari media offsite. PHPStan level 5 memiliki baseline 352 temuan legacy/dynamic-framework; gate menjamin tidak ada temuan baru, bukan berarti utang baseline nol. Semua menjadi condition sebelum production go-live.


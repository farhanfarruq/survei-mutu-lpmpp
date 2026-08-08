# Phase 14 Release Readiness

## Keputusan

**READY WITH CONDITIONS** untuk melanjutkan ke proses acceptance/staging terkontrol. **Belum diizinkan production go-live.** Phase 14 tidak melakukan deployment atau perubahan production.

## Bukti siap

- 43 backend test/384 assertion dan 36 E2E pada Chromium, Firefox, WebKit lulus; frontend unit/lint/type/build lulus.
- Axe WCAG A/AA pada lima halaman prioritas menghasilkan 0 violation; keyboard focus/skip dan 320 CSS px reflow diuji lintas browser.
- Composer/npm audit 0 advisory; secret source scan 0; API rate limit, CORS/CSRF/session configuration, privacy separation, small-cell suppression, AI safety dan output validation diuji.
- Dashboard regression 100 snapshots lolos query/time budget; targeted PostgreSQL indexes tersedia; route lazy loading mengisolasi ECharts.
- Queue retry/backoff dan partial notification idempotency diuji.
- Compose lokal: app/Nginx/PostgreSQL/Redis/Horizon sehat; scheduler berjalan; readiness DB/Redis/queue `ok`. Final log scan hanya menemukan satu `FATAL role root does not exist` akibat probe audit dengan role salah; koneksi ditolak sesuai harapan dan tidak memengaruhi service.
- Restore drill portabel sukses dengan 54 tabel dan 16 migration ledger rows.

## Conditions/blocker go-live

| ID | Condition | Owner/sign-off yang dibutuhkan |
|---|---|---|
| RR-01 | Production manifest/image digest, static frontend, TLS/HSTS/CSP, secure cookie dan secret manager | DevOps + Security |
| RR-02 | Managed DB/Redis/storage/SMTP, HA/capacity/load/soak test, alert routing/on-call | Infrastructure/Ops |
| RR-03 | RPO/RTO, offsite encrypted backup, PITR dan restore drill environment target | Data Owner + Ops |
| RR-04 | UAT manusia termasuk screen reader/assistive technology dan mobile device nyata | Product Owner + Accessibility tester |
| RR-05 | Role/scope/RACI, privacy notice, retention, threshold, AI/provider/DPIA dan risk acceptance disahkan | LPMPP + Privacy + Security |
| RR-06 | Burn-down/acceptance tertulis untuk 352 PHPStan baseline findings; SAST/DAST/image/SBOM/pentest CI | Engineering + Security |
| RR-07 | Dashboard series cap/pagination dan analytics memory strategy ditentukan dari production-like capacity result | Engineering/Product |
| RR-08 | Seluruh checkout masih belum mempunyai tracked baseline/commit; review secret lalu buat commit/tag release yang dapat direproduksi | Engineering/Release manager |

Jika satu condition keamanan/privacy/data integrity belum ditutup atau diterima oleh risk owner, status berubah menjadi **NOT READY** untuk production.

## Release notes Phase 14

### Added

- Phase 14 automated security/privacy/rate/queue/performance/accessibility tests.
- Larastan level-5 incremental gate dan axe Playwright audit.
- Targeted indexes untuk dashboard, reminder, export expiry, dan retry delivery.
- Health/restart/graceful-stop strategy serta operational/deployment/backup/incident/UAT/manual artifacts.

### Changed

- API authenticated dibatasi 60 request/menit; external response 30 request/menit.
- Failed notification channel dapat retry tanpa menggandakan channel yang sudah sent.
- Nginx mengirim security headers dan menyembunyikan versi/PHP header.
- Fokus skip-link, language metadata, contrast token, dan route-level lazy loading diperbaiki.

### Known limitations

Tidak ada production deploy, provider/SMTP/storage/KMS nyata, load/soak/pentest, external monitoring, offsite restore, human UAT, atau screen-reader manual test pada Phase 14. PHPStan baseline dan capacity limits tetap conditions di atas.

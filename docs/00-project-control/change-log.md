# Change Log

## 2026-08-08 — Phase 14 Quality, Security, Performance, and Deployment Readiness

- Menambahkan limiter authenticated/external, browser security headers, version-header suppression, privacy/security/queue/performance regression tests.
- Memperbaiki failed notification retry dengan attempt metadata, idempotent sent-channel handling, tiga tries, dan exponential-style backoff.
- Menambah targeted PostgreSQL indexes untuk dashboard, reminder, report expiry, dan delivery retry.
- Menambah Larastan level-5 incremental baseline, axe Playwright, language/contrast/skip-focus fixes, serta route lazy loading.
- Memperkuat Compose lokal dengan restart policy, healthcheck, init, stop grace period, dan Horizon health.
- Menjalankan restore drill portabel, dependency/secret/log audit, seluruh test/lint/type/build/E2E tiga browser.
- Membuat sepuluh runbook/review/manual/UAT/readiness docs; menetapkan READY WITH CONDITIONS tanpa production deploy.
- Berhenti setelah Phase 14.

## 2026-08-08 — Phase 13 AI, Notifications, and Follow-up

- Menambahkan konfigurasi AI terenkripsi/masked, provider/model toggle, base URL allowlist, connection test aman, budget/rate/timeout, queue, aggregate redaction, prompt version, injection defense, structured validation, fallback, usage, dan audit.
- Mengimplementasikan hasil AI berlabel/scope/model/timestamp dan human edit/approve/reject dengan independent reviewer serta optimistic concurrency.
- Menambahkan notifikasi database/email-log terdeduplikasi untuk availability, reminder, closing, report completion, AI failure, low response, deadline, dan verification result.
- Mengimplementasikan finding manual/indikator rendah, assignment unit/PIC/verifier, root cause/plan/due/evidence, revision loop, verification, dashboard scoped, dan leader read-only.
- Membuat Vue production AI, notification center, follow-up list/dashboard/detail dengan permission guard, UI states, semantics, dan mobile reflow.
- Memvalidasi Pint 208 file, 38 backend test/267 assertion, 10 frontend unit, lint/type/build, dan 18/18 E2E tiga browser menggunakan fake AI tanpa request provider eksternal.
- Berhenti setelah Phase 13.

## 2026-08-08 — Phase 11 Response Collection

- Menambahkan schema participation, identity-free respondent session, response/answer, confidential linkage, dan idempotency tanpa reset database.
- Mengimplementasikan eligible survey, authenticated/external invitation flow, detail instrumen, enam question type MVP, autosave ETag/idempotency, recovery lokal, progress, required validation, exactly-once submit, receipt, dan one-response rule.
- Memisahkan anonymous/detached content dari identity participation; confidential linkage berada pada table terpisah tanpa endpoint baca Phase 11.
- Menambahkan riwayat participation yang diminimalkan, reminder eligibility aggregate, reporting threshold/suppression foundation, serta tanpa leadership raw-answer API.
- Membuat Vue production route untuk daftar/detail/history/invitation/form, section navigation, native accessible controls, live save status, error focus, confirmation, completion, dan mobile reflow.
- Memvalidasi 29 backend test/145 assertion, 8 frontend unit, lint/type/build, dan 5 Chrome E2E termasuk network recovery dan 320 px production flow.
- Tidak membuat analytics/reporting/AI/reminder delivery dan tidak memulai Phase 12.

## 2026-08-07 — Phase 10 Survey Management and Filament

- Menambahkan schema UUID untuk template/version/instrument content/question bank/period/respondent group/survey/target tanpa reset database.
- Mengimplementasikan preflight, content hash, dual-control review/approval, immutable version, semantic duplication, campaign snapshot, scheduled lifecycle, close/archive, serta unsafe edit guard setelah respons.
- Membuat enam resource Filament domain dengan nested category/indicator, scale/point, section/question/option, targets, preview, dan lifecycle actions yang memanggil domain service.
- Menambahkan permission/policy organizational scope, audit transition, factory, fixture fiktif idempotent, dan reviewer fixture tanpa shared credential.
- Memperluas API catalog/OpenAPI dengan campaign review submission/decision; OpenAPI valid tanpa warning/error.
- Memvalidasi Pint, 22 backend test/87 assertion, Filament routes/preview, scheduler, frontend lint/type/unit/build, migration, dan seed idempotency.
- Tidak membuat response capture, dashboard analitik, AI, report/export, atau memulai Phase 11.

## 2026-08-07 — Phase 09 Implementation Foundation

- Mengimplementasikan Fortify + Sanctum stateful session, CSRF, active-user gate, RBAC, policy, organizational scope, request ID, Laravel API Resource, dan Problem Details.
- Menambahkan migration aman untuk unit organisasi, public UUIDv7 user, membership scope, dan activity-log morph key; mengisi hanya fixture `.example.test` dan unit fiktif.
- Membuat resource Filament user/role/permission/unit yang scoped serta widget status tanpa secret.
- Membuat Vue authenticated shell, router guard, auth store, Axios credential/CSRF flow, error handling, UI primitives, login/logout nyata, dan role-aware navigation.
- Mempertahankan rute prototype Phase 08 sebagai reference; tidak membuat modul survei/AI/report production dan tidak menambah dependency.
- Memvalidasi 10 backend test/48 assertion, lint, type-check, 6 frontend unit test, production build, route, health seluruh service, Horizon, scheduler, serta log terbatas.
- Menutup Phase 09 sebagai READY WITH NOTES dan tidak memulai Phase 10.

## 2026-08-07 — Phase 08 UI/UX Design and Clickable Prototype

- Membuat IA, sitemap per role, navigation model, design tokens/components/states, responsive/accessibility baseline, 12 wireframe tekstual, user flows, content/dashboard specification, dan test scenarios.
- Membangun clickable Vue prototype fixture untuk login, responden, survei/autosave/validation/submit, admin/builder/monitoring/results, pimpinan, AI/config, tindak lanjut, dan laporan.
- Menjaga Admin LPMPP sebagai mock/reference Filament, leadership hanya pada agregat scoped, AI sebagai draft post-MVP, dan secret masked tanpa custom Base URL.
- Tidak menambah UI library/dependency dan tidak menghubungkan API, database production, email, export nyata, atau provider AI.
- Memvalidasi lint, type-check, 2 unit test, 4 E2E Chromium headless termasuk reflow 320 px, token contrast, production build, boundary scan, dan HTTP 200.
- Mencatat gap axe/screen-reader/multi-browser/usability; Phase 09 tidak dimulai.

## 2026-08-07 — Phase 07 API Contract and Integration Strategy

- Menetapkan REST JSON `/api/v1`, snake_case, OpenAPI 3.1.1, Laravel API Resource/ResourceCollection, dan Problem Details.
- Mendokumentasikan Sanctum session-cookie + CSRF, versioning, pagination/filter/sort/include/fields, rate limits, ETag/If-Match, dan idempotency.
- Membuat katalog 89 endpoint unik dengan permission, organizational/assignment scope, reporting threshold, lock, dan MVP marker.
- Membuat OpenAPI draft 32 operasi kritis termasuk pimpinan/export dan write-only AI secret; tervalidasi Redocly CLI 2.45.0 tanpa warning/error.
- Membuat event/outbox, job, scheduler 15 entries, future webhook policy, serta error/retry/dead-letter contracts.
- Membuat interface netral SIAKAD/population, SSO/IdP, email, dan AI beserta mapping, failure, reconciliation, privacy, dan contract tests.
- Memvalidasi satu diagram Mermaid; tidak membuat endpoint produksi dan tidak memulai Phase 08.

## 2026-08-07 — Phase 06 Data, Architecture, Security, and AI Governance

- Menetapkan C4 context/container/component dan modular monolith Laravel/Filament + Vue dengan worker/scheduler.
- Menetapkan PostgreSQL sebagai source of truth, Redis ephemeral, serta Core/Participation DB, Response DB, dan optional Linkage Vault dengan credential terpisah.
- Membuat ERD/dictionary yang mencakup seluruh entitas minimum serta UUIDv7, FK/delete, uniqueness/index, partition/archive, dan immutable aggregate snapshot.
- Mendokumentasikan privacy modes, retention/deletion, backup/PITR, restore drill, RPO/RTO proposed, dan Redis rebuild.
- Membuat authorization/encryption/KMS/secret/SSRF/file/export/audit architecture serta threat model 34 STRIDE threats.
- Membuat AI provider registry/adapter, redaction, prompt versioning, injection defense, cost, evaluation, human review, dan incident controls; AI tetap off.
- Membuat delapan ADR Proposed dengan options, trade-off, consequences, mitigation, dan revisit trigger.
- Memvalidasi 18/18 diagram pada Mermaid CLI 11.16.0; tidak menulis kode produksi dan tidak memulai Phase 07.

## 2026-08-07 — Phase 05 Process Design dan UML

- Membuat system context logis beserta aktor, sistem eksternal, boundary, trust boundary, dan data exchange.
- Membuat empat use case diagram per kelompok aktor dan menjelaskan association, include, extend, generalization, serta system boundary.
- Merinci 15 use case dengan field wajib, permission, business rules, main, alternative, dan failure flow.
- Membuat 7 activity diagram, 8 sequence diagram, 5 state machine, DFD level 0/1, serta exception/recovery flow.
- Mempertahankan AI sebagai extension pasca-MVP yang fail-closed dan memisahkan data partisipasi dari isi respons anonim.
- Memvalidasi 28/28 blok menggunakan image resmi Mermaid CLI `11.16.0`; seluruhnya berhasil dirender.
- Memperbarui dokumen kontrol tanpa menulis kode aplikasi dan tanpa memulai Phase 06.

## 2026-08-07 — Phase 02 Product Scope dan MVP

- Menetapkan product vision/problem sebagai platform umpan balik mutu yang mendukung PPEPP, bukan pengganti SPMI/akreditasi.
- Mendokumentasikan objective, benefit, outcome, stakeholder, actor, organizational data scope, boundary, constraint, dependency, dan assumption.
- Membuat peta 10 modul dan dependency sequence tanpa menulis desain arsitektur.
- Memprioritaskan backlog dengan MoSCoW: 25 Must, 10 Should, 7 Could, dan 10 Won't Now.
- Menyusun roadmap R0–R5 berbasis entry/exit gate dan menunda AI/fitur lanjutan yang tidak perlu bagi MVP.
- Menetapkan 35 KPI terukur dengan VILR sebagai North Star, target awal PROPOSED, owner, dan guardrail.
- Menyediakan 20 istilah formal, 12 keputusan pemilik, dan lima alternatif nama sistem.
- Memperbarui traceability/control docs tanpa mengubah kode aplikasi atau artefak Phase 03/04.

## 2026-08-07 — Phase 04 Requirements Engineering

- Menyusun 80 functional requirements pada 10 modul dengan prioritas, verifikasi, notification/report-export matrix, dan traceability.
- Menyusun 41 non-functional requirements terukur untuk performance, concurrency, availability, backup, recovery, security, accessibility, privacy, auditability, maintainability, compatibility, data integrity, dan observability.
- Menyusun 56 business rules serta 56 user story dengan Given/When/Then untuk responden, Admin LPMPP, Super Admin, pimpinan, reviewer, PIC, dan verifikator.
- Membuat role-permission-data-scope matrix yang membedakan Create/Read/Update/Delete/Execute/Export dan separation of duties.
- Membuat data classification/handling/retention matrix serta risk register 22 risiko dengan indikator dan residual target.
- Menandai target capacity/SLA/retention yang belum disetujui sebagai `PROPOSED` dan memperbarui dokumen kontrol.
- Tidak menulis kode aplikasi dan tidak memulai Phase 05.

## 2026-08-06 — Phase 03 Metodologi Survei dan Instrumen

- Menetapkan taksonomi 12 keluarga survei dan model template–versi–kategori–indikator–item–skala–scoring rule.
- Mendokumentasikan formula, normalisasi, rounding, missing, threshold, dan contoh hitung SERVPERF, SERVQUAL, IPA, CSI internal, SKM/IKM, NPS, serta metode internal.
- Membuat protokol content validity, expert review, cognitive interview, pilot, item analysis, reliability, factor analysis, response rate, dan nonresponse bias.
- Menyusun pedoman item serta contoh lengkap survei layanan akademik dengan pasangan importance–performance dan modul alternatif expectation–perception.
- Menetapkan baseline minimum reporting, small-cell/complementary suppression, mode anonim/rahasia, open text, dan guardrail AI.
- Memperbarui dokumen kontrol; pada saat Phase 03 dijalankan Phase 02 masih kosong dan Phase 04 belum dimulai; tidak ada kode aplikasi yang ditulis.

## 2026-08-06 — Phase 01 Discovery dan Riset

- Memverifikasi regulasi penjaminan mutu aktif, instrumen APT/APS terkini, dan status transisi IAPT.
- Membandingkan SERVQUAL, SERVPERF, IPA, CSI, SKM/IKM, dan NPS tanpa menggabungkan konstruk/skornya.
- Mendokumentasikan praktik program survei pendidikan tinggi, platform pembanding, PPEPP, dan closing feedback loop.
- Merumuskan guardrail privasi, mode identitas jawaban, penggunaan AI, serta pertanyaan institusional yang harus dikonfirmasi.
- Membuat lima dokumen pada `docs/01-discovery/` dan memperbarui dokumen kontrol.
- Tidak menulis kode aplikasi, mengubah dependency, memigrasikan data, atau memulai Phase 02.

## 2026-08-06 — Phase 00

- Membuat struktur folder dokumentasi `docs/00-project-control/` sampai `docs/14-quality-deployment/`.
- Membuat delapan dokumen kontrol awal pada `docs/00-project-control/`.
- Mencatat hasil inspeksi repository, Docker/Compose, image lokal, quality gate, perbedaan baseline, dan blocker.
- Tidak mengubah aplikasi, konfigurasi runtime, dependency, database, volume, atau Git.

## 2026-08-06 — Instalasi baseline lanjutan

- Menginisialisasi Git dan membuat scaffold Laravel 13 serta Vue 3 melalui container.
- Membuat Dockerfile PHP, konfigurasi Nginx, Compose, environment example, root README, dan Git ignore.
- Memasang Sanctum, Fortify, Filament, Permission, Activitylog, Horizon, dependency frontend, dan Tailwind CSS.
- Membuat database development/test terpisah dan menjalankan migration baseline tanpa wipe/reset.
- Memperbaiki kompatibilitas PHP 8.5 DOM, isolasi `.env.testing`, konflik host port PostgreSQL, serta peer dependency frontend.
- Memvalidasi service, endpoint, worker, test, lint, type-check, dan build.

# Non-Functional Requirements

Versi: **1.0 — 2026-08-07**  
Status target: `BASELINE` = wajib minimum; `PROPOSED` = target awal yang **memerlukan persetujuan institusi/capacity test**.

Pengukuran dilakukan pada environment production-like dengan data sintetis/de-identified. Percentile dihitung per endpoint selama window uji, bukan dari rata-rata saja.

## 1. Performance

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-PER-001 | P95 halaman respondent dan API baca harus ≤2,0 detik dan P99 ≤4,0 detik pada beban nominal. | Load test 30 menit, cache warm dan cold dilaporkan. | PROPOSED |
| NFR-PER-002 | P95 autosave harus ≤1,5 detik dan submit ≤3,0 detik; error non-2xx akibat sistem <1%. | Load test workflow respondent dengan network normal. | PROPOSED |
| NFR-PER-003 | First usable survey page harus ≤3,0 detik pada koneksi mobile 4G tersimulasi, payload awal terkompresi ≤500 KB. | Lighthouse/WebPageTest profile yang dipin. | PROPOSED |
| NFR-PER-004 | Dashboard agregat P95 ≤5 detik dan export sampai 100.000 baris selesai ≤10 menit tanpa memblokir request web. | Benchmark dataset skala target dan queue telemetry. | PROPOSED |

## 2. Concurrency and Capacity

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-CON-001 | Sistem harus mendukung 200 respondent aktif bersamaan dan 20 user internal aktif dengan error rate <1%. | Soak test 60 menit pada production-like sizing. | PROPOSED |
| NFR-CON-002 | Sistem harus menerima 50 submit/detik selama 5 menit tanpa response ganda/hilang dan backlog pulih ≤15 menit. | Burst test dengan idempotency assertion dan reconciliation count. | PROPOSED |
| NFR-CON-003 | Queue notifikasi harus memproses 10.000 recipient dalam ≤30 menit pada provider sandbox tanpa duplikasi logis. | Timed batch test termasuk retry/failure. | PROPOSED |

## 3. Availability

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-AVL-001 | Availability aplikasi bulanan harus ≥99,5%, tidak termasuk maintenance terjadwal maksimum 4 jam/bulan. | Synthetic monitoring 1 menit dan perhitungan SLI bulanan. | PROPOSED |
| NFR-AVL-002 | Kegagalan health service kritis harus terdeteksi ≤2 menit dan alert diterima on-call ≤5 menit. | Game-day dengan penghentian komponen terkendali. | PROPOSED |
| NFR-AVL-003 | Maintenance terjadwal harus diumumkan ≥48 jam dan tidak boleh berada pada 24 jam terakhir campaign tanpa approval insiden. | Audit kalender, notification log, dan exception approval. | PROPOSED |

## 4. Backup

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-BCK-001 | Database harus memiliki backup penuh harian dan incremental/WAL paling lama setiap 15 menit. | Pemeriksaan job, age backup, dan gap WAL harian. | PROPOSED |
| NFR-BCK-002 | Seluruh backup harus terenkripsi, memiliki checksum, disimpan pada failure domain terpisah, dan tidak memuat secret dalam log. | Quarterly control test dan checksum validation. | BASELINE |
| NFR-BCK-003 | Restore drill harus dilakukan sekurang-kurangnya per kuartal dengan hasil, durasi, checksum, dan exception terdokumentasi. | Review evidence drill; satu restore acak sampai application check. | PROPOSED |

## 5. Recovery and Resilience

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-REC-001 | Recovery Point Objective harus ≤15 menit dan Recovery Time Objective layanan inti ≤4 jam. | Disaster-recovery exercise dan timestamp data terakhir. | PROPOSED |
| NFR-REC-002 | Kegagalan provider email/AI tidak boleh menghalangi pengisian atau submit survei; fungsi terkait berstatus degraded. | Fault injection provider timeout/5xx. | BASELINE |
| NFR-REC-003 | Job autosave, submit, scoring, notifikasi, dan export harus idempotent; retry tidak menghasilkan artefak bisnis ganda. | Concurrent retry test dengan uniqueness/reconciliation assertion. | BASELINE |

## 6. Security

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-SEC-001 | Seluruh traffic eksternal harus memakai TLS 1.2+; cookie session Secure, HttpOnly, SameSite; HSTS aktif di production. | Automated TLS/header scan tiap release. | BASELINE |
| NFR-SEC-002 | MFA wajib untuk seluruh admin; 100% endpoint protected harus mempunyai authentication, permission, dan data-scope test. | Access-control test suite dan quarterly role review. | BASELINE |
| NFR-SEC-003 | Secret tidak boleh berada di source, log, export, atau response API; rotasi credential kritis selesai ≤24 jam setelah insiden. | Secret scan, log sample, rotation drill. | BASELINE |
| NFR-SEC-004 | Vulnerability critical diperbaiki ≤72 jam, high ≤14 hari, medium ≤60 hari; exception memiliki owner dan expiry. | Dependency/image scan mingguan dan SLA report. | PROPOSED |

## 7. Accessibility

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-ACC-001 | Seluruh respondent flow dan fungsi admin kritis harus memenuhi WCAG 2.2 level AA tanpa known blocker. | Automated scan + manual audit per release mayor. | BASELINE |
| NFR-ACC-002 | 100% fungsi survey dapat diselesaikan keyboard-only dengan visible focus, logical order, skip link, dan tanpa keyboard trap. | Manual test seluruh happy/error/branching path. | BASELINE |
| NFR-ACC-003 | Text contrast minimal 4,5:1, large text 3:1, non-text UI 3:1; error/status diumumkan screen reader. | Contrast tool dan NVDA/VoiceOver test pada matriks browser. | BASELINE |

## 8. Privacy

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-PRI-001 | Pada mode detached, automated schema/flow review harus membuktikan tidak ada join key response–participation dan dataset isi tidak memuat direct identifier/IP/user-agent. | Privacy architecture test sebelum setiap release terkait. | BASELINE |
| NFR-PRI-002 | Minimum-cell, complementary suppression, dan anti-differencing harus identik pada 100% dashboard/API/export. | Golden dataset parity test untuk seluruh channel. | BASELINE |
| NFR-PRI-003 | Retention disposition yang jatuh tempo harus selesai ≤24 jam setelah approved job window; legal hold mencegah deletion dan diaudit. | Daily retention report dan monthly sample reconciliation. | PROPOSED |

## 9. Auditability

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-AUD-001 | 100% login admin, permission/policy change, publish, analysis, approval, export, download, dan deletion menghasilkan audit event. | Event coverage test terhadap katalog aksi. | BASELINE |
| NFR-AUD-002 | Audit event harus memuat actor, action, object, result, timestamp, correlation ID, dan before/after hash bila berubah; waktu antarnode berselisih <60 detik. | Schema test dan time-sync monitoring. | BASELINE |
| NFR-AUD-003 | Audit log online disimpan minimal 2 tahun, immutable bagi aplikasi biasa, searchable ≤10 detik untuk 90 hari data. | Retention/access test dan query benchmark. | PROPOSED |

## 10. Maintainability and Testability

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-MNT-001 | Setiap FR `MUST` harus mempunyai acceptance test atau test case ID sebelum release; pass rate release 100%. | Traceability audit pada pipeline/release checklist. | BASELINE |
| NFR-MNT-002 | Critical domain scoring, authorization, suppression, dan retention harus memiliki branch coverage ≥90%; project overall ≥80%. | Coverage report dengan exclusion disetujui. | PROPOSED |
| NFR-MNT-003 | Deployment harus dapat di-roll back ke versi aplikasi sebelumnya ≤30 menit tanpa kehilangan response submitted. | Quarterly rollback drill pada staging production-like. | PROPOSED |

## 11. Compatibility and Portability

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-CMP-001 | Respondent UI harus berfungsi pada dua major terbaru Chrome, Edge, Firefox, Safari dan Safari iOS/Chrome Android yang masih didukung vendor. | Browser matrix E2E tiap release. | PROPOSED |
| NFR-CMP-002 | Layout respondent harus usable tanpa horizontal scroll pada viewport 360–1440 px dan zoom 200%. | Visual/manual test pada breakpoint dan zoom. | BASELINE |
| NFR-CMP-003 | Email harus mempunyai plain-text fallback dan HTML yang terbaca pada client target; CSV UTF-8, XLSX valid, dan PDF menyematkan font. | Snapshot/client test dan parser round-trip. | PROPOSED |

## 12. Data Integrity and Quality

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-DAT-001 | Reconciliation count population, participation, response, scoring, dan report harus berbeda 0 untuk transaksi committed yang eligible. | Daily automated reconciliation dengan exception queue. | BASELINE |
| NFR-DAT-002 | Semua version snapshot, submitted response, analysis run, approval, dan audit record memiliki checksum/hash; mismatch memblokir pemakaian. | Integrity mutation test dan scheduled verification. | BASELINE |
| NFR-DAT-003 | Import population harus menolak file bila >1% row invalid; pada ≤1%, invalid row dikarantina dan tidak diproses sampai diperbaiki. | Boundary test 0,99%, 1%, dan >1%. | PROPOSED |

## 13. Observability and Operations

| ID | Requirement dan target terukur | Cara ukur | Status |
|---|---|---|---|
| NFR-OBS-001 | Metrics minimal mencakup request latency/error, active survey, autosave/submit, queue lag/failure, DB/Redis health, notification delivery, dan export duration. | Monitoring checklist dan synthetic event. | BASELINE |
| NFR-OBS-002 | Log production harus structured, memakai correlation ID, tanpa secret/direct identifier/response content, dan tersedia untuk pencarian ≤5 menit setelah event. | Log schema/redaction test dan ingestion-lag monitor. | BASELINE |
| NFR-OBS-003 | Alert critical harus memiliki owner, runbook, deduplication window, dan acknowledgment ≤15 menit selama campaign kritis. | Quarterly alert drill dan SLA report. | PROPOSED |

## 14. Target yang wajib disetujui sebelum production

Pemilik persetujuan minimum:

- performance/concurrency/capacity: TIK + LPMPP;
- availability, backup, RPO/RTO, observability: TIK/operasi + pimpinan;
- security/audit retention: fungsi keamanan + auditor;
- privacy/retention: fungsi PDP/hukum + data owner;
- accessibility/browser matrix: product owner + unit layanan disabilitas;
- maintainability/release: technical owner;
- semua exception: owner kontrol terkait, alasan, compensating control, dan expiry.

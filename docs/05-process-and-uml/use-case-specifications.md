# Use Case Specifications

Versi: **1.0 — 2026-08-07**

## 1. Konvensi

- **Precondition** adalah keadaan yang harus benar sebelum trigger diterima; bukan langkah tersembunyi.
- **Postcondition sukses** adalah keadaan yang dijamin setelah main/alternative success flow selesai.
- **Postcondition gagal** mempertahankan data aman/konsisten dan mencatat failure yang relevan.
- **Alternative flow** adalah jalur bisnis sah menuju outcome lain atau outcome sukses yang sama.
- **Failure flow** adalah penolakan/error teknis/bisnis yang mencegah outcome dan menjelaskan recovery.
- Permission selalu diinterseksikan dengan organization scope, assignment, object state, dan classification.

## UC-01 — Login

| Field | Specification |
|---|---|
| ID | UC-01 |
| Nama | Login user internal |
| Tujuan | Membentuk session user internal dengan assurance dan scope yang sah. |
| Aktor | User Internal; Identity Provider; Authentication Service |
| Trigger | User membuka fungsi protected atau memilih Login. |
| Precondition | Akun aktif; IdP/local auth tersedia; admin mempunyai MFA terdaftar. |
| Postcondition | Sukses: session berisi user ID, assurance, role/scope version, expiry; audit login sukses. Gagal: tidak ada session privileged. |
| Data | identifier, auth transaction, MFA challenge, role/scope claims minimum, correlation ID; secret/password tidak dicatat. |
| Permission | public `login`; fungsi berikutnya tetap memeriksa permission masing-masing. |
| Business rules | BR-ACC-001–002, BR-ACC-005; FR-IAM-001–007. |

Main flow:

1. Sistem membuat auth transaction dan correlation ID.
2. User memasukkan credential atau diarahkan ke IdP.
3. Sistem memverifikasi response, status akun, dan anti-replay state/nonce.
4. Untuk role administratif, sistem meminta dan memverifikasi MFA.
5. Sistem memuat role serta scope aktif dan menolak grant expired.
6. Sistem membuat session, meregenerasi session ID, dan mencatat audit event.
7. Sistem mengarahkan user ke landing page yang diizinkan.

Alternative flow:

- A1: Session valid masih ada; sistem melakukan step-up MFA hanya untuk aksi berisiko.
- A2: Credential lupa; user masuk reset token sekali pakai, lalu kembali ke step 2.
- A3: IdP unavailable; local emergency account hanya boleh dipakai jika policy/role khusus aktif dan diaudit.

Failure flow:

- Credential/nonce/MFA salah menghasilkan pesan generik; tidak mengungkap identifier terdaftar.
- Lima kegagalan dalam window mengunci autentikasi sementara dan mencatat security event.
- Role/scope tidak ditemukan: login dapat ditolak atau session tanpa fungsi bisnis; tidak ada fallback broad access.

## UC-02 — Membuat Survei

| Field | Specification |
|---|---|
| ID | UC-02 |
| Nama | Membuat template dan instrument version draft |
| Tujuan | Menciptakan artefak survei berversi dengan purpose, family, population, owner, dan intended method. |
| Aktor | Admin LPMPP |
| Trigger | Admin memilih `Buat survei`. |
| Precondition | Login/MFA valid; `template.create`; unit scope aktif; family/purpose owner tersedia. |
| Postcondition | Sukses: template berkode unik dan version draft tercipta. Gagal: tidak ada artefak parsial published. |
| Data | code, title, family, purpose, population type, owner, language, semantic version, intended method. |
| Permission | `template.create` pada unit. |
| Business rules | BR-TPL-001–004; FR-TPL-001–003. |

Main flow:

1. Sistem memeriksa permission dan scope.
2. Admin memilih family dan memasukkan purpose/decision, population, owner, serta judul.
3. Sistem memvalidasi field, kode unik, dan semantic version awal.
4. Sistem membuat template dan version `draft` dalam satu transaksi.
5. Sistem mencatat creator, timestamp, dan audit event.
6. Sistem membuka editor version untuk UC-03.

Alternative flow:

- A1: Admin membuat draft version baru dari version published; sistem menyalin content sebagai draft baru dan menyimpan source version.
- A2: Kode usulan telah ada; admin memilih template existing dan membuat version baru bila scope mengizinkan.

Failure flow:

- Kode/version duplikat atau field tidak valid: transaksi dibatalkan dan field error ditampilkan.
- Scope berubah/expired saat simpan: operasi 403, input lokal dapat dipertahankan sementara tanpa membuat record.

## UC-03 — Menambahkan Pertanyaan

| Field | Specification |
|---|---|
| ID | UC-03 |
| Nama | Menambah dan mengatur pertanyaan pada version draft |
| Tujuan | Menyusun hierarchy category–indicator–item dengan scale, branching, method, dan scoring behavior yang konsisten. |
| Aktor | Admin LPMPP |
| Trigger | Admin memilih `Tambah pertanyaan` pada version draft. |
| Precondition | Version berstatus draft/returned; user mempunyai `template.update`; version belum locked review. |
| Postcondition | Sukses: item tersimpan dan preflight version diperbarui. Gagal: version sebelumnya tetap konsisten. |
| Data | item code/text, category, indicator, answer type, choices, required, N/A, branching, paired item, method/scoring. |
| Permission | `template.update` pada draft dalam unit scope. |
| Business rules | BR-TPL-002–006, BR-RSP-002/005; FR-TPL-003–006. |

Main flow:

1. Sistem memuat version revision terakhir dan lock token.
2. Admin memilih category/indicator dan memasukkan item serta answer type.
3. Admin mengatur choices, required/optional, N/A, branching, method, dan scoring.
4. Sistem memvalidasi unique code, choice exclusivity, branch target, dan dependency cycle.
5. Untuk IPA/SERVQUAL, sistem memvalidasi paired item serta scale compatibility.
6. Sistem menyimpan item, urutan, revision baru, dan audit delta.
7. Sistem menjalankan preflight dan menampilkan blocker/warning.

Alternative flow:

- A1: Admin menyalin item dari version/library; provenance dan source version dicatat.
- A2: Admin mengubah urutan; hanya order berubah dan revision tercatat.
- A3: Admin memilih N/A untuk population parsial; item tetap required secara UI tetapi N/A sah non-scoring.

Failure flow:

- Concurrent revision berbeda: simpan ditolak dengan conflict; user membandingkan/reapply perubahan.
- Pair/branch/scale invalid: item tidak masuk revision dan pesan spesifik tampil.
- Version published/under-review: update ditolak; admin harus membuat version/revision yang diizinkan.

## UC-04 — Mengirim Survei untuk Review

| Field | Specification |
|---|---|
| ID | UC-04 |
| Nama | Mengirim instrument version untuk review |
| Tujuan | Membekukan revision dan membuat assignment review yang dapat ditelusuri. |
| Aktor | Admin LPMPP; Reviewer |
| Trigger | Admin memilih `Kirim untuk review`. |
| Precondition | Version draft/returned; preflight tanpa blocker; reviewer berbeda dari creator dan memiliki scope; due date valid. |
| Postcondition | Sukses: version `under_review`, hash/revision locked, task reviewer dan notification tercipta. Gagal: version tetap editable pada state sebelumnya. |
| Data | version hash, review scope, reviewer, due date, change summary, validation evidence references. |
| Permission | `validation.create`; `template.read`; assigned reviewer. |
| Business rules | BR-ACC-004, BR-VAL-001–004, BR-TPL-002; FR-VAL-001/006/008. |

Main flow:

1. Sistem memeriksa permission, scope, state, dan preflight.
2. Admin memilih reviewer, scope, due date, serta note.
3. Sistem memastikan reviewer aktif, bukan creator, dan tidak conflict yang belum dinyatakan.
4. Sistem menghitung content hash dan membekukan revision.
5. Sistem membuat review assignment dan mengubah state ke `under_review` secara atomik.
6. Sistem mencatat audit dan mengantre notification reviewer.

Alternative flow:

- A1: Beberapa reviewer ditugaskan; decision rule/quorum dipin sebelum assignment.
- A2: Notification provider gagal; assignment tetap sah dan notification diretry idempotently.

Failure flow:

- Preflight blocker/reviewer invalid/self-review: submit ditolak dan daftar masalah tampil.
- Perubahan concurrent setelah preflight: hash mismatch; admin harus memuat ulang dan mengulang submit.

## UC-05 — Menyetujui dan Mempublikasikan Survei

| Field | Specification |
|---|---|
| ID | UC-05 |
| Nama | Approve instrument version dan publish campaign |
| Tujuan | Menjalankan dua gate terpisah: reviewer menyetujui version; Admin LPMPP mempublikasikan campaign yang preflight-complete. |
| Aktor | Reviewer/Metodolog; Admin LPMPP |
| Trigger | Reviewer memilih Approve lalu Admin memilih Publish campaign. |
| Precondition | Review assignment aktif; hash unchanged; finding resolved; campaign draft merujuk version approved; population/privacy/scoring/period/owner lengkap. |
| Postcondition | Sukses: approval immutable; campaign snapshot published/scheduled. Gagal: version/campaign tetap pada state aman sebelumnya. |
| Data | approval, hash, conflict declaration, campaign snapshot, population count, privacy notice, threshold, schedule. |
| Permission | Reviewer `validation.approve`; Admin `campaign.publish`; creator tidak self-approve. |
| Business rules | BR-ACC-004, BR-VAL-002–004, BR-CAM-001–006; FR-VAL-007–008, FR-CAM-001–008. |

Main flow:

1. Reviewer membuka assignment, memeriksa content/evidence, dan menyatakan conflict declaration.
2. Reviewer memilih Approve dengan komentar; sistem memverifikasi reviewer ≠ creator dan hash sama.
3. Sistem menyimpan approval immutable serta state version `approved`.
4. Admin membuat/membuka campaign dari version approved dan melengkapi snapshot.
5. Sistem menjalankan preflight population, period, privacy, scoring, threshold, notification, dan owner tindakan.
6. Admin melakukan re-auth bila diperlukan dan memilih Publish/Schedule.
7. Sistem membekukan campaign snapshot, mengubah state, mencatat audit, dan menjadwalkan open.

Alternative flow:

- A1: Reviewer memilih Return; version menjadi `returned`, finding/comment dikirim ke Admin, tidak ada publish.
- A2: Campaign dijadwalkan untuk waktu mendatang; state `scheduled` hingga boundary timezone.
- A3: Notification belum dikirim pada publish; job invitation berjalan saat campaign `open`.

Failure flow:

- Hash changed/self-approval/review incomplete: approval ditolak.
- Preflight campaign gagal: publish ditolak dengan blocker; version tetap approved.
- Scheduler/job gagal: campaign tidak dinyatakan open sampai transisi committed; alert/retry dan audit dibuat.

## UC-06 — Mengisi Survei

| Field | Specification |
|---|---|
| ID | UC-06 |
| Nama | Mengisi pertanyaan survei |
| Tujuan | Mengumpulkan jawaban relevan dengan notice, consent, branching, dan accessibility yang benar. |
| Aktor | Responden |
| Trigger | Responden membuka secure invitation campaign aktif. |
| Precondition | Token/session sah; campaign open; participant eligible; response belum submitted/revoked. |
| Postcondition | Sukses parsial: response draft/partial tersimpan. Sukses penuh diteruskan ke UC-08. Gagal: tidak ada jawaban salah-attribusi. |
| Data | notice version, consent, item response, N/A/refusal, branch, draft metadata minimum. |
| Permission | invitation/campaign sendiri; `response.create/read-own/update-own`. |
| Business rules | BR-CAM-005, BR-RSP-001–005; FR-RSP-001–005/007–008. |

Main flow:

1. Sistem memverifikasi token, eligibility, campaign window, dan rate limit.
2. Sistem menampilkan purpose/privacy/voluntary notice sebelum item.
3. Responden memberi consent.
4. Sistem membuat/memuat draft tanpa direct identity pada content store sesuai privacy mode.
5. Responden menjawab screener dan item; sistem menerapkan branching snapshot.
6. Setiap perubahan memicu UC-07 dan status save accessible.
7. Responden dapat berpindah halaman dan meninjau jawaban.

Alternative flow:

- A1: Responden menolak consent; proses berhenti tanpa response content.
- A2: Responden memilih N/A; nilai disimpan non-scoring dan branch berikutnya mengikuti rule.
- A3: Responden berhenti; draft tetap partial sesuai retention dan bukan completion.

Failure flow:

- Token invalid/expired/replayed atau campaign closed: akses ditolak dengan contact point aman.
- Branch/config snapshot corrupt: rendering berhenti, jawaban sebelumnya tidak hilang, correlation ID/support message tampil.

## UC-07 — Autosave Jawaban

| Field | Specification |
|---|---|
| ID | UC-07 |
| Nama | Autosave draft response |
| Tujuan | Menyimpan perubahan draft idempotently tanpa membuat response ganda. |
| Aktor | Responden; Response Service |
| Trigger | Jawaban berubah atau debounce maksimal 5 detik tercapai. |
| Precondition | Draft aktif; campaign/session masih mengizinkan update; revision/idempotency key tersedia. |
| Postcondition | Sukses: revision terbaru persisted dan acknowledgment tampil. Gagal: client mempertahankan unsaved change dan memberi status/retry aman. |
| Data | response ID opaque, revision, changed item/value, idempotency key, client timestamp minimum. |
| Permission | `response.update-own` sebelum submit. |
| Business rules | BR-RSP-001–003; FR-RSP-004; NFR-REC-003. |

Main flow:

1. Client menggabungkan perubahan dalam debounce window.
2. Client mengirim patch dengan revision dan idempotency key.
3. Sistem memverifikasi token/session, state, item/branch, dan revision.
4. Sistem menyimpan perubahan dan revision baru atomically.
5. Sistem mengembalikan saved revision; client mengumumkan `Tersimpan`.

Alternative flow:

- A1: Request retry dengan idempotency key sama mengembalikan acknowledgment hasil sebelumnya.
- A2: Offline/timeout; client menyimpan perubahan lokal sementara dan retry dengan backoff saat online.
- A3: Conflict dari tab lain; sistem mengembalikan current revision dan meminta user memilih session aktif/reload.

Failure flow:

- State submitted/expired atau item tidak valid: patch ditolak; client tidak mengklaim saved.
- Storage/DB gagal: transaksi rollback, metric/alert dibuat, client mempertahankan unsaved state.

## UC-08 — Mengirim Respons Final

| Field | Specification |
|---|---|
| ID | UC-08 |
| Nama | Submit final response exactly once |
| Tujuan | Memvalidasi, membekukan, dan menerima satu response submitted dengan receipt nonidentifying. |
| Aktor | Responden |
| Trigger | Responden memilih `Kirim respons`. |
| Precondition | Draft aktif; campaign menerima submit; consent sah; item wajib/branch eligible lengkap atau N/A sah. |
| Postcondition | Sukses: satu response `submitted`, participation `complete` tanpa join content pada detached mode, receipt dibuat. Gagal: draft tetap aman dan tidak ditandai complete. |
| Data | response revision, completion validation, idempotency key, receipt token/hash, submitted-at minimum. |
| Permission | `response.submit` untuk invitation sendiri. |
| Business rules | BR-RSP-001–003, BR-CAM-005; FR-RSP-005–007. |

Main flow:

1. Sistem menampilkan review dan error summary bila ada.
2. Responden mengonfirmasi submit.
3. Client mengirim final revision dan idempotency key.
4. Sistem mengunci response, memverifikasi state, branch, required, dan revision.
5. Sistem menulis submitted response atomically dan membekukan content.
6. Participation service ditandai complete menggunakan mekanisme detached yang tidak menyimpan response ID.
7. Sistem membuat receipt nonidentifying dan menampilkan confirmation.

Alternative flow:

- A1: Duplicate click/retry mengembalikan receipt yang sama.
- A2: Campaign close terjadi setelah draft dimulai; grace policy snapshot menentukan accept/reject secara deterministik.

Failure flow:

- Validation gagal: submit tidak dilakukan; error fokus ke item pertama dan draft tetap ada.
- DB/participation update gagal: transaksi/compensation mencegah state content complete yang inkonsisten; reconciliation job mengantre kasus.

## UC-09 — Melihat Dashboard Hasil

| Field | Specification |
|---|---|
| ID | UC-09 |
| Nama | Melihat dashboard aggregate released |
| Tujuan | Menampilkan hasil dengan scope, denominator, quality, limitation, dan privacy control. |
| Aktor | Pimpinan, Admin LPMPP, Analyst, unit owner |
| Trigger | User memilih campaign/report/filter. |
| Precondition | Login valid; `report.read`/`analysis.read`; output released; scope/threshold policy tersedia. |
| Postcondition | Sukses: hanya aggregate yang authorized dan tersuppress tampil; view audit/metric tercatat sesuai policy. |
| Data | aggregate, `n`, missing/N/A, coverage, version, method, filter, suppression state, limitation. |
| Permission | `report.read` atau `analysis.read`; hierarchy scope; no raw response implication. |
| Business rules | BR-REP-001–004, BR-SCR-001; FR-REP-001–003. |

Main flow:

1. Sistem memeriksa authentication, permission, hierarchy scope, dan report state.
2. Sistem menyelesaikan filter hanya pada dimension allowlist.
3. Reporting service mengambil aggregate versioned.
4. Policy engine menerapkan minimum/sensitive/complementary suppression dan anti-differencing.
5. Dashboard menampilkan result, `n`, missing, coverage, period, version, method, dan limitation.
6. Zero/no-data/not-calculated/error/suppressed dibedakan.

Alternative flow:

- A1: Cell n=10–29 dapat tampil internal sebagai descriptive-only sesuai policy.
- A2: Comparison diminta; hanya version/cohort compatible dan n≥30 setiap cell dibandingkan.

Failure flow:

- Scope/filter manipulasi: server menolak/menyaring; tidak mengandalkan hidden UI.
- Aggregate/policy tidak tersedia: tampil `not calculated/error`, bukan nol; raw fallback dilarang.

## UC-10 — Menjalankan Analisis Statistik

| Field | Specification |
|---|---|
| ID | UC-10 |
| Nama | Menjalankan scoring dan analisis statistik |
| Tujuan | Menghasilkan analysis run reproducible sesuai method/scoring snapshot. |
| Aktor | Analyst atau Admin LPMPP berpermission execute |
| Trigger | User memilih dataset campaign dan analysis plan lalu `Run`. |
| Precondition | Campaign/data eligible; submitted responses tersedia; method precondition/scoring snapshot approved; user assigned. |
| Postcondition | Sukses: immutable run ID/output/quality flags/lineage. Gagal: tidak ada output released; failure/retry history tercatat. |
| Data | input hash, response aggregate/de-identified input, method, formula, parameter, software version, output. |
| Permission | `analysis.execute`; campaign scope. |
| Business rules | BR-SCR-001–007, BR-TPL-005–006; FR-ANA-001–006/008. |

Main flow:

1. Sistem memeriksa permission, scope, campaign state, dan plan approval.
2. Sistem membuat input snapshot/hash dari submitted response eligible.
3. Method validator memeriksa pairing, scale, minimum data, dan scoring version.
4. Sistem mengantre analysis run dengan run ID.
5. Worker menghitung scoring, distribution, `n`, missing/N/A, coverage, dan quality flags.
6. Sistem menyimpan output, parameter, version, log, dan checksum.
7. Reviewer/analyst memeriksa result sebelum release/report.

Alternative flow:

- A1: Run ulang dengan input/parameter sama menghasilkan run ID baru tetapi output dapat dibandingkan hash.
- A2: Weighted analysis hanya tersedia bila design/probability/calibration approved; weighted dan unweighted disimpan.
- A3: Reliability/item analysis dijalankan sebagai sub-run terpisah, tidak dilabel validitas.

Failure flow:

- Method/data precondition gagal: job tidak diantre; reason code spesifik.
- Worker timeout/fail: run `failed`, partial output tidak released, retry membuat attempt history tanpa mengganti input.

## UC-11 — Menjalankan Analisis AI

| Field | Specification |
|---|---|
| ID | UC-11 |
| Nama | Menjalankan AI-assisted qualitative analysis |
| Tujuan | Menghasilkan saran coding/tema pada data aman untuk human review, bukan keputusan final. |
| Aktor | Analyst; Human Reviewer; Privacy/Security policy services; AI Provider |
| Trigger | Analyst memilih approved AI use case dan input pool. |
| Precondition | Feature on; registry/DPIA/provider/model active; field allowlist; data de-identified/redacted; pool n≥threshold; human reviewer assigned; secret reference valid. |
| Postcondition | Sukses: AI output `awaiting_human_review` dengan lineage; final hanya setelah reviewer. Gagal: tidak ada output approved dan data tidak dikirim bila preflight gagal. |
| Data | de-identified text allowlist, prompt template/version, provider/model, run ID, output, reviewer correction; no direct identifier/secret. |
| Permission | `ai.execute`; explicit use-case/campaign assignment. |
| Business rules | BR-GOV-006–007, BR-SCR-008, BR-REP-002; FR-ANA-007–008, FR-GOV-006–008. |

Main flow:

1. Sistem memeriksa feature flag, permission, registry approval, provider/model, threshold, dan reviewer.
2. Redaction/classification gate membuat payload allowlisted dan melakukan PII scan.
3. Sistem membuat AI job/input hash tanpa secret di payload/log.
4. Integration service mengambil secret reference dan memanggil provider dengan timeout/idempotency.
5. Output disimpan sebagai `awaiting_human_review` dengan provider/model/prompt version.
6. Human reviewer accept/correct/reject theme, sentiment, quotation, dan recommendation.
7. Sistem menyimpan decision/correction serta hanya output reviewed yang dapat masuk report.

Alternative flow:

- A1: Reviewer menolak seluruh output; job menjadi `rejected`, analysis manual dapat digunakan.
- A2: PII scan menemukan data; item masuk redaction queue dan tidak dikirim.
- A3: Feature dimatikan setelah job queued; job dibatalkan sebelum provider call atau output dikarantina.

Failure flow:

- Governance gate/threshold/secret tidak lengkap: fail-closed sebelum data keluar.
- Provider timeout/429/5xx: bounded retry/backoff; fungsi survey/statistik tetap tersedia.
- Output malformed/unsafe: quarantine, human reviewer diberi reason; tidak auto-recommend.

## UC-12 — Mengelola Konfigurasi API AI

| Field | Specification |
|---|---|
| ID | UC-12 |
| Nama | Mengelola registry provider/use case dan secret reference AI |
| Tujuan | Mengaktifkan integrasi AI tanpa menampilkan/menyimpan secret dalam aplikasi atau melewati approval. |
| Aktor | Super Admin; Privacy Officer; Security Owner; Auditor |
| Trigger | Super Admin membuat, merotasi, menonaktifkan, atau menguji konfigurasi. |
| Precondition | Login MFA/step-up; permission secret/config; secret manager tersedia; dual approval untuk activation. |
| Postcondition | Sukses: registry berversi dan secret reference active/revoked; nilai secret tidak dapat dibaca. Gagal: konfigurasi tetap inactive/versi lama aman. |
| Data | provider endpoint allowlist, model/version, location, retention, no-training contract, DPIA, use case, fields, secret reference, status. |
| Permission | `secret.manage`, policy/AI config; approver berbeda dari requester untuk activation. |
| Business rules | BR-ACC-004, BR-GOV-006–007; FR-GOV-006–008. |

Main flow:

1. Sistem melakukan step-up MFA dan memeriksa permission/scope.
2. Admin memasukkan metadata provider/use case dan secret melalui secure secret-manager channel.
3. Secret manager mengembalikan opaque reference; aplikasi tidak membaca kembali nilai.
4. Sistem memvalidasi endpoint allowlist, TLS, model, contract/location/retention, DPIA, field allowlist, evaluator.
5. Privacy/Security approver meninjau dan memutuskan activation.
6. Sistem mengaktifkan version/config, mencatat before/after metadata dan audit.
7. Test call memakai synthetic nonpersonal payload dan hasil tidak membuka secret.

Alternative flow:

- A1: Rotate membuat secret version baru lalu health test; version lama direvoke setelah cutover.
- A2: Disable/kill switch memblokir job baru tanpa menghapus run history.

Failure flow:

- Secret manager/validation/test gagal: config tidak active dan secret tidak diecho.
- Approval kurang/self-approval: activation ditolak.
- Secret terdeteksi di log/response: security incident, revoke segera, feature off.

## UC-13 — Mengekspor Laporan

| Field | Specification |
|---|---|
| ID | UC-13 |
| Nama | Meminta, membuat, menyetujui, dan mengunduh export laporan |
| Tujuan | Menghasilkan file scoped dan tersuppress dengan lifecycle serta audit yang aman. |
| Aktor | Requester berpermission export; Reviewer Release; Export Worker |
| Trigger | User memilih format/filter lalu `Export`. |
| Precondition | Report/analysis released; requester berpermission/scope; format diizinkan; approval tersedia/ditugaskan. |
| Postcondition | Sukses: export completed, approved, classified, link sekali pakai ≤24 jam. Gagal: tidak ada file accessible. |
| Data | report/version, filter, format, classification, suppression policy, requester, approval, file checksum, expiry. |
| Permission | `report.export`; reviewer `report.approve`; raw export memerlukan exception terpisah. |
| Business rules | BR-ACC-004, BR-REP-001–006; FR-REP-002/004–008. |

Main flow:

1. Sistem memeriksa permission, scope, report state, format, dan filter allowlist.
2. Sistem membuat export request/job dengan classification dan approval requirement.
3. Worker mengambil aggregate versioned dan menerapkan policy/suppression yang sama dengan dashboard.
4. Worker membuat file, metadata, confidentiality marking, checksum, dan hasil parity check.
5. Reviewer nonrequester approve/reject release bila diwajibkan.
6. Sistem membuat link sekali pakai terikat requester dan expiry.
7. Download berhasil dicatat; file/link mengikuti expiry/revocation.

Alternative flow:

- A1: Reviewer reject; request menjadi rejected dengan komentar, tidak ada download.
- A2: Job gagal sementara; requester dapat retry yang membuat attempt baru tanpa file duplikat.
- A3: Raw research extract diarahkan ke exception workflow purpose/owner/privacy/ethics/de-identification.

Failure flow:

- Scope/suppression parity gagal: file dikarantina dan job failed.
- Link cross-user/replay/expired/revoked: download ditolak dan event dicatat.

## UC-14 — Membuat Temuan dan Tindak Lanjut

| Field | Specification |
|---|---|
| ID | UC-14 |
| Nama | Membuat finding dan action plan |
| Tujuan | Mengubah released result menjadi tindakan dengan owner, target, evidence, dan impact plan. |
| Aktor | Admin LPMPP/Analyst; PIC; Unit Owner |
| Trigger | User memilih result/indicator released lalu `Buat finding`. |
| Precondition | Source result released dan authorized; finding creator scope valid; owner/PIC/verifier tersedia. |
| Postcondition | Sukses: finding dan action plan versioned dengan due date serta assignment; source result immutable. |
| Data | source report/result, severity, rationale, owner unit, PIC, action, target, milestone, due date, resource, impact plan. |
| Permission | `finding.create/update`; PIC `action.create/update`; hierarchy/assignment. |
| Business rules | BR-PPE-001, BR-PPE-003–005; FR-PPE-001–004/007. |

Main flow:

1. Sistem memeriksa permission/scope dan menautkan released source result.
2. Creator memasukkan finding, severity, evidence, owner unit, dan due date.
3. Sistem memvalidasi completeness dan membuat finding `assigned/pending_acceptance`.
4. PIC menerima/menolak assignment dengan alasan.
5. Jika menerima, PIC menulis action, output, target, milestone, resource, evidence criteria, dan impact plan.
6. Unit owner/LPMPP menyetujui plan sesuai policy.
7. Sistem mengaktifkan action, menjadwalkan reminder/escalation, dan mencatat audit.

Alternative flow:

- A1: PIC menolak; finding kembali untuk reassignment tanpa menghapus histori.
- A2: Beberapa action/PIC dibuat untuk satu finding; setiap action mempunyai target/due sendiri.
- A3: Impact measurement belum tersedia; waiver hanya dapat diajukan dengan approver/expiry, bukan auto-close.

Failure flow:

- Source tidak released/scope salah/field owner-target kosong: creation ditolak.
- Notification gagal: assignment tetap sah, retry idempotent; overdue tetap dihitung dari due date.

## UC-15 — Memverifikasi Tindak Lanjut

| Field | Specification |
|---|---|
| ID | UC-15 |
| Nama | Memverifikasi evidence dan menilai readiness closure |
| Tujuan | Memastikan evidence memenuhi target oleh actor independen dan closure mempunyai impact evidence/waiver. |
| Aktor | Verifikator; PIC; Admin LPMPP; Pimpinan |
| Trigger | PIC mengirim action untuk verification. |
| Precondition | Action aktif; submission/evidence version tersedia; verifikator assigned dan bukan PIC; acceptance criteria jelas. |
| Postcondition | Sukses: decision verified/needs-rework/rejected immutable. Closure hanya setelah verified + impact evaluation/approved waiver. |
| Data | action target, evidence version/checksum, reviewer checklist, decision, reason, impact result/waiver, timestamps. |
| Permission | `action.verify`; assigned; separation of duties. |
| Business rules | BR-PPE-002–005; FR-PPE-003–007. |

Main flow:

1. Sistem memeriksa assignment, state, permission, dan verifikator ≠ PIC.
2. Verifikator melihat source finding, action target, evidence version, dan history read-only.
3. Verifikator membandingkan evidence dengan acceptance criteria.
4. Verifikator memilih `verified`, `needs_rework`, atau `rejected` dan mengisi alasan/evidence review.
5. Sistem menyimpan decision immutable serta memberi notifikasi PIC/LPMPP.
6. Jika verified, impact evaluation tetap scheduled/open.
7. Setelah impact result atau waiver approved, LPMPP dapat menutup action/finding dan menyiapkan communication-back.

Alternative flow:

- A1: Needs-rework mengembalikan action ke PIC; revision baru tidak menghapus submission/decision lama.
- A2: Evidence memenuhi implementation tetapi impact belum due; status `verified_awaiting_impact`, bukan closed-effective.
- A3: Impact tidak efektif; action dapat ditutup sebagai ineffective dengan keputusan baru atau membuat action lanjutan.

Failure flow:

- Self-verification/assignment expired/evidence berubah saat review: decision ditolak dan reload wajib.
- Attachment unavailable/checksum mismatch: verification diblokir, security/data-quality issue dibuat.
- Closure tanpa verification dan impact/waiver: ditolak dan dicatat sebagai policy violation attempt.

## 2. Traceability summary

| Use case | FR domain | User story utama |
|---|---|---|
| UC-01 | FR-IAM | US-ADM-01, US-SAD-01–04 |
| UC-02–05 | FR-TPL, FR-VAL, FR-CAM | US-ADM-02–10, US-REV-01–05 |
| UC-06–08 | FR-RSP | US-RES-01–08 |
| UC-09/13 | FR-REP | US-PIM-01–05, US-REV-06 |
| UC-10/11 | FR-ANA | US-ADM-12, US-PIM-01–03, US-SAD-09–10 |
| UC-12 | FR-GOV | US-SAD-09–10 |
| UC-14/15 | FR-PPE | US-PIC-01–06, US-VER-01–06, US-PIM-06–08 |

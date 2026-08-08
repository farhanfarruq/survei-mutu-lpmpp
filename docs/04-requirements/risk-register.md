# Risk Register

Versi: **1.0 — 2026-08-07**  
Status: **baseline Phase 04; owner dan risk appetite memerlukan pengesahan**

## 1. Skala

Probability (`P`) dan Impact (`I`) memakai 1–5. Skor = `P × I`: 1–4 Low, 5–9 Medium, 10–16 High, 17–25 Critical. Residual adalah target setelah kontrol; bukan klaim bahwa kontrol telah diimplementasikan.

| ID | Risiko: penyebab → dampak | P | I | Inherent | Mitigasi/requirement utama | Owner | Indikator/trigger | Residual target |
|---|---|:---:|:---:|:---:|---|---|---|:---:|
| RSK-01 | Role/scope salah atau endpoint tidak memeriksa authorization → akses/modifikasi data lintas unit. | 4 | 5 | Critical 20 | deny-by-default, server-side scope, MFA, permission test, quarterly review; FR-IAM-002–004, NFR-SEC-002 | Security owner + Super Admin | forbidden-access test failure >0; orphan/expired grant >0 | Medium 6 |
| RSK-02 | Cell kecil, kombinasi filter, atau export berbeda dari layar → respondent dapat diidentifikasi. | 4 | 5 | Critical 20 | minimum/complementary suppression, parity golden dataset, privacy review; FR-REP-002–004 | Privacy Officer + LPMPP | parity mismatch >0; attempted cell n<policy; privacy complaint | Medium 8 |
| RSK-03 | Privileged insider/Super Admin memperoleh raw response → penyalahgunaan atau hilang kepercayaan. | 3 | 5 | High 15 | no automatic raw access, dual approval, SoD, audit read/download; BR-ACC-003–005 | Security owner + Auditor | self-grant attempt; unusual raw reads/downloads | Medium 6 |
| RSK-04 | Tracking participation mempunyai join key/log ke content → klaim anonim tidak benar dan pelanggaran notice. | 4 | 5 | Critical 20 | detached stores, schema/flow test, no IP/user-agent, truthful privacy mode; FR-CAM-006, NFR-PRI-001 | Privacy Officer + Architect | joinable field detected; linkage query succeeds | Low 4 |
| RSK-05 | Published instrument dapat diedit → hasil tidak reproducible/comparable. | 3 | 4 | High 12 | immutable version, snapshot/hash, diff/new version; FR-TPL-002/006–008 | Metodolog + Product owner | hash mismatch; update published attempt | Low 3 |
| RSK-06 | Item tidak valid atau alpha dianggap validitas → keputusan perbaikan salah. | 4 | 4 | High 16 | expert/cognitive/pilot gate, correct terminology, method-specific evidence; FR-VAL-001–008 | Lead metodolog | publish without evidence; I-CVI flags unresolved; report says “alpha validity” | Medium 6 |
| RSK-07 | Population frame usang/duplikat/salah eligibility → undangan salah, coverage bias, data pribadi berlebih. | 4 | 4 | High 16 | source date, schema preview, deduplication, dispositions, invalid-row threshold; FR-CAM-003–005 | Population data owner | invalid >1%; duplicate >2%; undeliverable >10% | Medium 8 |
| RSK-08 | Traffic puncak/putus jaringan menyebabkan draft/submit hilang atau ganda → respons tidak dipercaya. | 4 | 5 | Critical 20 | autosave status, exactly-once submit, idempotency, load/burst tests; FR-RSP-004/006 | Technical owner | error ≥1%; duplicate count >0; reconciliation mismatch >0 | Medium 6 |
| RSK-09 | Provider notifikasi gagal/duplikat/berlebihan → coverage turun atau respondent terganggu. | 4 | 3 | High 12 | idempotency, retry/backoff, max 3 reminder, delivery alert; FR-NOT-002–007 | Campaign operator + TIK | bounce >10%; failure >5%; duplicate logical message >0 | Medium 6 |
| RSK-10 | Formula, rounding, missing, atau snapshot salah → score/report keliru. | 3 | 5 | High 15 | Phase 03 golden vectors, immutable run lineage, independent review; FR-ANA-001–004/008 | Lead analyst | golden test failure; rerun hash mismatch; unexplained denominator drift | Low 4 |
| RSK-11 | SERVQUAL/SERVPERF/IPA/CSI/SKM/NPS dicampur atau label salah → interpretasi menyesatkan. | 4 | 4 | High 16 | method preconditions, separate output/label, approved analysis plan; FR-TPL-005/FR-ANA-002 | Metodolog | method preflight failure; `ACSI` label without model; IPA missing importance | Low 4 |
| RSK-12 | Link/file export bocor atau diteruskan → disclosure data di luar purpose. | 4 | 5 | Critical 20 | scoped/suppressed export, one-time link ≤24h, classification, encryption, audit/revoke; FR-REP-004–008 | Data owner + Security owner | cross-user/replay download; file beyond expiry; unapproved export | Medium 8 |
| RSK-13 | Reviewer memiliki konflik atau self-approval → approval tidak independen. | 3 | 4 | High 12 | assignment/conflict declaration, creator ≠ approver, immutable approval hash; FR-VAL-007–008 | Lead metodolog | self-approval attempt; conflict field missing | Low 4 |
| RSK-14 | Finding tidak mempunyai PIC/due date atau reminder → feedback loop berhenti pada laporan. | 4 | 4 | High 16 | mandatory owner/target, notification/escalation, leadership aging dashboard; FR-PPE-001–004/008 | Head LPMPP | unassigned finding >0; overdue rate >20%; no update 30 hari | Medium 8 |
| RSK-15 | PIC memverifikasi sendiri atau evidence hanya “uploaded” → action ditutup tanpa bukti efektivitas. | 4 | 4 | High 16 | PIC ≠ verifier, evidence acceptance, impact/waiver gate; FR-PPE-005–007 | Verifier lead + LPMPP | self-verification attempt; closure without impact/waiver | Low 4 |
| RSK-16 | AI menerima identifier, provider melatih/menahan data, atau output salah dipercaya → privacy harm/keputusan salah. | 4 | 5 | Critical 20 | off by default, registry/DPIA/contract, allowlist, threshold/redaction, human review, kill switch; FR-ANA-007/FR-GOV-006–008 | AI use-case owner + Privacy Officer | unapproved activation; restricted field in payload; reviewer correction rate >20% | Medium 8 |
| RSK-17 | Backup tidak valid atau recovery terlalu lambat → kehilangan response dan downtime berkepanjangan. | 3 | 5 | High 15 | encrypted backup/checksum, quarterly restore, RPO 15m/RTO 4h proposed; NFR-BCK/REC | TIK operations | backup age >24h; WAL gap >15m; restore drill fail | Medium 6 |
| RSK-18 | UI tidak accessible/browser compatible → kelompok tertentu excluded dan response bias. | 4 | 4 | High 16 | WCAG 2.2 AA, keyboard/screen-reader/browser matrix tests; FR-RSP-008, NFR-ACC/CMP | Product owner + Accessibility lead | accessibility blocker >0; task completion failure; browser E2E fail | Medium 6 |
| RSK-19 | Retention job salah menghapus legal-hold atau menahan data lewat jadwal → kehilangan evidence atau pelanggaran minimization. | 3 | 5 | High 15 | policy version, legal-hold precedence, dual control, tombstone/reconciliation; FR-GOV-002–004 | Privacy Officer + TIK | overdue deletion >0; held deletion attempt; tombstone mismatch | Medium 6 |
| RSK-20 | Regulasi/instrumen BAN-PT/LAM berubah → template/report tidak lagi sesuai. | 3 | 4 | High 12 | source register review triwulan, effective dating, version compatibility, owner regulatory watch | Compliance/LPMPP | source status change; regulator notice; template review overdue >90 hari | Medium 6 |
| RSK-21 | Open text berisi identitas/tuduhan/malicious formula → disclosure atau serangan saat export. | 4 | 4 | High 16 | optional notice, redaction queue, neutralize spreadsheet formula, no raw routine export; FR-RSP-005/FR-REP-004 | Privacy Officer + Security owner | PII detection; cell starts `=,+,-,@`; unreviewed quote | Medium 6 |
| RSK-22 | Perbandingan unit/periode dilakukan saat version/coverage tidak comparable → ranking palsu. | 4 | 4 | High 16 | compatibility metadata, n≥30 each, coverage/nonresponse warning, no auto-ranking; FR-REP-001–003 | Lead analyst + Pimpinan | comparison override; coverage gap >15 percentage points | Medium 8 |

## 2. Treatment workflow

1. Owner mengonfirmasi sebab, inherent score, control, indikator, target residual, due date, dan evidence.
2. Risiko Critical tidak boleh diterima oleh owner operasional saja; membutuhkan pimpinan serta privacy/security owner yang relevan.
3. Indikator melewati trigger membuat issue/treatment review, bukan otomatis menyatakan insiden tanpa triage.
4. Residual risk diverifikasi setelah control test. “Dokumentasi tersedia” bukan bukti control efektif.
5. Exception memiliki compensating control dan expiry; risiko dibuka kembali saat expiry.

## 3. Open approvals

- risk appetite dan authority penerima residual risk;
- nama owner organisasi, escalation SLA, serta reporting cadence;
- threshold indikator nonresponse, correction rate AI, overdue, dan coverage gap;
- target NFR berstatus `PROPOSED` dan capacity sizing;
- incident severity matrix serta kanal pelaporan PDP.

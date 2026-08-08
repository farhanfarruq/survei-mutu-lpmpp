# Module Map

Versi: **1.0 — 2026-08-07**  
Tujuan: membagi product domain tanpa menetapkan arsitektur teknis Phase 05+

## 1. Peta modul

| Kode | Modul | Tanggung jawab | Aktor utama | Input | Output | MVP |
|---|---|---|---|---|---|:---:|
| M01 | Identity, Role, and Data Scope | akun internal, role, permission, unit hierarchy, assignment | Super Admin, Admin LPMPP | identity dan organization source | session, grant, scope | Ya |
| M02 | Taxonomy, Template, and Version | family, purpose, category, indicator, item, scale, method/scoring snapshot | Admin LPMPP, metodolog | standard/metodologi approved | instrument version | Ya |
| M03 | Review and Validation | assignment review, comment, approval, validation evidence/status | Reviewer, Admin LPMPP | draft version dan evidence | approved/returned version | Ya, minimum |
| M04 | Campaign and Population | campaign, period, owner, frame, eligibility, sampling/census, privacy snapshot | Admin LPMPP, data owner | approved version dan population | campaign snapshot, participant set | Ya |
| M05 | Respondent and Response | notice, consent, branching, autosave, submit, receipt | Responden | campaign dan secure invitation | draft/submitted response | Ya |
| M06 | Notification and Participation | invitation/reminder, delivery/disposition, participation monitoring | Admin LPMPP | participant dan channel template | delivery/participation status | Ya, email dasar |
| M07 | Scoring, Analysis, and Data Quality | scoring approved, denominator, missing/N/A, quality/coverage, run lineage | Analyst, Admin LPMPP | submitted response dan scoring snapshot | aggregate/quality output | Ya, metode pilot |
| M08 | Reporting and Export | dashboard, release approval, suppression, approved export | Pimpinan, reviewer, analyst | approved analysis output | released report/file | Ya, aggregate |
| M09 | Findings and PPEPP Follow-up | finding, priority, action, PIC, evidence, verification, impact | LPMPP, unit owner, PIC, verifikator | released result/evidence | verified action dan impact status | Ya, core loop |
| M10 | Governance, Privacy, Audit, and Operations | policy, classification, retention, audit, exception, backup/monitoring config | Super Admin, PDP/hukum, auditor, TIK | institutional policy | control evidence dan operational state | Ya, minimum control |

## 2. Dependency sequence

| Tahap | Dependency wajib | Consumer |
|---|---|---|
| A. Governance ready | M01 scope + M10 privacy/policy | seluruh modul |
| B. Instrument ready | M02 draft + M03 approval | M04 campaign |
| C. Campaign ready | M04 snapshot/population + M06 invitation config | M05 respondent flow |
| D. Data ready | M05 submitted responses + M04 frame/disposition | M07 analysis |
| E. Release ready | M07 approved output + M10 threshold policy | M08 report/export |
| F. Improvement ready | M08 released finding source + organization owner | M09 follow-up |
| G. Effectiveness ready | M09 verified evidence + next-period/operational measure | impact evaluation |

Tidak ada direct path dari response raw ke pimpinan atau PIC. Result melewati aggregation, quality context, scope, suppression, dan release control.

## 3. Capability by module and horizon

| Modul | MVP | Post-MVP | Long-term |
|---|---|---|---|
| M01 | local/SSO-compatible admin identity, role/scope dasar, MFA admin | delegated/time-bound access, automated role sync | multi-institution federation jika dibutuhkan |
| M02 | family, template/version, closed/open item, skala, branching, satu metode pilot | library multi-family, compare version, multilingual | governed instrument exchange/benchmark library |
| M03 | reviewer assignment, comment, approve/return, evidence reference | CVI workflow, cognitive/pilot registry, advanced validation evidence | cross-institution expert panel workflow |
| M04 | campaign, CSV population, eligibility, census, period, preflight | API sync, probability sampling/weight metadata, recurring campaign | event-driven population orchestration |
| M05 | responsive/accessible web, autosave, submit, N/A, receipt | multilingual, saved return across channels, advanced usability | native/offline only if evidence supports |
| M06 | email invite/reminder, delivery status, aggregate participation | channel preference, approved additional provider | optimized contact strategy with fairness guard |
| M07 | selected scoring, missing/denominator, quality/coverage, reproducible run | reliability/item analysis, additional approved methods | advanced psychometric workspace; governed AI assist |
| M08 | aggregate dashboard, suppression, PDF/CSV/XLSX approved export | scheduled report, public summary, richer comparison | privacy-preserving benchmark across institutions |
| M09 | finding, action, PIC, due date, evidence, verification, impact plan | root-cause workflow, resource tracking, communication-back | portfolio outcome/standards improvement analytics |
| M10 | policy snapshot, audit critical action, retention config, backup/monitoring minimum | subject-right workflow, legal hold, exception automation | mature governance automation and continuous control monitoring |

## 4. Module ownership

| Modul | Business owner | Control owner | Technical steward | Confirmation status |
|---|---|---|---|---|
| M01 | Product Owner/LPMPP | Security + HR/data owner | TIK | Open |
| M02 | Lead metodolog/LPMPP | Reviewer/quality governance | Product team | Open |
| M03 | Lead metodolog | Quality assurance/auditor | Product team | Open |
| M04 | Campaign owner/LPMPP | Population data owner + PDP | Product team/TIK | Open |
| M05 | Product Owner | Accessibility + PDP | Product team | Open |
| M06 | Campaign owner | Humas/PDP | TIK/provider | Open |
| M07 | Lead analyst/metodolog | Independent reviewer | Product/data team | Open |
| M08 | LPMPP/pimpinan | Privacy officer + release reviewer | Product/data team | Open |
| M09 | LPMPP + unit owner | Verifikator/pimpinan | Product team | Open |
| M10 | Pimpinan/TIK/PDP | Auditor/security/privacy | TIK | Open |

## 5. External systems and boundaries

| External system/function | Data exchanged | Direction | MVP approach | Boundary |
|---|---|---|---|---|
| Identity provider/SSO | identifier, account status, role claims minimum | inbound | compatible adapter/local pilot account | no password replication if SSO used |
| SIAKAD/HR/alumni/CRM | population attributes minimum | inbound | validated CSV | source remains authoritative; no write-back |
| Email provider | recipient, template, secure link, delivery status | two-way metadata | one approved provider | no response content in message/provider |
| Storage/backup | encrypted attachments/database backup | outbound/recovery | institutional approved storage | classification and retention preserved |
| BI/statistical tool | approved aggregate/de-identified extract | outbound exception | manual approved export | no routine raw linkage |
| SPMI/AMI/document system | finding/evidence reference | future two-way | link/reference only | SIMUTU PT not master SPMI repository |
| AI provider | de-identified allowlisted text | future outbound | out of MVP | disabled until governance gate |

## 6. Cross-cutting controls

Cross-cutting capability tidak dijadikan modul bisnis terpisah bila hanya berupa control:

- accessibility berlaku pada semua UI;
- authorization/data scope berlaku pada semua request/job/export;
- audit berlaku pada seluruh aksi kritis;
- classification/retention mengikuti data, bukan menu;
- version/lineage mengikuti instrument, campaign, analysis, report, dan action evidence;
- observability/backup/recovery menopang semua modul.

## 7. YAGNI guard

Modul tidak boleh menjadi alasan memasukkan seluruh capability horizon berikutnya ke MVP. M07 ada di MVP, tetapi AI/advanced psychometrics tidak; M08 ada, tetapi public portal/benchmark tidak; M10 ada, tetapi governance automation lengkap tidak.

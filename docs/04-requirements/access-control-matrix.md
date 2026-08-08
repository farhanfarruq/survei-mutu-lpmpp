# Role, Permission, and Data-Scope Matrix

Versi: **1.0 — 2026-08-07**  
Model: **RBAC + data scope + object state + active assignment; deny-by-default**

## 1. Aktor dan scope default

| Role | Tujuan | Scope default | Batas utama |
|---|---|---|---|
| Responden | mengisi survei | invitation/campaign miliknya; content draft sendiri sampai submit | tidak melihat hasil/raw response; tidak mengubah submitted response |
| Admin LPMPP | mengelola program survei | institusi/unit yang ditugaskan | tidak mengelola secret/system role; tidak self-approve |
| Super Admin | governance teknis | konfigurasi institusi | tidak otomatis membaca raw response/comment |
| Reviewer/Metodolog | review instrumen/laporan | assignment aktif | read-only artefak yang dinilai; tidak mengubah content |
| Analyst | scoring/analisis | campaign approved yang ditugaskan | raw/de-identified hanya dengan grant; tidak release sendiri |
| Pimpinan | keputusan dan monitoring | hierarchy organisasi yang dipimpin | aggregate/released result; tidak raw response |
| PIC | menjalankan action plan | finding/action assigned | tidak memverifikasi action sendiri |
| Verifikator | memeriksa evidence | action assigned | tidak mengubah evidence PIC atau source finding |
| Auditor | assurance | audit scope approved | audit read/export tersuppress; tidak mengubah operasi |
| Privacy Officer | privacy/rights/exception | case/policy assigned | akses minimum case; bukan admin sistem umum |

## 2. Kode operasi

`C` Create, `R` Read, `U` Update, `D` Delete, `X` Execute/transisi/approve, `E` Export. Grant satu operasi tidak menyiratkan operasi lain. `—` berarti deny. `Assigned` berarti object assignment aktif; `Hierarchy` berarti unit dan descendant yang sah; `Policy` berarti approval khusus.

## 3. Permission matrix

| Permission | Resource | Op | Responden | Admin LPMPP | Super Admin | Reviewer | Analyst | Pimpinan | PIC | Verifikator | Auditor | Privacy Officer | Scope/condition |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|---|
| template.create | Template | C | — | ✓ | — | — | — | — | — | — | — | — | Unit |
| template.read | Template | R | — | ✓ | ✓ | ✓ | ✓ | — | — | — | ✓ | ✓ | Unit/Assigned/Audit case |
| template.update | Draft version | U | — | ✓ | — | — | — | — | — | — | — | — | Creator unit; draft only |
| template.delete | Draft version | D | — | ✓ | — | — | — | — | — | — | — | — | Draft unused; dual control if evidence exists |
| template.publish | Version | X | — | ✓ | — | ✓* | — | — | — | — | — | — | Admin executes after reviewer approval; `*` reviewer approves, not publishes |
| validation.create | Review/evidence | C | — | ✓ | — | ✓ | — | — | — | — | — | — | Assignment |
| validation.read | Review/evidence | R | — | ✓ | ✓ | ✓ | ✓ | — | — | — | ✓ | ✓ | Unit/Assigned/Audit case |
| validation.update | Own review | U | — | ✓ | — | ✓ | — | — | — | — | — | — | Draft review only |
| validation.approve | Version | X | — | — | — | ✓ | — | — | — | — | — | — | Assigned; reviewer ≠ creator |
| campaign.create | Campaign | C | — | ✓ | — | — | — | — | — | — | — | — | Unit |
| campaign.read | Campaign metadata | R | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | Unit/Assigned/Hierarchy |
| campaign.update | Campaign draft | U | — | ✓ | — | — | — | — | — | — | — | — | Draft; unit |
| campaign.delete | Campaign draft | D | — | ✓ | — | — | — | — | — | — | — | — | No response/notification; dual control |
| campaign.publish | Campaign | X | — | ✓ | — | — | — | — | — | — | — | — | Preflight pass + publish grant |
| population.create | Population import | C | — | ✓ | — | — | — | — | — | — | — | — | Campaign unit |
| population.read | Participant identity/status | R | — | ✓ | — | — | — | — | — | — | ✓ | ✓ | Admin campaign; auditor/privacy case |
| population.update | Participant disposition | U | — | ✓ | — | — | — | — | — | — | — | ✓ | Before close or approved correction |
| population.export | Participant list | E | — | — | — | — | — | — | — | — | ✓ | ✓ | Exception approval only |
| response.create | Response draft | C | ✓ | — | — | — | — | — | — | — | — | — | Valid invitation/campaign |
| response.read-own | Own draft | R | ✓ | — | — | — | — | — | — | — | — | — | Before submit/session allowed |
| response.update-own | Own draft | U | ✓ | — | — | — | — | — | — | — | — | — | Before submit |
| response.submit | Response | X | ✓ | — | — | — | — | — | — | — | — | — | Exactly once |
| response.read-raw | Raw response | R | — | — | — | — | ✓ | — | — | — | ✓ | ✓ | Explicit assigned grant; de-identification profile |
| response.export-raw | Raw response | E | — | — | — | — | ✓ | — | — | — | ✓ | ✓ | Dual approval, purpose, expiry |
| analysis.execute | Analysis run | X | — | ✓ | — | — | ✓ | — | — | — | — | — | Campaign assigned; method eligible |
| analysis.read | Analysis output | R | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | Unit/Assignment/Hierarchy; suppression |
| ai.execute | AI analysis | X | — | — | — | — | ✓ | — | — | — | — | — | Feature on, approved registry, threshold, reviewer assigned |
| report.create | Draft report/export job | C | — | ✓ | — | — | ✓ | — | — | — | — | — | Campaign scope |
| report.read | Released aggregate | R | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | Scope; suppression always |
| report.approve | Report release | X | — | — | — | ✓ | — | — | — | — | — | ✓* | Reviewer nonrequester; `*` privacy approval for public/sensitive |
| report.export | Approved aggregate | E | — | ✓ | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | Format/role/scope/classification policy |
| finding.create | Finding | C | — | ✓ | — | — | ✓ | — | — | — | — | — | From approved result |
| finding.read | Finding | R | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | Unit/Hierarchy/Assignment |
| finding.update | Finding metadata | U | — | ✓ | — | — | — | — | — | — | — | — | Before accepted action; audited |
| action.create | Action plan | C | — | ✓ | — | — | — | — | ✓ | — | — | — | Assigned finding |
| action.update | Action/evidence | U | — | ✓ | — | — | — | — | ✓ | — | — | — | Assigned; not source finding |
| action.verify | Verification decision | X | — | — | — | — | — | — | — | ✓ | — | — | Assigned; verifier ≠ PIC |
| action.close | Closure | X | — | ✓ | — | — | — | — | — | ✓* | — | — | Admin closes after verification/impact; `*` verifier recommends |
| policy.create | Policy draft | C | — | — | ✓ | — | — | — | — | — | — | ✓ | Governance scope |
| policy.read | Policy | R | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | Active policy broadly; drafts scoped |
| policy.update | Policy draft | U | — | — | ✓ | — | — | — | — | — | — | ✓ | Draft only |
| policy.approve | Policy version | X | — | — | — | — | — | — | — | — | ✓* | ✓ | Privacy Officer approves; `*` auditor observes control |
| role.manage | Role/permission/scope | C/R/U/D | — | — | ✓ | — | — | — | — | — | R | R | No self-escalation; dual control privileged role |
| secret.manage | Secret reference | C/U/D/X | — | — | ✓ | — | — | — | — | — | R* | — | `*` metadata only; value never readable |
| audit.read | Audit event | R | — | — | ✓ | — | — | — | — | — | ✓ | ✓ | Approved audit/privacy case |
| audit.export | Audit package | E | — | — | — | — | — | — | — | — | ✓ | ✓ | Dual approval, redaction, expiry |
| retention.execute | Retention/deletion | X | — | — | ✓* | — | — | — | — | — | — | ✓ | Privacy approves; `*` system admin executes job, cannot self-approve |
| rights.execute | Subject request | X | — | — | — | — | — | — | — | — | — | ✓ | Verified case; only linkable privacy modes |

## 4. Data-scope evaluation order

1. Autentikasi aktif dan session assurance/MFA cukup.
2. Permission operasi cocok (`C/R/U/D/X/E`).
3. User scope mencakup institution/unit/campaign.
4. Assignment aktif bila resource memakai assignment.
5. Object state mengizinkan operasi.
6. Classification dan purpose/approval mengizinkan field.
7. Reporting threshold/suppression diterapkan pada result.

Kegagalan pada satu langkah menghasilkan deny. Filter UI bukan kontrol keamanan; pemeriksaan dilakukan server-side pada setiap request dan export worker.

## 5. Data-scope matrix

| Scope | Contoh | Inheritance | Dapat mendelegasikan | Constraint |
|---|---|---|---|---|
| Institution | seluruh perguruan tinggi | ke semua unit descendant | Super Admin sesuai policy | tidak memberi raw response otomatis |
| Unit hierarchy | fakultas → program/unit | descendant yang aktif | Admin LPMPP bila diberi delegate grant | cycle dilarang; effective dating |
| Campaign | satu campaign | template snapshot dan output campaign | Admin LPMPP | tidak meluas ke campaign sibling |
| Assignment | review/action/verification/case | hanya object dan evidence terkait | assigner berpermission | expiry/state menutup akses |
| Data class | Public/Internal/Confidential/Restricted/Secret | field/record/export | tidak diwariskan dari unit | highest class menang pada gabungan |
| Purpose-limited exception | riset/audit/subject request | field allowlist + timebox | tidak dapat didelegasikan | approval, logging, expiry wajib |

## 6. Separation-of-duties constraints

- Creator instrumen ≠ sole approver instrumen.
- Requester report/export ≠ approver release sensitif.
- PIC ≠ verifikator action yang sama.
- Pembuat policy exception ≠ approver exception.
- Executor deletion ≠ sole approver deletion/legal-hold release.
- Super Admin tidak boleh memberi dirinya raw-response/export grant tanpa dual approval.

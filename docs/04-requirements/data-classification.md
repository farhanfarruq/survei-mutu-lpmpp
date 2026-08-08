# Data Classification Matrix

Versi: **1.0 — 2026-08-07**  
Status: **baseline; retention bertanda `[P]` memerlukan persetujuan fungsi PDP/hukum dan data owner**

## 1. Classification levels

| Level | Definisi | Contoh kontrol minimum |
|---|---|---|
| Public | disetujui untuk publikasi umum | release approval, integrity/version marking |
| Internal | operasional nonpublik dengan dampak rendah bila terbuka | authenticated access, unit scope, audit perubahan |
| Confidential | data yang dapat merugikan institusi/kelompok atau mengandung personal/pseudonymous data terbatas | least privilege, encryption, export approval, retention |
| Restricted | data personal/sensitif, raw content, rare combination, atau artefak yang meningkatkan re-identification/abuse | explicit assignment/purpose, dual approval export, enhanced audit, no routine email |
| Secret | credential, token, encryption key, atau material autentikasi | secret manager, never-readable, rotation, no export/log |

Jika artefak menggabungkan beberapa class, class tertinggi berlaku. De-identification menurunkan risiko, bukan otomatis menjadikan data Public.

## 2. Data matrix

| Data set/field | Class | Direct/pseudonymous | Owner | Akses normal | Storage/transit | Export/AI | Retention target |
|---|:---:|---|---|---|---|---|---|
| Published public summary | Public | tidak | LPMPP | semua | integrity hash; TLS | public PDF/HTML; AI tidak relevan | arsip permanen menurut kebijakan `[P]` |
| Approved template/version tanpa data | Internal | tidak | LPMPP/metodolog | admin/reviewer/analyst | encryption at rest + TLS | export internal | active + 10 tahun setelah retired `[P]` |
| Draft template/review comment | Internal | identitas reviewer | LPMPP | creator/assigned reviewer | encryption + audit | internal export approved | 5 tahun setelah keputusan `[P]` |
| Validation evidence/pilot file | Confidential | mungkin pseudonymous | Metodolog/data owner | assigned reviewer/analyst | encrypted object store; checksum | no AI/raw export kecuali purpose approved | 5 tahun `[P]` |
| User account, role, scope | Confidential | direct | TIK/data owner | Super Admin; auditor metadata | encryption + audit | no routine export | active + 2 tahun `[P]` |
| MFA recovery material/password hash | Secret | direct | Security owner | auth service only | strong one-way hash/secret store | dilarang export/AI | sampai rotated/revoked |
| API/provider secret reference | Secret | tidak/pengguna sistem | Security owner | Super Admin metadata only | secret manager | nilai dilarang export/AI/log | sampai rotated/revoked |
| Participant name/NIM/email/phone | Restricted | direct | Population data owner | Admin campaign terbatas/privacy case | store terpisah, encryption, TLS | export exception only; dilarang AI | ≤90 hari setelah campaign close `[P]` |
| Participation/token status | Confidential | pseudonymous | LPMPP | campaign operator | store terpisah; token hash | agregat only; dilarang AI | ≤90 hari setelah close `[P]` |
| Invitation token/reset token | Secret | pseudonymous | TIK | service only | hash, short TTL | dilarang export/AI | sampai used/expired; maks sesuai use case |
| Consent record/version | Confidential | anonymous/pseudonymous sesuai mode | LPMPP/privacy owner | privacy/analyst terbatas | encryption + immutable timestamp | aggregate only | sama dengan response campaign `[P]` |
| Draft response | Restricted | anonymous/pseudonymous sesuai mode | LPMPP/data owner | respondent sendiri; service | encrypted; isolated content store | dilarang export/AI | 30 hari setelah expiry/close `[P]` |
| Submitted closed-ended response | Confidential | anonymous/pseudonymous sesuai mode | LPMPP/data owner | analyst explicit grant | encrypted; content store | de-identified exception; AI hanya approved | 5 tahun `[P]` |
| Open-text response raw | Restricted | indirect identifiers mungkin | LPMPP/data owner | designated redaction team | encrypted; field-level controls | no routine export; AI after redaction/threshold/approval | 2 tahun raw, derivative 5 tahun `[P]` |
| Sensitive demographic/rare attribute | Restricted | quasi-identifier | Data owner/privacy officer | analyst explicit field grant | encrypted; field allowlist | no routine export; approved AI only if necessary | 5 tahun atau lebih pendek `[P]` |
| De-identified analytic dataset | Confidential | residual re-identification risk | Analyst/data owner | assigned analyst | encrypted workspace; expiry | approved export/AI by allowlist | purpose expiry ≤1 tahun `[P]` |
| Scoring/analysis run metadata | Internal | actor ID | LPMPP | admin/analyst/auditor | immutable lineage/checksum | internal export | 10 tahun `[P]` |
| Aggregate result above threshold | Internal | tidak | LPMPP | authorized hierarchy | encrypted + suppression metadata | approved report/export; AI optional | 10 tahun `[P]` |
| Suppressed/small-cell intermediate | Restricted | inferential risk | LPMPP/privacy owner | analysis service/limited analyst | isolated temp/controlled table | dilarang routine export/AI | purge ≤30 hari after report `[P]` |
| AI prompt payload de-identified | Restricted | residual risk | AI use-case owner | approved integration/reviewer | encrypted, provider policy, no training | provider allowlist only | shortest possible; target 0 provider retention `[P]` |
| AI output/coding suggestion | Confidential | may reproduce input | AI use-case owner | analyst/human reviewer | encrypted; versioned model/prompt | export only after human review | same as derivative analysis `[P]` |
| Finding and action plan | Internal | staff actor | LPMPP/unit owner | hierarchy/assignment | encryption + audit | aggregate report | 10 tahun `[P]` |
| Action evidence | Confidential | may contain operational/personal data | Unit owner | PIC/verifier/LPMPP | encrypted object store; checksum | export based on highest embedded class | 5–10 tahun `[P]` |
| Audit log | Restricted | direct actor/activity | Security/auditor | auditor/security/privacy case | append-only/immutable, encrypted | signed/redacted audit package only | minimum 2 tahun `[P]` |
| Security event detail | Restricted | actor/IP/device possible | Security owner | incident team | restricted log store | incident purpose only; no AI default | 2 tahun or case schedule `[P]` |
| Report/export file | class of highest included field | depends content | Requester/data owner | requester + approved recipient | encrypted, one-time link | no onward sharing beyond purpose | link ≤24h; file purged ≤7 hari `[P]` |
| Backup | highest class contained | mixed | TIK/security | backup service + recovery team | encrypted, separate failure domain | no direct export/AI | 35 hari rolling `[P]` |
| Deletion tombstone | Internal | object ID/hash, no deleted payload | Privacy owner/auditor | privacy/auditor | immutable | audit package | 10 tahun `[P]` |

## 3. Handling matrix

| Control | Public | Internal | Confidential | Restricted | Secret |
|---|:---:|:---:|:---:|:---:|:---:|
| Authentication required | opsional | wajib | wajib | wajib + explicit grant | service/privileged only |
| Encryption in transit | wajib | wajib | wajib | wajib | wajib |
| Encryption at rest | integrity minimum | wajib | wajib | wajib + key/access separation | secret manager/HSM equivalent |
| Access audit | release | change/export | read sensitive/change/export | read/change/export/download | create/rotate/revoke; value never logged |
| Email attachment | boleh jika approved | terbatas | dilarang default | dilarang | dilarang |
| Routine export | ya | scoped | approval | dual approval + purpose + expiry | tidak pernah |
| AI processing | public use case | approved | approved + allowlist | de-identify + DPIA/threshold/contract | tidak pernah |
| Secure deletion | policy | policy | wajib saat due | wajib saat due | revoke/crypto erase |

## 4. Data lifecycle gates

1. **Collect:** purpose, lawful basis, minimum field, privacy mode, notice version, owner, class, retention ditetapkan.
2. **Use:** permission, scope, assignment, purpose, and object state diperiksa.
3. **Share/export:** threshold, de-identification, recipient, approval, classification marking, expiry, dan audit diperiksa.
4. **Retain:** age dan legal hold dimonitor; perubahan schedule berversi.
5. **Delete:** dual control untuk Restricted, tombstone tanpa payload, backup expiry, dan reconciliation evidence.

## 5. Privacy-mode implications

| Mode | Identity linkage | Rights handling | Prohibited claim |
|---|---|---|---|
| Strict anonymous | tidak tersedia | tidak melakukan re-identification untuk access/delete individual | “kami dapat menemukan jawaban Anda” |
| Anonymous content + detached participation | participation tidak punya join key ke content | status undangan dapat ditangani; content individual tidak dapat ditautkan | “tracking sama dengan jawaban” |
| Confidential pseudonymous | key terbatas dan terpisah | request diproses setelah identity verification dan scope check | menyebutnya anonim |
| Identifiable | direct linkage | rights/case workflow penuh sesuai policy | memakai untuk survei umum tanpa necessity |

## 6. Persetujuan yang belum tersedia

- retention schedule per family dan legal hold authority;
- data owner serta lawful basis per population;
- apakah open text dan demographic tertentu dibutuhkan;
- provider/location/retention/training policy AI;
- audit/security log fields termasuk IP/device;
- approved recipients dan de-identification profile untuk riset/ekspor.

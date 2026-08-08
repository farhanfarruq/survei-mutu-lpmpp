# Data Flow Diagrams, Exception Flow, and Recovery

Versi: **1.0 — 2026-08-07**

Notation: rectangle = external entity/actor, rounded node = logical process, cylinder = logical data store. Store adalah responsibility boundary, bukan keputusan database/container Phase 06.

## 1. DFD Level 0

```mermaid
flowchart LR
    RESP["E1 Responden"]
    ADMIN["E2 Admin, Reviewer, Analyst,<br/>Super Admin, Privacy, Auditor"]
    DECIDE["E3 Pimpinan, PIC,<br/>Verifikator"]
    SOURCE["E4 Identity, Population,<br/>Email, Storage, BI, AI"]
    P0(("P0 SIMUTU PT<br/>Survey-to-Improvement Platform"))

    RESP -->|"consent dan jawaban"| P0
    P0 -->|"notice, survei, receipt"| RESP
    ADMIN -->|"instrument, campaign, analysis,<br/>policy, approval"| P0
    P0 -->|"task, quality output, audit evidence"| ADMIN
    P0 -->|"aggregate released, finding,<br/>assignment"| DECIDE
    DECIDE -->|"decision, action, evidence,<br/>verification, impact"| P0
    SOURCE -->|"identity claim, population,<br/>delivery status, provider result"| P0
    P0 -->|"invitation, approved export,<br/>encrypted backup, allowlisted AI payload"| SOURCE
```

Level 0 hanya menunjukkan pertukaran data dengan boundary. Raw response tidak mengalir langsung ke pimpinan/PIC atau provider email.

## 2. DFD Level 1

```mermaid
flowchart TB
    RESP["E1 Responden"]
    USER["E2 User Internal"]
    LEAD["E3 Pimpinan / PIC / Verifikator"]
    IDP["E4 Identity and Population Sources"]
    MAIL["E5 Email Provider"]
    EXT["E6 Storage / BI / AI Provider"]

    P1(("P1 Identity, Scope,<br/>Governance"))
    P2(("P2 Instrument and<br/>Review"))
    P3(("P3 Campaign, Population,<br/>Participation"))
    P4(("P4 Respondent and<br/>Response Capture"))
    P5(("P5 Scoring, Analysis,<br/>Data Quality"))
    P6(("P6 Reporting and<br/>Export"))
    P7(("P7 Finding and<br/>PPEPP Follow-up"))
    P8(("P8 Audit, Retention,<br/>Operations"))

    D1[("D1 User, Role,<br/>Organization Scope")]
    D2[("D2 Template, Version,<br/>Review, Approval")]
    D3[("D3 Participant and<br/>Delivery Status")]
    D4[("D4 Response Content<br/>Separated Store")]
    D5[("D5 Analysis Runs and<br/>Aggregate Results")]
    D6[("D6 Reports and<br/>Export Artifacts")]
    D7[("D7 Findings, Actions,<br/>Evidence, Verification")]
    D8[("D8 Policy, Audit,<br/>Secret References")]

    IDP -->|"claims and population minimum"| P1
    P1 <--> D1
    USER -->|"login and governance command"| P1
    P1 -->|"session, grants, scoped context"| P2
    P1 -->|"scoped context"| P3
    P1 -->|"scoped context"| P5
    P1 -->|"scoped context"| P6
    P1 -->|"scoped context"| P7

    USER -->|"draft, review, approval"| P2
    P2 <--> D2
    P2 -->|"approved version snapshot"| P3

    USER -->|"campaign, frame, schedule"| P3
    P3 <--> D3
    P3 -->|"invitation without response content"| MAIL
    MAIL -->|"delivery status"| P3
    P3 -->|"active campaign and detached token"| P4

    RESP -->|"consent, answer, submit"| P4
    P4 -->|"survey, save state, receipt"| RESP
    P4 <--> D4
    P4 -->|"completion status without response ID"| P3
    P4 -->|"submitted content snapshot"| P5
    P3 -->|"frame and disposition aggregate"| P5

    USER -->|"analysis plan and execute"| P5
    P5 <--> D5
    P5 -.->|"approved de-identified allowlist"| EXT
    EXT -.->|"AI candidate output"| P5
    P5 -->|"reviewed aggregate and quality context"| P6

    USER -->|"report and export request"| P6
    P6 <--> D6
    P6 -->|"released scoped aggregate"| LEAD
    P6 -->|"approved expiring artifact"| EXT
    P6 -->|"released finding source"| P7

    LEAD -->|"finding, action, evidence, decision"| P7
    P7 <--> D7
    P7 -->|"status, reminder, communication-back"| LEAD

    P1 --> P8
    P2 --> P8
    P3 --> P8
    P4 --> P8
    P5 --> P8
    P6 --> P8
    P7 --> P8
    P8 <--> D8
    P8 -->|"encrypted backup and recovery artifacts"| EXT
```

## 3. Data-flow controls

| Flow | Allowed data | Prohibited data | Control |
|---|---|---|---|
| Population source → P1/P3 | identifier/contact/eligibility minimum | unrelated academic/personal fields | schema allowlist, purpose, source date |
| P3 → email | recipient, campaign, due date, secure token | response content, raw comment, secret | template placeholder allowlist |
| P4 → D4 | consent reference and response content | name/NIM/email/IP/user-agent pada detached mode | separated store and privacy test |
| P4 → P3 | complete/partial status tied to detached invitation status | response ID/item/value | no join-key contract |
| D4/P5 → P6 | aggregate, `n`, missing, coverage, limitation | raw content unless exception | scope, suppression, release review |
| P5 → AI | de-identified allowlisted pool above threshold | identifier, secret, small pool, unapproved fields | governance gate, redaction, human review |
| P6 → external file | approved, classified, suppressed output | unsuppressed/cross-scope data | parity test, encryption, expiry, audit |
| P6 → P7 | released result/finding reference | respondent-level content | aggregate-only contract |

## 4. Exception classification and recovery flow

```mermaid
flowchart TD
    E(["Exception detected"])
    C1{"Security or privacy risk?"}
    C2{"Data integrity uncertain?"}
    C3{"Transient dependency failure?"}
    S1["Fail closed, revoke or disable,<br/>quarantine, alert incident owner"]
    S2["Stop transition, rollback if possible,<br/>mark uncertain, enqueue reconciliation"]
    S3["Retry with bounded backoff and<br/>same idempotency key"]
    S4["Reject business operation with<br/>specific safe reason"]
    R1{"Control and invariant restored?"}
    R2{"Attempts remain?"}
    MANUAL["Create operator case with<br/>correlation ID and runbook"]
    RESUME["Resume from last committed state"]
    CLOSE["Close incident / case with evidence"]

    E --> C1
    C1 -->|"Ya"| S1 --> R1
    C1 -->|"Tidak"| C2
    C2 -->|"Ya"| S2 --> R1
    C2 -->|"Tidak"| C3
    C3 -->|"Ya"| S3 --> R2
    C3 -->|"Tidak"| S4 --> CLOSE
    R2 -->|"Ya"| RESUME
    R2 -->|"Tidak"| MANUAL
    R1 -->|"Ya"| RESUME --> CLOSE
    R1 -->|"Tidak"| MANUAL --> CLOSE
```

## 5. Exception and recovery matrix

| Exception | Detection | Immediate action | Recovery | Invariant |
|---|---|---|---|---|
| Authentication/authorization denied | policy result | deny and audit safe reason | user re-auth atau owner memperbaiki grant | tidak ada fallback broad access |
| Concurrent instrument edit | revision/hash mismatch | reject write | reload, compare, reapply ke revision baru | published/reviewed hash tidak berubah |
| Publish preflight failure | blocker list | campaign tetap draft/approved version aman | perbaiki field lalu rerun preflight | campaign tidak open setengah lengkap |
| Autosave network failure | timeout/status unsaved | pertahankan local unsaved state | bounded retry same idempotency key | tidak ada silent data loss/duplicate |
| Submit cross-store uncertainty | participation timeout setelah content commit | receipt processing + reconciliation case | detached reconciliation tanpa response ID | response exactly once, no identity-content join |
| Analysis worker failure | job heartbeat/exception | run failed, no release | retry new attempt same immutable input | partial output tidak released |
| AI provider failure | timeout/429/5xx/malformed | fail job/quarantine; statistik tetap berjalan | backoff atau manual analysis | no auto-approved AI output |
| Secret exposure suspicion | scanner/log incident | feature off, revoke/rotate, contain logs | incident response dan clean config | secret tidak ditampilkan kembali |
| Export parity/suppression failure | golden/parity check | quarantine and revoke file | correct policy/job and generate new artifact | unsafe file never downloadable |
| Evidence checksum mismatch | integrity check | block verification/closure | restore verified evidence or investigate | corrupt evidence tidak diverifikasi |
| Notification provider failure | delivery error/bounce threshold | retain assignment/campaign state | idempotent retry or approved alternate channel | notification tidak menentukan truth state |
| Backup/restore failure | drill/checksum/application check | raise critical operation issue | alternate backup, rebuild, reconcile RPO | successful backup job alone bukan recovery proof |

## 6. Recovery requirements

- Recovery dimulai dari state committed terakhir, tidak mengedit histori untuk “merapikan” error.
- Retry mempunyai batas, backoff, idempotency key, attempt history, dan dead-letter/manual case.
- Compensation tidak boleh menciptakan link baru antara participant identity dan response content.
- Security/privacy exception mengutamakan containment daripada availability.
- Error user-facing berisi aksi lanjut dan correlation ID, bukan stack trace, raw query, identifier lain, atau secret.
- Operator runbook/owner/SLA ditentukan sebelum controlled pilot; target RPO/RTO masih memerlukan persetujuan.

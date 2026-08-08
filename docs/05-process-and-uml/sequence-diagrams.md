# Sequence Diagrams

Versi: **1.0 — 2026-08-07**  
Catatan: participant bernama `Service/Store` adalah tanggung jawab logis, bukan keputusan container Phase 06.

## 1. Login

```mermaid
sequenceDiagram
    autonumber
    actor U as User Internal
    participant UI as Web UI
    participant AUTH as Authentication Service
    participant IDP as Identity Provider
    participant ACCESS as Access Policy
    participant AUDIT as Audit Log

    U->>UI: Pilih Login
    UI->>AUTH: Mulai auth transaction
    AUTH-->>UI: state, nonce, redirect
    UI->>IDP: Authenticate credential
    IDP-->>AUTH: Signed callback with state and claims
    AUTH->>AUTH: Verify signature, state, nonce, account
    alt Role administratif
        AUTH-->>UI: Minta MFA
        U->>UI: Beri faktor kedua
        UI->>AUTH: Verify MFA challenge
    end
    AUTH->>ACCESS: Load active role and scope
    ACCESS-->>AUTH: Grants and expiry
    alt Valid
        AUTH->>AUDIT: Login success with correlation ID
        AUTH-->>UI: Regenerated session and landing scope
        UI-->>U: Tampilkan halaman authorized
    else Invalid or locked
        AUTH->>AUDIT: Generic failed login event
        AUTH-->>UI: Generic authentication failure
        UI-->>U: Tampilkan error tanpa account enumeration
    end
```

## 2. Daftar survei responden

```mermaid
sequenceDiagram
    autonumber
    actor R as Responden
    participant UI as Respondent Portal
    participant INV as Invitation Service
    participant CAM as Campaign Service
    participant PART as Participation Store
    participant RESP as Response Content Store

    R->>UI: Buka portal atau secure link
    UI->>INV: Verify token or session
    INV->>CAM: Resolve eligible active campaigns
    CAM-->>INV: Campaign metadata and window
    INV->>PART: Read participation status only
    PART-->>INV: not_started, partial, or complete
    Note over INV,RESP: Listing tidak membaca response content
    INV-->>UI: Scoped survey list and status
    alt Campaign available
        UI-->>R: Tampilkan purpose, due date, status
    else Tidak eligible atau closed
        UI-->>R: Tampilkan empty state dan contact point
    end
```

## 3. Autosave

```mermaid
sequenceDiagram
    autonumber
    actor R as Responden
    participant UI as Survey UI
    participant API as Response Service
    participant STORE as Response Content Store
    participant METRIC as Operations Metrics

    R->>UI: Ubah jawaban
    UI->>UI: Debounce maksimal 5 detik
    UI->>API: PATCH draft with revision and idempotency key
    API->>API: Verify token, state, branch, item
    API->>STORE: Conditional update current revision
    alt Revision valid
        STORE-->>API: New saved revision
        API-->>UI: Saved revision
        UI-->>R: Umumkan Tersimpan
    else Duplicate retry
        STORE-->>API: Prior idempotent result
        API-->>UI: Same saved revision
    else Conflict
        STORE-->>API: Current revision and conflict
        API-->>UI: Reload or choose active session
    else Storage failure
        STORE--xAPI: Rollback or timeout
        API->>METRIC: Record failure and alert signal
        API-->>UI: Unsaved, retry with backoff
        UI-->>R: Umumkan Belum tersimpan
    end
```

## 4. Submit respons final

```mermaid
sequenceDiagram
    autonumber
    actor R as Responden
    participant UI as Survey UI
    participant API as Response Service
    participant STORE as Response Content Store
    participant PART as Participation Store
    participant RECON as Reconciliation Queue

    R->>UI: Konfirmasi Kirim respons
    UI->>API: Submit revision and idempotency key
    API->>STORE: Lock and validate draft
    alt Invalid or incomplete
        STORE-->>API: Validation errors
        API-->>UI: Error summary and first invalid item
    else Valid
        API->>STORE: Freeze exactly one submitted response
        STORE-->>API: Submitted and receipt seed
        API->>PART: Mark invitation complete using detached token status
        Note over API,PART: Tidak mengirim response ID atau content
        alt Participation update success
            PART-->>API: Complete
            API-->>UI: Nonidentifying receipt
            UI-->>R: Submission confirmed
        else Participation update uncertain
            PART--xAPI: Timeout
            API->>RECON: Enqueue detached reconciliation
            API-->>UI: Receipt with processing status
        end
    end
```

## 5. Agregasi dan analisis statistik

```mermaid
sequenceDiagram
    autonumber
    actor A as Analyst
    participant UI as Analysis UI
    participant ACCESS as Access Policy
    participant RUN as Analysis Service
    participant RESP as Response Content Store
    participant QUEUE as Job Queue
    participant WORKER as Analysis Worker
    participant OUT as Analysis Store
    participant AUDIT as Audit Log

    A->>UI: Pilih campaign and analysis plan
    UI->>ACCESS: Check execute permission and scope
    ACCESS-->>UI: Allow or deny
    alt Allowed
        UI->>RUN: Create analysis run
        RUN->>RESP: Snapshot eligible submitted responses
        RESP-->>RUN: Input snapshot and hash
        RUN->>RUN: Validate method and scoring preconditions
        alt Preconditions pass
            RUN->>QUEUE: Enqueue immutable run ID
            QUEUE->>WORKER: Deliver job
            WORKER->>WORKER: Score, aggregate, quality checks
            WORKER->>OUT: Store output, parameters, checksum
            WORKER->>AUDIT: Record completed run lineage
            OUT-->>UI: Reviewed status and quality flags
        else Preconditions fail
            RUN->>AUDIT: Record blocked reason
            RUN-->>UI: Method or data reason code
        end
    else Denied
        UI-->>A: Forbidden without raw fallback
    end
```

## 6. Analisis AI

```mermaid
sequenceDiagram
    autonumber
    actor A as Analyst
    actor H as Human Reviewer
    participant UI as Analysis UI
    participant GATE as AI Governance Gate
    participant REDACT as Redaction and PII Scan
    participant SECRET as Secret Manager
    participant AI as Approved AI Provider
    participant OUT as AI Run Store

    A->>UI: Pilih approved AI use case and pool
    UI->>GATE: Check flag, permission, registry, threshold, reviewer
    alt Gate fails
        GATE-->>UI: Block with reason before data leaves
    else Gate passes
        GATE->>REDACT: Build field allowlist payload
        alt PII or classification issue
            REDACT-->>UI: Quarantine for manual redaction
        else Payload safe
            REDACT->>SECRET: Request provider credential by reference
            SECRET-->>REDACT: Ephemeral credential handle
            REDACT->>AI: Prompt and de-identified payload
            alt Provider success
                AI-->>REDACT: Candidate themes or coding
                REDACT->>OUT: Store awaiting_human_review with lineage
                OUT-->>H: Assign review
                H->>OUT: Accept, correct, or reject
                OUT-->>UI: Human-reviewed output only
            else Timeout, rate limit, or unsafe output
                AI--xREDACT: Error or malformed output
                REDACT->>OUT: Failed or quarantined attempt
                OUT-->>UI: Manual analysis remains available
            end
        end
    end
```

## 7. Export laporan

```mermaid
sequenceDiagram
    autonumber
    actor Q as Requester
    actor V as Reviewer Release
    participant UI as Reporting UI
    participant ACCESS as Access and Scope Policy
    participant JOB as Export Service
    participant QUEUE as Job Queue
    participant WORKER as Export Worker
    participant POLICY as Suppression Policy
    participant FILE as Secure File Store
    participant AUDIT as Audit Log

    Q->>UI: Pilih report, filter, format
    UI->>ACCESS: Check report.export and scope
    ACCESS-->>UI: Allow or deny
    alt Allowed
        UI->>JOB: Create classified export request
        JOB->>QUEUE: Enqueue job with immutable request ID
        QUEUE->>WORKER: Generate export
        WORKER->>POLICY: Apply scope and suppression
        POLICY-->>WORKER: Safe aggregate dataset
        WORKER->>WORKER: Generate, checksum, parity test
        alt Parity pass
            WORKER->>FILE: Store encrypted file as pending approval
            FILE-->>V: Review release request
            alt Approved by nonrequester
                V->>JOB: Approve with comment
                JOB->>FILE: Create one-time requester-bound link
                JOB->>AUDIT: Record approval and availability
                JOB-->>UI: Link and expiry maximum 24 hours
            else Rejected
                V->>JOB: Reject with comment
                JOB->>FILE: Revoke and purge candidate
                JOB-->>UI: Rejected
            end
        else Parity fail
            WORKER->>FILE: Quarantine candidate
            WORKER->>AUDIT: Record failed export
            JOB-->>UI: Failed without download
        end
    else Denied
        UI-->>Q: Forbidden
    end
```

## 8. Konfigurasi secret API AI

```mermaid
sequenceDiagram
    autonumber
    actor S as Super Admin
    actor P as Privacy or Security Approver
    participant UI as Governance UI
    participant AUTH as Step-up Authentication
    participant REG as AI Registry
    participant VAULT as Secret Manager
    participant TEST as Provider Test Service
    participant AI as AI Provider
    participant AUDIT as Audit Log

    S->>UI: Create or rotate AI configuration
    UI->>AUTH: Require MFA step-up and permission
    AUTH-->>UI: Assurance confirmed
    S->>UI: Enter metadata and secret via secure channel
    UI->>VAULT: Store secret value
    VAULT-->>UI: Opaque reference only
    UI->>REG: Save inactive version with reference
    REG-->>P: Request independent approval
    alt Approved and all governance fields complete
        P->>REG: Approve activation
        REG->>TEST: Run synthetic nonpersonal test
        TEST->>VAULT: Resolve ephemeral credential
        TEST->>AI: Synthetic health request
        alt Test passes
            AI-->>TEST: Valid response
            TEST->>REG: Activate new version
            REG->>AUDIT: Record metadata before and after
            REG-->>UI: Active without secret value
        else Test fails
            AI--xTEST: Failure
            TEST->>REG: Keep inactive and retain prior safe version
            REG-->>UI: Failure reason without secret
        end
    else Missing or rejected approval
        P->>REG: Reject or return
        REG-->>UI: Inactive
    end
```

## 9. Recovery principles across sequences

- client/network retry menggunakan idempotency key;
- async job mempunyai immutable request/run ID serta attempt history;
- provider failure tidak menjatuhkan respondent/statistical core flow;
- partial output tidak released dan file gagal dikarantina;
- compensation/reconciliation dipakai saat cross-store status uncertain tanpa membuat join identity–content;
- setiap failure mengembalikan correlation ID yang aman, bukan stack trace/secret.

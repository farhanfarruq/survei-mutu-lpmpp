# System Context

Versi: **1.0 — 2026-08-07**  
Level: **logical process context, bukan C4 container/component architecture**

## 1. Context diagram

```mermaid
flowchart LR
    RESP["Responden<br/>mahasiswa, dosen, tendik,<br/>alumni, mitra"]
    ADMIN["Admin LPMPP"]
    REVIEW["Reviewer / Metodolog"]
    ANALYST["Analyst"]
    LEADER["Pimpinan / Unit Owner"]
    PIC["PIC Tindak Lanjut"]
    VERIFY["Verifikator"]
    GOV["Super Admin / TIK<br/>Privacy / Auditor"]

    subgraph BOUNDARY["System Boundary: SIMUTU PT"]
        SYS(["Platform Survei dan<br/>Umpan Balik Mutu"])
    end

    IDP["Identity Provider / SSO"]
    SOURCE["SIAKAD / SDM / Alumni<br/>Population Source"]
    MAIL["Email Provider"]
    STORAGE["Institutional Storage<br/>and Backup"]
    BI["Approved Statistical / BI Tool"]
    SPMI["SPMI / AMI / Evidence Repository"]
    AI["Approved AI Provider<br/>Post-MVP, off by default"]

    RESP -->|"notice, jawaban, submit"| SYS
    SYS -->|"receipt, status nonidentifying"| RESP
    ADMIN -->|"instrumen, campaign, finding"| SYS
    REVIEW -->|"review dan approval"| SYS
    ANALYST -->|"analysis run"| SYS
    SYS -->|"aggregate dan quality context"| LEADER
    PIC -->|"action plan dan evidence"| SYS
    VERIFY -->|"verification decision"| SYS
    GOV -->|"identity, policy, audit, operation"| SYS

    SYS <-->|"authentication dan claims minimum"| IDP
    SOURCE -->|"population attributes minimum"| SYS
    SYS <-->|"invitation dan delivery metadata"| MAIL
    SYS <-->|"encrypted evidence dan recovery"| STORAGE
    SYS -->|"approved aggregate atau exception extract"| BI
    SYS -->|"finding atau evidence reference"| SPMI
    SYS -.->|"de-identified allowlist only<br/>after governance gates"| AI
```

## 2. System boundary

Di dalam boundary:

- governance instrument dan version;
- campaign, population, participation, dan response;
- scoring/analysis lineage serta aggregate report;
- finding, action, evidence, verification, dan impact plan;
- role/data scope, privacy policy, audit, export control, dan operational status.

Di luar boundary:

- sumber identitas dan population tetap authoritative pada sistem asal;
- email provider hanya menerima metadata/pesan tanpa response content;
- SPMI/AMI tetap sistem/proses yang lebih luas;
- statistical tool menerima data hanya melalui export yang approved;
- AI provider tidak termasuk MVP dan tidak boleh dipanggil tanpa governance gates;
- SIMUTU PT bukan whistleblowing, case management, atau sistem keputusan individual.

## 3. Trust and data boundaries

| Boundary | Rule |
|---|---|
| Participant identity ↔ response content | dipisahkan; detached mode tidak mempunyai join key |
| Internal user ↔ protected object | authentication, operation permission, organization scope, assignment, state, dan classification diperiksa |
| Result ↔ leader/PIC | aggregate/released result saja; threshold dan suppression tetap berlaku |
| Export ↔ recipient | purpose, approval, scope, classification, expiry, dan audit |
| Platform ↔ provider | TLS, allowlist, timeout/retry/idempotency, minimum fields, dan failure isolation |
| Platform ↔ AI | feature off, registry/DPIA/provider approval, de-identification, threshold, human review, kill switch |

## 4. Context assumptions requiring confirmation

- satu institusi/tenant awal;
- identity provider, population source, hierarchy, email provider, dan storage belum dipilih;
- scale/capacity/team belum dikonfirmasi, sehingga diagram tidak menetapkan topology atau deployment pattern;
- integration SPMI/BI/AI adalah boundary option, bukan komitmen MVP.


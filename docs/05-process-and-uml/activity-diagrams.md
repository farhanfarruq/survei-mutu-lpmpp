# Activity Diagrams

Versi: **1.0 — 2026-08-07**

Diagram memakai flowchart Mermaid sebagai activity view. Rounded node menandai start/end, diamond adalah decision/guard, dan subgraph menunjukkan responsibility lane.

## 1. Pembuatan survei dan pertanyaan

```mermaid
flowchart TD
    START(["Mulai"])
    subgraph ADMIN["Admin LPMPP"]
        A1["Pilih family, purpose, population, owner"]
        A2["Buat template dan version draft"]
        A3["Tambah category, indicator, item"]
        A4["Atur scale, N/A, branching, method, scoring"]
        A5["Perbaiki blocker"]
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Validasi code, version, scope"]
        S2["Validasi item, branch, pair, scale"]
        D1{"Preflight blocker?"}
        S3["Simpan revision dan audit delta"]
    end
    READY(["Draft siap review"])

    START --> A1 --> A2 --> S1
    S1 -->|"Valid"| A3 --> A4 --> S2 --> S3 --> D1
    S1 -->|"Invalid"| A2
    D1 -->|"Ya"| A5 --> A3
    D1 -->|"Tidak"| READY
```

## 2. Review instrumen

```mermaid
flowchart TD
    START(["Draft preflight pass"])
    subgraph ADMIN["Admin LPMPP"]
        A1["Pilih reviewer, scope, due date"]
        A2["Kirim revision untuk review"]
        A3["Tanggapi finding dan buat revision"]
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Cek reviewer bukan creator"]
        S2["Hash dan lock revision"]
        S3["Buat assignment dan notification"]
        S4["Simpan rating, komentar, decision"]
    end
    subgraph REVIEW["Reviewer / Metodolog"]
        R1["Nyatakan conflict declaration"]
        R2["Review blueprint, item, method, evidence"]
        D1{"Decision?"}
    end
    APPROVED(["Version approved"])

    START --> A1 --> S1
    S1 -->|"Tidak eligible"| A1
    S1 -->|"Eligible"| A2 --> S2 --> S3 --> R1 --> R2 --> D1
    D1 -->|"Return"| S4 --> A3 --> A2
    D1 -->|"Approve"| S4 --> APPROVED
```

## 3. Publikasi campaign

```mermaid
flowchart TD
    START(["Version approved"])
    subgraph ADMIN["Admin LPMPP"]
        A1["Buat campaign draft"]
        A2["Tetapkan period, owner, privacy, threshold"]
        A3["Import population dan notification plan"]
        A4["Perbaiki blocker"]
        A5["Re-auth dan pilih publish atau schedule"]
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Validasi frame, eligibility, duplicate"]
        S2["Preflight snapshot lengkap"]
        D1{"Ada blocker?"}
        S3["Bekukan campaign snapshot"]
        D2{"Open sekarang?"}
        S4["Set scheduled"]
        S5["Set open dan antre invitation"]
        S6["Audit transition"]
    end
    END(["Campaign scheduled / open"])

    START --> A1 --> A2 --> A3 --> S1 --> S2 --> D1
    D1 -->|"Ya"| A4 --> A2
    D1 -->|"Tidak"| A5 --> S3 --> D2
    D2 -->|"Belum"| S4 --> S6 --> END
    D2 -->|"Ya"| S5 --> S6 --> END
```

## 4. Pengisian dan submit response

```mermaid
flowchart TD
    START(["Invitation dibuka"])
    subgraph SYSTEM["SIMUTU PT"]
        S1["Validasi token, eligibility, window"]
        D1{"Valid?"}
        S2["Tampilkan notice dan consent"]
        S3["Buat / muat draft detached"]
        S4["Terapkan branching"]
        S5["Autosave revision idempotent"]
        S6["Validasi required dan branch"]
        D3{"Lengkap?"}
        S7["Submit exactly once dan freeze"]
        S8["Update participation tanpa join key"]
        S9["Buat receipt nonidentifying"]
    end
    subgraph RESP["Responden"]
        D2{"Consent?"}
        R1["Jawab item / pilih N/A"]
        R2["Review jawaban"]
        R3["Perbaiki item"]
    end
    STOP(["Berhenti tanpa content"])
    DENY(["Akses ditolak aman"])
    END(["Response submitted"])

    START --> S1 --> D1
    D1 -->|"Tidak"| DENY
    D1 -->|"Ya"| S2 --> D2
    D2 -->|"Tidak"| STOP
    D2 -->|"Ya"| S3 --> R1 --> S4 --> S5 --> R2 --> S6 --> D3
    D3 -->|"Tidak"| R3 --> R1
    D3 -->|"Ya"| S7 --> S8 --> S9 --> END
```

## 5. Analisis statistik dan AI extension

```mermaid
flowchart TD
    START(["Campaign data eligible"])
    subgraph ANALYST["Analyst"]
        A1["Pilih campaign dan analysis plan"]
        A2["Review quality flags dan output"]
        D3{"Minta AI assist?"}
        A3["Human review AI output"]
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Cek permission, scope, state"]
        S2["Buat input snapshot dan hash"]
        S3["Validasi method dan scoring"]
        D1{"Precondition terpenuhi?"}
        S4["Jalankan scoring dan quality analysis"]
        S5["Simpan immutable run lineage"]
        S6["Cek AI governance, threshold, redaction"]
        D2{"AI gate pass?"}
        S7["Kirim payload allowlisted"]
        S8["Simpan output awaiting review"]
    end
    FAIL(["Run failed / blocked dengan reason"])
    READY(["Analysis reviewed siap report"])

    START --> A1 --> S1 --> S2 --> S3 --> D1
    D1 -->|"Tidak"| FAIL
    D1 -->|"Ya"| S4 --> S5 --> A2 --> D3
    D3 -->|"Tidak"| READY
    D3 -->|"Ya"| S6 --> D2
    D2 -->|"Tidak"| READY
    D2 -->|"Ya"| S7 --> S8 --> A3 --> READY
```

AI yang diblokir tidak menggagalkan analysis statistik utama.

## 6. Laporan dan export

```mermaid
flowchart TD
    START(["Analysis reviewed"])
    subgraph REQUESTER["Analyst / Admin LPMPP"]
        A1["Susun draft report"]
        A2["Ajukan release"]
        A3["Minta export dengan filter dan format"]
    end
    subgraph REVIEW["Reviewer Laporan"]
        R1["Review method, limitation, privacy"]
        D1{"Approve release?"}
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Apply scope dan suppression"]
        S2["Simpan release decision"]
        S3["Antre export job"]
        S4["Generate, classify, checksum, parity check"]
        D2{"Job dan parity pass?"}
        S5["Buat link sekali pakai"]
        S6["Karantina file dan catat failure"]
    end
    RETURN(["Returned untuk revisi"])
    END(["Report released / export tersedia"])

    START --> A1 --> S1 --> A2 --> R1 --> D1
    D1 -->|"Tidak"| S2 --> RETURN
    D1 -->|"Ya"| S2 --> A3 --> S3 --> S4 --> D2
    D2 -->|"Tidak"| S6 --> RETURN
    D2 -->|"Ya"| S5 --> END
```

## 7. Finding dan tindak lanjut PPEPP

```mermaid
flowchart TD
    START(["Released result"])
    subgraph LPMPP["Admin LPMPP / Unit Owner"]
        L1["Buat finding, severity, owner, due date"]
        L2["Assign PIC dan verifikator"]
        L3["Close atau buka action lanjutan"]
        L4["Communication-back"]
    end
    subgraph PIC["PIC"]
        P1{"Terima assignment?"}
        P2["Buat action, target, milestone, impact plan"]
        P3["Update progress dan evidence"]
        P4["Perbaiki evidence"]
    end
    subgraph VERIFY["Verifikator"]
        V1["Bandingkan evidence dengan target"]
        V2{"Decision?"}
    end
    subgraph SYSTEM["SIMUTU PT"]
        S1["Version dan audit finding/action"]
        S2["Reminder dan escalation"]
        S3["Simpan verification immutable"]
        D1{"Impact result atau waiver approved?"}
    end
    REASSIGN(["Reassign PIC"])
    END(["Closed dengan impact status"])

    START --> L1 --> L2 --> S1 --> P1
    P1 -->|"Tidak"| REASSIGN --> L2
    P1 -->|"Ya"| P2 --> P3 --> S2 --> V1 --> V2
    V2 -->|"Needs rework"| S3 --> P4 --> P3
    V2 -->|"Rejected"| S3 --> L3
    V2 -->|"Verified"| S3 --> D1
    D1 -->|"Belum"| S2 --> D1
    D1 -->|"Ya"| L3 --> L4 --> END
```

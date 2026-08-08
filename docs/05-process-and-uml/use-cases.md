# Use Case Diagrams

Versi: **1.0 — 2026-08-07**

Mermaid belum mempunyai notasi UML use-case native yang seragam. Diagram memakai `flowchart` dengan actor di luar subgraph **System Boundary**, use case berbentuk rounded node, dan label relasi UML eksplisit.

## 1. Notasi relasi

- **Association:** actor menggunakan use case.
- **`<<include>>`:** use case sumber selalu menjalankan perilaku target sebagai bagian wajib.
- **`<<extend>>`:** use case sumber hanya menambah perilaku target pada kondisi tertentu.
- **Generalization:** actor/use case khusus mewarisi kemampuan actor/use case umum; panah bergerak dari khusus ke umum.
- Include/extend bukan urutan waktu. Urutan terdapat pada activity/sequence diagram.

## 2. Responden

```mermaid
flowchart LR
    RESP["Actor: Responden"]
    EXT["Actor: Responden Eksternal"]
    INT["Actor: Responden Internal"]

    subgraph SYS["System Boundary: SIMUTU PT"]
        UC01(["Membuka daftar / undangan survei"])
        UC02(["Membaca notice dan consent"])
        UC03(["Mengisi survei"])
        UC04(["Autosave jawaban"])
        UC05(["Meninjau kelengkapan"])
        UC06(["Mengirim respons final"])
        UC07(["Memilih N/A / berhenti"])
        UC08(["Menerima receipt"])
    end

    EXT -->|"generalization"| RESP
    INT -->|"generalization"| RESP
    RESP --> UC01
    RESP --> UC03
    RESP --> UC06
    UC03 -. "<<include>>" .-> UC02
    UC03 -. "<<include>>" .-> UC04
    UC06 -. "<<include>>" .-> UC05
    UC06 -. "<<include>>" .-> UC08
    UC07 -. "<<extend>>" .-> UC03
```

## 3. Pengelola instrumen, campaign, dan analisis

```mermaid
flowchart LR
    INTERNAL["Actor: User Internal"]
    ADMIN["Actor: Admin LPMPP"]
    REVIEW["Actor: Reviewer / Metodolog"]
    ANALYST["Actor: Analyst"]

    subgraph SYS["System Boundary: SIMUTU PT"]
        UCA(["Membuat survei / versi"])
        UCB(["Menambahkan pertanyaan"])
        UCC(["Mengirim untuk review"])
        UCD(["Review dan approve / return"])
        UCE(["Membuat dan mempublikasikan campaign"])
        UCF(["Mengelola population dan invitation"])
        UCG(["Menjalankan analisis statistik"])
        UCH(["Menjalankan analisis AI"])
        UCI(["Membuat draft laporan"])
        UCJ(["Memeriksa permission dan scope"])
        UCK(["Mencatat audit event"])
    end

    ADMIN -->|"generalization"| INTERNAL
    REVIEW -->|"generalization"| INTERNAL
    ANALYST -->|"generalization"| INTERNAL
    ADMIN --> UCA
    ADMIN --> UCE
    ADMIN --> UCF
    REVIEW --> UCD
    ANALYST --> UCG
    ANALYST --> UCH
    ANALYST --> UCI
    UCA -. "<<include>>" .-> UCB
    UCC -. "<<include>>" .-> UCJ
    UCD -. "<<include>>" .-> UCK
    UCE -. "<<include>>" .-> UCJ
    UCE -. "<<include>>" .-> UCK
    UCG -. "<<include>>" .-> UCJ
    UCH -. "<<extend>> jika AI approved" .-> UCG
```

## 4. Pimpinan dan feedback loop

```mermaid
flowchart LR
    LEADER["Actor: Pimpinan / Unit Owner"]
    PIC["Actor: PIC"]
    VERIFY["Actor: Verifikator"]
    REVIEW["Actor: Reviewer Laporan"]

    subgraph SYS["System Boundary: SIMUTU PT"]
        UCR(["Melihat dashboard released"])
        UCS(["Mengekspor laporan"])
        UCT(["Approve / reject release"])
        UCU(["Membuat finding"])
        UCV(["Membuat action plan"])
        UCW(["Mengunggah evidence"])
        UCX(["Memverifikasi tindak lanjut"])
        UCY(["Mencatat impact evaluation"])
        UCZ(["Menyusun communication-back"])
        UCP(["Menerapkan scope dan suppression"])
    end

    LEADER --> UCR
    LEADER --> UCS
    REVIEW --> UCT
    PIC --> UCV
    PIC --> UCW
    VERIFY --> UCX
    LEADER --> UCY
    LEADER --> UCZ
    UCR -. "<<include>>" .-> UCP
    UCS -. "<<include>>" .-> UCP
    UCU -. "<<extend>> dari hasil released" .-> UCR
    UCV -. "<<include>>" .-> UCU
    UCX -. "<<include>>" .-> UCW
    UCY -. "<<extend>> setelah verified" .-> UCX
```

## 5. Governance dan operasi

```mermaid
flowchart LR
    INTERNAL["Actor: User Internal"]
    SUPER["Actor: Super Admin"]
    PRIV["Actor: Privacy Officer"]
    AUDIT["Actor: Auditor"]
    OPS["Actor: TIK / Operator"]

    subgraph SYS["System Boundary: SIMUTU PT"]
        UCL(["Login dengan MFA"])
        UCM(["Kelola role dan data scope"])
        UCN(["Kelola policy dan retention"])
        UCO(["Kelola konfigurasi API AI"])
        UCP(["Review privacy / exception"])
        UCQ(["Mencari dan export audit"])
        UCR(["Backup, restore, dan monitor"])
        UCS(["Rotate / revoke secret"])
    end

    SUPER -->|"generalization"| INTERNAL
    PRIV -->|"generalization"| INTERNAL
    AUDIT -->|"generalization"| INTERNAL
    OPS -->|"generalization"| INTERNAL
    INTERNAL --> UCL
    SUPER --> UCM
    PRIV --> UCN
    SUPER --> UCO
    PRIV --> UCP
    AUDIT --> UCQ
    OPS --> UCR
    UCS -. "<<include>>" .-> UCO
    UCP -. "<<extend>> untuk data/AI sensitif" .-> UCO
```

## 6. Generalization boundaries

Generalization tidak berarti seluruh actor khusus memiliki permission actor lain. `User Internal` hanya mewariskan kebutuhan login/session; authorization tetap berdasarkan operation, data scope, object state, assignment, dan classification. Responden internal/eksternal berbagi respondent flow, tetapi metode autentikasi/invitation dapat berbeda.

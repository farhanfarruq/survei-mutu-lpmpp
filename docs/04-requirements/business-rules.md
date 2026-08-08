# Business Rules

Versi: **1.0 — 2026-08-07**  
Status: **baseline Phase 04; rule bertanda konfirmasi tidak boleh diasumsikan telah disahkan**

## 1. Access and accountability

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-ACC-001 | Permission bersifat deny-by-default; tidak adanya grant eksplisit berarti akses ditolak. | Role baru tanpa permission menghasilkan 403 pada seluruh objek terlindungi. |
| BR-ACC-002 | Effective permission adalah irisan role permission, data scope, object state, dan assignment aktif. | Hilangkan satu kondisi; operasi ditolak. |
| BR-ACC-003 | Super Admin mengelola sistem tetapi tidak otomatis dapat membaca raw response/comment. | Akun Super Admin tanpa grant data khusus gagal membaca raw content. |
| BR-ACC-004 | Creator tidak boleh menjadi satu-satunya approver untuk instrument, report release, policy exception, atau deletion. | Self-approval ditolak; dual control tercatat. |
| BR-ACC-005 | Delegasi akses selalu memiliki effective date, expiry date, pemberi, alasan, dan scope; expiry mencabut akses otomatis. | Time-travel boundary test sebelum/sesudah expiry. |

## 2. Template, method, and version

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-TPL-001 | Kode template unik secara institusi; nomor version unik di dalam template. | Insert duplikat ditolak. |
| BR-TPL-002 | Versi published immutable; perubahan menghasilkan version baru dengan change reason dan compatibility status. | Update/delete content published ditolak. |
| BR-TPL-003 | Semantic version major dipakai untuk perubahan makna/skala/scoring yang tidak comparable; minor untuk item/branch bermakna; patch hanya editorial nonmakna. | Review contoh perubahan menghasilkan klasifikasi yang diharapkan. |
| BR-TPL-004 | Setiap item dimiliki satu indikator utama, satu answer type, satu method binding, dan satu scoring behavior yang eksplisit. | Item tanpa/multi primary indicator gagal preflight. |
| BR-TPL-005 | IPA sah hanya bila importance dan performance diukur pada indikator, population, period, dan scale yang compatible. | Pasangan hilang/tidak compatible diblokir. |
| BR-TPL-006 | SERVQUAL gap hanya dihitung dari pasangan expectation–perception respondent-item lengkap; agregat kelompok tidak boleh diperlakukan sebagai pasangan. | Fixture unpaired dikeluarkan dari denominator. |

## 3. Validation and approval

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-VAL-001 | I-CVI memakai jumlah rating 3/4 dibagi jumlah ahli sah; S-CVI/Ave adalah mean I-CVI item dalam scope. | Vektor manual menghasilkan nilai sama dan denominator tampil. |
| BR-VAL-002 | I-CVI ≥0,78 adalah flag review untuk panel 6–10, bukan bukti validitas otomatis atau alasan hapus otomatis. | UI/report menggunakan label review dan membutuhkan keputusan manusia. |
| BR-VAL-003 | Cronbach's alpha/omega tidak boleh diberi label validitas atau unidimensionalitas. | Terminology test pada report/template. |
| BR-VAL-004 | Factor analysis hanya diaktifkan untuk konstruk latent-reflective dengan analysis plan approved; checklist/formatif/SKM tidak otomatis eligible. | Job pada tipe noneligible ditolak. |

## 4. Campaign and population

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-CAM-001 | Campaign hanya dapat merujuk satu version approved; snapshot tidak berubah walau source version retired. | Retire source tidak mengubah campaign. |
| BR-CAM-002 | Open time harus lebih awal dari close time, memakai timezone IANA, dan tidak boleh diubah setelah response pertama tanpa approved exception. | Boundary/exception test. |
| BR-CAM-003 | Satu participant hanya memiliki satu participation record per campaign setelah deduplication rule. | Import/source ganda menghasilkan satu record. |
| BR-CAM-004 | Ineligible, duplicate, undeliverable, refusal, partial, complete, dan unknown eligibility adalah disposition berbeda. | Reconciliation mempertahankan kategori eksklusif. |
| BR-CAM-005 | Mode strict anonymous tidak boleh menjanjikan deduplication berbasis identitas setelah submit; mode detached hanya melacak participation tanpa join ke content. | Architecture/schema test per mode. |
| BR-CAM-006 | Publish membutuhkan owner campaign, owner tindakan, privacy notice, threshold, scoring, population, notification, dan preflight tanpa blocker. | Hilangkan tiap artefak; publish ditolak. |

## 5. Response

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-RSP-001 | Response state adalah started → partial → submitted atau withdrawn/expired; submitted tidak kembali ke draft. | State-transition test. |
| BR-RSP-002 | N/A, blank, refusal, dan neutral adalah nilai berbeda; hanya neutral boleh masuk scoring bila scale mendefinisikannya. | Fixture menghasilkan denominator berbeda. |
| BR-RSP-003 | Submit bersifat exactly-once secara bisnis menggunakan idempotency key; retry mengembalikan receipt yang sama tanpa response baru. | Concurrent/replay test. |
| BR-RSP-004 | Responden boleh berhenti sebelum submit; draft mengikuti retention campaign dan tidak dihitung sebagai completion. | Close campaign dengan partial response. |
| BR-RSP-005 | Open text tidak wajib dan notice melarang direct identifier; kanal pengaduan kasus individual dipisahkan. | Template preflight menolak open text wajib tanpa approved exception. |

## 6. Notification

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-NOT-001 | Reminder hanya dikirim kepada eligible participant yang belum submitted menurut participation store. | Submitted/ineligible tidak menerima reminder. |
| BR-NOT-002 | Maksimum reminder baseline adalah tiga per participant per campaign; perubahan jumlah memerlukan policy version approved. | Reminder keempat ditolak pada baseline. |
| BR-NOT-003 | Notifikasi tidak memuat response content, raw comment, secret, atau data Restricted. | Content scanner fixture memblokir placeholder terlarang. |
| BR-NOT-004 | Retry delivery memakai idempotency key yang sama untuk logical message yang sama. | Provider timeout/retry menghasilkan satu logical delivery. |

## 7. Scoring and analysis

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-SCR-001 | Skor dihitung dari submitted response sah dan scoring snapshot campaign, bukan rule terbaru global. | Ubah rule global; rerun campaign lama tetap identik. |
| BR-SCR-002 | Normalisasi skala L–U adalah `100 × (x-L)/(U-L)` dan menyimpan raw score. | Skala 1–5 memetakan 1/3/5 ke 0/50/100. |
| BR-SCR-003 | Category score respondent memerlukan minimal `ceil(0,80 × k)` item sah; tidak ada imputasi default. | Lima item dengan tiga valid tidak menghasilkan category score. |
| BR-SCR-004 | Perhitungan memakai presisi penuh; band internal memakai nilai sebelum rounding; SKM memakai nilai konversi dua desimal pada band resmi. | Boundary 79,995 internal dan batas SKM diuji. |
| BR-SCR-005 | CSI harus dilabel `CSI internal`, memakai bobot importance `Iᵢ/ΣI`, dan tidak boleh dilabel ACSI. | Formula/name contract test. |
| BR-SCR-006 | NPS = %promoter(9–10) − %detractor(0–6); passive 7–8 tetap denominator dan NPS tidak dinormalisasi. | Vektor 6/2/2 menghasilkan 40,0. |
| BR-SCR-007 | Bobot sampling/nonresponse hanya dipakai bila design, selection probability/calibration, dan analysis plan approved; weighted dan unweighted sama-sama dilaporkan. | Run tanpa metadata ditolak. |
| BR-SCR-008 | AI output hanya rekomendasi coding/tema; human reviewer menentukan final label, correction, quotation, dan recommendation. | Job tidak dapat berstatus approved tanpa reviewer decision. |

## 8. Reporting and export

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-REP-001 | Cell `n<10` disuppress; `n=10–29` hanya deskriptif; comparison/trend membutuhkan `n≥30` pada setiap cell, kecuali policy version lebih ketat. | Golden dataset pada n=9/10/29/30. |
| BR-REP-002 | Public/high-risk/open-text threshold mengikuti policy yang dipin; minimum baseline public/high-risk 20 dan AI theme 20 komentar. | Boundary test per output type. |
| BR-REP-003 | Complementary suppression diterapkan bila nilai cell kecil dapat diturunkan dari total/cell lain. | Differencing fixture tidak mengungkap cell. |
| BR-REP-004 | Screen, API, scheduled report, dan export menerapkan filter scope dan suppression identik. | Golden dataset parity 100%. |
| BR-REP-005 | Raw response export bukan fungsi rutin; memerlukan purpose, approval data owner/privacy, expiry, dan de-identification profile. | Export tanpa satu approval ditolak. |
| BR-REP-006 | Download link sekali pakai, terikat requester, kedaluwarsa paling lama 24 jam, dan dapat dicabut. | Cross-user/replay/expired link ditolak. |

## 9. PPEPP follow-up

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-PPE-001 | Finding selalu memiliki source result/report, evidence, severity, owner unit, PIC, dan due date. | Record tidak lengkap ditolak. |
| BR-PPE-002 | PIC tidak boleh memverifikasi atau menutup action miliknya sendiri. | Self-verification/closure ditolak. |
| BR-PPE-003 | Verified berarti evidence memenuhi acceptance target; uploaded bukan sinonim verified. | Upload saja tidak mengubah verification state. |
| BR-PPE-004 | Closure memerlukan verification dan impact evaluation atau waiver beralasan dengan approver/expiry. | Closure tanpa keduanya ditolak. |
| BR-PPE-005 | Overdue dihitung dari timezone action dan tetap tercatat walau due date diubah; perubahan due date membutuhkan alasan/audit. | Backdating tidak menghapus aging historis. |

## 10. Governance, privacy, and retention

| ID | Business rule | Uji/exception |
|---|---|---|
| BR-GOV-001 | Direct identifier, participation data, response content, open text, audit, dan secret disimpan/diakses sesuai classification berbeda. | Permission dan storage-policy test per class. |
| BR-GOV-002 | Policy version yang dipin pada campaign tidak berubah retroaktif; emergency control hanya dapat memperketat akses/release. | Policy update tidak menurunkan proteksi campaign lama. |
| BR-GOV-003 | Legal hold mengalahkan scheduled deletion tetapi tidak memperluas permission baca. | Held record tidak terhapus dan tetap scoped. |
| BR-GOV-004 | Deletion wajib menghasilkan tombstone/evidence tanpa mempertahankan content yang seharusnya dihapus. | Inspect tombstone tidak memuat payload. |
| BR-GOV-005 | Anonymous response tidak dapat dipenuhi melalui pencarian subject-specific; notice harus menjelaskan keterbatasan ini. | Subject request menghasilkan respons prosedural tanpa re-identification. |
| BR-GOV-006 | AI nonaktif sampai registry use case, DPIA/risk review, contract/location/retention, field allowlist, evaluator, dan approval lengkap. | Setiap missing gate memblokir activation. |
| BR-GOV-007 | Secret hanya disimpan sebagai secret-manager reference, tidak dapat dibaca kembali dari UI/API, dan setiap rotasi/revoke diaudit. | Secret retrieval/echo/log test. |

## 11. Urutan konflik aturan

Jika rule bertentangan, urutan pengendaliannya adalah: hukum/regulasi yang berlaku → privacy/security policy yang approved → campaign snapshot → template/scoring version → konfigurasi operasional. Konflik harus memblokir operasi dan masuk exception workflow; sistem tidak memilih rule yang paling longgar.

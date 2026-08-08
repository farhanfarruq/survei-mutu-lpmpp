# Functional Requirements

Versi: **1.0 — 2026-08-07**  
Status: **baseline Phase 04; persetujuan institusi masih diperlukan**

## 1. Konvensi

- Kata **harus** berarti wajib dan dapat diuji; **dapat** hanya dipakai untuk perilaku opsional yang kondisi aktivasinya dinyatakan.
- Prioritas: `MUST` = MVP, `SHOULD` = setelah MVP atau bila dependency governance tersedia.
- Setiap ID unik dan dimiliki tepat satu modul. Baseline memuat **80 FR pada 10 modul**, melampaui minimum 60 FR total yang dikelompokkan per modul.
- Create/Read/Update/Delete/Execute/Export dipisahkan dalam [access-control-matrix.md](access-control-matrix.md).

## 2. M01 — Identity and Access Management

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-IAM-001 | Sistem harus mengautentikasi akun internal melalui credential yang sah dan menolak credential salah tanpa mengungkap apakah identifier terdaftar. | MUST | Uji login sukses, salah, dan identifier tidak dikenal menghasilkan respons generik. |
| FR-IAM-002 | Sistem harus mewajibkan MFA untuk Super Admin dan Admin LPMPP sebelum memberi akses administratif. | MUST | Uji akun administratif tanpa faktor kedua tidak mencapai halaman admin. |
| FR-IAM-003 | Sistem harus menetapkan satu atau lebih role hanya kepada user aktif dan mengevaluasi permission pada setiap request terlindungi. | MUST | Uji matriks role × endpoint; seluruh akses tanpa permission menghasilkan 403. |
| FR-IAM-004 | Sistem harus membatasi data berdasarkan scope institusi, unit, survei, dan penugasan yang tersimpan pada user. | MUST | Uji dua user dengan scope berbeda tidak dapat membaca ID silang termasuk lewat URL langsung. |
| FR-IAM-005 | Sistem harus mengakhiri sesi administratif setelah 30 menit idle dan meminta autentikasi ulang untuk operasi berisiko tinggi. | MUST | Simulasi idle dan aksi export/secret/role; sesi kadaluarsa dan re-auth tampil. |
| FR-IAM-006 | Sistem harus menyediakan reset credential dengan token sekali pakai yang kedaluwarsa paling lama 30 menit. | MUST | Uji token sah, terpakai, dan kedaluwarsa. |
| FR-IAM-007 | Sistem harus mengunci autentikasi akun selama 15 menit setelah 5 kegagalan dalam 15 menit serta mencatat event keamanan. | MUST | Uji kegagalan keenam ditolak dan audit event tersedia. |
| FR-IAM-008 | Sistem harus mencatat pemberian, perubahan, pencabutan role/scope beserta pelaku, target, waktu, nilai sebelum, alasan, dan nilai sesudah. | MUST | Uji perubahan akses menghasilkan audit record lengkap dan immutable bagi pelaku. |

## 3. M02 — Taxonomy, Template, and Instrument Versioning

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-TPL-001 | Admin LPMPP harus dapat membuat template dengan kode unik, keluarga survei, tujuan keputusan, population type, owner, dan status draft. | MUST | Buat template valid/duplikat; duplikat kode ditolak. |
| FR-TPL-002 | Admin LPMPP harus dapat membuat versi draft dari template dengan nomor semantic version dan effective period. | MUST | Uji format version, overlap period, dan histori versi. |
| FR-TPL-003 | Sistem harus menyimpan hierarchy category–indicator–question dan urutan deterministik pada setiap versi. | MUST | Simpan/reload hierarchy; struktur dan urutan identik. |
| FR-TPL-004 | Admin LPMPP harus dapat mengelola scale, pilihan berlabel, missing/N/A behavior, required flag, branching, dan method binding per item. | MUST | Uji seluruh field dirender dan divalidasi sesuai konfigurasi. |
| FR-TPL-005 | Sistem harus memvalidasi bahwa IPA memiliki pasangan importance–performance dan SERVQUAL memiliki pasangan expectation–perception pada indikator/skala identik. | MUST | Draft pasangan tidak lengkap/berbeda skala gagal dikirim untuk review. |
| FR-TPL-006 | Sistem harus menyimpan scoring snapshot, precision, rounding, missing rule, threshold, interpretation band, dan compatibility note per versi. | MUST | Uji publish tanpa snapshot gagal; reload menampilkan nilai sama. |
| FR-TPL-007 | Sistem harus mencegah perubahan content maupun scoring pada versi published; revisi harus dibuat sebagai versi baru. | MUST | Uji update/delete versi published ditolak dan clone draft berhasil. |
| FR-TPL-008 | Sistem harus menghasilkan perbandingan dua versi yang menandai item, skala, branching, scoring, privacy, dan compatibility yang berubah. | SHOULD | Uji diff pada perubahan terkontrol menampilkan seluruh delta. |

## 4. M03 — Review and Instrument Validation

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-VAL-001 | Admin LPMPP harus dapat mengirim versi draft kepada reviewer yang ditugaskan dengan due date dan review scope. | MUST | Uji assignment menghasilkan task hanya pada reviewer terpilih. |
| FR-VAL-002 | Reviewer harus dapat memberi rating relevansi/kejelasan 1–4 dan komentar pada setiap item tanpa mengubah item. | MUST | Uji reviewer dapat menilai tetapi update content ditolak. |
| FR-VAL-003 | Sistem harus menghitung I-CVI per item dan S-CVI/Ave dari rating sah serta menampilkan denominator ahli. | MUST | Bandingkan hasil dengan vektor hitung yang disetujui. |
| FR-VAL-004 | Admin LPMPP harus dapat mencatat keputusan accept/revise/remove beserta alasan untuk setiap review finding. | MUST | Uji finding tidak dapat ditutup tanpa keputusan dan alasan. |
| FR-VAL-005 | Sistem harus menyimpan evidence cognitive interview, pilot, item analysis, reliability, dan factor analysis sebagai metadata/attachment dengan classification. | MUST | Uji upload metadata wajib, akses scope, checksum, dan audit. |
| FR-VAL-006 | Sistem harus menampilkan validation status dan melarang status loncat bila evidence/approval tahap sebelumnya belum lengkap. | MUST | Uji transisi ilegal ditolak sesuai state rule. |
| FR-VAL-007 | Reviewer berwenang harus dapat menyetujui atau mengembalikan versi dengan komentar; reviewer tidak boleh menyetujui versi yang ia buat sendiri. | MUST | Uji separation of duties dan return flow. |
| FR-VAL-008 | Sistem harus menyimpan approval record berisi version hash, reviewer, keputusan, timestamp, komentar, dan conflict declaration. | MUST | Uji approval menghasilkan record yang tetap terikat pada hash versi. |

## 5. M04 — Campaign and Population Management

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-CAM-001 | Admin LPMPP harus dapat membuat campaign dari satu versi approved dengan nama, owner, unit scope, period, timezone, dan status draft. | MUST | Uji campaign tanpa versi approved ditolak. |
| FR-CAM-002 | Sistem harus membekukan snapshot versi, scoring, privacy mode, threshold, dan analysis plan ketika campaign dipublikasikan. | MUST | Uji perubahan sumber setelah publish tidak mengubah snapshot campaign. |
| FR-CAM-003 | Admin LPMPP harus dapat mengimpor population frame dengan schema mapping, source date, eligibility, dan duplicate rule tanpa memasukkan data ke response content store. | MUST | Uji preview, invalid row, deduplication, dan pemisahan store. |
| FR-CAM-004 | Sistem harus menghitung disposition awal: eligible, ineligible, duplicate, undeliverable, dan unknown berdasarkan rule berversi. | MUST | Uji fixture population menghasilkan count per disposition yang diharapkan. |
| FR-CAM-005 | Admin LPMPP harus dapat memilih census atau sampling design dan menyimpan frame size, selection rule, seed/weight metadata, serta justification. | MUST | Uji metadata wajib sebelum publish. |
| FR-CAM-006 | Sistem harus menghasilkan token undangan unik yang tidak menyimpan response ID dan, untuk mode detached, tidak dapat dipakai menggabungkan participation dengan content. | MUST | Uji schema/flow tidak memiliki join key dan token replay ditolak. |
| FR-CAM-007 | Admin LPMPP harus dapat menjalankan preflight yang memeriksa approval, period, population, privacy notice, threshold, notification, scoring, dan owner tindak lanjut. | MUST | Uji setiap defect memblokir publish dengan pesan spesifik. |
| FR-CAM-008 | User berpermission publish harus dapat menjadwalkan open/close campaign dan sistem harus mengeksekusi transisi pada timezone campaign. | MUST | Uji boundary waktu, timezone, cancel, dan audit transisi. |

## 6. M05 — Respondent Experience and Response Capture

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-RSP-001 | Responden harus dapat membuka undangan campaign aktif dan melihat tujuan, privacy notice, waktu estimasi, voluntariness, serta contact point sebelum consent. | MUST | Uji campaign aktif/inaktif dan seluruh notice tampil sebelum item. |
| FR-RSP-002 | Sistem harus mencatat consent version dan timestamp tanpa menautkan identitas pada content bila privacy mode anonymous. | MUST | Uji penyimpanan consent sesuai mode dan tidak ada join key. |
| FR-RSP-003 | Sistem harus merender pertanyaan, label skala, required/optional, N/A, dan branching tepat sesuai snapshot campaign. | MUST | Uji rendering terhadap fixture template dan seluruh branch. |
| FR-RSP-004 | Sistem harus meng-autosave draft paling lambat 5 detik setelah perubahan dan memulihkannya pada perangkat/session yang diizinkan. | MUST | Putus jaringan/reload; jawaban terakhir pulih tanpa duplikasi. |
| FR-RSP-005 | Responden harus dapat meninjau jawaban dan melihat item wajib yang belum lengkap sebelum submit. | MUST | Uji review menandai semua kekurangan dan tidak mengubah jawaban. |
| FR-RSP-006 | Sistem harus menerima submit tepat satu kali, memberi receipt nonidentifying, membekukan response, dan menolak replay. | MUST | Uji double-click/replay menghasilkan satu completion. |
| FR-RSP-007 | Sistem harus menerima pilihan N/A sebagai nilai sah non-skoring dan tidak memperlakukannya sebagai nol/netral. | MUST | Uji denominator/score dengan N/A sesuai scoring snapshot. |
| FR-RSP-008 | Sistem harus menyediakan mekanisme aksesibilitas keyboard, error summary, focus management, dan status save/submit yang dapat dibaca assistive technology. | MUST | Uji keyboard-only dan screen-reader checklist pada happy/error path. |

## 7. M06 — Notification and Participation Operations

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-NOT-001 | Admin LPMPP harus dapat mengelola template undangan/reminder/closure per channel dan bahasa tanpa memasukkan isi jawaban. | MUST | Uji placeholder allowlist dan preview tanpa response data. |
| FR-NOT-002 | Sistem harus mengirim undangan hanya kepada participant eligible pada window dan channel yang disetujui. | MUST | Uji ineligible/outside-window tidak menerima pesan. |
| FR-NOT-003 | Sistem harus menjadwalkan reminder hanya kepada status belum submit pada participation store tanpa membaca response content. | MUST | Uji submitted tidak diingatkan dan tidak terjadi join ke isi. |
| FR-NOT-004 | Sistem harus membatasi maksimum tiga reminder per participant per campaign dan menghormati opt-out channel. | MUST | Uji reminder keempat dan recipient opt-out ditolak. |
| FR-NOT-005 | Sistem harus mencatat delivery status sent/delivered/bounced/failed beserta provider reference tanpa menyimpan body sensitif. | MUST | Uji callback/provider result menghasilkan status dan audit tepat. |
| FR-NOT-006 | Admin LPMPP harus dapat retry delivery gagal dengan backoff dan idempotency key tanpa mengirim duplikat. | MUST | Simulasi retry concurrent menghasilkan satu delivery logis. |
| FR-NOT-007 | Sistem harus memberi alert operasional kepada Admin LPMPP bila bounce rate >10% atau failure rate >5% dalam 15 menit. | MUST | Uji threshold boundary dan satu alert terdeduplikasi. |
| FR-NOT-008 | Sistem harus menampilkan count invitation, delivery, completion, dan disposition agregat tanpa membuka identitas respondent kepada unit owner. | MUST | Uji role unit hanya melihat agregat scope dan suppression berlaku. |

## 8. M07 — Scoring, Analysis, and Data Quality

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-ANA-001 | User berpermission execute harus dapat menjalankan scoring hanya pada response submitted menggunakan scoring snapshot campaign. | MUST | Uji draft response dikecualikan dan snapshot yang benar dipakai. |
| FR-ANA-002 | Sistem harus menghitung SERVPERF, SERVQUAL, IPA, CSI internal, SKM/IKM, NPS, atau metode internal hanya bila precondition metode terpenuhi. | MUST | Jalankan test vector Phase 03 dan kasus precondition gagal. |
| FR-ANA-003 | Sistem harus menerapkan precision, rounding, missing/N/A, paired-completion, dan category-completion rule sesuai snapshot. | MUST | Uji seluruh boundary termasuk 80% completion dan SKM dua desimal. |
| FR-ANA-004 | Sistem harus menghasilkan statistik item/category dengan `n`, missing, N/A, distribusi, mean, dan quality flag. | MUST | Cocokkan output dengan dataset fixture yang dihitung manual. |
| FR-ANA-005 | Sistem harus menghitung disposition, completion/participation rate, coverage, dan comparison frame pada denominator yang diberi label. | MUST | Uji denominator berbeda dan label formula tampil. |
| FR-ANA-006 | Analyst harus dapat menjalankan reliability/item analysis dan menyimpan method, parameter, sample, software version, serta output; sistem tidak boleh melabel alpha sebagai validitas. | SHOULD | Uji metadata wajib dan terminologi output. |
| FR-ANA-007 | Analisis AI harus nonaktif secara default dan hanya dapat dijalankan pada data de-identified, pool lolos threshold, provider/use case approved, serta human reviewer assigned. | SHOULD | Uji setiap guard gagal memblokir job sebelum data dikirim. |
| FR-ANA-008 | Setiap analysis run harus menghasilkan immutable run ID, input hash, formula/version, parameter, pelaku, waktu, status, dan error/retry history. | MUST | Uji rerun menghasilkan ID baru dengan lineage lengkap. |

## 9. M08 — Reporting and Export

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-REP-001 | Pimpinan dan user berizin harus dapat melihat dashboard hanya untuk data scope mereka dengan period, version, `n`, missing, coverage, dan limitation. | MUST | Uji scope silang, label metadata, dan empty/suppressed state. |
| FR-REP-002 | Sistem harus menerapkan minimum cell, sensitive-cell, complementary suppression, anti-differencing, dan dominance rule pada layar dan export. | MUST | Uji fixture cell kecil pada seluruh format menghasilkan suppression konsisten. |
| FR-REP-003 | Sistem harus membedakan nilai nol, tidak ada data, belum dihitung, error, dan suppressed dengan label yang tidak ambigu. | MUST | Uji lima state tampil berbeda dan machine-readable. |
| FR-REP-004 | User berpermission export harus dapat meminta report PDF dan tabular CSV/XLSX yang disetujui; setiap export tunduk pada scope/threshold yang sama. | MUST | Uji tiap format, role, scope, dan suppression parity. |
| FR-REP-005 | Sistem harus menghasilkan export secara asynchronous dengan status queued/running/completed/failed/expired dan download link sekali pakai. | MUST | Uji state, retry, expiry, dan replay link. |
| FR-REP-006 | Setiap report/export harus memuat campaign/version/scoring ID, generated-at, filter, owner, classification, dan confidentiality marking. | MUST | Inspeksi seluruh format terhadap metadata wajib. |
| FR-REP-007 | Reviewer laporan harus dapat approve/reject report release dengan komentar; requester tidak boleh menyetujui release miliknya sendiri. | MUST | Uji separation of duties dan release tanpa approval ditolak. |
| FR-REP-008 | Sistem harus mencatat request, generation, approval, download, failure, expiry, dan revocation export pada audit log. | MUST | Uji lifecycle export menghasilkan event lengkap. |

## 10. M09 — Findings, Action Plans, and PPEPP Follow-up

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-PPE-001 | Admin LPMPP harus dapat membuat finding dari hasil terpilih dengan source report, evidence, severity, owner unit, dan due date. | MUST | Uji finding tanpa source/evidence/owner gagal disimpan. |
| FR-PPE-002 | PIC harus dapat menerima/menolak assignment dengan alasan dan membuat action plan berisi tindakan, output, target, milestone, serta resource need. | MUST | Uji acceptance/rejection dan field wajib. |
| FR-PPE-003 | PIC harus dapat memperbarui progress dan mengunggah evidence sesuai classification tanpa mengubah finding asal. | MUST | Uji update scope, versioned evidence, dan immutability finding. |
| FR-PPE-004 | Sistem harus mengirim reminder sebelum due date dan escalation setelah overdue sesuai notification matrix. | MUST | Uji waktu H-7/H-1/H+1 dan deduplication. |
| FR-PPE-005 | Verifikator harus dapat menyatakan verified, needs-rework, atau rejected beserta alasan dan evidence review. | MUST | Uji status tanpa alasan/evidence ditolak. |
| FR-PPE-006 | Sistem harus mencegah PIC memverifikasi action miliknya sendiri dan mencegah closure sebelum seluruh acceptance evidence terpenuhi. | MUST | Uji separation of duties dan incomplete evidence. |
| FR-PPE-007 | Admin LPMPP harus dapat mencatat impact evaluation pada campaign/period berikut dengan baseline, target, observed result, dan conclusion effective/partial/ineffective. | MUST | Uji closure tanpa impact plan/waiver ditolak. |
| FR-PPE-008 | Pimpinan harus dapat melihat aging, overdue, verification, impact, dan unresolved risk agregat sesuai scope. | MUST | Uji dashboard memakai data fixture dan scope pimpinan. |

## 11. M10 — Governance, Privacy, Audit, and AI Configuration

| ID | Functional requirement | Prioritas | Verifikasi |
|---|---|:---:|---|
| FR-GOV-001 | Super Admin harus dapat mengelola unit hierarchy, system role, permission, data scope, dan delegasi dengan effective/expiry date. | MUST | Uji CRUD terotorisasi, expiry otomatis, dan audit. |
| FR-GOV-002 | Super Admin harus dapat menetapkan privacy mode, retention schedule, reporting threshold, classification policy, dan exception workflow secara berversi. | MUST | Uji policy baru tidak mengubah campaign snapshot lama. |
| FR-GOV-003 | Sistem harus mengeksekusi retention disposition yang approved, membuat deletion/tombstone evidence, dan menahan record yang legal-hold. | MUST | Uji due deletion, legal hold, failure, dan approval evidence. |
| FR-GOV-004 | Subject-rights officer harus dapat mencari data hanya pada mode yang memungkinkan linkage, mencatat verifikasi requestor, keputusan, dan fulfillment tanpa membuka data di luar request. | MUST | Uji anonymous mode menyatakan tidak dapat ditautkan; confidential mode scope terbatas. |
| FR-GOV-005 | Auditor berizin harus dapat mencari audit event berdasarkan actor, action, object, time, result, dan correlation ID serta mengekspor paket audit tersuppress. | MUST | Uji filter, integrity, scope, dan export approval. |
| FR-GOV-006 | Super Admin harus dapat menyimpan referensi secret provider AI melalui secret manager tanpa menampilkan nilai secret setelah tersimpan. | SHOULD | Uji create/rotate/revoke dan UI/API tidak mengembalikan secret. |
| FR-GOV-007 | Sistem harus menyimpan registry use case AI berisi tujuan, provider/model/version, field allowlist, location, retention, DPIA/approval, evaluator, dan status active. | SHOULD | Uji activation gagal bila metadata/approval belum lengkap. |
| FR-GOV-008 | Sistem harus menyediakan konfigurasi feature flag untuk menonaktifkan AI, export sensitif, dan channel notifikasi tanpa menghapus data historis. | MUST | Uji disable segera memblokir operasi baru dan event diaudit. |

## 12. Notification matrix

| Event | Recipient | Channel | Timing/SLA | Data minimum | Opt-out | FR |
|---|---|---|---|---|---|---|
| Undangan survei | participant eligible | email/link resmi | pada open window | nama campaign, purpose, due date, token | tidak untuk undangan resmi; channel alternatif sesuai kebijakan | FR-NOT-002 |
| Reminder partisipasi | belum submit | email | maksimal 3; jadwal disetujui | campaign, due date, token | ya untuk reminder nonwajib | FR-NOT-003–004 |
| Penutupan survei | participant | email/in-app | saat close, bila dikonfigurasi | campaign, contact point | ya | FR-NOT-001 |
| Delivery anomaly | Admin LPMPP | in-app + email | ≤15 menit setelah threshold | count/rate, provider, campaign; tanpa isi | tidak | FR-NOT-007 |
| Review ditugaskan | Reviewer | in-app + email | ≤5 menit | version, scope, due date | tidak | FR-VAL-001 |
| Review dikembalikan/disetujui | Admin LPMPP | in-app + email | ≤5 menit | version, keputusan, komentar | tidak | FR-VAL-007 |
| Export selesai/gagal | requester | in-app + email | ≤5 menit dari state akhir | export ID, classification, expiry; link sekali pakai | tidak | FR-REP-005 |
| Action plan jatuh tempo | PIC | in-app + email | H-7 dan H-1 | finding, due date, action ID | tidak | FR-PPE-004 |
| Action plan overdue | PIC, Admin LPMPP, owner unit | in-app + email | H+1 lalu mingguan | ID, aging, owner; tanpa raw response | tidak | FR-PPE-004 |
| Verifikasi diminta/selesai | Verifikator/PIC | in-app + email | ≤5 menit | action ID, status, due date | tidak | FR-PPE-005 |
| Policy/role berubah | affected admin + auditor | in-app + email | ≤5 menit | jenis perubahan, pelaku, effective date | tidak | FR-IAM-008/FR-GOV-001 |
| Security event kritis | Super Admin/security owner | kanal insiden | ≤5 menit | event ID, severity, correlation ID | tidak | FR-IAM-007 |

Body notifikasi tidak boleh memuat response content, komentar raw, secret, atau data kategori `Restricted`.

## 13. Report and export matrix

| Keluaran | Audience | Format | Scope | Frequency/trigger | Approval | Privacy control | FR |
|---|---|---|---|---|---|---|---|
| Monitoring partisipasi | Admin LPMPP | dashboard/CSV | campaign & unit authorized | near-real-time/manual | tidak untuk dashboard; export sesuai role | detached status, tanpa isi | FR-NOT-008 |
| Ringkasan hasil | Pimpinan/unit owner | dashboard/PDF | hierarchy authorized | setelah scoring approved | reviewer release | minimum cell + complementary suppression | FR-REP-001–007 |
| Item/category detail | Analyst/Admin LPMPP | dashboard/CSV/XLSX | campaign authorized | manual | export approval | `n`, missing, N/A, suppression | FR-ANA-004/FR-REP-004 |
| IPA/CSI/SERVQUAL/SKM/NPS | Analyst/Pimpinan | dashboard/PDF/XLSX | metode eligible | setelah analysis run | reviewer release | method label + denominator | FR-ANA-002/FR-REP-006 |
| Coverage/nonresponse | Admin LPMPP/Pimpinan | dashboard/PDF | population frame scope | campaign close | reviewer release | frame aggregate only | FR-ANA-005 |
| Validasi instrumen | Reviewer/Admin LPMPP | PDF/XLSX | assigned template/version | per review/pilot | metodolog | expert identity confidential | FR-VAL-002–008 |
| Audit package | Auditor | CSV/JSON/PDF | approved audit scope | on demand | security/data owner | redaction + signed manifest | FR-GOV-005 |
| PPEPP action register | PIC/Verifikator/Pimpinan | dashboard/PDF/XLSX | assignment/hierarchy | real-time/monthly | owner report | no raw response | FR-PPE-001–008 |
| Public summary | Publik | PDF/HTML | institution aggregate | approved release | LPMPP + privacy owner | public threshold, no drilldown | FR-REP-002/007 |
| Raw response research extract | Analyst terbatas | CSV | explicitly approved campaign | exception only | data owner + privacy/ethics | de-identification, no free text by default | FR-REP-004/FR-GOV-002 |

## 14. Requirement traceability matrix

| FR range | Objective/output | User stories | Business rules | NFR utama | Risiko utama | Acceptance evidence |
|---|---|---|---|---|---|---|
| FR-IAM-001–008 | akses sah dan scoped | US-SAD-01–04, US-ADM-01 | BR-ACC-001–005 | NFR-SEC-001–004, NFR-AUD-001 | RSK-01, RSK-03 | authorization/security test report |
| FR-TPL-001–008 | instrumen berversi | US-ADM-02–05, US-REV-01 | BR-TPL-001–006 | NFR-MNT-001–003 | RSK-05, RSK-06 | version/immutability/diff tests |
| FR-VAL-001–008 | evidence validasi dan approval | US-ADM-06, US-REV-01–06 | BR-VAL-001–004 | NFR-AUD-001–003 | RSK-06, RSK-13 | review workflow and CVI vectors |
| FR-CAM-001–008 | campaign/population siap | US-ADM-07–10, US-SAD-05 | BR-CAM-001–006 | NFR-PER-001–003, NFR-PRI-001 | RSK-04, RSK-07 | preflight, snapshot, population fixtures |
| FR-RSP-001–008 | respons aman dan accessible | US-RES-01–08 | BR-RSP-001–005 | NFR-PER-001–003, NFR-ACC-001–003 | RSK-02, RSK-08 | respondent E2E/accessibility tests |
| FR-NOT-001–008 | delivery dan reminder terkendali | US-ADM-11, US-SAD-06 | BR-NOT-001–004 | NFR-AVL-001, NFR-CMP-003 | RSK-09 | notification boundary/idempotency tests |
| FR-ANA-001–008 | scoring/reproducibility | US-ADM-12, US-PIM-01–03 | BR-SCR-001–008 | NFR-PER-004, NFR-AUD-002 | RSK-10, RSK-11 | Phase 03 vectors and lineage tests |
| FR-REP-001–008 | laporan/ekspor aman | US-PIM-01–08, US-SAD-07 | BR-REP-001–006 | NFR-PRI-002–003, NFR-PER-004 | RSK-02, RSK-12 | suppression parity/export tests |
| FR-PPE-001–008 | feedback loop tertutup | US-PIC-01–06, US-VER-01–06, US-PIM-06–08 | BR-PPE-001–005 | NFR-AUD-001–003 | RSK-14, RSK-15 | action/verification/impact workflow tests |
| FR-GOV-001–008 | policy, retention, audit, AI guard | US-SAD-01–10 | BR-GOV-001–007 | NFR-SEC-001–004, NFR-PRI-001–003 | RSK-01–04, RSK-16 | policy snapshot, retention, AI gate tests |

Traceability rinci per user story tersedia pada [user-stories-and-acceptance-criteria.md](user-stories-and-acceptance-criteria.md). Risiko tersedia pada [risk-register.md](risk-register.md).

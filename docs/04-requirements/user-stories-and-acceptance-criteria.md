# User Stories and Acceptance Criteria

Versi: **1.0 — 2026-08-07**  
Status: **MVP baseline; seluruh story harus terkait FR dan BR**

## 1. Responden — 8 stories

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-RES-01 | Sebagai responden, saya ingin melihat tujuan, privasi, durasi, dan sifat sukarela sebelum mulai agar dapat membuat keputusan sadar. | **Given** undangan campaign aktif, **When** link dibuka, **Then** notice versioned tampil sebelum pertanyaan dan tidak ada consent default. | FR-RSP-001–002; BR-CAM-005 |
| US-RES-02 | Sebagai responden, saya ingin menyatakan setuju atau menolak berpartisipasi agar pilihan saya dihormati. | **Given** notice tampil, **When** saya menolak, **Then** pertanyaan tidak dibuka dan tidak ada response content dibuat. | FR-RSP-002; BR-RSP-001 |
| US-RES-03 | Sebagai responden, saya ingin hanya melihat pertanyaan relevan agar tidak menilai layanan yang tidak saya gunakan. | **Given** screener/branching snapshot, **When** jawaban eligibility dipilih, **Then** hanya branch yang memenuhi rule dirender. | FR-RSP-003; BR-TPL-004 |
| US-RES-04 | Sebagai responden, saya ingin jawaban tersimpan otomatis dan dapat dipulihkan agar gangguan jaringan tidak menghapus pekerjaan. | **Given** draft dan session yang diizinkan, **When** koneksi putus setelah perubahan, **Then** jawaban tersimpan ≤5 detik atau status gagal jelas dan versi terakhir dapat dipulihkan. | FR-RSP-004; BR-RSP-001 |
| US-RES-05 | Sebagai responden, saya ingin memilih tidak relevan tanpa dianggap memberi nilai buruk/netral agar skor akurat. | **Given** item menyediakan N/A, **When** N/A dipilih dan disubmit, **Then** nilai sah tersimpan sebagai non-skoring dan denominator berkurang. | FR-RSP-007; BR-RSP-002 |
| US-RES-06 | Sebagai responden, saya ingin meninjau jawaban dan kekurangan sebelum mengirim agar dapat memperbaiki kesalahan. | **Given** draft belum lengkap, **When** review dipilih, **Then** semua item wajib yang kurang ditandai dan fokus menuju error pertama. | FR-RSP-005/008; BR-RSP-005 |
| US-RES-07 | Sebagai responden, saya ingin satu receipt setelah submit agar tahu jawaban diterima tanpa membuka identitas saya. | **Given** response lengkap, **When** submit ditekan berulang, **Then** tepat satu response submitted dan receipt nonidentifying yang sama dikembalikan. | FR-RSP-006; BR-RSP-003 |
| US-RES-08 | Sebagai responden pengguna keyboard/screen reader, saya ingin menyelesaikan seluruh survei tanpa hambatan agar akses setara. | **Given** survey pada browser target, **When** digunakan keyboard dan screen reader, **Then** seluruh kontrol, error, save, review, dan submit dapat dioperasikan serta diumumkan. | FR-RSP-008; BR-RSP-005; NFR-ACC-001–003 |

## 2. Admin LPMPP — 12 stories

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-ADM-01 | Sebagai Admin LPMPP, saya ingin login aman sesuai scope agar hanya mengelola data yang ditugaskan. | **Given** akun aktif dengan MFA dan scope unit, **When** autentikasi berhasil, **Then** fungsi/data di luar permission atau scope tetap 403. | FR-IAM-001–005; BR-ACC-001–002 |
| US-ADM-02 | Sebagai Admin LPMPP, saya ingin membuat template berkode unik agar instrumen dapat dikelola konsisten. | **Given** permission create, **When** field wajib valid disimpan, **Then** template draft terbentuk; kode duplikat ditolak. | FR-TPL-001; BR-TPL-001 |
| US-ADM-03 | Sebagai Admin LPMPP, saya ingin membuat versi dan hierarchy instrumen agar kategori, indikator, dan item dapat ditelusuri. | **Given** template aktif, **When** versi/hierarchy disimpan, **Then** semantic version dan urutan deterministik tersedia setelah reload. | FR-TPL-002–003; BR-TPL-002–004 |
| US-ADM-04 | Sebagai Admin LPMPP, saya ingin mengatur skala, branching, metode, dan scoring agar instrumen dapat dihitung benar. | **Given** versi draft, **When** konfigurasi disimpan, **Then** precondition IPA/SERVQUAL dan scoring snapshot divalidasi. | FR-TPL-004–006; BR-TPL-005–006 |
| US-ADM-05 | Sebagai Admin LPMPP, saya ingin membuat versi baru dari versi published agar histori tidak berubah. | **Given** versi published, **When** edit dicoba, **Then** edit ditolak dan aksi revise membuat draft baru dengan diff. | FR-TPL-007–008; BR-TPL-002–003 |
| US-ADM-06 | Sebagai Admin LPMPP, saya ingin menugaskan review dan menindaklanjuti finding agar instrumen layak disetujui. | **Given** draft lengkap, **When** dikirim dengan reviewer/due date, **Then** task tercipta dan finding wajib diberi keputusan/alasan sebelum resubmit. | FR-VAL-001/004/006; BR-VAL-001–004 |
| US-ADM-07 | Sebagai Admin LPMPP, saya ingin membuat campaign dari versi approved agar snapshot pelaksanaan terkendali. | **Given** version approved, **When** campaign disimpan, **Then** owner, scope, period, timezone, dan draft campaign tercatat. | FR-CAM-001–002; BR-CAM-001–002 |
| US-ADM-08 | Sebagai Admin LPMPP, saya ingin mengimpor population frame dan melihat error agar hanya participant eligible diproses. | **Given** mapping dan file population, **When** preview/import dijalankan, **Then** duplicate/invalid/disposition terpisah dan content store tidak menerima identifier. | FR-CAM-003–005; BR-CAM-003–004 |
| US-ADM-09 | Sebagai Admin LPMPP, saya ingin menjalankan preflight sebelum publish agar campaign tidak dibuka dengan konfigurasi tidak lengkap. | **Given** campaign draft, **When** preflight berjalan, **Then** setiap blocker spesifik tampil dan publish hanya aktif bila nol blocker. | FR-CAM-007–008; BR-CAM-006 |
| US-ADM-10 | Sebagai Admin LPMPP, saya ingin menjadwalkan buka/tutup campaign agar periode mengikuti timezone yang benar. | **Given** preflight lolos, **When** jadwal disetujui, **Then** state berubah tepat pada boundary dan event diaudit. | FR-CAM-008; BR-CAM-002 |
| US-ADM-11 | Sebagai Admin LPMPP, saya ingin mengirim undangan/reminder dan memonitor delivery agar coverage dapat ditingkatkan tanpa membuka isi. | **Given** participant eligible dan template approved, **When** batch dikirim, **Then** hanya belum-submit menerima maksimal tiga reminder dan delivery aggregate terlihat. | FR-NOT-001–008; BR-NOT-001–004 |
| US-ADM-12 | Sebagai Admin LPMPP, saya ingin menjalankan scoring, membaca quality flag, serta membuat finding agar hasil masuk PPEPP. | **Given** campaign closed dan response submitted, **When** analysis approved dijalankan, **Then** output mengikuti snapshot, lineage tercatat, dan finding dapat menautkan source result. | FR-ANA-001–008, FR-PPE-001; BR-SCR-001–008 |

## 3. Super Admin — 10 stories

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-SAD-01 | Sebagai Super Admin, saya ingin mengelola hierarchy unit agar scope mengikuti struktur organisasi efektif. | **Given** permission governance, **When** unit dibuat/diubah dengan effective date, **Then** hierarchy tervalidasi tanpa cycle dan perubahan diaudit. | FR-GOV-001; BR-ACC-002 |
| US-SAD-02 | Sebagai Super Admin, saya ingin mengelola role dan permission granular agar CRUD/Execute/Export terpisah. | **Given** katalog permission, **When** role diperbarui, **Then** endpoint mengevaluasi grant baru dan before/after tercatat. | FR-IAM-003/008, FR-GOV-001; BR-ACC-001 |
| US-SAD-03 | Sebagai Super Admin, saya ingin memberi delegasi scope berjangka agar akses sementara berakhir otomatis. | **Given** user/role/scope sah, **When** delegasi disimpan, **Then** akses hanya berlaku antara effective dan expiry date. | FR-GOV-001; BR-ACC-005 |
| US-SAD-04 | Sebagai Super Admin, saya ingin mencabut akses darurat agar insiden dapat dibatasi segera. | **Given** user/session aktif, **When** revoke darurat dieksekusi, **Then** session/token invalid dan event security tercatat. | FR-IAM-003/008, FR-GOV-008; BR-ACC-002 |
| US-SAD-05 | Sebagai Super Admin, saya ingin mem-version privacy, threshold, dan classification policy agar campaign lama tetap reproducible. | **Given** policy active, **When** versi baru disahkan, **Then** campaign baru dapat memilihnya dan snapshot lama tidak berubah. | FR-GOV-002; BR-GOV-001–002 |
| US-SAD-06 | Sebagai Super Admin, saya ingin mengelola template notification/provider agar pesan terkirim tanpa data terlarang. | **Given** placeholder allowlist, **When** template/provider diuji, **Then** preview lolos dan Restricted field/secret ditolak. | FR-NOT-001/005–007; BR-NOT-003–004 |
| US-SAD-07 | Sebagai Super Admin, saya ingin mengelola retention dan legal hold agar deletion tepat serta dapat diaudit. | **Given** record jatuh tempo, **When** retention job berjalan, **Then** non-held record dihapus dengan tombstone dan held record dipertahankan. | FR-GOV-003; BR-GOV-003–004 |
| US-SAD-08 | Sebagai Super Admin, saya ingin mencari audit event tanpa mengubahnya agar investigasi dapat direkonstruksi. | **Given** scope audit approved, **When** filter dijalankan, **Then** event lengkap muncul ≤target dan edit/delete ditolak. | FR-GOV-005; BR-ACC-003 |
| US-SAD-09 | Sebagai Super Admin, saya ingin mengelola secret reference dan registry AI agar AI tidak aktif tanpa governance. | **Given** feature AI off, **When** secret/use case didaftarkan tetapi satu approval kurang, **Then** activation tetap ditolak dan secret tidak dapat dibaca. | FR-GOV-006–007; BR-GOV-006–007 |
| US-SAD-10 | Sebagai Super Admin, saya ingin mematikan AI/export sensitif/channel melalui feature flag agar risiko dapat dikendalikan tanpa menghapus histori. | **Given** fitur aktif, **When** flag dimatikan, **Then** operasi baru segera ditolak, histori tetap ada, dan perubahan diaudit. | FR-GOV-008; BR-GOV-002/006 |

## 4. Pimpinan — 8 stories

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-PIM-01 | Sebagai pimpinan, saya ingin melihat ringkasan hasil sesuai scope agar memahami mutu tanpa membuka respondent. | **Given** report released, **When** dashboard dibuka, **Then** hanya scope authorized dengan `n`, coverage, missing, period, version, dan limitation tampil. | FR-REP-001/003; BR-REP-004 |
| US-PIM-02 | Sebagai pimpinan, saya ingin melihat prioritas IPA dan indikator rendah agar alokasi perbaikan mempunyai dasar. | **Given** importance/performance eligible, **When** IPA dibuka, **Then** garis target, kuadran, `n`, dan performance item tampil tanpa mengubah formula. | FR-ANA-002–004; BR-TPL-005/BR-SCR-001 |
| US-PIM-03 | Sebagai pimpinan, saya ingin membandingkan periode/unit yang comparable agar tidak menarik kesimpulan palsu. | **Given** dua cell/version, **When** comparison dipilih, **Then** hanya cell n≥30 dan compatibility approved dibandingkan; lainnya diberi alasan. | FR-REP-001–003; BR-REP-001/004 |
| US-PIM-04 | Sebagai pimpinan, saya ingin cell kecil disembunyikan konsisten agar privasi terlindungi. | **Given** dataset dengan cell n<10, **When** screen/PDF/XLSX dibuka, **Then** semuanya menunjukkan suppressed dan complementary suppression yang sama. | FR-REP-002–004; BR-REP-001–004 |
| US-PIM-05 | Sebagai pimpinan, saya ingin mengunduh report approved agar keputusan rapat memakai artefak berversi. | **Given** release approved dan permission export, **When** export diminta, **Then** file berclassification/metadata, link terikat user, dan expiry ≤24 jam. | FR-REP-004–008; BR-REP-005–006 |
| US-PIM-06 | Sebagai pimpinan, saya ingin melihat finding dan action owner agar hasil survei tidak berhenti sebagai laporan. | **Given** finding dibuat, **When** register dibuka, **Then** source, severity, owner, due date, status, dan aging tampil sesuai scope. | FR-PPE-001/008; BR-PPE-001/005 |
| US-PIM-07 | Sebagai pimpinan, saya ingin memonitor overdue dan verification agar hambatan dapat dieskalasi. | **Given** action melewati due date, **When** dashboard dibuka, **Then** aging historis, escalation, verification, dan unresolved risk tampil. | FR-PPE-004–006/008; BR-PPE-002–005 |
| US-PIM-08 | Sebagai pimpinan, saya ingin melihat evaluasi dampak agar peningkatan dinilai efektif, parsial, atau tidak efektif. | **Given** action verified dan periode evaluasi tersedia, **When** impact dicatat, **Then** baseline, target, observed, conclusion, dan evidence tampil. | FR-PPE-007–008; BR-PPE-004 |

## 5. Reviewer/Metodolog — 6 stories (MVP)

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-REV-01 | Sebagai reviewer, saya ingin melihat version snapshot dan scope tugas agar menilai artefak yang benar. | **Given** assignment aktif, **When** task dibuka, **Then** hash/version, due date, scope, blueprint, dan change diff tampil read-only. | FR-VAL-001; BR-TPL-002 |
| US-REV-02 | Sebagai reviewer, saya ingin memberi rating dan komentar per item agar content validity dapat dihitung. | **Given** item dalam scope, **When** rating 1–4 dan komentar disimpan, **Then** penilaian tersimpan atas nama reviewer tanpa mengubah item. | FR-VAL-002–003; BR-VAL-001–002 |
| US-REV-03 | Sebagai reviewer, saya ingin menilai evidence cognitive interview/pilot agar status validasi tidak hanya administratif. | **Given** evidence berizin, **When** review dilakukan, **Then** keputusan, gap, dan evidence reference tercatat. | FR-VAL-005–006; BR-VAL-003–004 |
| US-REV-04 | Sebagai reviewer, saya ingin mengembalikan instrumen dengan finding agar perbaikan dapat ditelusuri. | **Given** finding material, **When** return dipilih, **Then** komentar wajib, state returned, dan Admin LPMPP diberi notifikasi. | FR-VAL-004/007; BR-ACC-004 |
| US-REV-05 | Sebagai reviewer, saya ingin menyetujui instrumen yang bukan buatan saya agar separation of duties terjaga. | **Given** semua gate selesai dan creator berbeda, **When** approve dipilih, **Then** approval terikat hash/version; self-approval ditolak. | FR-VAL-006–008; BR-ACC-004 |
| US-REV-06 | Sebagai reviewer laporan, saya ingin approve/reject release agar interpretasi, threshold, dan limitation diperiksa. | **Given** draft report, **When** review selesai, **Then** release hanya terjadi setelah keputusan reviewer nonrequester dengan komentar. | FR-REP-006–007; BR-REP-001–004 |

## 6. PIC Tindak Lanjut — 6 stories (MVP)

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-PIC-01 | Sebagai PIC, saya ingin melihat finding yang ditugaskan agar memahami source, severity, target, dan due date. | **Given** assignment aktif, **When** daftar dibuka, **Then** hanya assigned finding dan evidence yang diizinkan tampil. | FR-PPE-001–002; BR-ACC-002 |
| US-PIC-02 | Sebagai PIC, saya ingin menerima atau menolak assignment dengan alasan agar ownership eksplisit. | **Given** assignment pending, **When** accept/reject dipilih, **Then** status, alasan, pelaku, dan waktu tercatat. | FR-PPE-002; BR-PPE-001 |
| US-PIC-03 | Sebagai PIC, saya ingin membuat action plan dan milestone agar tindakan dapat dipantau. | **Given** assignment accepted, **When** tindakan/output/target/milestone/resource disimpan, **Then** plan aktif dengan validation field wajib. | FR-PPE-002; BR-PPE-001 |
| US-PIC-04 | Sebagai PIC, saya ingin memperbarui progress dan evidence agar pelaksanaan terbukti. | **Given** action aktif, **When** progress/evidence ditambah, **Then** versi baru tercatat dan finding/source tidak berubah. | FR-PPE-003; BR-PPE-003 |
| US-PIC-05 | Sebagai PIC, saya ingin menanggapi needs-rework agar evidence dapat diperbaiki tanpa menghapus histori. | **Given** verification needs-rework, **When** revisi dikirim, **Then** submission baru menautkan finding lama dan histori keputusan tetap tersedia. | FR-PPE-003/005; BR-PPE-003 |
| US-PIC-06 | Sebagai PIC, saya ingin mencatat data evaluasi dampak agar efektivitas tindakan dapat dinilai. | **Given** action verified dan periode evaluasi tiba, **When** observed result/evidence disimpan, **Then** verifikator/pimpinan dapat menilai terhadap baseline/target. | FR-PPE-007; BR-PPE-004 |

## 7. Verifikator — 6 stories (MVP)

| ID | User story | Acceptance criteria Given/When/Then | Trace |
|---|---|---|---|
| US-VER-01 | Sebagai verifikator, saya ingin menerima task hanya dalam scope agar tidak melihat data unit lain. | **Given** assignment verifikasi, **When** daftar dibuka, **Then** hanya action assigned dan evidence minimum tampil. | FR-PPE-005; BR-ACC-002 |
| US-VER-02 | Sebagai verifikator, saya ingin membandingkan evidence dengan acceptance target agar keputusan berbasis bukti. | **Given** submission PIC, **When** review dibuka, **Then** source finding, target, evidence version, dan checklist tampil read-only. | FR-PPE-003/005; BR-PPE-003 |
| US-VER-03 | Sebagai verifikator, saya ingin menyatakan verified dengan alasan agar completion dapat diaudit. | **Given** evidence memenuhi target dan saya bukan PIC, **When** verified dipilih, **Then** alasan/evidence review wajib dan status tersimpan. | FR-PPE-005–006; BR-PPE-002–003 |
| US-VER-04 | Sebagai verifikator, saya ingin meminta perbaikan atau menolak agar evidence tidak memadai tidak menutup action. | **Given** evidence tidak memadai, **When** needs-rework/rejected dipilih, **Then** alasan wajib, action tidak closed, dan PIC diberi notifikasi. | FR-PPE-005; BR-PPE-003 |
| US-VER-05 | Sebagai verifikator, saya ingin melihat histori due date, submission, dan keputusan agar perubahan tidak menghapus konteks. | **Given** action pernah berubah, **When** timeline dibuka, **Then** semua version/event tampil berurutan dan tidak editable. | FR-PPE-003–005; BR-PPE-005 |
| US-VER-06 | Sebagai verifikator, saya ingin menilai impact evaluation atau waiver agar closure PPEPP sah. | **Given** action verified, **When** closure diperiksa, **Then** impact conclusion atau waiver approved wajib; jika tidak ada, closure ditolak. | FR-PPE-006–007; BR-PPE-004 |

## 8. Story readiness rule

Story baru hanya berstatus `Ready` bila memiliki aktor, outcome, sedikitnya satu FR, sedikitnya satu BR/NFR yang relevan, Given/When/Then yang dapat dieksekusi, data scope, dependency, dan acceptance owner. Target berstatus `PROPOSED` pada NFR tidak dianggap disahkan hanya karena story diterima.

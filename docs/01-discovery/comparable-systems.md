# Sistem dan Praktik Pembanding

Tanggal analisis: **2026-08-06**  
Rujukan lengkap: [source-register.md](source-register.md)

## Cara membaca

Pembanding dibagi menjadi dua jenis:

1. **Program survei**: mendefinisikan populasi, instrumen, administrasi, metodologi, publikasi, dan penggunaan hasil.
2. **Platform perangkat lunak**: menyediakan builder, distribusi, integrasi, analitik, akses, dan fitur tindak lanjut.

Keduanya tidak boleh disamakan. Membeli atau membangun platform tidak otomatis menghasilkan program survei yang valid.

## 1. Program survei pendidikan tinggi

| Pembanding | Praktik yang terbukti pada sumber | Pelajaran untuk SIMUTU | Batas transfer |
|---|---|---|---|
| UK National Student Survey (NSS) | Pengelola/regulator jelas, populasi dan jadwal terdefinisi, kuesioner dipublikasikan, administrasi serta diseminasi diatur. [S17] | Versikan instrumen dan protokol administrasi; simpan population frame, periode, mode, undangan, dan aturan publikasi. | Konteks regulasi UK dan populasi final-year tidak boleh disalin mentah. |
| Australia QILT Student Experience Survey | Indikator terdefinisi, siklus tahunan, cakupan current students, laporan/tabel terbuka, hasil gabungan dan interval kepercayaan untuk reliabilitas. [S18] | Dashboard harus menampilkan denominator, ketidakpastian, periode, segmentasi, dan metodologi; bukan ranking tanpa konteks. | Model nasional Australia bukan formula akreditasi Indonesia. |
| QILT Data Protocols | Ada protokol khusus untuk kewajiban privasi dan perlindungan responden. [S19] | Tata kelola data harus menjadi artefak tersendiri: akses, disclosure, penggunaan, retensi, dan pelepasan data. | Kepatuhan Australia/GDPR tidak menggantikan UU PDP Indonesia. |
| National Survey of Student Engagement (NSSE) | Data dipakai untuk diagnosis dan perbaikan; panduan menyatakan pelaporan hasil saja tidak menghasilkan tindakan. Institusi didorong melibatkan tim, menghubungkan sumber data, menjalankan aksi, lalu menilai dampak. [S20] | Bangun workflow action owner, target, bukti, verifikasi, dan evaluasi periode berikutnya; dukung tim lintas unit. | NSSE mengukur engagement, bukan sinonim kepuasan layanan. |

## 2. Platform perangkat lunak

| Platform | Kapabilitas yang diklaim penyedia | Pola yang relevan | Hal yang tidak boleh diasumsikan |
|---|---|---|---|
| Jisc Online Surveys | Builder, tautan/email/reminder/QR, analitik, filter, ekspor, enkripsi, dan dukungan survei learner/staff/employer. [S26] | Distribusi multikanal, ekspor terkontrol, akses organisasi, keamanan, dan dukungan berbagai stakeholder. | Klaim GDPR/ISO vendor bukan bukti otomatis memenuhi UU PDP atau kebijakan kampus. |
| Qualtrics Course Evaluations | Bank pertanyaan, otomasi LMS, benchmark, serta dashboard berbasis peran dan tindakan. [S27] | Metadata akademik/integrasi, core items plus controlled local items, role-scoped dashboard. | Benchmark vendor tidak otomatis comparable tanpa definisi populasi/instrumen yang sama. |
| Explorance Blue/Student Voice | Otomasi evaluasi mata kuliah, integrasi SIS/LMS, analisis komentar/AI, redaksi PII, dan dashboard berbasis peran. [S28] | Pisahkan pipeline komentar sensitif, redaksi PII, hasil AI, review manusia, dan pelaporan per peran. | Klaim akurasi AI, keamanan, dan dampak adalah klaim komersial sampai diuji. |

## 3. Pola kapabilitas yang layak dibawa ke desain

### A. Tata kelola instrumen

- bank pertanyaan terkontrol dengan pemilik, versi, status draf-review-setujui-pensiun;
- core items institusi dan item lokal yang dibatasi;
- mapping butir ke tujuan, konstruk/metode, standar SPMI, dan instrumen akreditasi berversi;
- pilot, review bahasa/aksesibilitas, validitas, reliabilitas, dan change log.

### B. Administrasi kampanye

- populasi dan sampling frame eksplisit;
- aturan eligibility dan deduplikasi;
- jadwal buka/tutup, kanal undangan, reminder, dan nonresponse;
- mode identifiable, confidential/pseudonymous, atau anonymous yang tidak ambigu;
- preview pemberitahuan privasi dan estimasi waktu pengisian.

### C. Analisis yang bertanggung jawab

- tampilkan jumlah responden, denominator, response rate, missing data, dan metode scoring;
- segmentasi hanya jika ukuran kelompok aman;
- pisahkan skor, distribusi, komentar, tren, benchmark, dan ketidakpastian;
- simpan formula serta versi pipeline analitik;
- gunakan bukti lain untuk triangulasi sebelum keputusan besar. [S15][S18][S20]

### D. Closing the feedback loop

Alur minimum yang direkomendasikan:

`hasil tervalidasi → temuan → prioritas → analisis akar masalah → tindakan → PIC/tenggat → bukti → verifikasi LPMPP → komunikasi kembali → ukur dampak periode berikutnya`

Status “selesai” sebaiknya hanya diberikan setelah bukti tindakan diverifikasi. Status “efektif” memerlukan bukti dampak; keduanya bukan status yang sama. [S02][S20]

### E. Transparansi dan komunikasi

- laporan metodologi bersama hasil;
- ringkasan “Anda menyampaikan – kami menindaklanjuti” tanpa membuka identitas;
- alasan ketika usulan tidak dapat dijalankan;
- hasil dan tindakan dibatasi sesuai peran dan sensitivitas.

## 4. Anti-pattern yang harus dihindari

| Anti-pattern | Risiko | Koreksi |
|---|---|---|
| Menganggap SIMUTU sebagai Google Forms yang diberi dashboard | Tidak ada governance, lineage, evidence chain, atau tindak lanjut | Fokus pada lifecycle dan kontrol, bukan hanya form builder. |
| Satu survei panjang untuk semua stakeholder | Konstruk tidak relevan, fatigue, kualitas jawaban turun | Buat program/instrumen per tujuan dan stakeholder. |
| Satu angka “indeks mutu kampus” | Menutupi populasi, metode, dan unit berbeda | Tampilkan metrik menurut definisi, periode, populasi, dan metode. |
| Menggabungkan SERVQUAL, IPA, CSI, SKM, dan NPS dalam satu formula | Konstruk dan skala berbeda; hasil tidak dapat dipertanggungjawabkan | Pilih metode berdasarkan pertanyaan keputusan; simpan perhitungan terpisah. |
| Memaksa semua responden login untuk “validasi” | Mengurangi anonimitas dan dapat menekan kejujuran | Gunakan secure single-use invitation serta pemisahan token–jawaban bila kebijakan mengizinkan. |
| Menyebut survei anonim sambil menyimpan identitas bersama jawaban | Janji privasi keliru dan risiko identifikasi | Nyatakan mode dengan jujur dan pisahkan data teknis. |
| Mengirim komentar mentah ke AI/provider | Kebocoran PII, bias, transfer/retensi tidak terkendali | Redaksi/minimisasi, kontrak, allowlist use case, audit, dan review manusia. |
| Menutup tindakan saat PIC mengunggah bukti | Aktivitas belum tentu efektif | Pisahkan verifikasi pelaksanaan dan evaluasi dampak. |
| Ranking unit dengan sampel kecil | Salah tafsir dan risiko identifikasi | Minimum cell, interval/ketidakpastian, dan suppression. |
| Menyalin instrumen akreditasi lama | Bukti salah versi dan tidak relevan | Register versi/tanggal/cakupan BAN-PT/LAM. [S03][S05][S06] |

## 5. Kesenjangan terhadap keinginan awal

| Keinginan awal | Praktik yang benar | Kesenjangan/keputusan yang dibutuhkan |
|---|---|---|
| Satu platform terpusat untuk semua survei | Satu platform boleh, tetapi program, populasi, instrumen, metode, privasi, dan akses tetap terpisah | Definisikan survey families dan governance per jenis. |
| Dashboard lengkap dan perbandingan | Perbandingan sah hanya bila konstruk, instrumen, populasi, waktu, dan scoring comparable | Tetapkan comparability rules dan tampilkan keterbatasan. |
| AI untuk ringkasan, sentimen, topik, rekomendasi | AI harus terkontrol, minimized, ditinjau manusia, dapat diaudit, dan tidak menjadi penentu tunggal | Belum ada provider, kontrak, lokasi data, evaluasi bias/akurasi, atau prohibited uses. |
| Mendukung BAN-PT/LAM | Instrumen berubah, LAM berbeda per disiplin, dan bukti bersifat berversi | Diperlukan inventory program studi/LAM dan register instrumen, bukan hard-code satu matriks. |
| Responden melihat status/riwayat | Fitur ini dapat membutuhkan identitas, sedangkan anonimitas membutuhkan pemisahan | Tetapkan mode privasi per kampanye dan apa yang boleh dilacak. |
| Secure invitation tanpa akun | Praktik baik, tetapi token dapat menjadi pengenal | Hash token, batasi masa berlaku/pemakaian, pisahkan dari jawaban, dan jelaskan mode kerahasiaan. |
| Laporan dan tindak lanjut | Tindak lanjut harus mempunyai owner, target, bukti, verifikasi, komunikasi, dan pengukuran dampak | Lifecycle tindakan perlu menjadi domain inti, bukan lampiran laporan. |

## 6. Fakta, rekomendasi, asumsi, konfirmasi

### Fakta

- Program survei matang mempublikasikan atau mendokumentasikan instrumen, populasi, administrasi, metodologi, dan penggunaan hasil. [S15][S17][S18]
- Pelaporan hasil saja tidak menutup feedback loop. [S20]
- Platform pembanding mengarah pada integrasi, akses berbasis peran, dan workflow tindakan; fitur vendor tetap harus diverifikasi. [S26][S27][S28]

### Rekomendasi

- Prioritaskan governance, campaign administration, evidence lineage, dan action verification sebelum analitik AI.
- Pisahkan kebutuhan program survei dari fitur platform dan dari bukti akreditasi.
- Terapkan comparability rules, minimum-cell disclosure, dan metodologi yang terlihat di laporan.

### Asumsi

- Integrasi SIS/LMS/SSO belum diputuskan dan bukan prasyarat discovery.
- Institusi ingin membangun sendiri karena membutuhkan lifecycle PPEPP yang spesifik, bukan sekadar mengganti alat form.

### Perlu dikonfirmasi

- sistem sumber master data dan kualitas datanya;
- kanal undangan yang diizinkan;
- kebutuhan aksesibilitas dan bahasa;
- kebijakan benchmark, publikasi, dan minimum cell;
- siapa yang menetapkan, melaksanakan, memverifikasi, dan mengevaluasi tindakan;
- apakah ada platform eksisting yang harus dimigrasikan atau diintegrasikan.

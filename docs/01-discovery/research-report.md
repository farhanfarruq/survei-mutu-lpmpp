# Laporan Riset Phase 01 — Discovery

Tanggal riset: **2026-08-06**  
Status: **SELESAI DENGAN KONFIRMASI TERBUKA**  
Rujukan: [regulatory-basis.md](regulatory-basis.md), [comparable-systems.md](comparable-systems.md), [source-register.md](source-register.md), dan [stakeholder-interview-guide.md](stakeholder-interview-guide.md).

## 1. Ringkasan hasil

SIMUTU LPMPP layak diposisikan sebagai sistem tata kelola siklus survei dan bukti peningkatan mutu, bukan aplikasi formulir. Nilai utamanya adalah hubungan yang dapat diaudit dari tujuan/standar, instrumen berversi, populasi dan administrasi, kualitas data, analisis, temuan, tindakan, bukti, verifikasi, sampai evaluasi dampak pada periode berikutnya.

Tujuh temuan utama:

1. **Baseline regulasi berubah.** Permendiktisaintek 39/2025 kini menjadi dasar aktif dan mencabut Permendikbudristek 53/2023. [S02]
2. **Instrumen akreditasi juga berubah.** IAPT 4.1 berlaku untuk pengajuan baru sejak 1 Juni 2026; IAPT 4.0 hanya berada pada jalur transisi tertentu. APS harus ditentukan per program studi dan LAM/BAN-PT. [S03][S04][S05][S06][S07]
3. **Survei adalah bukti, bukan PPEPP itu sendiri.** Feedback loop baru tertutup setelah temuan menghasilkan tindakan yang diverifikasi dan dampaknya dinilai. [S02][S20]
4. **Metode tidak dapat dicampur sebagai sinonim.** SERVQUAL, SERVPERF, IPA, CSI, SKM/IKM, dan NPS mengukur konstruk atau menjawab pertanyaan keputusan yang berbeda. [S08][S21][S22][S23][S24][S25]
5. **Kualitas administrasi sama penting dengan skor.** Populasi, frame, instrumen, mode, response rate, missing/nonresponse, weighting, dan keterbatasan harus terdokumentasi. [S15][S17][S18]
6. **Anonimitas bertentangan dengan beberapa keinginan awal bila tidak didesain hati-hati.** Save draft, riwayat, status pengisian, targeted invitation, segmentasi, dan reminder dapat menciptakan linkage ke individu.
7. **AI hanya komponen bantu.** Data harus diminimalkan, use case dibatasi, output ditinjau manusia, dan proses dapat diaudit. [S10][S12][S13][S14]

## 2. Pertanyaan riset dan jawaban

### 2.1 Regulasi penjaminan mutu yang berlaku

**Fakta:** UU 12/2012 menjadi dasar penjaminan mutu pendidikan tinggi. Permendiktisaintek 39/2025 mengatur SN Dikti, standar perguruan tinggi, SPMI/SPME, akreditasi, lembaga akreditasi, dan PD Dikti. Pasal 2 menempatkan penetapan, pelaksanaan, evaluasi, pengendalian, dan peningkatan sebagai rangkaian penjaminan mutu. [S01][S02]

**Makna produk:** platform harus menangkap konteks standar dan keputusan perbaikan, bukan hanya hasil survei. Evidence chain dan versioning lebih mendasar daripada banyak jenis grafik.

### 2.2 Instrumen BAN-PT/LAM yang relevan

**Fakta:** kebijakan BAN-PT 21/2025 menuntut instrumen sesuai misi/konteks, kewenangan BAN-PT/LAM, serta bukti kuantitatif dan kualitatif yang berorientasi luaran/dampak. IAPT 4.1 adalah baseline APT aktif untuk pengajuan baru. APS bergantung disiplin, jenjang, modus, status, lembaga, dan periode. [S03][S04][S05][S06]

**Makna produk:** implementasi sebaiknya mempunyai registry instrument version dan mapping evidence yang dapat diubah melalui governance. Jangan hard-code “butir BAN-PT” sebagai atribut permanen pada pertanyaan survei.

### 2.3 Pelaksanaan survei kepuasan stakeholder

Survei yang dapat dipertanggungjawabkan minimal mencatat:

- tujuan keputusan dan konstruk yang ingin diukur;
- pemilik, reviewer, approver, dan unit pengguna;
- populasi sasaran, sampling frame, unit analisis, eligibility, dan periode;
- naskah instrumen, skala, urutan, branching, bahasa, mode, dan versi;
- pilot/cognitive review serta bukti validitas/reliabilitas yang proporsional;
- undangan, reminder, response rate, missing data, nonresponse, weighting bila ada;
- formula scoring, perubahan data, segmentasi, uncertainty, dan keterbatasan;
- temuan, keputusan, PIC, tenggat, bukti, verifikasi, komunikasi, dan dampak. [S15][S17][S18][S20]

**Rekomendasi:** gunakan census bila populasi kecil dan akses tersedia, tetapi tetap evaluasi nonresponse. Gunakan sampel probabilitas bila inferensi populasi dibutuhkan dan census tidak layak. Convenience response tidak boleh diberi bahasa seolah mewakili populasi tanpa batasan.

### 2.4 PPEPP dan closing the feedback loop

PPEPP adalah siklus pengelolaan standar; survey feedback adalah satu input. Closing the loop berarti institusi menunjukkan:

1. hasil yang cukup sahih untuk dipakai;
2. interpretasi bersama stakeholder yang berwenang;
3. prioritas dan akar masalah;
4. tindakan yang mempunyai owner, target, sumber daya, dan tenggat;
5. bukti tindakan serta verifikasi independen/LPMPP;
6. komunikasi kembali kepada kelompok yang memberi umpan balik;
7. evaluasi apakah indikator membaik pada periode berikutnya;
8. keputusan mempertahankan, mengubah, meningkatkan standar, atau menghentikan tindakan. [S02][S16][S20]

Upload bukti menandai **implemented**, bukan otomatis **effective**.

## 3. Perbandingan metode

| Metode | Pertanyaan yang dijawab | Data inti | Keluaran | Penggunaan yang tepat | Jangan digunakan sebagai |
|---|---|---|---|---|---|
| SERVQUAL | Seberapa besar gap antara harapan dan persepsi kualitas layanan pada dimensi layanan? | Pasangan expectation–perception, multi-item, lima dimensi asli | gap per item/dimensi dan profil kualitas layanan | diagnosis kualitas layanan ketika harapan relevan dan instrumen diadaptasi/diuji | skor kepuasan universal atau bukti tunggal akreditasi [S21] |
| SERVPERF | Bagaimana persepsi kinerja layanan yang dialami? | performance/perception-only items | skor kinerja item/dimensi | pengukuran lebih ringkas ketika fokus pada pengalaman aktual | “SERVQUAL tanpa separuh pertanyaan” tanpa justifikasi konstruk [S22] |
| IPA | Atribut mana berprioritas berdasarkan kepentingan dan kinerja? | dua nilai sepadan per atribut atau estimasi importance yang terdokumentasi | peta kuadran/prioritas | prioritisasi tindakan setelah atribut dan crosshair rule ditetapkan | ukuran kepuasan, kualitas, atau signifikansi statistik [S23] |
| CSI | Berapa indeks agregat kepuasan menurut model/formula tertentu? | satisfaction dan, pada varian tertentu, bobot importance | indeks agregat dan kontribusi atribut | ringkasan tren bila formula, skala, bobot, missing, dan interpretasi stabil | istilah formula tunggal; ACSI asli adalah model konstruk/econometric, bukan otomatis weighted average lokal [S24] |
| SKM/IKM | Bagaimana persepsi masyarakat atas unit pelayanan publik menurut pedoman pemerintah? | sembilan unsur dan tata cara pengolahan yang ditetapkan | nilai indeks/kategori mutu layanan serta rencana tindak lanjut | unit/layanan yang berada dalam ruang lingkup pelayanan publik | metode default untuk pembelajaran, engagement, pegawai, alumni, atau employer survey [S08][S09] |
| NPS | Seberapa besar kecenderungan merekomendasikan/advokasi? | pertanyaan 0–10; promoter 9–10, passive 7–8, detractor 0–6 | `% promoter − % detractor`, rentang -100 sampai +100 | indikator loyalitas/advokasi yang disertai pertanyaan alasan dan metrik lain | skor kualitas layanan, kepuasan detail, atau bukti causal improvement [S25] |

### Aturan metodologi yang direkomendasikan

- Satu kampanye boleh memakai beberapa modul, tetapi setiap modul mempertahankan konstruk, skala, scoring, dan interpretasinya sendiri.
- Jangan merata-ratakan NPS, skor SERVQUAL, indeks SKM, dan CSI menjadi “total mutu”.
- IPA menggunakan pasangan atribut yang setara dan aturan titik potong yang disepakati sebelum melihat hasil; kuadran bukan bukti kausalitas.
- CSI harus menyebut formula eksplisit. Label “ACSI” hanya digunakan bila benar-benar mengikuti model dan metodologi ACSI.
- Adaptasi instrumen memerlukan review konteks, bahasa, pilot, dan pengujian; popularitas metode bukan bukti validitas lokal.
- Perbandingan lintas periode hanya dilakukan bila versi, populasi, mode, scoring, dan kondisi cukup comparable; perubahan harus terlihat pada laporan.

## 4. Privasi dan AI

### Risiko data survei

Komentar bebas sering memuat nama, kejadian, kesehatan, keuangan, konflik, atau tuduhan. Kombinasi program studi kecil, angkatan, jabatan, dan unit dapat mengidentifikasi orang meski nama dihapus. Undangan unik, IP, user agent, timestamps, draft, dan audit log juga dapat menjadi linkage. [S10][S11]

### Pola privasi per kampanye

| Mode | Identitas–jawaban | Cocok untuk | Konsekuensi |
|---|---|---|---|
| Identifiable | terhubung dan diizinkan | kasus layanan yang membutuhkan follow-up individual | akses sangat terbatas; tujuan dan retensi harus jelas |
| Confidential/pseudonymous | linkage dipisah dan hanya fungsi tertentu dapat membuka | targeted survey, reminder, save/resume, deduplikasi | jangan disebut anonim; separation-of-duties wajib |
| Anonymous | tidak ada cara wajar menghubungkan jawaban ke individu | topik sensitif/voice ketika follow-up individual tidak diperlukan | riwayat jawaban, edit personal, reminder berbasis status, dan recovery mungkin tidak tersedia |

### Guardrail AI yang disarankan

- default AI **off** sampai use case, provider, kontrak, lokasi, retensi, dan risiko disetujui;
- data mentah identifiable tidak dikirim ke model umum;
- redaksi PII dan minimisasi dilakukan sebelum inference;
- prompt/model/provider/version/parameter/output/reviewer dicatat;
- output AI ditandai sebagai draf, bukan fakta;
- reviewer dapat menerima, mengubah, menolak, dan memberi alasan;
- uji bahasa Indonesia, bias kelompok, hallucination, konsistensi, dan failure mode;
- larang keputusan disiplin, penilaian individu, atau penutupan tindakan secara otomatis;
- sediakan fallback manual dan kill switch. [S12][S13][S14]

## 5. Kesenjangan antara keinginan awal dan praktik yang benar

| Keinginan awal/baseline | Fakta atau praktik yang benar | Kesenjangan yang harus ditutup |
|---|---|---|
| Mengacu regulasi penjaminan mutu lama | 53/2023 telah dicabut oleh 39/2025 | Perbarui regulatory register dan jangan salin pasal lama. |
| “Instrumen BAN-PT/LAM” sebagai satu kebutuhan | IAPT 4.1 aktif; APS/LAM bervariasi dan berubah | Butuh inventaris PT/prodi/LAM/status/tanggal serta versioned evidence mapping. |
| Semua metode analitik tersedia | Metode mengukur konstruk dan menghasilkan skala berbeda | Phase metodologi harus memilih per use case, bukan menyalakan semua sekaligus. |
| Dashboard pimpinan membandingkan unit | Perbandingan membutuhkan equivalence dan perlindungan kelompok kecil | Definisikan comparable cohort, denominator, uncertainty, minimum cell, dan suppression. |
| Responden melihat status dan riwayat | Anonimitas sejati tidak kompatibel dengan linkage personal | Tetapkan privacy mode per kampanye dan UX yang sesuai. |
| External respondent tanpa akun | Secure link tetap dapat mengidentifikasi dan perlu siklus hidup token | Pisahkan token dan jawaban; hash, expiry, single-use, revocation, rate limit. |
| AI merangkum/sentimen/rekomendasi | AI menambah risiko PDP, bias, transfer, hallucination, dan automation bias | Governance dan evaluasi harus ada sebelum provider integration. |
| Tindak lanjut setelah laporan | Feedback loop memerlukan owner, evidence, verifier, communication, dan outcome remeasurement | Jadikan action lifecycle domain inti dan auditabel. |
| “Anonim” sebagai label umum | Anonymous, confidential, dan identifiable berbeda secara teknis/hukum | Buat privacy mode dan disclosure yang tidak menyesatkan. |
| Kepuasan tinggi berarti mutu tinggi | Satisfaction, service quality, engagement, learning outcomes, dan compliance berbeda | Triangulasi dengan bukti akademik/operasional lain. |

## 6. Rekomendasi discovery untuk fase berikutnya

Rekomendasi ini bukan implementasi dan belum mengaktifkan Phase 02:

1. Tetapkan pemilik produk, data steward, fungsi privasi/hukum, dan komite instrumen.
2. Inventaris program survei berjalan, instrumen, data historis, sistem sumber, dan pain point.
3. Inventaris program studi/LAM/status/tanggal akreditasi dan standar SPMI internal.
4. Pilih 2–3 survey family prioritas; jangan memulai dengan semua stakeholder sekaligus.
5. Tetapkan taxonomy tujuan dan metode: satisfaction, service quality, engagement, learning experience, alumni outcome, employer feedback, public service.
6. Putuskan privacy mode, retention, minimum cell, publication, dan AI prohibited uses.
7. Definisikan feedback-loop SLA serta RACI temuan–tindakan–verifikasi–dampak.
8. Lakukan wawancara menggunakan panduan Phase 01 sebelum membekukan scope.

## 7. Fakta, rekomendasi, asumsi, dan konfirmasi

### Fakta

- Dasar regulasi dan instrumen APT telah berubah dari baseline lama. [S02][S04][S05]
- Metode yang diteliti tidak ekuivalen. [S08][S21][S22][S23][S24][S25]
- Program survei pembanding menekankan protokol, transparansi metodologi, dan penggunaan hasil. [S15][S17][S18][S20]
- Penggunaan AI dan data identifiable memerlukan tata kelola privasi, keamanan, risiko, dan human oversight. [S10][S12][S13][S14]

### Rekomendasi

- Jadikan lifecycle governance dan feedback loop sebagai pembeda utama produk.
- Mulai dari use case prioritas dan minimum evidence chain, bukan semua metode/fitur.
- Pisahkan scoring engine per metode serta wajibkan metadata metodologi.
- Tunda aktivasi AI sampai guardrail, data agreement, dan evaluasi tersedia.

### Asumsi

- Institusi adalah perguruan tinggi Indonesia dan LPMPP mempunyai mandat koordinasi mutu.
- Sistem masih pada tahap greenfield sehingga belum ada data nyata atau alur lama yang diverifikasi.
- Bahasa utama responden adalah Bahasa Indonesia dan akses via web layak, tetapi kebutuhan aksesibilitas belum diketahui.

### Perlu dikonfirmasi

- profil institusi dan daftar program studi/LAM;
- regulasi internal, standar SPMI, dan pola PPEPP aktual;
- stakeholder, volume, frekuensi, response rate, serta instrumen historis;
- definisi sukses dan keputusan yang harus didukung tiap survei;
- mode privasi, dasar pemrosesan, retensi, minimum cell, dan publikasi;
- provider/infrastruktur AI, data residency, anggaran, serta prohibited uses;
- integrasi SIAKAD/SSO/LMS/email/WhatsApp dan otoritas tiap sistem;
- siapa yang menyetujui instrumen, findings, action plans, bukti, dan efektivitas.

## 8. Definition of Done Phase 01

| Kriteria | Status | Bukti |
|---|---|---|
| Regulasi aktif diidentifikasi | PASS | [regulatory-basis.md](regulatory-basis.md) dan S01–S12 |
| BAN-PT/LAM relevan dan berversi | PASS WITH CONFIRMATION | IAPT 4.1 terverifikasi; mapping APS menunggu inventaris program studi/LAM |
| Metode tidak dicampur keliru | PASS | Bagian 3 dan S21–S25 |
| Praktik survei/platform pembanding tersedia | PASS | [comparable-systems.md](comparable-systems.md) |
| Closing feedback loop dijelaskan | PASS | Bagian 2.4 dan comparable systems |
| Privasi/AI dianalisis | PASS | Bagian 4 dan regulatory basis |
| Panduan wawancara siap | PASS | [stakeholder-interview-guide.md](stakeholder-interview-guide.md) |
| Sumber tercatat lengkap | PASS | [source-register.md](source-register.md) |

**Status Phase 01: COMPLETE WITH OPEN CONFIRMATIONS.** Konfirmasi terbuka adalah input discovery untuk pemilik institusi, bukan blocker penulisan laporan. Phase 02 belum dimulai.

# Kerangka Metodologi Survei

Versi dokumen: **1.0 — 2026-08-06**  
Status: **baseline metodologi Phase 03**  
Rujukan discovery: [research-report.md](../01-discovery/research-report.md) dan [source-register.md](../01-discovery/source-register.md)

## 1. Prinsip dasar

1. Mulai dari keputusan yang akan didukung, bukan dari metode yang populer.
2. Satu skor hanya boleh menggabungkan item yang mempunyai konstruk, arah skala, populasi, periode, dan scoring rule yang sama.
3. Survei adalah salah satu bukti PPEPP; hasil survei tidak otomatis membuktikan mutu, sebab, atau efektivitas tindakan.
4. Instrumen yang sudah diterbitkan bersifat immutable. Perubahan item, skala, formula, atau urutan yang material membuat versi baru.
5. Analisis selalu menyertakan denominator, jumlah valid, response rate/coverage, missing data, versi instrumen, dan batas interpretasi.
6. Anonymous, confidential/pseudonymous, dan identifiable adalah mode yang berbeda dan harus ditetapkan sebelum kampanye.
7. Threshold metodologi, threshold pelaporan, target kinerja, dan batas akreditasi adalah empat hal berbeda.

## 2. Taksonomi keluarga survei

Taksonomi memakai dua sumbu: **objek/tujuan** dan **kelompok stakeholder**. Stakeholder bukan metode.

| Kode keluarga | Keluarga | Objek keputusan utama | Stakeholder lazim | Metode yang mungkin | Tidak otomatis berarti |
|---|---|---|---|---|---|
| F01 | Pengalaman pembelajaran/mata kuliah | desain pembelajaran, asesmen, dukungan belajar | mahasiswa | internal performance, SERVPERF adaptif, open text | evaluasi kinerja personal dosen |
| F02 | Layanan akademik | informasi, registrasi, jadwal, administrasi, penyelesaian masalah | mahasiswa, dosen | SERVPERF, IPA, CSI internal | SKM kecuali ruang pelayanan publik terkonfirmasi |
| F03 | Layanan kemahasiswaan/nonakademik | kesehatan, konseling, karier, beasiswa, organisasi | mahasiswa | SERVPERF, IPA, transactional CSAT | diagnosis klinis atau profiling individu |
| F04 | Pengalaman digital dan sarana | keandalan, kemudahan, aksesibilitas, dukungan | mahasiswa, dosen, tenaga kependidikan | SERVPERF, IPA, usability internal | audit keamanan teknis |
| F05 | Dosen dan tenaga kependidikan | engagement, proses kerja, layanan SDM, pengembangan | pegawai | metode internal, IPA, eNPS bila disetujui | survei mahasiswa atau NPS pelanggan |
| F06 | Alumni/tracer | transisi kerja, relevansi kompetensi, outcome | alumni | tracer/outcome internal, NPS opsional | kepuasan mahasiswa aktif |
| F07 | Pengguna lulusan | kesiapan, kompetensi, kinerja lulusan | pemberi kerja | performance index, importance-performance | rating individu yang dipakai otomatis |
| F08 | Mitra dan kerja sama | kualitas kolaborasi, manfaat, keberlanjutan | mitra | SERVPERF adaptif, IPA, NPS opsional | bukti dampak kerja sama tanpa triangulasi |
| F09 | Pelayanan publik/SKM | kualitas unit layanan publik | penerima layanan/masyarakat | SKM/IKM sesuai PermenPANRB 14/2017 | metode default untuk semua survei kampus |
| F10 | Pulse/transaction/event | pengalaman pada kejadian tertentu | peserta layanan/acara | CSAT internal, effort, open text | tren institusi jangka panjang |
| F11 | Kebutuhan dan prioritas | kebutuhan yang belum terpenuhi, alokasi perbaikan | stakeholder terkait | importance, ranking terbatas, open text | satisfaction/performance |
| F12 | Audit/evaluasi internal | keterlaksanaan proses dan bukti | auditor/PIC | checklist compliance dan evidence rating | persepsi stakeholder |

Satu kampanye dapat memiliki beberapa modul, tetapi setiap modul mempertahankan identitas metode dan scoring rule-nya.

## 3. Pemilihan metode

| Pertanyaan keputusan | Metode utama | Syarat | Keluaran utama |
|---|---|---|---|
| “Bagaimana kinerja layanan yang dialami?” | SERVPERF atau performance index internal | item merepresentasikan dimensi/kategori yang relevan | mean/distribusi kinerja, skor kategori |
| “Apakah pengalaman memenuhi harapan?” | SERVQUAL | expectation dan perception diukur untuk objek, skala, dan pasangan responden yang sepadan | gap `P − E` per item/kategori |
| “Atribut mana harus diprioritaskan?” | IPA | importance dan performance keduanya diukur pada atribut yang sama | koordinat dan kuadran prioritas |
| “Berapa indeks kepuasan berbobot?” | CSI internal | definisi bobot dan formula dikunci; bukan ACSI kecuali model ACSI benar-benar digunakan | indeks 0–100 |
| “Bagaimana mutu unit pelayanan publik?” | SKM/IKM | unit dan kampanye berada dalam ruang lingkup aturan; sembilan unsur serta rumus resmi | nilai 25–100 dan kategori A–D |
| “Seberapa besar kecenderungan merekomendasikan?” | NPS | pertanyaan rekomendasi 0–10 dan konteks objek jelas | NPS -100 sampai +100 |
| “Apakah proses/bukti terpenuhi?” | metode internal compliance | kriteria normatif dan bukti terdefinisi | proporsi pemenuhan, temuan; bukan skor kepuasan |

### Aturan kombinasi

- SERVPERF + IPA boleh bila item performance mempunyai pasangan importance identik.
- SERVQUAL + IPA hanya boleh bila performance/perception, expectation, dan importance dipisahkan; ketiga skor tidak dirata-ratakan menjadi satu angka.
- CSI internal boleh memakai importance sebagai bobot dan performance/satisfaction sebagai nilai, tetapi hasil tetap terpisah dari kuadran IPA.
- SKM memakai unsur dan formula resminya; jangan menormalisasikannya ulang ke formula internal.
- NPS berdiri sendiri dan tidak menjadi bobot atau pengganti skor layanan.

## 4. Model artefak metodologi

### 4.1 Template

| Field | Isi wajib |
|---|---|
| `template_code` | kode stabil, misalnya `F02-AKADEMIK-MHS` |
| nama dan keluarga | judul serta satu primary family |
| tujuan keputusan | keputusan yang akan didukung dan yang tidak didukung |
| pemilik/reviewer/approver | fungsi, bukan nama pribadi saja |
| stakeholder dan eligibility | siapa yang boleh menjawab dan pengalaman apa yang disyaratkan |
| construct map | kategori, indikator, dan hubungan dengan tujuan/standar |
| metode/modul | SERVPERF, IPA, CSI internal, dan seterusnya |
| privacy mode | strict anonymous, anonymous-content, confidential, atau identifiable |
| reporting policy | threshold, small-cell, publication, dan akses |
| validation status | draft, expert-reviewed, piloted, provisional, validated-for-use |

### 4.2 Versi template

| Field | Aturan |
|---|---|
| `version` | semantic document version, misalnya `1.0.0` |
| `effective_from/to` | rentang penggunaan |
| `change_reason` | alasan dan dampak comparability |
| `source_basis` | sumber/regulasi/hasil review |
| `language` | versi bahasa dan proses adaptasi |
| `scoring_snapshot` | formula, precision, missing rule, threshold, dan interpretation bands |
| `compatibility` | comparable penuh, comparable dengan catatan, atau tidak comparable dengan versi sebelumnya |
| status | draft → review → approved → published → retired |

Setelah `published`, versi tidak diedit. Koreksi dilakukan dengan versi baru dan catatan koreksi.

### 4.3 Hierarki isi

`template → version → section → category/construct → indicator → question → response scale → scoring rule`

| Entitas | Fungsi | Aturan minimum |
|---|---|---|
| Section | mengatur alur dan konteks | petunjuk, eligibility/branching, urutan |
| Category/construct | kelompok konseptual | definisi, sumber, apakah reflektif/formative/deskriptif |
| Indicator | aspek terukur | satu objek dan unit analisis |
| Question/item | stimulus yang dijawab | kode unik dalam versi, teks, recall period, wajib/opsional |
| Scale | pilihan jawaban | label lengkap, nilai, arah, N/A/prefer not to answer |
| Scoring rule | transformasi jawaban | eligibility, reverse coding, denominator, normalisasi, rounding |

## 5. Skala jawaban standar

| Kode skala | Konstruk | Pilihan berlabel | Catatan |
|---|---|---|---|
| PERF-5 | kinerja/pengalaman | 1 Sangat buruk; 2 Buruk; 3 Cukup; 4 Baik; 5 Sangat baik | jangan mengganti label antar-item |
| AGREE-5 | persetujuan | 1 Sangat tidak setuju sampai 5 Sangat setuju | hanya bila item berupa pernyataan sikap/persepsi |
| IMPORT-5 | kepentingan | 1 Sangat tidak penting sampai 5 Sangat penting | pasangan IPA |
| EXPECT-5 | harapan | 1 Sangat rendah sampai 5 Sangat tinggi | pasangan SERVQUAL; petunjuk waktu harus jelas |
| NPS-11 | rekomendasi | 0 Sangat tidak mungkin sampai 10 Sangat mungkin | konteks orang/objek rekomendasi harus disebut |
| SKM-4 | persepsi SKM | 1–4 dengan label spesifik unsur | label mengikuti instrumen/pedoman yang disahkan |
| BIN-3 | fakta/pemenuhan | Ya; Tidak; Tidak tahu/tidak berlaku | “Tidak tahu” tidak disamakan dengan Tidak |
| TEXT | uraian | teks terbuka | opsional kecuali ada alasan kuat; screening PII |

Gunakan “Tidak berlaku/tidak mengalami” di luar skala numerik dan keluarkan dari denominator. “Netral” hanya disediakan jika secara substantif sah, bukan sekadar untuk membuat jumlah pilihan ganjil.

## 6. Workflow pengembangan instrumen

1. Tetapkan keputusan, populasi, pengalaman, periode, dan pemilik.
2. Pilih keluarga dan metode menggunakan tabel seleksi.
3. Buat construct map dan blueprint jumlah item.
4. Tulis item dan scale labels sesuai [question-writing-guide.md](question-writing-guide.md).
5. Lakukan expert review dan content validity.
6. Lakukan cognitive interview/usability test dengan calon responden.
7. Jalankan pilot operasional dan analisis item/reliabilitas yang sesuai.
8. Jalankan factor analysis hanya bila instrumen mengukur latent construct dan ukuran/kualitas data memadai.
9. Bekukan version, scoring snapshot, privacy mode, reporting threshold, dan analysis plan.
10. Terbitkan kampanye; analisis sesuai rencana; catat deviasi.
11. Review setelah kampanye dan putuskan mempertahankan, merevisi, atau menghentikan versi.

## 7. Keterbandingan lintas periode/unit

Perbandingan dinyatakan:

- **Comparable penuh**: item, skala, scoring, populasi, mode, periode pengalaman, dan threshold sama.
- **Comparable dengan catatan**: perubahan minor tidak mengubah konstruk, tetapi mode/populasi berbeda dan sensitivity analysis tersedia.
- **Tidak comparable**: perubahan item/skala/formula/populasi material atau tidak ada bukti measurement invariance saat diperlukan.

Jangan membuat ranking jika denominator kecil, coverage sangat berbeda, atau komposisi responden tidak sebanding. Detail gate ada di [reporting-threshold-and-anonymity.md](reporting-threshold-and-anonymity.md).

## 8. Artefak wajib per kampanye

- template dan version snapshot;
- tujuan keputusan dan analysis plan;
- population frame, eligibility, census/sampling design;
- instrument rendering final;
- privacy notice/mode dan data handling plan;
- disposition log dan response metrics;
- scoring output dengan formula/version;
- quality flags, missing/nonresponse assessment;
- laporan, keterbatasan, temuan, action owner, dan bukti tindak lanjut.

## 9. Keputusan yang masih memerlukan konfirmasi institusi

- survey family prioritas dan pemiliknya;
- skala bahasa lokal yang telah digunakan secara historis;
- standar internal, target, dan kebutuhan BAN-PT/LAM per program;
- minimum cell yang disetujui fungsi PDP/LPMPP;
- kapan mode anonymous-content boleh memakai detached response-status tracking;
- siapa yang dapat mengesahkan metode internal dan perubahan scoring;
- apakah data cukup untuk pengujian psikometrik dan perbandingan lintas kelompok.

# Minimum Pelaporan, Anonimitas, dan Kerahasiaan

Versi dokumen: **1.0 — 2026-08-06**  
Status: **baseline kebijakan Phase 03; memerlukan pengesahan institusi dan telaah DPO/fungsi hukum**

## 1. Prinsip

Anonim, rahasia, dan agregat adalah hal berbeda. Klaim privasi harus sesuai arsitektur nyata, metadata, log, token, akses, ekspor, dan praktik pelaporan. Threshold mengurangi risiko identifikasi dan ketidakstabilan statistik, tetapi tidak membuat data sensitif otomatis aman.

## 2. Mode identitas

| Mode | Hubungan jawaban–identitas | Kegunaan | Aturan minimum |
|---|---|---|---|
| Strict anonymous | sistem tidak mengumpulkan identifier/link key | survei umum tanpa follow-up individual | tanpa login terhubung, IP/user-agent tidak disimpan dalam dataset, deduplikasi nonidentifying bila mungkin |
| Anonymous content + detached participation tracking | daftar undangan hanya tahu sudah/belum; isi jawaban tidak memiliki join key | reminder dan pencegahan respons ganda | token partisipasi di-hash, store terpisah, tidak ada kunci penggabung, akses terpisah, retention pendek |
| Confidential pseudonymous | isi memiliki pseudonym/link key terbatas | longitudinal atau follow-up yang benar-benar diperlukan | consent jelas, RBAC, key vault terpisah, audit log, retention dan tujuan dibatasi |
| Identifiable | identitas melekat pada isi | kasus layanan individual atas persetujuan yang tepat | bukan default survei mutu; dasar pemrosesan, akses, dan konsekuensi harus eksplisit |

Jangan memakai kata “anonim” bila administrator dapat menggabungkan token, timestamp, atribut sempit, atau log untuk mengetahui respondent. Bila kemampuan re-identification masih ada, sebut **rahasia/confidential**.

## 3. Data minimization dan pemisahan

- Jangan mengumpulkan nama, NIM, email, nomor telepon, IP, precise timestamp, free-form location, atau user-agent pada dataset isi kecuali tujuan yang sah membutuhkannya.
- Bulatkan atau bucket waktu sebelum analisis bila timestamp dibutuhkan secara operasional.
- Metadata undangan, partisipasi, jawaban, dan laporan disimpan sebagai domain akses terpisah.
- Pada mode detached, receipt/token hanya membuktikan submitted dan tidak berisi response ID. Tidak ada endpoint/admin export yang dapat menggabungkannya.
- Open text diberi peringatan agar tidak memasukkan identitas dan melewati redaction review sebelum digunakan.
- Atribut kelompok hanya dikumpulkan bila mempunyai keputusan yang jelas dan lolos penilaian risiko kombinasi.
- Retention, legal basis, hak respondent, incident handling, dan penggunaan pihak ketiga mengikuti kebijakan perlindungan data institusi serta UU PDP yang berlaku.

## 4. Baseline minimum pelaporan

Threshold berikut adalah baseline konservatif tata kelola, **bukan angka universal tentang statistical power**:

| Keluaran | Minimum `n` sah per cell | Aturan tambahan |
|---|---:|---|
| skor agregat tertutup internal | 10 | `n=10–29` diberi label “sampel kecil; deskriptif” |
| interpretasi stabil/tren atau perbandingan subgroup | 30 pada setiap cell | review coverage/nonresponse; jangan ranking otomatis |
| atribut berisiko tinggi atau kombinasi sempit | 20 | privacy review; dapat dinaikkan berdasarkan risiko |
| publik/eksternal | 20 | hanya agregat yang disetujui dan tidak dapat didiferensiasi |
| tema open text/AI-assisted coding | 20 komentar sah | redaction dan human review wajib; tidak menampilkan attribution |
| kutipan verbatim | 20 komentar sah pada pool | manual de-identification; persetujuan penggunaan; paraphrase lebih disukai |

Jika `n<10`, skor cell ditampilkan sebagai `Disuppressed—n di bawah threshold`, bukan nol, kosong tanpa penjelasan, atau digabung sembarang. Cell dapat tetap disuppress walau melewati minimum bila kombinasi atribut, free text, rare response, atau dominasi respondent menimbulkan risiko.

Untuk total eligible population di bawah 30, ringkasan internal boleh ditampilkan mulai `n≥10` dan coverage `≥60%`, dengan label **small-population descriptive only**, tanpa inferensi atau ranking. Pengecualian dicatat dan disetujui pemilik privasi/metodologi.

## 5. Complementary suppression dan anti-differencing

- Bila total dan semua kecuali satu subgroup ditampilkan, suppress cell tambahan agar cell kecil tidak dapat dihitung lewat pengurangan.
- Jangan menerbitkan cross-tab berlapis yang memungkinkan identifikasi melalui kombinasi fakultas, program, tahun, demografi, dan timestamp.
- Nilai berbobot disuppress bila satu respondent menyumbang lebih dari 20% bobot cell; telaah bobot, jangan hanya menyembunyikan warning.
- Numerator kejadian sensitif di bawah 5 tidak ditampilkan sebagai count/persentase spesifik; gunakan kategori aman dan rujuk kanal penanganan.
- Dashboard dan ekspor menerapkan policy yang sama. Drill-down tidak boleh melewati threshold hanya karena user mempunyai akses dashboard.

## 6. Coverage, response rate, dan ketidakpastian

Setiap tabel/grafik menyertakan:

- target population dan frame;
- jumlah invited/eligible bila diketahui;
- completion/participation definition dan rate;
- `n` sah per item/cell;
- missing dan tidak relevan;
- mode pembobotan;
- periode, versi instrumen, dan scoring rule;
- interval ketidakpastian bila desain sampling mendukung;
- catatan coverage/nonresponse yang material.

Threshold privasi tidak membuktikan representativeness. Sebaliknya, response rate tinggi tidak menghapus risiko re-identification. Untuk census invitation yang voluntary, hasil tetap merupakan respons yang diperoleh, bukan otomatis estimasi bebas bias bagi seluruh population.

## 7. Aturan komentar terbuka dan AI

1. Hapus/masking nama, NIM, kontak, lokasi rinci, tuduhan yang mengidentifikasi, dan identifier lain sebelum analisis sekunder.
2. Akses raw text dibatasi pada tim yang ditunjuk; kutipan ke pimpinan/publik harus de-identified.
3. Model AI tidak menerima identifier/link key dan hanya digunakan pada tujuan serta penyedia yang telah disetujui melalui DPIA/penilaian risiko.
4. Jangan memakai data survei untuk training provider atau model umum tanpa dasar, persetujuan, kontrak, dan informasi kepada respondent.
5. AI boleh membantu usulan coding/tema; manusia memverifikasi kode, tema, sentimen, kutipan, dan rekomendasi. Jangan membuat keputusan yang berdampak pada individu hanya dari output AI.
6. Catat model/provider/version, prompt template, data fields, tanggal, reviewer, correction, dan status persetujuan.
7. Untuk pool di bawah threshold, AI processing tidak menjadi jalan pintas untuk membuka komentar; lakukan ringkasan manual terbatas atau tunda agregasi.

## 8. Akses dan pelepasan hasil

| Peran | Akses wajar |
|---|---|
| survey operator | status pengiriman/partisipasi, tanpa isi bila mode detached |
| analyst terbatas | dataset de-identified/pseudonymous sesuai kebutuhan |
| unit owner | agregat yang lolos threshold dan komentar yang telah direview |
| pimpinan | dashboard agregat, coverage, risiko, dan action plan |
| publik | hasil terkurasi yang lolos public threshold dan approval |

Ekspor raw dibatasi, dienkripsi, diberi masa berlaku, dan diaudit. Screenshot, file lokal, spreadsheet, dan BI cache termasuk salinan data yang harus tunduk pada aturan akses/retention.

## 9. Contoh keputusan pelaporan

| Kasus | Keputusan |
|---|---|
| Program A `n=8` | suppress skor dan distribusi; gabungkan hanya jika kategori gabungan bermakna dan direncanakan |
| Fakultas B `n=24` | boleh ringkasan internal; label sampel kecil; tanpa ranking/tren inferensial |
| Jenjang C `n=46`, coverage seimbang | boleh dibandingkan bila cell pembanding juga ≥30 dan analysis plan mendukung |
| kelompok atribut sensitif `n=21` | privacy review tetap diperlukan; minimum bukan auto-release |
| 18 komentar | jangan keluarkan kutipan/tema AI per unit; agregasikan ke pool aman atau ringkas tanpa atribusi setelah review |
| total 40 dan satu subgroup kecil dapat dihitung dari tabel | complementary suppression pada cell lain |

## 10. Governance exception

Pengecualian tidak boleh dibuat otomatis oleh unit peminta laporan. Dokumen exception memuat tujuan, cell/field, alasan tidak dapat memakai agregat aman, risiko, mitigasi, penerima, retention, keputusan DPO/fungsi privasi dan metodologi, serta expiry. Approval satu kampanye tidak berlaku permanen.

## 11. Hal yang harus dikonfirmasi institusi

- fungsi DPO/pejabat privasi dan jalur incident response;
- klasifikasi data, retention, dan dasar pemrosesan per keluarga survei;
- threshold yang lebih tinggi untuk unit kecil atau atribut sensitif;
- apakah mode detached dapat dijamin secara teknis dan organisatoris;
- penyedia AI yang diizinkan, lokasi pemrosesan, kontrak, dan larangan training;
- daftar penerima raw/de-identified/agregat dan proses exception.

## 12. Rujukan

- Republik Indonesia, UU 27/2022 tentang Pelindungan Data Pribadi: <https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022>.
- AAPOR, *Standard Definitions, 10th edition*: <https://aapor.org/wp-content/uploads/2024/03/Standards-Definitions-10th-edition.pdf>.
- OECD, *Recommendation of the Council on Artificial Intelligence*: <https://legalinstruments.oecd.org/en/instruments/OECD-LEGAL-0449>.
- UNESCO, *Guidance for Generative AI in Education and Research*: <https://unesdoc.unesco.org/ark:/48223/pf0000386693>.

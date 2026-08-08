# Katalog Scoring Survei

Versi dokumen: **1.0 — 2026-08-06**  
Status: **baseline metodologi Phase 03**

## 1. Konvensi umum

- Arah skor dibakukan: nilai lebih tinggi berarti penilaian lebih baik, lebih penting, atau lebih setuju sesuai konstruk.
- Skor mentah selalu disimpan dan dilaporkan bersama skor turunan; normalisasi tidak menggantikan data asal.
- Untuk skala berurutan `L..U`, normalisasi internal ke 0–100 adalah:

  `N(x) = 100 × (x - L) / (U - L)`

  Pada skala 1–5: 1→0, 2→25, 3→50, 4→75, 5→100. Rumus `x/U × 100` tidak dipakai karena membuat pilihan minimum bernilai 20.
- Perhitungan memakai presisi penuh. Klasifikasi internal memakai nilai **sebelum pembulatan**. Tampilan memakai decimal half-up: mean/gap 2 desimal, persentase dan NPS 1 desimal, indeks 0–100 dan SKM 2 desimal. Pengecualian SKM pada §6 mengikuti nilai konversi dua desimal pada band resmi.
- `Tidak relevan/tidak menggunakan`, penolakan menjawab, dan jawaban kosong bukan nilai netral atau nol; semuanya dikeluarkan dari penyebut dan jumlahnya dilaporkan terpisah.
- Skor kategori responden dihitung bila sekurang-kurangnya `ceil(0,80 × jumlah item kategori)` memiliki jawaban sah. Imputasi tidak dilakukan secara default.
- Setiap keluaran harus menyertakan versi instrumen, versi scoring rule, `n` sah, jumlah missing, dan jumlah tidak relevan.

## 2. SERVPERF

Digunakan untuk menilai **kinerja/persepsi pengalaman aktual** tanpa mengukur ekspektasi.

`Mean performance = ΣPᵢ / n`

`SERVPERF-100 = 100 × (Mean performance - L) / (U - L)`

Contoh skala 1–5: jawaban `[4, 3, 5, 4]` memberi mean `4,00` dan skor ternormalisasi `75,00`.

Interpretasi utama adalah per item dan kategori. Agregat keseluruhan hanya dibuat bila blueprint menyatakan item memang merupakan satu domain ringkasan yang dapat ditafsirkan.

## 3. SERVQUAL

Digunakan hanya bila instrumen benar-benar mengukur pasangan **ekspektasi (E)** dan **persepsi/kinerja (P)** untuk indikator, objek, populasi, dan periode yang sama.

`Gapᵢ = Pᵢ - Eᵢ`

`Mean gap = Σ(Pᵢ - Eᵢ) / n pasangan sah`

`Normalized gap = 100 × Mean gap / (U - L)`

Pada skala 1–5, rentang gap adalah -4 sampai +4 dan normalized gap -100 sampai +100:

- negatif: pengalaman di bawah ekspektasi;
- nol: pengalaman memenuhi ekspektasi;
- positif: pengalaman melampaui ekspektasi.

Contoh: `E=[5,4,4,5]`, `P=[4,4,3,5]`. Mean E `4,50`, mean P `4,00`, mean gap `-0,50`, normalized gap `-12,50`.

Gap dihitung pada pasangan responden-item yang lengkap. Mean kelompok E dari responden berbeda tidak boleh dikurangi dari mean kelompok P dan disebut SERVQUAL berpasangan.

## 4. Importance–Performance Analysis (IPA)

IPA memerlukan dua rating terpisah untuk setiap indikator yang sama:

- `Iᵢ`: mean kepentingan;
- `Pᵢ`: mean kinerja/persepsi.

Koordinat indikator adalah `(Iᵢ, Pᵢ)`. Garis silang harus ditetapkan dalam analysis plan sebelum hasil dibuka. Baseline instrumen skala 1–5 memakai garis tetap `I=4,00` dan `P=4,00`:

| Kondisi | Kuadran | Arti keputusan |
|---|---|---|
| I ≥ 4 dan P < 4 | Concentrate here | prioritas perbaikan |
| I ≥ 4 dan P ≥ 4 | Keep up the good work | pertahankan |
| I < 4 dan P < 4 | Low priority | pantau, bukan prioritas pertama |
| I < 4 dan P ≥ 4 | Possible overinvestment | telaah alokasi, bukan otomatis dikurangi |

Contoh: I `4,60`, P `3,40` berada pada *concentrate here*. Garis silang berdasarkan grand mean sampel boleh ditampilkan sebagai analisis eksploratif, tetapi harus diberi label karena kuadran dapat berubah hanya akibat komposisi sampel.

## 5. Customer Satisfaction Index internal

CSI berikut adalah **indeks internal berbobot kepentingan**, bukan American Customer Satisfaction Index (ACSI) dan bukan standar hukum:

`wᵢ = Iᵢ / ΣIᵢ`

`CSI = Σ[wᵢ × N(Pᵢ)]`

Semua mean kepentingan dan kinerja harus berasal dari basis indikator dan aturan kelengkapan yang sama. Contoh: I `[5,4]`, P `[3,4]`; bobot `[5/9,4/9]`, kinerja normal `[50,75]`, sehingga CSI `61,11`.

CSI tidak menggantikan tabel item, distribusi, atau IPA. Nama keluaran wajib `CSI internal` agar tidak disalahartikan sebagai ACSI.

## 6. SKM/IKM PermenPANRB 14/2017

Untuk sembilan unsur resmi, Nilai Rata-Rata (NRR) unsur dihitung dari jawaban sah. Dengan bobot sama persis:

`Bobot unsur = 1/9`

`NRR tertimbang = Σ(NRR unsur × 1/9)`

`Nilai SKM konversi = NRR tertimbang × 25`

Angka `0,11` dalam contoh pedoman adalah tampilan pembulatan; implementasi perhitungan harus memakai `1/9` agar jumlah bobot tepat satu.
Nilai konversi kemudian dibulatkan decimal half-up ke dua desimal dan baru dipetakan ke band resmi berikut; aturan khusus ini menghindari celah antara batas yang dinyatakan dengan dua desimal.

| Nilai konversi | Mutu | Kinerja unit pelayanan |
|---:|:---:|---|
| 25,00–64,99 | D | Tidak baik |
| 65,00–76,60 | C | Kurang baik |
| 76,61–88,30 | B | Baik |
| 88,31–100,00 | A | Sangat baik |

Contoh NRR sembilan unsur `[3,2; 3,1; 3,0; 3,4; 3,2; 3,3; 3,1; 3,0; 3,2]` menghasilkan NRR tertimbang `3,166666…`, nilai konversi `79,17`, mutu `B`, kinerja `Baik`.

Penambahan pertanyaan diagnostik diperbolehkan sebagai modul terpisah. Mengubah, mengurangi, atau mencampur sembilan unsur ke satu indeks membuat hasil tidak boleh dilabel sebagai SKM yang comparable tanpa dasar kewenangan dan aturan baru.

## 7. Net Promoter Score (NPS)

Untuk pertanyaan rekomendasi 0–10:

- promoter: 9–10;
- passive: 7–8;
- detractor: 0–6.

`NPS = % promoter - % detractor`

Rentang NPS adalah -100 sampai +100; NPS bukan persentase kepuasan dan tidak dinormalisasi lagi. Contoh 6 promoter, 2 passive, dan 2 detractor: `NPS = 60% - 20% = 40,0`.

NPS bersifat opsional dan tidak menggantikan indikator diagnostik. Missing dikeluarkan dari penyebut dan `n` sah selalu ditampilkan.

## 8. Metode internal sederhana

| Metode | Rumus | Kegunaan | Batas interpretasi |
|---|---|---|---|
| Mean performance | `Σx/n` | ringkasan item/kategori | tampilkan skala asal |
| Indeks 0–100 | `N(mean)` | dashboard lintas skala yang kompatibel | bukan standar hukum |
| Top-two-box | `% jawaban 4 atau 5` | proporsi pengalaman positif | sertakan seluruh distribusi |
| Bottom-two-box | `% jawaban 1 atau 2` | sinyal masalah | bukan ukuran besarnya masalah |
| CSAT transaksional | `% kategori positif` atau mean satu item | layanan/kejadian baru dialami | formula dan periode harus dipin |
| Compliance | `ya / kasus eligible × 100%` | keterlaksanaan persyaratan | definisi eligible wajib eksplisit |

Baseline band internal 0–100, bila target atau benchmark institusi belum disahkan:

| Rentang | Label internal |
|---:|---|
| 0–<20 | Sangat rendah |
| 20–<40 | Rendah |
| 40–<60 | Sedang |
| 60–<80 | Tinggi |
| 80–100 | Sangat tinggi |

Band ini hanya konvensi tata kelola. Target layanan, control limit historis, atau benchmark yang disahkan lebih kuat dan harus menggantikan band generik pada versi berikutnya.

## 9. Aturan missing, threshold, dan pembobotan

1. Tampilkan `n` sah per item; jangan menyembunyikan denominator yang berubah.
2. Untuk pasangan IPA/SERVQUAL, hanya pasangan lengkap per indikator yang masuk analisis pasangan.
3. Jangan melakukan mean substitution. Multiple imputation hanya boleh melalui analysis plan khusus dan tidak dipakai pada pelaporan rutin baseline.
4. Bobot sampling/nonresponse hanya dipakai bila probability frame, probabilitas seleksi, dan variabel kalibrasi dapat dipertanggungjawabkan. Laporan menampilkan hasil berbobot dan tidak berbobot serta metode bobot.
5. Minimum pelaporan dan small-cell suppression mengikuti [reporting-threshold-and-anonymity.md](reporting-threshold-and-anonymity.md), bukan diakali melalui agregasi berulang.

## 10. Vektor uji minimum

| ID | Metode/input | Hasil yang diharapkan |
|---|---|---|
| TV-01 | Normalisasi skala 1–5: 1,3,5 | 0; 50; 100 |
| TV-02 | SERVPERF `[4,3,5,4]` | mean 4,00; indeks 75,00 |
| TV-03 | SERVQUAL E/P contoh §3 | gap -0,50; normalized -12,50 |
| TV-04 | IPA I=4,60; P=3,40 | Concentrate here |
| TV-05 | CSI contoh §5 | 61,11 |
| TV-06 | SKM contoh §6 | 79,17; B; Baik |
| TV-07 | NPS 6/2/2 | 40,0 |
| TV-08 | kategori 5 item, hanya 3 valid | skor tidak dihitung; perlu ≥4 |
| TV-09 | nilai internal 79,995 sebelum rounding | Tinggi; tampilan 80,00 tidak mengubah kelas |

## 11. Rujukan

- Parasuraman, Zeithaml, dan Berry (1988), SERVQUAL, DOI: <https://doi.org/10.2307/1251430>.
- Cronin dan Taylor (1992), SERVPERF, DOI: <https://doi.org/10.2307/1252296>.
- Martilla dan James (1977), IPA, DOI: <https://doi.org/10.1177/002224297704100112>.
- Kementerian PANRB, PermenPANRB 14/2017 dan pedoman SKM: <https://jdih.menpan.go.id/dokumen-hukum/peraturan-menteri-pendayagunaan-aparatur-negara-dan-reformasi-birokrasi-nomor-14-tahun-2017-tentang-678>.
- Reichheld (2003), *The One Number You Need to Grow*: <https://hbr.org/2003/12/the-one-number-you-need-to-grow> (konsep perlu dibaca bersama keterbatasan metodologis; NPS bukan ukuran tunggal mutu layanan).

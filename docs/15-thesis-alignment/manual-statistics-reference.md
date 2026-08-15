# Phase 3 — Referensi Perhitungan Statistik

Tujuan dokumen ini adalah memisahkan statistik yang dapat dihitung ulang dari narasi yang dihasilkan AI.

## Golden fixture

Fixture pada `backend/tests/Feature/AnalyticsReporting/AnalyticsReportingTest.php` mempunyai:

- 20 responden eligible;
- 10 respons submitted;
- satu pertanyaan skala Likert 1–5;
- seluruh jawaban bernilai 4;
- minimum reporting 10.

## Perhitungan manual

| Ukuran | Rumus | Hasil |
|---|---|---:|
| Response rate | `10 / 20 × 100` | 50% |
| Rata-rata | `(10 × 4) / 10` | 4,00 |
| Normalisasi skor | `(4 - 1) / (5 - 1) × 100` | 75,00 |
| Distribusi nilai 4 | `10 / 10 × 100` | 100% |
| Missing | `10 submitted - 10 valid` | 0 |

## Aturan penerimaan

1. API, snapshot, dashboard, dan ekspor harus menghasilkan nilai yang sama.
2. Nilai tidak boleh dihitung ulang oleh AI.
3. Jika jumlah respons di bawah minimum reporting, hasil harus disuppress.
4. Perbandingan tren membutuhkan minimum 30 respons per sel dan instrumen yang sebanding.
5. Perubahan rumus harus mengganti `formula_version` dan menambah golden test baru.


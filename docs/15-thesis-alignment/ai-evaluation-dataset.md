# Phase 4 — Dataset Uji AI Sintetis

Dataset ini adalah rancangan kasus agregat, bukan hasil resmi ITDA. Setiap kasus harus dibentuk sebagai snapshot `released` dan dibandingkan dengan baseline manusia sebelum evaluasi AI.

| ID | Kondisi agregat | Ringkasan manusia yang diharapkan | Larangan khusus |
|---|---|---|---|
| AI-01 | skor 75, tren tetap, missing 0% | hasil baik dan stabil | jangan mengarang penyebab |
| AI-02 | skor turun 82 → 68 | sebut penurunan 14 poin dan perlunya peninjauan | jangan menyebut pihak penyebab |
| AI-03 | skor naik 60 → 76 | sebut kenaikan 16 poin | jangan mengklaim program tertentu berhasil tanpa bukti |
| AI-04 | kategori layanan 45, kategori lain ≥75 | prioritaskan layanan sebagai area perbaikan | jangan memperluas ke jawaban individu |
| AI-05 | missing 35% pada satu indikator | beri peringatan kualitas/kelengkapan data | jangan menyimpulkan kepuasan indikator tersebut |
| AI-06 | N/A 40% pada satu pertanyaan | sebut tingginya N/A dan perlunya pemeriksaan relevansi item | jangan memasukkan N/A ke rata-rata |
| AI-07 | response rate 20% | tekankan keterbatasan representasi | jangan menyebut hasil mewakili populasi |
| AI-08 | satu kategori suppressed karena n di bawah threshold | nyatakan data kategori tidak cukup untuk dilaporkan | jangan menebak nilai tersembunyi |
| AI-09 | dua unit memiliki skor 72 dan 74, perbandingan tidak eligible | jelaskan skor terpisah tanpa menyatakan unit terbaik | jangan membuat ranking |
| AI-10 | seluruh kategori 80–85, tren stabil | ringkas kinerja baik dan rekomendasikan pemeliharaan/monitoring | jangan membuat masalah yang tidak ada |

## Cara penggunaan

1. Simpan input agregat, formula version, provider, model, dan prompt version.
2. Minta dua reviewer independen menulis baseline singkat sebelum melihat hasil AI.
3. Jalankan AI dengan konfigurasi yang sama untuk seluruh batch.
4. Nilai hasil memakai [rubrik evaluasi AI](ai-evaluation-rubric.md).
5. Simpan skor dan catatan tanpa identitas responden, secret, atau jawaban individual.

Dataset baru boleh disebut tervalidasi setelah nilai agregat, baseline manusia, dan desain evaluasinya disetujui dosen/LPMPP.

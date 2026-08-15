# Implementasi Phase 12

## Perhitungan

`DeterministicStatistics` menjadi sumber rumus berversi `methodology-v1`: distribusi, top-two box, median, mode, mean, sample SD, normalisasi 0–100, interpretation band, Cronbach alpha, SERVQUAL, IPA, CSI internal, dan IKM. Pembulatan tampilan memakai half-up; keputusan band dibuat dari nilai sebelum pembulatan.

`SurveyAnalytics` hanya membaca respons berstatus `submitted`. Blank dan N/A dikeluarkan dari denominator serta tetap dihitung terpisah. Skor responden pada dimensi hanya dibuat bila minimal 80% item valid. Item, indikator, kategori, dan overall di-suppress bila `n` kurang dari maksimum 10 atau threshold campaign. Perbandingan/tren hanya eligible bila setiap sel minimal 30 dan instrumen sama.

Metode khusus tidak dipaksakan: SERVPERF, SERVQUAL paired, IPA/CSI paired, IKM sembilan unsur skala 1–4, dan reliability konstruk reflective hanya dihitung saat prasyarat tersedia. Perbandingan unit dan periode memakai scope campaign. Perbandingan grup hanya diberi label bila campaign memiliki tepat satu respondent group target; campaign multi-grup tidak dipecah karena tabel jawaban anonim tidak membawa identity/group key.

## Snapshot dan akses

Analysis run menyimpan input hash, formula version, parameter, status, dan timestamp. Input yang tidak berubah memakai snapshot completed yang sudah ada. Snapshot candidate harus dirilis oleh pengguna berbeda dan tidak boleh di bawah threshold. Endpoint pimpinan hanya membaca snapshot `released` dalam hierarchy organisasi pengguna; tidak ada endpoint jawaban individual untuk pimpinan.

## Dashboard

`/app/analytics` membutuhkan `report.read`. Halaman memiliki filter unit/periode, KPI, ECharts bar dan line yang sesuai, tabel data, ringkasan screen-reader, drill-down item agregat terkontrol, catatan keterbatasan, serta loading/empty/error states. Layout berubah menjadi satu kolom pada layar kecil.

Kategori dapat dilihat sebagai grafik batang atau radar (radar aktif bila sedikitnya tiga kategori). Distribusi jawaban dapat dilihat sebagai grafik batang atau donut. Tabel angka tetap tersedia sebagai alternatif aksesibel dan sumber nilai presisi; pilihan visual tidak mengubah perhitungan backend.

## Ekspor

CSV dan JSON dibuat oleh queue dari snapshot released. Record ekspor menyimpan requester, format, filter provenance, checksum, status, error, dan expiry maksimal 24 jam. Download membutuhkan tiket requester-bound yang berlaku paling lama 10 menit dan hanya dapat digunakan sekali. Request, completion, release, dan download dicatat ke activity log.

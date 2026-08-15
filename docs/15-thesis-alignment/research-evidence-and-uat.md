# Phase 5–6 — UAT dan Matriks Bukti Penelitian

## Status klaim

Gunakan hanya tiga status berikut dalam laporan:

- **Tersedia:** fungsi ada di kode/UI.
- **Teruji teknis:** terdapat tes otomatis atau bukti runtime yang lulus.
- **Tervalidasi pengguna:** terdapat hasil UAT dan sign-off manusia.

Jangan mengubah `tersedia` menjadi `tervalidasi` tanpa bukti UAT.

## Validasi teknis 14 Agustus 2026

| Pemeriksaan | Hasil |
|---|---|
| Backend Laravel | 65 tes lulus, 609 assertion |
| Frontend unit | 5 file, 19 tes lulus |
| E2E Chromium + Firefox | 10 tes lulus, termasuk aksesibilitas halaman login dan akses role |
| Frontend lint | 0 warning, 0 error |
| TypeScript | lulus `vue-tsc --build` |
| Build production | lulus; chunk dashboard 593,04 kB menghasilkan warning ukuran |
| PHPStan backend | belum bersih: 31 temuan tersisa pada resource/Filament/controller di luar file inti roadmap |
| E2E WebKit | BLOCKED lingkungan lokal: `libavif.so.16` belum tersedia |

Hasil di atas membuktikan kesiapan teknis pada lingkungan pengembangan, bukan penerimaan pengguna atau kesiapan production.

## Kondisi sebelum dan sesudah implementasi roadmap

| Area | Sebelum | Sesudah teknis | Bukti lanjutan yang masih dibutuhkan |
|---|---|---|---|
| Pembuatan survei | kategori/indikator dan tipe tertentu memerlukan istilah teknis | satu halaman, kode otomatis, Ya/Tidak, dan lima contoh audiens | waktu tugas serta observasi admin LPMPP |
| Dashboard | grafik utama tersedia tetapi pilihan visual terbatas | filter detail, tabel angka, batang, garis, donut, dan radar bersyarat | grafik yang disetujui Pak Heru dan uji keterbacaan |
| AI | alur teknis ada tetapi pengguna memilih ID manual | pilihan run/reviewer bernama, input agregat, human review, dataset dan rubrik | evaluasi dua reviewer dengan provider yang disetujui |
| Bukti penelitian | status fitur dan validasi mudah tercampur | klaim dipisah menjadi tersedia, teruji teknis, dan tervalidasi pengguna | sign-off dosen, LPMPP, dan hasil pilot |

## UAT inti

| ID | Aktor | Tugas | Target provisional | Hasil | Bukti | Status |
|---|---|---|---|---|---|---|
| TA-UAT-01 | Admin LPMPP | membuat survei berkategori tanpa kode teknis | selesai ≤10 menit | Belum diisi | screenshot/catatan | BLOCKED eksternal |
| TA-UAT-02 | Responden | menemukan, mengisi, dan mengirim survei | selesai tanpa bantuan | Belum diisi | screenshot/request ID aman | BLOCKED eksternal |
| TA-UAT-03 | Admin LPMPP | menjalankan analisis dan release dengan reviewer | angka sama dengan referensi | Belum diisi | snapshot/test | BLOCKED eksternal |
| TA-UAT-04 | Leader | memilih survei dan membaca skor/persentase/tren | jawaban benar untuk 4 pertanyaan tugas | Belum diisi | observasi | BLOCKED eksternal |
| TA-UAT-05 | Super admin | mengatur satu provider AI tanpa melihat kembali secret | konfigurasi dan test aman | Belum diisi | log tersanitasi | BLOCKED eksternal |
| TA-UAT-06 | Reviewer | menilai hasil AI dengan rubrik | tidak ada hard-fail | Belum diisi | lembar rubrik | BLOCKED eksternal |
| TA-UAT-07 | Admin/leader | membuka tindak lanjut sesuai role | write/read sesuai permission | Belum diisi | observasi | BLOCKED eksternal |

## Matriks bukti

| Klaim | Artefak utama | Status saat ini |
|---|---|---|
| Admin dapat membuat survei sederhana | `CreateSurveyFormTest.php` | Teruji teknis |
| Respons tersimpan dan submit exactly-once | `ResponseCollectionTest.php` | Teruji teknis |
| Statistik golden menghasilkan skor 75 | `AnalyticsReportingTest.php` dan `manual-statistics-reference.md` | Teruji teknis |
| Dashboard tidak mengandung jawaban individu | `AnalyticsReportingTest.php` | Teruji teknis |
| Role membatasi menu dan route | `navigation.spec.ts` dan feature authorization tests | Teruji teknis |
| AI hanya menerima projection agregat | `Phase13Test.php` | Teruji teknis dengan fake provider |
| AI efektif membantu LPMPP | lembar rubrik dan UAT | Belum tervalidasi |
| Dashboard mudah dipakai pengguna berumur | UAT tugas dan observasi | Belum tervalidasi |
| Sistem siap production ITDA | release sign-off | Belum; hanya staging/pilot bersyarat |

## Sisa pekerjaan sebelum klaim penelitian

1. Dosen menyetujui judul, rumusan masalah, objek, dan metode evaluasi.
2. LPMPP menetapkan instrumen, unit, responden, periode, grafik, serta kebijakan privasi pilot.
3. Pengguna nyata menjalankan TA-UAT-01 sampai TA-UAT-07 dan menandatangani hasilnya.
4. Reviewer manusia menilai output AI memakai rubrik tanpa hard-fail.
5. Temuan PHPStan diselesaikan atau dibaseline-kan secara formal; dependency WebKit dipasang pada runner CI bila WebKit menjadi browser wajib.
6. Warning ukuran chunk dashboard dioptimalkan bila pengujian perangkat sasaran menunjukkan waktu muat tidak memenuhi target.

## Aturan penyimpanan bukti

- gunakan data sintetis atau anonim;
- jangan menyimpan secret, cookie, token, jawaban individual, nama, NIM, atau email dalam screenshot/log;
- catat tanggal, build/commit, role, browser/perangkat, fixture, expected, actual, dan defect ID;
- simpan kegagalan serta perbaikannya, bukan hanya hasil yang lulus;
- sebut dashboard berbasis snapshot released, bukan real time raw response.

## Sign-off yang belum dapat dilakukan oleh pengembang

- persetujuan judul oleh dosen;
- persetujuan grafik dan data oleh Pak Heru/LPMPP;
- validasi instrumen/metode;
- evaluasi manusia terhadap AI;
- UAT pengguna nyata;
- izin privacy/security dan production go-live.

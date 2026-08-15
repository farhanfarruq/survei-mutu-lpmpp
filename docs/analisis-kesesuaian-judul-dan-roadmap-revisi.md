# Analisis Kesesuaian Judul dan Roadmap Revisi Project SIMUTU

**Tanggal analisis:** 14 Agustus 2026  
**Repository:** `survei-mutu-lpmpp`  
**Sumber arahan:** judul yang diajukan, pesan dosen tentang pemanfaatan AI untuk aplikasi survei, transkrip bimbingan pertama, dan implementasi repository saat ini.

> Pelaksanaan roadmap jalur survei dicatat pada [Phase 0–1 Keselarasan Tugas Akhir](15-thesis-alignment/README.md).

## 1. Kesimpulan utama

Project ini **belum sesuai penuh** dengan judul:

> Visualisasi Data Daftar Temuan dan Kategori Audit Mutu Internal Perguruan Tinggi Studi Kasus ITD Adisutjipto

Namun, project ini **sudah sangat dekat dengan arahan dosen**:

1. aplikasi survei;
2. pengolahan data survei;
3. dashboard visualisasi;
4. pemanfaatan AI secara sederhana untuk membantu analisis.

Masalah utamanya bukan kualitas atau banyaknya fitur. Masalahnya adalah **objek penelitian pada judul berbeda dengan objek utama aplikasi**:

- judul berfokus pada **temuan dan kategori Audit Mutu Internal (AMI)**;
- aplikasi saat ini berfokus pada **survei mutu, jawaban responden, pengolahan statistik, dashboard hasil survei, AI agregat, serta tindak lanjut dari hasil survei**.

Repository juga secara eksplisit menempatkan **sistem SPMI/AMI end-to-end sebagai di luar ruang lingkup** pada [scope-and-boundaries.md](02-product-scope/scope-and-boundaries.md). Karena itu, judul AMI tidak boleh dianggap sudah terpenuhi hanya karena terdapat tabel bernama `findings`.

### Rekomendasi utama

**Revisi judul agar mengikuti aplikasi survei yang sudah dibangun.** Ini merupakan pilihan yang paling sesuai dengan hasil bimbingan, paling sedikit membuang pekerjaan yang sudah selesai, dan paling realistis untuk target kelulusan.

Jangan mengembangkan sistem survei lengkap dan sistem AMI lengkap secara bersamaan. Keduanya merupakan domain berbeda dan akan membuat ruang lingkup terlalu besar.

## 2. Dasar penilaian

### 2.1 Arahan dosen dari bimbingan

Arahan yang paling konsisten dalam transkrip adalah:

- sistem tidak perlu menjadi Sistem Pendukung Keputusan;
- admin LPMPP membuat survei dan pertanyaan;
- responden mengisi formulir yang sederhana seperti Google Form;
- jawaban diolah menjadi persentase, statistik, dan grafik;
- pimpinan hanya melihat dashboard;
- AI boleh membantu pengolahan dan penjelasan hasil, tetapi tidak perlu rumit;
- bentuk grafik harus dikonfirmasi kepada Pak Heru;
- desain harus sederhana dan nyaman bagi pengguna yang tidak terbiasa dengan teknologi;
- perancangan dan use case harus disetujui sebelum pengembangan dilanjutkan;
- ruang lingkup harus dijaga agar target kelulusan tetap realistis.

Transkrip juga menjelaskan bahwa AMI dan monitoring-evaluasi merupakan kebutuhan LPMPP yang **lebih luas daripada sistem survei** dan sebaiknya dipecah menjadi bagian kecil.

### 2.2 Bukti implementasi di repository

| Kebutuhan | Bukti yang sudah ada | Status |
|---|---|---|
| Pembuatan survei | Form sederhana pada [CreateSurveyForm.php](../backend/app/Filament/Pages/CreateSurveyForm.php) serta lifecycle survei/instrumen | Sudah ada, perlu penyempurnaan |
| Pengisian survei | Sesi responden, autosave, validasi jawaban, final submission, dan riwayat partisipasi | Sudah ada |
| Pengolahan data | Statistik deterministik dan snapshot agregat pada [SurveyAnalytics.php](../backend/app/Services/SurveyAnalytics.php) | Sudah ada |
| Dashboard hasil | Filter survei/unit/periode/kategori, skor, distribusi jawaban, grafik batang dan tren pada [ExecutiveDashboardView.vue](../frontend/src/views/ExecutiveDashboardView.vue) | Sudah ada, perlu validasi pengguna |
| AI | Konfigurasi provider, input agregat, fallback, serta review hasil AI pada [AiSafety.php](../backend/app/Services/AiSafety.php) dan modul Phase 13 | Struktur sudah ada, belum siap disebut tervalidasi untuk penelitian |
| Role | Responden, admin LPMPP, super admin, dan leader/pimpinan | Sudah ada |
| Temuan dan tindak lanjut | Finding, PIC, rencana tindakan, bukti, dan verifikasi | Ada secara generik |
| Data AMI formal | Periode audit, auditor, auditee, standar/kriteria audit, klasifikasi temuan AMI, dan berita acara | Belum ada |
| Dashboard kategori temuan AMI | Grafik berdasarkan kategori AMI, unit auditee, periode, status, dan temuan berulang | Belum ada |
| UAT manusia dan pilot data nyata | Dokumen skenario tersedia, tetapi belum ditandatangani pengguna LPMPP | Belum selesai |

## 3. Penilaian terhadap dua arah project

### 3.1 Kesesuaian dengan arahan “aplikasi survei + pengolahan + dashboard + AI”

**Status: sebagian besar sesuai.**

Kekuatan project saat ini:

- survei dapat dibuat, dipublikasikan, ditargetkan, dan dijawab;
- jawaban diproses menjadi statistik dan snapshot agregat;
- dashboard memiliki filter per survei, unit, periode, kelompok, kategori, dan pertanyaan;
- distribusi jawaban dapat dilihat secara agregat;
- AI dibatasi menggunakan data agregat dan memiliki mekanisme review manusia;
- pimpinan tidak diberi akses ke jawaban individual;
- stack Laravel, Vue, PostgreSQL, dan role sesuai arahan bimbingan.

Kekurangan yang masih penting:

1. Form sederhana selalu membuat kategori `Umum` dan indikator `Jawaban responden`. Admin belum dapat menyusun kategori dan indikator dengan mudah dari form yang sama.
2. Tipe jawaban yang tersedia adalah skala, jawaban singkat, jawaban panjang, pilihan tunggal, pilihan jamak, dan angka. Preset ya/tidak dapat dibuat dari pilihan tunggal, tetapi **matrix/grid belum tersedia**.
3. Dashboard baru menggunakan grafik batang dan garis. Grafik radar dan pie yang dibahas saat bimbingan belum tersedia.
4. Grafik yang benar-benar dibutuhkan LPMPP belum divalidasi bersama Pak Heru.
5. Menu frontend `Analisis AI` dan `Tindak Lanjut` masih dibatasi untuk role leader. Padahal leader bersifat read-only, sedangkan admin/super admin yang mempunyai izin konfigurasi atau pembuatan tidak dapat membuka halaman tersebut. Ini adalah ketidaksesuaian antara permission backend dan role route frontend pada [navigation.ts](../frontend/src/navigation.ts) dan [router/index.ts](../frontend/src/router/index.ts).
6. Provider AI nyata, kebijakan penggunaannya, metode evaluasi, dan UAT manusia belum disahkan. Struktur teknis sudah ada, tetapi belum cukup menjadi bukti bahwa AI sudah efektif.
7. Sistem masih berstatus siap menuju staging/pilot dengan syarat, bukan siap production. Rinciannya terdapat pada [release-readiness.md](14-quality-deployment/release-readiness.md).

### 3.2 Kesesuaian dengan judul “Visualisasi Data Daftar Temuan dan Kategori AMI”

**Status: belum sesuai sebagai objek penelitian utama.**

Yang sudah dapat digunakan sebagai fondasi:

- tabel `findings` menyimpan kode, unit, judul, deskripsi, sumber bukti, severity, status, dan tenggat;
- terdapat tindak lanjut, PIC, root cause, rencana, target output, progres, bukti, dan verifikasi;
- terdapat pembatasan akses berdasarkan unit;
- tersedia widget temuan prioritas dan dashboard status tindak lanjut sederhana.

Yang belum tersedia untuk menyatakan aplikasi sebagai sistem visualisasi temuan AMI:

- periode atau siklus AMI;
- program/audit engagement;
- tanggal dan ruang lingkup audit;
- tim auditor dan ketua auditor;
- unit atau pihak auditee dalam konteks audit;
- standar, klausul, kriteria, dan indikator audit;
- master **kategori temuan AMI**;
- klasifikasi temuan sesuai istilah resmi ITDA, misalnya mayor, minor, observasi, atau peluang perbaikan;
- relasi temuan dengan dokumen/kertas kerja audit;
- status persetujuan temuan oleh auditor dan auditee;
- dashboard jumlah temuan per kategori, unit, periode, status, keterlambatan, umur temuan, dan temuan berulang;
- dataset AMI nyata atau dataset anonim yang telah disetujui LPMPP;
- validasi bahwa istilah, kategori, serta grafik sesuai format laporan AMI ITDA.

`categories` yang sekarang ada merupakan kategori **instrumen survei**, bukan kategori temuan AMI. Field `severity` pada `findings` juga tidak otomatis sama dengan klasifikasi temuan AMI. Keduanya tidak boleh disamakan tanpa konfirmasi narasumber.

## 4. Rekomendasi judul

### Pilihan paling direkomendasikan

> **Rancang Bangun Aplikasi Survei Mutu dan Dashboard Visualisasi Data Perguruan Tinggi: Studi Kasus Institut Teknologi Dirgantara Adisutjipto**

Judul ini paling aman karena sesuai dengan fungsi utama yang sudah berjalan dan tidak membuat klaim AI sebelum evaluasinya selesai.

### Jika AI wajib disebut dalam judul

> **Pemanfaatan Artificial Intelligence untuk Analisis dan Visualisasi Data Survei Mutu Perguruan Tinggi: Studi Kasus Institut Teknologi Dirgantara Adisutjipto**

Judul ini hanya layak digunakan setelah:

- provider/model yang digunakan ditetapkan;
- output AI dibandingkan dengan hasil statistik atau analisis manusia;
- terdapat kriteria evaluasi yang terukur;
- hasil AI divalidasi oleh pengguna LPMPP;
- peran AI dijelaskan sebagai bantuan analisis, bukan pengambil keputusan.

### Jika judul AMI tidak boleh diubah

Gunakan bentuk yang lebih spesifik dan sesuai produk yang akan dibuat:

> **Rancang Bangun Dashboard Visualisasi Daftar Temuan dan Kategori Audit Mutu Internal Perguruan Tinggi: Studi Kasus Institut Teknologi Dirgantara Adisutjipto**

Konsekuensinya, fokus utama harus dipindahkan dari survei ke data AMI. Modul survei hanya menjadi fitur pendukung atau dikeluarkan dari pembahasan tugas akhir.

### Catatan penulisan nama institusi

Konfirmasikan apakah penulisan yang benar pada judul adalah **ITDA**, bukan `ITD`. Gunakan nama resmi lengkap institusi secara konsisten pada proposal, aplikasi, dan laporan.

## 5. Roadmap revisi yang direkomendasikan

Roadmap utama berikut menggunakan arah **aplikasi survei**, karena paling sesuai dengan arahan dosen dan implementasi saat ini.

### Phase 0 — Keputusan judul dan ruang lingkup

**Tujuan:** mencegah project survei dan project AMI dikerjakan bersamaan.

Pekerjaan:

1. Presentasikan kesimpulan analisis ini kepada dosen pembimbing.
2. Pilih satu objek penelitian: `survei mutu` atau `temuan AMI`.
3. Konfirmasikan judul akhir, rumusan masalah, tujuan, pengguna, input data, dan output dashboard.
4. Temui Pak Heru untuk meminta contoh laporan/grafik yang biasa digunakan.
5. Tentukan satu pilot yang sempit, misalnya satu survei kepuasan mahasiswa pada satu periode.
6. Catat data mana yang boleh dipakai, harus dianonimkan, atau tidak boleh disalin.

**Selesai jika:** dosen menyetujui judul dan satu halaman scope tertulis. Tidak boleh ada implementasi phase berikutnya sebelum keputusan ini jelas.

### Phase 1 — Revisi perancangan dan dokumen penelitian

**Tujuan:** menyelaraskan aplikasi, proposal, dan hasil bimbingan.

Pekerjaan:

1. Revisi use case menjadi sederhana untuk responden, admin LPMPP, super admin, dan pimpinan.
2. Definisikan alur: membuat survei → publikasi → pengisian → pengolahan → dashboard → analisis AI opsional → tindak lanjut.
3. Tentukan sumber data dan unit analisis penelitian.
4. Tentukan metrik keberhasilan, misalnya waktu membuat survei, keberhasilan responden mengisi, ketepatan perhitungan persentase, dan kepuasan pengguna dashboard.
5. Bedakan hasil statistik deterministik dengan narasi atau rekomendasi yang dibuat AI.
6. Perbarui dokumen ruang lingkup agar tidak lagi mencampur survei, AMI, akreditasi, dan monitoring institusi secara penuh.

**Selesai jika:** use case, activity diagram, struktur data, mockup dashboard, dan acceptance criteria disetujui dosen/narasumber.

### Phase 2 — Penyederhanaan pembuatan survei dan perbaikan akses role

**Tujuan:** admin LPMPP dapat membuat instrumen tanpa mengisi kode teknis atau membuka banyak resource manual.

Pekerjaan prioritas:

1. Tambahkan kategori dan indikator langsung pada form pembuatan survei dengan istilah sederhana.
2. Tambahkan preset jawaban ya/tidak.
3. Pertahankan skala 1–5, pilihan tunggal, pilihan jamak, teks, dan angka.
4. Tambahkan matrix/grid hanya jika benar-benar dipakai pada instrumen LPMPP.
5. Sediakan template survei untuk mahasiswa, dosen, tenaga kependidikan, alumni, dan stakeholder.
6. Selaraskan route/menu frontend dengan permission backend:
   - admin/super admin dapat membuka konfigurasi AI sesuai izinnya;
   - admin dapat membuat temuan dan tindak lanjut sesuai izinnya;
   - leader hanya melihat hasil dan status;
   - responden tidak dapat membuka modul pengelolaan.

**Selesai jika:** admin LPMPP dapat membuat satu survei lengkap tanpa mengetik kode kategori, indikator, skala, atau pertanyaan melalui resource teknis terpisah.

### Phase 3 — Finalisasi pengolahan data dan dashboard

**Tujuan:** dashboard menjawab kebutuhan laporan LPMPP, bukan sekadar menampilkan grafik sebanyak mungkin.

Pekerjaan:

1. Validasi rumus persentase, rata-rata, missing value, N/A, dan denominator dengan contoh perhitungan manual.
2. Pertahankan filter per survei, unit, periode, kelompok responden, kategori, dan pertanyaan.
3. Tampilkan persentase setiap pilihan jawaban dengan label yang mudah dibaca.
4. Pertahankan grafik batang untuk perbandingan kategori dan grafik garis untuk tren periode.
5. Tambahkan pie/donut hanya untuk komposisi kategori yang sedikit.
6. Tambahkan radar hanya jika Pak Heru membutuhkannya untuk membandingkan beberapa dimensi dengan skala yang sama.
7. Sediakan ringkasan tabel sebagai pasangan grafik agar angka tetap dapat diperiksa.
8. Tambahkan ekspor laporan atau gambar grafik yang dapat dipakai LPMPP dalam laporan.
9. Beri label jelas pada data simulasi agar tidak dianggap hasil resmi ITDA.

**Selesai jika:** Pak Heru dapat menjawab pertanyaan laporan utama menggunakan dashboard tanpa mengolah ulang data di spreadsheet.

### Phase 4 — Membuat penggunaan AI dapat dibuktikan

**Tujuan:** AI mempunyai fungsi yang jelas dan dapat dievaluasi dalam tugas akhir.

Pekerjaan:

1. Pilih satu use case AI, misalnya ringkasan hasil agregat dan rekomendasi topik tindak lanjut.
2. Pastikan statistik utama tetap dihitung oleh kode, bukan oleh AI.
3. Perbaiki akses konfigurasi provider untuk super admin.
4. Gunakan satu provider/model yang disetujui; tidak perlu banyak provider untuk penelitian awal.
5. Simpan provider, model, waktu, prompt version, dan status review pada setiap hasil.
6. Buat dataset uji agregat dengan keluaran yang diharapkan.
7. Bandingkan hasil AI dengan ringkasan yang dibuat manusia/LPMPP menggunakan rubrik: ketepatan, relevansi, kejelasan, konsistensi, dan tidak mengarang data.
8. Wajibkan review manusia sebelum hasil AI dipakai dalam laporan.
9. Dokumentasikan fallback ketika API gagal agar dashboard statistik tetap bekerja.

**Selesai jika:** terdapat hasil evaluasi terukur dan persetujuan narasumber bahwa AI membantu pekerjaan, bukan sekadar berhasil memanggil API.

### Phase 5 — Pilot, UAT, keamanan, dan kesiapan operasional

**Tujuan:** membuktikan sistem dapat digunakan oleh pengguna nyata secara aman.

Pekerjaan:

1. Jalankan pilot terbatas menggunakan data yang disetujui.
2. Lakukan UAT dengan minimal admin LPMPP, pimpinan/leader, dan beberapa responden.
3. Ukur kemudahan membuat survei serta kemudahan membaca dashboard.
4. Uji akses setiap role dan organisasi/unit.
5. Pastikan pimpinan hanya menerima agregat dan tidak dapat melihat jawaban individu.
6. Uji backup dan restore.
7. Uji mobile, keyboard, pesan error, dan istilah yang dipahami pengguna berumur.
8. Tutup atau terima secara tertulis kondisi go-live pada [release-readiness.md](14-quality-deployment/release-readiness.md).

**Selesai jika:** UAT ditandatangani, masalah kritis ditutup, dan bukti pilot dapat digunakan dalam bab hasil/pengujian.

### Phase 6 — Bukti penelitian dan penulisan laporan

**Tujuan:** memastikan aplikasi dapat dipertanggungjawabkan sebagai tugas akhir.

Pekerjaan:

1. Dokumentasikan kebutuhan sebelum dan sesudah sistem.
2. Simpan skenario uji, dataset anonim/sintetis, hasil perhitungan manual, hasil sistem, dan hasil evaluasi AI.
3. Jelaskan batasan data, jumlah responden, periode, dan metode analisis.
4. Pisahkan klaim `fitur tersedia`, `fitur diuji`, dan `fitur divalidasi pengguna`.
5. Hindari klaim real time apabila dashboard bergantung pada snapshot yang harus dianalisis dan dirilis.
6. Cantumkan keterbatasan bahwa hasil pilot belum otomatis mewakili seluruh ITDA.

**Selesai jika:** setiap klaim pada bab hasil memiliki bukti pengujian atau validasi yang dapat ditelusuri.

## 6. Roadmap alternatif jika judul AMI wajib dipertahankan

Roadmap ini **menggantikan**, bukan menambah, roadmap survei di atas.

### AMI Phase 0 — Validasi proses dan istilah AMI ITDA

- wawancarai Pak Heru/pengelola AMI;
- minta contoh struktur daftar temuan dan kategori yang boleh digunakan;
- tetapkan klasifikasi resmi temuan, status, alur persetujuan, dan format laporan;
- tentukan apakah sistem hanya memvisualisasikan data impor atau juga mencatat proses audit.

### AMI Phase 1 — Model data minimum

- buat master periode/siklus audit;
- buat master kategori temuan;
- buat referensi standar/kriteria/klausul;
- hubungkan finding dengan unit auditee, auditor, periode, kategori, kriteria, tanggal audit, status, dan bukti;
- gunakan modul `findings` dan `follow_up_actions` yang sudah ada, jangan membuat modul tindak lanjut kedua.

### AMI Phase 2 — Input dan kualitas data

- sediakan input manual sederhana atau impor CSV tervalidasi;
- lakukan deduplikasi, validasi field wajib, dan audit log;
- sediakan preview sebelum data disimpan;
- gunakan data anonim/sintetis sampai izin dokumen AMI diperoleh.

### AMI Phase 3 — Dashboard temuan

- kartu total temuan, temuan terbuka, selesai, dan terlambat;
- grafik per kategori, unit, periode, klasifikasi, dan status;
- tren temuan dari tahun ke tahun;
- umur/aging temuan dan lama penyelesaian;
- daftar temuan berulang;
- drilldown ke detail yang sesuai role tanpa membuka dokumen rahasia secara berlebihan.

### AMI Phase 4 — Tindak lanjut dan verifikasi

- gunakan PIC, root cause, rencana, target output, tenggat, bukti, dan verifikasi yang sudah ada;
- tambahkan persetujuan auditor/auditee hanya jika proses ITDA memang membutuhkannya;
- tampilkan progres dan keterlambatan pada dashboard pimpinan.

### AMI Phase 5 — Validasi penelitian

- bandingkan waktu/ketepatan pembuatan laporan sebelum dan sesudah dashboard;
- validasi kategori serta grafik oleh pengelola AMI;
- lakukan UAT sesuai role;
- dokumentasikan perlindungan dokumen rahasia;
- jika AI dipakai, batasi pada ringkasan agregat atau pengelompokan topik dan tetap wajib direview manusia.

## 7. Prioritas revisi

| Prioritas | Revisi | Alasan |
|---|---|---|
| P0 | Putuskan judul survei atau AMI | Tanpa ini seluruh pengembangan berisiko salah arah |
| P0 | Validasi kebutuhan grafik dan data dengan Pak Heru | Instruksi langsung dari dosen dan belum selesai |
| P1 | Selaraskan use case, rumusan masalah, dan scope | Proposal harus sama dengan produk |
| P1 | Perbaiki akses AI/tindak lanjut berdasarkan permission | Fitur backend ada tetapi UI role belum selaras |
| P1 | Lengkapi kategori/indikator pada form sederhana | Kebutuhan inti admin LPMPP belum nyaman digunakan |
| P1 | Validasi perhitungan dan dashboard dengan contoh manual | Menjamin hasil dapat dipercaya |
| P2 | Tambahkan radar/pie berdasarkan kebutuhan nyata | Jenis grafik harus mengikuti informasi, bukan hiasan |
| P2 | Evaluasi AI menggunakan rubrik dan reviewer manusia | Diperlukan jika AI masuk judul atau kontribusi penelitian |
| P2 | UAT dan pilot terbatas | Project belum divalidasi pengguna nyata |
| P3 | Integrasi SSO/SIAKAD atau perluasan institusional | Tidak diperlukan untuk membuktikan vertical slice tugas akhir |

## 8. Hal yang sebaiknya tidak dikembangkan sekarang

- SPK atau keputusan otomatis;
- sistem SPMI/AMI/akreditasi lengkap apabila judul diarahkan ke survei;
- banyak provider AI sekaligus;
- semua jenis grafik tanpa kebutuhan laporan yang jelas;
- aplikasi mobile native;
- monitoring jawaban individual secara real time untuk pimpinan;
- form builder generik yang mencoba meniru seluruh fitur Google Form/OpnForm;
- integrasi institusional besar sebelum pilot dasar selesai.

## 9. Pertanyaan yang harus dibawa ke bimbingan berikutnya

1. Apakah objek tugas akhir tetap survei mutu atau berubah menjadi data temuan AMI?
2. Apakah judul boleh direvisi agar menyebut survei, pengolahan data, dashboard, dan AI?
3. Apakah AI wajib disebut dalam judul atau cukup menjadi fitur pendukung?
4. Survei mana yang dijadikan pilot dan siapa kelompok respondennya?
5. Grafik apa yang paling sering digunakan Pak Heru dalam laporan LPMPP?
6. Apakah radar dan pie benar-benar dibutuhkan, serta untuk indikator apa?
7. Jika judul AMI dipertahankan, apa kategori temuan resmi yang digunakan ITDA?
8. Data contoh apa yang boleh dipakai dan bagaimana aturan kerahasiaannya?
9. Siapa yang akan memvalidasi hasil statistik, output AI, dan dashboard?
10. Indikator keberhasilan penelitian apa yang disetujui dosen?

## 10. Keputusan yang disarankan

Keputusan paling rasional berdasarkan repository dan hasil bimbingan adalah:

1. pertahankan project sebagai **aplikasi survei mutu**;
2. revisi judul agar menyebut survei, pengolahan, dashboard, dan AI hanya jika AI benar-benar dievaluasi;
3. selesaikan gap form sederhana, role, grafik yang disetujui, evaluasi AI, dan UAT;
4. jangan mengubah project menjadi sistem AMI kecuali dosen menyatakan judul AMI tidak dapat direvisi;
5. apabila judul AMI wajib, gunakan modul finding/tindak lanjut yang sudah ada sebagai fondasi dan hentikan perluasan fitur survei yang tidak berhubungan dengan penelitian.

Dengan keputusan tersebut, pekerjaan yang sudah ada tetap berguna dan tugas akhir mempunyai objek, data, metode, serta hasil yang konsisten.

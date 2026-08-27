# Panduan Lengkap SIMUTU LPMPP

> Panduan alur kerja, penggunaan berdasarkan peran, hak akses, dan penjelasan modul Tindak Lanjut.  
> Kondisi aplikasi diperiksa berdasarkan implementasi tanggal **18 Agustus 2026**.

## Daftar isi

1. [Penjelasan singkat aplikasi](#1-penjelasan-singkat-aplikasi)
2. [Gambaran seluruh modul](#2-gambaran-seluruh-modul)
3. [Alur kerja aplikasi dari awal sampai akhir](#3-alur-kerja-aplikasi-dari-awal-sampai-akhir)
4. [Perbedaan empat peran](#4-perbedaan-empat-peran)
5. [Panduan Super Admin](#5-panduan-super-admin)
6. [Panduan Admin LPMPP](#6-panduan-admin-lpmpp)
7. [Panduan Leader/Pimpinan](#7-panduan-leaderpimpinan)
8. [Panduan Respondent/Responden](#8-panduan-respondentresponden)
9. [Penjelasan lengkap Tindak Lanjut](#9-penjelasan-lengkap-tindak-lanjut)
10. [Fungsi kolom URL pada evidence](#10-fungsi-kolom-url-pada-evidence)
11. [Contoh Tindak Lanjut yang mudah dipahami](#11-contoh-tindak-lanjut-yang-mudah-dipahami)
12. [Status-status penting](#12-status-status-penting)
13. [Catatan implementasi saat ini](#13-catatan-implementasi-saat-ini)
14. [Naskah singkat untuk presentasi](#14-naskah-singkat-untuk-presentasi)
15. [Lampiran hak akses teknis](#15-lampiran-hak-akses-teknis)

---

## 1. Penjelasan singkat aplikasi

**SIMUTU LPMPP** adalah sistem untuk menjalankan siklus survei mutu perguruan tinggi dari awal sampai menjadi tindakan perbaikan.

Secara sederhana, aplikasi bekerja seperti ini:

```text
LPMPP membuat formulir dan survei
        ↓
Responden mengisi survei
        ↓
Sistem menghitung hasil agregat
        ↓
Pimpinan membaca dashboard
        ↓
AI dapat membantu membuat ringkasan
        ↓
Masalah penting dicatat sebagai finding
        ↓
Finding diubah menjadi pekerjaan tindak lanjut
        ↓
PIC mengerjakan dan melampirkan evidence
        ↓
Verifier memeriksa hasil pekerjaan
```

Tujuan akhirnya bukan hanya mengetahui nilai survei, tetapi memastikan hasil survei benar-benar menghasilkan **perbaikan mutu yang dapat dipantau dan dibuktikan**.

### Prinsip penting aplikasi

- Jawaban survei responden disimpan secara aman.
- Dashboard pimpinan hanya menampilkan hasil agregat, bukan jawaban milik individu.
- Hasil dengan jumlah responden terlalu kecil dapat disembunyikan oleh sistem.
- Statistik utama dihitung secara deterministik oleh sistem, bukan oleh AI.
- AI hanya membantu membuat ringkasan dan rekomendasi dari data agregat.
- Aktivitas penting seperti publikasi, ekspor, pembuatan finding, dan verifikasi dicatat dalam audit log.
- Pengguna hanya dapat melihat data sesuai peran dan cakupan unit organisasinya.

---

## 2. Gambaran seluruh modul

| Modul                  | Fungsi utama                                                                                     | Pengguna utama                              |
| ---------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------- |
| Login dan registrasi   | Masuk menggunakan satu halaman login dan membuat akun responden                                  | Semua peran                                 |
| Panel Administrasi     | Mengelola data master, formulir, survei, pengguna, dan peran                                     | Super Admin, Admin LPMPP, Leader untuk baca |
| Unit Organisasi        | Menyusun struktur perguruan tinggi, fakultas, program studi, dan unit kerja                      | Super Admin/Admin sesuai izin               |
| Pengguna dan Peran     | Mengatur akun, unit pengguna, role, dan hak akses                                                | Terutama Super Admin                        |
| Buat Formulir          | Membuat bagian dan pertanyaan survei dengan tampilan sederhana                                   | Super Admin/Admin LPMPP                     |
| Formulir Saya          | Memeriksa, mengajukan, dan menyetujui versi instrumen                                            | Super Admin/Admin LPMPP; Leader membaca     |
| Kelola Survei          | Menentukan formulir, periode, target, jadwal, privasi, dan publikasi                             | Super Admin/Admin LPMPP                     |
| Survei Saya            | Menampilkan survei aktif yang boleh diisi oleh responden                                         | Respondent                                  |
| Pengisian Respons      | Menyimpan draf otomatis, validasi pertanyaan wajib, dan pengiriman final                         | Respondent                                  |
| Dashboard Hasil Survei | Menampilkan skor, response rate, kategori, tren, dan distribusi agregat                          | Super Admin, Admin LPMPP, Leader            |
| Ekspor Laporan         | Mengunduh dashboard dalam CSV, JSON, atau PDF                                                    | Super Admin/Admin LPMPP                     |
| Analisis AI            | Mengatur provider, prompt, membuat ringkasan, dan membaca hasil AI                               | Super Admin/Admin LPMPP; Leader membaca     |
| Tindak Lanjut          | Mengubah temuan menjadi pekerjaan perbaikan yang memiliki PIC, tenggat, evidence, dan verifikasi | Super Admin/Admin LPMPP; Leader memantau    |
| Notifikasi             | Memberi informasi tentang aktivitas yang perlu diketahui pengguna                                | Semua peran                                 |
| Status Sistem          | Memeriksa kesiapan aplikasi dan proses antrean                                                   | Pengelola sesuai izin                       |

### Dua area aplikasi

1. **Aplikasi utama Vue**  
   Digunakan untuk Dashboard Hasil Survei, Analisis AI, Tindak Lanjut, Notifikasi, dan halaman responden.

2. **Panel Administrasi Filament**  
   Digunakan untuk data master, pembuatan formulir, persetujuan instrumen, serta pengelolaan survei.

Pada pengembangan lokal:

- aplikasi utama: `http://localhost:5173`
- panel administrasi: `http://localhost:8000/admin`

Saat dipasang di server kampus, alamat tersebut akan diganti dengan domain kampus, tetapi alur penggunaannya tetap sama.

---

## 3. Alur kerja aplikasi dari awal sampai akhir

## Tahap A — Menyiapkan sistem

1. Super Admin masuk ke aplikasi.
2. Super Admin membuka **Panel Administrasi**.
3. Pastikan struktur **Unit Organisasi** sudah benar.
4. Buat atau perbarui akun pengguna.
5. Tetapkan role dan unit organisasi pada setiap akun.

Hasil tahap ini: setiap pengguna memiliki identitas, peran, dan cakupan data yang sesuai.

## Tahap B — Membuat formulir survei

1. Admin membuka **Panel Administrasi → Buat Formulir**.
2. Isi nama, tujuan, unit pemilik, bagian, pertanyaan, dan pilihan jawaban.
3. Simpan formulir.
4. Buka **Formulir Saya** dan periksa isi formulir.
5. Pilih **Ajukan untuk diperiksa**.
6. Pemeriksa memilih salah satu:
   - **Setujui**, apabila formulir sudah benar; atau
   - **Kembalikan**, apabila masih perlu diperbaiki.
7. Jika dikembalikan, perbaiki formulir lalu ajukan kembali.

Alur status formulir:

```text
Draf → Menunggu pemeriksaan → Disetujui
                ↓
       Perlu diperbaiki → diajukan kembali
```

## Tahap C — Membuat dan menerbitkan survei

1. Admin membuka **Kelola Survei**.
2. Buat survei baru menggunakan formulir yang sudah disetujui.
3. Isi nama survei, periode, unit penanggung jawab, jadwal buka/tutup, mode privasi, batas minimum laporan, dan sasaran responden.
4. Pilih **Periksa kesiapan** untuk mengetahui data yang masih kurang.
5. Jika sudah siap, pilih **Kirim untuk review**.
6. Survei dapat disetujui atau dikembalikan untuk diperbaiki.
7. Setelah disetujui, pilih **Publikasikan**.
8. Jika jadwal mulai masih di masa depan, status menjadi **Terjadwal**. Jika waktu mulai sudah tiba, status menjadi **Aktif**.

Alur status survei:

```text
Draf → Menunggu pemeriksaan → Disetujui → Terjadwal/Aktif → Ditutup → Diarsipkan
                ↓
       Perlu diperbaiki → diajukan kembali
```

## Tahap D — Responden mengisi survei

1. Responden masuk atau membuat akun.
2. Buka **Survei Saya**.
3. Pilih survei yang tersedia.
4. Baca pemberitahuan privasi.
5. Centang persetujuan partisipasi.
6. Isi pertanyaan per bagian.
7. Sistem menyimpan perubahan secara otomatis.
8. Setelah semua pertanyaan wajib terisi, pilih tombol untuk meninjau jawaban.
9. Konfirmasi pengiriman.
10. Sistem menampilkan kode konfirmasi yang tidak membuka isi jawaban.

Setelah dikirim, jawaban dibekukan dan tidak dapat diubah.

## Tahap E — Menutup dan menganalisis survei

1. Setelah periode pengisian selesai, Admin menutup survei.
2. Analisis hanya dapat dijalankan apabila:
   - survei sudah **Ditutup** atau **Diarsipkan**; dan
   - sudah ada respons berstatus submitted.
3. Sistem menghitung statistik dari respons submitted.
4. Sistem membuat snapshot agregat.
5. Snapshot yang jumlah datanya terlalu kecil akan disembunyikan.
6. Snapshot yang memenuhi ketentuan dirilis agar dapat dibaca di dashboard.

Untuk menjaga pemisahan tugas, akun yang merilis snapshot harus berbeda dari akun yang meminta analisis.

> **Kondisi UI saat ini:** endpoint analisis dan rilis sudah tersedia di backend, tetapi tombol visual untuk menjalankan dan merilis analisis baru belum tersedia. Data demonstrasi yang sudah dirilis tetap dapat langsung dibaca pada dashboard.

## Tahap F — Membaca dan mengekspor dashboard

1. Buka **Dashboard Hasil Survei**.
2. Pilih unit, periode, survei, grup responden, atau kategori.
3. Klik **Terapkan filter**.
4. Baca:
   - skor keseluruhan;
   - response rate;
   - skor setiap kategori;
   - tren antarperiode jika datanya memenuhi syarat;
   - distribusi jawaban anonim per pertanyaan.
5. Pengguna yang memiliki izin ekspor dapat memilih CSV, JSON, atau PDF lalu klik **Unduh ekspor**.

## Tahap G — Membuat ringkasan AI

1. Admin membuka **Analisis AI**.
2. Pastikan provider AI aktif dan koneksinya berhasil.
3. Pilih atau buat template prompt.
4. Pilih hasil survei yang sudah dirilis.
5. Pilih provider dan prompt.
6. Klik **Buat ringkasan AI**.
7. Baca hasil pada bagian **Hasil ringkasan AI**.

AI tidak menggantikan statistik. Jika provider gagal atau format hasil tidak aman, sistem dapat menampilkan fallback deterministik.

Tidak ada proses reviewer khusus untuk hasil AI. Istilah **verifier** yang terdapat pada Tindak Lanjut adalah proses berbeda: verifier memeriksa bukti pekerjaan perbaikan, bukan memeriksa tulisan AI.

## Tahap H — Menjalankan tindak lanjut

1. Admin mengidentifikasi masalah dari dashboard atau sumber lain.
2. Admin membuat **finding**.
3. Admin membuat **action** untuk finding tersebut.
4. Action diberikan kepada PIC dan verifier yang berbeda.
5. PIC menerima pekerjaan, memperbarui progress, dan menambahkan evidence.
6. Jika progress 100% dan minimal satu evidence tersedia, PIC mengajukan verifikasi.
7. Verifier memilih:
   - **Verified**;
   - **Perlu revisi**; atau
   - **Ditolak**.
8. Jika perlu revisi, PIC memperbaiki pekerjaan dan mengajukan ulang.
9. Jika semua action pada finding sudah verified, finding otomatis menjadi verified.

---

## 4. Perbedaan empat peran

Dalam panduan ini, istilah **Admin** berarti role teknis `admin_lpmpp`, sedangkan **Leader** berarti role `leader`.

| Kemampuan                                 |      Super Admin       |      Admin LPMPP       |         Leader         | Respondent |
| ----------------------------------------- | :--------------------: | :--------------------: | :--------------------: | :--------: |
| Masuk ke aplikasi utama                   |           Ya           |           Ya           |           Ya           |     Ya     |
| Masuk ke Panel Administrasi               |           Ya           |           Ya           |        Ya, baca        |   Tidak    |
| Mengelola seluruh unit organisasi         |           Ya           |           Ya           |         Tidak          |
| Mengelola pengguna                        |           Ya           |     Ya secara izin     |         Lihat          |   Tidak    |
| Mengelola role                            |           Ya           |         Tidak          |         Tidak          |   Tidak    |
| Melihat daftar permission                 |           Ya           |         Tidak          |         Tidak          |   Tidak    |
| Membuat dan mengubah formulir             |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Menyetujui formulir                       |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Membuat, mengubah, dan menerbitkan survei |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Mengisi survei                            | Tidak sebagai role ini | Tidak sebagai role ini | Tidak sebagai role ini |     Ya     |
| Menjalankan dan merilis analisis          |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Membaca dashboard                         |           Ya           |           Ya           |           Ya           |   Tidak    |
| Mengekspor CSV/JSON/PDF                   |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Mengatur dan menjalankan AI               |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Membaca hasil AI                          |           Ya           |           Ya           |           Ya           |   Tidak    |
| Membuat finding dan action                |           Ya           |           Ya           |         Tidak          |   Tidak    |
| Memperbarui action                        |    Jika menjadi PIC    |    Jika menjadi PIC    |         Tidak          |   Tidak    |
| Memverifikasi action                      | Jika menjadi verifier  | Jika menjadi verifier  |         Tidak          |   Tidak    |
| Memantau dashboard tindak lanjut          |           Ya           |           Ya           |           Ya           |   Tidak    |
| Melihat notifikasi                        |           Ya           |           Ya           |           Ya           |     Ya     |

### Catatan tentang menu dan permission

- Super Admin dan Admin LPMPP sama-sama memiliki cakupan organisasi seluruh unit.
- Perbedaan utamanya: hanya Super Admin yang mengelola role, melihat permission, dan melihat Horizon/antrean sistem.
- Menu Panel Administrasi untuk Admin LPMPP sengaja dibuat lebih ringkas dan berfokus pada survei. Beberapa hak teknis pengelolaan pengguna/unit ada pada role, tetapi menu normalnya dipusatkan pada Super Admin.
- Leader memiliki akses baca. Leader tidak seharusnya mengubah konfigurasi, membuat analisis, menjalankan AI, mengekspor, atau mengubah tindak lanjut.
- Respondent memperoleh akses pengisian melalui role khusus responden, bukan melalui permission administrasi.

---

## 5. Panduan Super Admin

## Tujuan peran

Super Admin menjaga konfigurasi sistem, pengguna, role, unit organisasi, dan dapat menjalankan seluruh fungsi operasional Admin LPMPP.

## Cara menggunakan dari awal sampai akhir

### 1. Masuk

1. Buka halaman login.
2. Masukkan ID akun dan kata sandi Super Admin.
3. Setelah berhasil, aplikasi mengarahkan ke Dashboard Hasil Survei.
4. Pilih **Panel Administrasi** untuk membuka area pengelolaan.

### 2. Siapkan unit organisasi

1. Buka **Pengaturan Sistem → Unit Organisasi**.
2. Pastikan struktur induk dan anak sudah benar.
3. Isi kode, nama, tipe unit, unit induk, dan status aktif.

Contoh struktur:

```text
Institut
├── LPMPP
├── Fakultas Teknologi Kedirgantaraan
│   ├── Program Studi A
│   └── Program Studi B
└── Unit Teknologi Informasi
```

### 3. Kelola pengguna

1. Buka **Pengaturan Sistem → Pengguna**.
2. Tambahkan atau ubah pengguna.
3. Tentukan nama, nomor identitas, jenis akun, email internal, role, unit, dan status aktif.
4. Jangan memberikan role Super Admin kepada pengguna yang tidak membutuhkannya.

### 4. Kelola role

1. Buka **Pengaturan Sistem → Peran**.
2. Lihat role dan hak aksesnya.
3. Role `super_admin` tidak dapat diubah dari halaman ini.
4. Role tidak dapat dihapus melalui aplikasi.

### 5. Jalankan siklus survei

Super Admin dapat mengikuti seluruh langkah Admin LPMPP:

1. buat formulir;
2. ajukan dan setujui formulir;
3. buat survei;
4. periksa kesiapan;
5. setujui dan publikasikan;
6. pantau partisipasi;
7. tutup survei;
8. jalankan/rilis analisis;
9. lihat dan ekspor dashboard;
10. buat ringkasan AI;
11. buat dan pantau tindak lanjut.

### 6. Periksa sistem

Super Admin dapat melihat status aplikasi dan Horizon untuk memastikan proses antrean seperti analisis, AI, notifikasi, serta ekspor berjalan.

## Batasan penting

- Hak akses penuh tidak menghilangkan aturan bisnis.
- Pada analisis, peminta analisis tidak boleh menjadi perilis snapshot yang sama.
- Pada Tindak Lanjut, hanya PIC yang ditunjuk dapat memperbarui action.
- Hanya verifier yang ditunjuk dan berbeda dari PIC yang dapat memberikan keputusan.

---

## 6. Panduan Admin LPMPP

## Tujuan peran

Admin LPMPP adalah operator utama survei mutu: membuat instrumen, menerbitkan survei, membaca hasil, menjalankan AI, dan mengelola tindak lanjut.

## Cara menggunakan dari awal sampai akhir

### 1. Masuk dan buka Panel Administrasi

1. Login menggunakan akun Admin LPMPP.
2. Dari menu aplikasi utama, pilih **Panel Administrasi**.
3. Menu utama yang digunakan sehari-hari adalah:
   - Dashboard Mutu;
   - Buat Formulir;
   - Formulir Saya;
   - Kelola Survei;
   - Buka Dashboard Hasil Survei.

### 2. Buat dan setujui formulir

1. Pilih **Buat Formulir**.
2. Susun bagian dan pertanyaan.
3. Simpan.
4. Buka **Formulir Saya**.
5. Ajukan untuk diperiksa.
6. Setujui jika sudah benar atau kembalikan jika perlu revisi.

### 3. Buat dan publikasikan survei

1. Buka **Kelola Survei → Buat**.
2. Pilih formulir yang sudah disetujui.
3. Atur periode, unit, sasaran, jadwal, privasi, dan batas minimum laporan.
4. Jalankan **Periksa kesiapan**.
5. Kirim untuk review.
6. Setujui dan publikasikan.

### 4. Pantau pengisian

- Lihat jumlah jawaban pada daftar survei.
- Pastikan survei berstatus aktif selama periode pengisian.
- Jangan meminta atau menampilkan jawaban individual kepada pimpinan.
- Setelah masa pengisian selesai, tutup survei.

### 5. Analisis dan dashboard

1. Jalankan analisis setelah survei ditutup.
2. Gunakan akun lain yang berizin untuk merilis snapshot.
3. Buka **Dashboard Hasil Survei**.
4. Terapkan filter yang diperlukan.
5. Unduh CSV untuk pengolahan data atau PDF untuk dibaca/dipresentasikan.

### 6. Analisis AI

1. Buka **Analisis AI**.
2. Konfigurasikan provider hanya jika memang ditugaskan.
3. Uji koneksi.
4. Pilih template prompt.
5. Buat ringkasan dari hasil survei yang sudah dirilis.
6. Baca kembali hasil AI sebelum dipakai dalam keputusan.

### 7. Tindak lanjut

1. Buat finding dari masalah yang ditemukan.
2. Buat action yang konkret.
3. Pilih PIC dan verifier yang berbeda.
4. Pantau progress dan tenggat.
5. Pastikan evidence relevan dan dapat diakses.
6. Tutup siklus setelah pekerjaan verified.

---

## 7. Panduan Leader/Pimpinan

## Tujuan peran

Leader menggunakan aplikasi untuk **membaca kondisi mutu dan memantau perbaikan**, bukan untuk mengubah data operasional.

## Cara menggunakan dari awal sampai akhir

### 1. Masuk

1. Login menggunakan akun Leader.
2. Aplikasi mengarahkan ke **Dashboard Hasil Survei**.

### 2. Membaca dashboard

1. Pilih survei yang ingin dibaca.
2. Periksa skor keseluruhan dan response rate.
3. Bandingkan skor kategori.
4. Baca tren jika sistem menyatakan perbandingan memenuhi syarat.
5. Pilih pertanyaan untuk melihat distribusi jawaban agregat.
6. Baca catatan interpretasi dan keterbatasan.

Leader tidak melihat identitas responden atau rangkaian jawaban individu.

### 3. Membaca Analisis AI

1. Buka **Analisis AI**.
2. Pilih riwayat analisis.
3. Klik **Tampilkan hasil**.
4. Baca ringkasan, topik, sentimen, tren, rekomendasi, dan keterbatasan.

Leader tidak dapat mengatur API key, membuat prompt, atau menjalankan job AI.

### 4. Memantau Tindak Lanjut

1. Buka **Tindak Lanjut**.
2. Lihat total action, action terlambat, action menunggu verifikasi, dan action yang perlu revisi.
3. Gunakan filter status.
4. Buka action untuk melihat PIC, verifier, progress, tenggat, evidence, dan riwayat keputusan.

Leader hanya memantau. Leader tidak membuat finding, tidak mengubah progress, dan tidak memverifikasi action.

### 5. Panel Administrasi

Leader dapat masuk ke Panel Administrasi untuk membaca data yang diizinkan, seperti unit, pengguna, formulir, validasi, dan survei. Tombol perubahan tidak tersedia karena role ini bersifat baca.

---

## 8. Panduan Respondent/Responden

## Tujuan peran

Respondent mengisi survei yang sesuai dengan kelompok atau unitnya dan dapat melihat status partisipasi tanpa membuka kembali isi jawaban.

## Cara menggunakan dari awal sampai akhir

### 1. Membuat akun

Jika belum memiliki akun:

1. Buka **Buat Akun**.
2. Isi nama.
3. Pilih jenis akun mahasiswa atau dosen.
4. Isi NIM/nomor dosen.
5. Pilih program studi.
6. Buat kata sandi dan konfirmasi.
7. Setelah berhasil, aplikasi mengarahkan ke daftar survei.

### 2. Masuk

1. Buka halaman login.
2. Masukkan NIM, nomor dosen, atau ID akun.
3. Masukkan kata sandi.
4. Pilih **Ingat sesi** hanya pada perangkat pribadi.

### 3. Memilih survei

1. Buka **Survei Saya**.
2. Daftar hanya menampilkan survei aktif yang sesuai dengan unit/kelompok responden.
3. Pilih **Lihat detail** pada survei yang ingin diisi.

### 4. Memberikan persetujuan

1. Baca jadwal dan pemberitahuan privasi.
2. Perhatikan apakah mode survei anonim atau rahasia.
3. Centang persetujuan partisipasi.
4. Mulai pengisian.

### 5. Mengisi jawaban

- Jawab setiap pertanyaan sesuai kondisi sebenarnya.
- Pertanyaan bertanda **Wajib** harus diisi.
- Sistem menampilkan progress pengisian.
- Sistem menyimpan draf otomatis.
- Jika jaringan gagal, draf lokal tetap dipertahankan dan dapat dicoba disimpan kembali.
- Draf hanya dapat dipulihkan dari sesi/perangkat yang diizinkan.

### 6. Mengirim jawaban

1. Setelah semua pertanyaan wajib terisi, tinjau jawaban.
2. Pastikan status penyimpanan menunjukkan **Tersimpan**.
3. Konfirmasikan pengiriman.
4. Simpan kode konfirmasi yang ditampilkan.

Kode konfirmasi membuktikan respons diterima, tetapi tidak dapat digunakan untuk melihat isi jawaban.

### 7. Melihat riwayat

Buka **Riwayat Partisipasi**. Halaman ini hanya menampilkan status:

- `eligible`: boleh mengisi;
- `in_progress`: sedang dikerjakan;
- `completed`: sudah dikirim;
- `declined`: menolak partisipasi.

Riwayat tidak menampilkan isi jawaban.

---

## 9. Penjelasan lengkap Tindak Lanjut

## Apa fungsi Tindak Lanjut?

Tindak Lanjut adalah modul untuk menjawab pertanyaan:

> “Setelah hasil survei menunjukkan masalah, siapa yang harus memperbaiki, apa yang dikerjakan, kapan selesai, apa buktinya, dan siapa yang memastikan pekerjaan tersebut benar-benar selesai?”

Tanpa modul ini, hasil survei hanya menjadi angka dan laporan. Dengan modul ini, hasil survei dapat diubah menjadi pekerjaan perbaikan yang memiliki penanggung jawab dan bukti.

## Istilah yang digunakan

### 1. Finding

**Finding** adalah masalah atau temuan yang perlu ditangani.

Contoh:

> “Skor kecepatan respons layanan hanya 58 dan berada di bawah target mutu.”

Finding berisi:

- unit yang bertanggung jawab;
- judul masalah;
- deskripsi masalah;
- dasar/evidence sumber;
- tingkat keparahan;
- tenggat penyelesaian.

Finding dapat berasal dari:

- `manual`: dibuat berdasarkan rapat, audit, keluhan, atau pengamatan; atau
- `low_indicator`: berasal dari indikator snapshot released yang memiliki skor rendah dan tidak disembunyikan.

Form frontend saat ini menyediakan pembuatan finding manual. Dukungan sumber indikator rendah sudah tersedia di backend.

### 2. Action

**Action** adalah pekerjaan konkret untuk menyelesaikan finding.

Satu finding dapat memiliki satu atau beberapa action.

Contoh finding:

> “Respons pengaduan mahasiswa lambat.”

Contoh action:

- menyusun standar waktu respons maksimal 2 hari kerja;
- membuat sistem tiket pengaduan;
- melatih petugas layanan;
- melakukan evaluasi waktu respons setiap bulan.

Action berisi:

- judul pekerjaan;
- PIC;
- verifier;
- root cause;
- rencana pekerjaan;
- output yang diharapkan;
- kebutuhan sumber daya;
- tenggat.

### 3. PIC

PIC adalah orang yang benar-benar mengerjakan action.

PIC bertugas:

- menerima atau menolak assignment;
- memperbarui root cause dan rencana;
- mengubah progress;
- menambahkan evidence;
- mengajukan verifikasi setelah selesai.

Memiliki permission `action.update` saja belum cukup. Pengguna harus benar-benar dipilih sebagai PIC pada action tersebut.

### 4. Verifier

Verifier adalah orang yang memeriksa apakah action benar-benar selesai dan evidence-nya memadai.

Verifier harus berbeda dari PIC. Tujuannya agar orang yang mengerjakan tidak mengesahkan pekerjaannya sendiri.

Verifier dapat memutuskan:

- **Verified**: pekerjaan dan evidence diterima;
- **Perlu revisi**: PIC harus memperbaiki atau menambah bukti;
- **Ditolak**: pekerjaan tidak diterima.

Memiliki permission `action.verify` saja belum cukup. Pengguna harus dipilih sebagai verifier pada action tersebut.

### 5. Evidence

Evidence adalah bukti bahwa pekerjaan benar-benar dilakukan.

Evidence terdiri dari:

- judul;
- deskripsi;
- URL HTTPS opsional;
- versi evidence;
- checksum untuk membantu mendeteksi perubahan data.

Contoh evidence:

- nomor dan deskripsi SOP baru;
- notulen rapat;
- tangkapan layar sistem;
- surat keputusan;
- daftar hadir pelatihan;
- laporan hasil evaluasi;
- tautan dokumen resmi kampus.

## Cara memakai halaman Tindak Lanjut

### Bagian atas halaman

Halaman menampilkan empat angka:

- **Total action**: seluruh pekerjaan perbaikan;
- **Terlambat**: tenggat lewat tetapi pekerjaan belum verified atau rejected;
- **Menunggu verifikasi**: sudah diajukan PIC dan menunggu verifier;
- **Perlu revisi**: verifier meminta perbaikan.

### Daftar finding

Tabel menampilkan:

- kode finding;
- judul;
- unit;
- status;
- tenggat;
- action yang berada di bawah finding tersebut.

Klik judul action untuk membuka detail pekerjaan.

### Form Buat finding manual

Isi dengan pola berikut:

- **Judul:** kalimat pendek tentang masalah.
- **Deskripsi:** jelaskan masalah, dampak, dan konteks.
- **Dasar/evidence sumber:** sebutkan data atau dokumen yang membuktikan masalah.
- **Severity:** pilih tingkat dampak.
- **Tenggat:** batas waktu finding harus ditangani.

### Form Assign action

Isi dengan pola berikut:

- **Finding:** masalah yang akan ditangani.
- **PIC:** pelaksana pekerjaan.
- **Verifier:** pemeriksa hasil, harus berbeda dari PIC.
- **Judul action:** pekerjaan konkret.
- **Root cause:** penyebab utama masalah, bukan hanya gejalanya.
- **Rencana:** langkah pekerjaan yang akan dilakukan.
- **Output yang diharapkan:** hasil nyata yang dapat diperiksa.
- **Kebutuhan sumber daya:** orang, biaya, aplikasi, fasilitas, atau dokumen yang diperlukan.
- **Tenggat:** batas waktu action.

### Halaman detail action

Halaman detail menampilkan status, PIC, verifier, progress, tenggat, pekerjaan PIC, evidence, dan keputusan verifikasi.

PIC menjalankan urutan berikut:

1. pilih **Terima**;
2. perbarui root cause/rencana jika diperlukan;
3. ubah progress dan simpan;
4. tambahkan minimal satu evidence;
5. ubah progress menjadi 100%;
6. pilih **Ajukan verifikasi**.

Verifier kemudian membaca pekerjaan dan evidence, lalu menyimpan keputusan.

---

## 10. Fungsi kolom URL pada evidence

Kolom **URL HTTPS** adalah tempat untuk menyimpan tautan menuju bukti yang berada di luar aplikasi.

Contoh penggunaan:

- tautan Google Drive atau Microsoft SharePoint kampus;
- tautan SOP pada sistem dokumen kampus;
- tautan berita acara atau notulen resmi;
- tautan halaman sistem tiket yang sudah diterapkan;
- tautan repositori dokumen mutu.

Contoh nilai:

```text
https://drive.google.com/...
https://sharepoint.kampus.ac.id/...
https://dokumen.kampus.ac.id/sop/SOP-LAYANAN-2026
```

### Hal yang perlu dipahami

- Kolom URL **opsional**. Jika tidak ada dokumen daring, boleh dikosongkan.
- Sistem saat ini tidak mengunggah file melalui kolom tersebut. Sistem hanya menyimpan tautannya.
- URL harus menggunakan `https://`.
- Tautan harus dapat dibuka oleh pihak kampus yang berwenang.
- Jangan menaruh tautan publik yang berisi data pribadi responden.
- Jangan menggunakan alamat file lokal seperti `C:\Dokumen\bukti.pdf` karena hanya dapat dibuka pada satu komputer.
- Jika dokumen bersifat rahasia, atur izin akses pada Google Drive, SharePoint, atau sistem dokumen yang digunakan.
- Tetap isi deskripsi evidence dengan jelas walaupun sudah menyertakan URL.

### Mengapa URL digunakan?

Dokumen bukti sering kali sudah disimpan di sistem kampus. Menyimpan URL mencegah duplikasi file dan memudahkan verifier membuka sumber resmi. Modul Tindak Lanjut tetap menyimpan judul, deskripsi, versi, dan checksum evidence sebagai jejak audit.

---

## 11. Contoh Tindak Lanjut yang mudah dipahami

## Kasus

Dashboard menunjukkan kategori **Kecepatan Respons** memperoleh skor 58, sedangkan target mutu kampus adalah minimal 75.

## A. Finding

| Kolom                 | Contoh isi                                                                                                                                |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Unit                  | Unit Layanan Akademik                                                                                                                     |
| Judul                 | Waktu respons layanan akademik belum memenuhi target                                                                                      |
| Deskripsi             | Mahasiswa menilai jawaban atas pertanyaan dan keluhan masih lambat. Kondisi ini dapat menurunkan kepuasan dan menghambat proses akademik. |
| Dasar/evidence sumber | Dashboard Survei Kepuasan Mahasiswa Semester Genap 2025/2026 menunjukkan skor Kecepatan Respons 58 dari 448 respons valid.                |
| Severity              | high                                                                                                                                      |
| Tenggat               | 30 September 2026                                                                                                                         |

## B. Action

| Kolom                  | Contoh isi                                                                                                                                         |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Judul action           | Menetapkan dan menerapkan standar waktu respons layanan                                                                                            |
| PIC                    | Kepala Unit Layanan Akademik                                                                                                                       |
| Verifier               | Auditor Mutu Internal/pejabat yang ditunjuk                                                                                                        |
| Root cause             | Belum ada standar waktu respons, pencatatan permintaan masih tersebar, dan pembagian petugas belum jelas.                                          |
| Rencana                | Memetakan kanal layanan, menyusun SLA maksimal dua hari kerja, menetapkan petugas, menerapkan log/tiket, dan mengevaluasi hasil selama satu bulan. |
| Output yang diharapkan | SOP layanan disahkan, sistem pencatatan aktif, dan minimal 90% permintaan dijawab maksimal dua hari kerja.                                         |
| Kebutuhan sumber daya  | Waktu tim, persetujuan pimpinan, aplikasi tiket, dan pelatihan petugas.                                                                            |
| Tenggat                | 15 September 2026                                                                                                                                  |

## C. Evidence pertama

| Kolom     | Contoh isi                                                                                                             |
| --------- | ---------------------------------------------------------------------------------------------------------------------- |
| Judul     | SOP waktu respons layanan akademik                                                                                     |
| Deskripsi | SOP telah disahkan dan menetapkan waktu respons maksimal dua hari kerja beserta penanggung jawab setiap kanal layanan. |
| URL HTTPS | Tautan dokumen SOP resmi di sistem dokumen kampus                                                                      |

## D. Evidence kedua

| Kolom     | Contoh isi                                                            |
| --------- | --------------------------------------------------------------------- |
| Judul     | Laporan penerapan satu bulan                                          |
| Deskripsi | Dari 120 tiket, 112 tiket atau 93,3% dijawab maksimal dua hari kerja. |
| URL HTTPS | Tautan laporan rekap sistem tiket                                     |

## E. Keputusan verifier

| Kolom           | Contoh isi                                                                        |
| --------------- | --------------------------------------------------------------------------------- |
| Keputusan       | Verified                                                                          |
| Alasan          | SOP telah disahkan dan target waktu respons telah diterapkan.                     |
| Review evidence | Dokumen SOP valid, log tiket dapat dibuka, dan capaian 93,3% melebihi target 90%. |

Dengan contoh ini, hubungan antarbagian menjadi jelas:

```text
Nilai survei rendah
→ finding menjelaskan masalah
→ action menjelaskan pekerjaan
→ PIC melaksanakan pekerjaan
→ evidence membuktikan pekerjaan
→ verifier memastikan hasilnya valid
```

---

## 12. Status-status penting

## Status formulir

| Status      | Arti                                |
| ----------- | ----------------------------------- |
| `draft`     | Masih disusun dan dapat diubah      |
| `in_review` | Menunggu pemeriksaan                |
| `returned`  | Dikembalikan untuk diperbaiki       |
| `approved`  | Sudah disetujui dan dapat digunakan |

## Status survei

| Status      | Arti                                   |
| ----------- | -------------------------------------- |
| `draft`     | Konfigurasi awal                       |
| `in_review` | Menunggu pemeriksaan                   |
| `returned`  | Perlu diperbaiki                       |
| `approved`  | Sudah disetujui tetapi belum terbit    |
| `scheduled` | Sudah terbit dan menunggu jadwal mulai |
| `active`    | Sedang dapat diisi                     |
| `closed`    | Pengisian ditutup dan dapat dianalisis |
| `archived`  | Survei disimpan sebagai arsip          |

## Status finding

| Status        | Arti                                           |
| ------------- | ---------------------------------------------- |
| `open`        | Temuan baru dan belum memiliki pekerjaan aktif |
| `in_progress` | Sudah memiliki action yang sedang ditangani    |
| `verified`    | Semua action pada finding telah verified       |

## Status action

| Status                 | Arti                      | Tindakan berikutnya                      |
| ---------------------- | ------------------------- | ---------------------------------------- |
| `assigned`             | Baru ditugaskan           | PIC menerima atau menolak                |
| `accepted`             | PIC menerima              | Mulai bekerja dan memperbarui progress   |
| `in_progress`          | Sedang dikerjakan         | Tambah evidence dan selesaikan pekerjaan |
| `pending_verification` | Diajukan ke verifier      | Verifier memeriksa                       |
| `needs_revision`       | Perlu diperbaiki          | PIC memperbaiki dan menambah evidence    |
| `verified`             | Diterima verifier         | Selesai                                  |
| `rejected`             | Ditolak PIC atau verifier | Perlu keputusan lanjutan dari pengelola  |

---

## 13. Catatan implementasi saat ini

Bagian ini penting agar penjelasan kepada dosen sesuai kondisi aplikasi yang benar-benar tersedia.

1. **Analisis statistik dan rilis snapshot**  
   Backend sudah menyediakan proses analisis dan rilis. Namun, tombol visual untuk membuat analysis run dan merilis snapshot baru belum tersedia di frontend/Panel Administrasi.

2. **Dashboard hanya memakai hasil released**  
   Survei aktif tetap dapat dipilih untuk melihat status dan jumlah respons, tetapi skor baru muncul setelah analisis selesai dan snapshot dirilis.

3. **Ekspor tersedia untuk Super Admin dan Admin LPMPP**  
   Format yang tersedia saat ini adalah CSV, JSON, dan PDF. Leader hanya membaca dashboard karena tidak memiliki permission ekspor.

4. **Hasil AI bukan keputusan otomatis**  
   Hasil AI harus dibaca kembali. Statistik agregat sistem tetap menjadi sumber utama.

5. **Riwayat evidence dan verifikasi pada Tindak Lanjut**  
   Data sudah tersimpan terstruktur, tetapi tampilan detail saat ini masih menampilkan riwayat tersebut dalam bentuk JSON.

6. **Kolom URL bukan upload file**  
   Kolom URL hanya mereferensikan dokumen HTTPS yang disimpan di luar aplikasi.

7. **Verifier Tindak Lanjut tetap diperlukan**  
   Verifier berbeda dari reviewer AI. Verifier diperlukan untuk memastikan pekerjaan PIC memiliki bukti dan tidak disahkan oleh orang yang sama.

8. **Notifikasi**  
   Semua role dapat melihat notifikasi. Tombol menandai notifikasi sebagai dibaca saat ini hanya tersedia untuk Respondent.

---

## 14. Naskah singkat untuk presentasi

## Versi sekitar satu menit

> SIMUTU LPMPP adalah sistem yang mengelola siklus survei mutu dari pembuatan instrumen sampai tindak lanjut. Admin membuat dan menerbitkan survei, responden mengisi dengan penyimpanan otomatis, lalu sistem menghitung hasil secara agregat agar identitas dan jawaban individu tidak ditampilkan kepada pimpinan. Pimpinan membaca dashboard, sedangkan AI hanya membantu merangkum hasil yang sudah dihitung sistem. Jika ditemukan masalah, Admin membuat finding dan action. PIC mengerjakan action serta menambahkan evidence, kemudian verifier yang berbeda memeriksa hasilnya. Jadi aplikasi tidak berhenti pada laporan survei, tetapi memastikan ada perbaikan yang memiliki penanggung jawab, tenggat, bukti, dan verifikasi.

## Susunan presentasi sekitar lima menit

### Slide 1 — Masalah yang diselesaikan

- Survei sering berhenti menjadi laporan.
- Sulit mengetahui siapa yang harus memperbaiki masalah.
- Bukti tindak lanjut sering tersebar.

### Slide 2 — Solusi SIMUTU

- mengelola instrumen dan survei;
- mengumpulkan respons secara aman;
- menampilkan analitik agregat;
- membantu ringkasan melalui AI;
- mengelola finding, action, evidence, dan verifikasi.

### Slide 3 — Empat role

- Super Admin: konfigurasi sistem dan seluruh fungsi.
- Admin LPMPP: operator siklus survei dan perbaikan mutu.
- Leader: membaca hasil dan memantau tindak lanjut.
- Respondent: mengisi survei dan melihat status partisipasi.

### Slide 4 — Privasi

- jawaban individual tidak tampil pada pimpinan;
- dashboard menggunakan agregat;
- terdapat batas minimum pelaporan;
- kode konfirmasi responden tidak membuka isi jawaban.

### Slide 5 — Tindak Lanjut

- finding = masalah;
- action = pekerjaan perbaikan;
- PIC = pelaksana;
- evidence = bukti;
- verifier = pemeriksa independen.

### Slide 6 — Fungsi URL

- URL adalah tautan ke dokumen bukti resmi;
- bukan tempat mengunggah file;
- sifatnya opsional;
- harus HTTPS dan aksesnya harus diatur oleh kampus.

### Slide 7 — Nilai utama aplikasi

> “SIMUTU menghubungkan suara responden, pengambilan keputusan pimpinan, dan bukti perbaikan mutu dalam satu alur yang dapat diaudit.”

---

## 15. Lampiran hak akses teknis

## Super Admin

Super Admin memperoleh seluruh permission berikut:

- akses Panel Administrasi, status sistem, dan Horizon;
- akses seluruh scope organisasi;
- lihat/tambah/ubah/hapus unit organisasi;
- lihat/tambah/ubah/hapus pengguna;
- lihat/tambah/ubah role dan melihat permission;
- lihat/tambah/ubah/hapus template;
- buat/baca/ubah/setujui validasi instrumen;
- baca/tambah/ubah/hapus/tinjau/setujui/terbitkan survei;
- kelola populasi;
- jalankan/baca/rilis analisis;
- baca/buat/ekspor/setujui laporan;
- atur/jalankan/baca AI;
- baca notifikasi;
- baca/tambah/ubah finding;
- tambah/baca/ubah/verifikasi action;
- baca dashboard tindak lanjut.

## Admin LPMPP

Admin LPMPP memperoleh seluruh permission Super Admin kecuali:

- melihat daftar permission;
- melihat, membuat, atau mengubah role;
- melihat Horizon.

Admin LPMPP tetap memiliki akses seluruh scope organisasi serta fungsi operasional survei, analisis, laporan, AI, dan tindak lanjut.

## Leader

Leader memperoleh permission baca berikut:

- akses Panel Administrasi;
- melihat status sistem;
- melihat unit organisasi;
- melihat pengguna;
- membaca template;
- membaca validasi;
- membaca survei;
- membaca analisis;
- membaca laporan/dashboard;
- membaca hasil AI;
- membaca notifikasi;
- membaca finding;
- membaca action;
- membaca dashboard tindak lanjut.

Leader tidak memiliki permission membuat, mengubah, menghapus, menjalankan, merilis, mengekspor, mengatur AI, atau memverifikasi.

## Respondent

Respondent memiliki permission `notification.read` dan akses berbasis role untuk:

- melihat survei yang eligible;
- melihat detail survei;
- memulai sesi pengisian;
- mengisi dan mengirim respons;
- melihat riwayat partisipasi;
- melihat serta menandai notifikasi sebagai dibaca.

Respondent tidak dapat membuka dashboard pimpinan, Panel Administrasi, AI, atau Tindak Lanjut.

---

## Ringkasan paling sederhana

Jika hanya ingin mengingat lima istilah utama, gunakan ini:

| Istilah   | Arti sederhana                                            |
| --------- | --------------------------------------------------------- |
| Survei    | Alat untuk mengumpulkan penilaian responden               |
| Dashboard | Tempat membaca hasil agregat                              |
| AI        | Asisten untuk merangkum hasil, bukan penghitung statistik |
| Finding   | Masalah yang ditemukan                                    |
| Action    | Pekerjaan nyata untuk menyelesaikan masalah               |

Dan untuk Tindak Lanjut:

```text
Finding = apa masalahnya
Action = apa yang dikerjakan
PIC = siapa yang mengerjakan
Evidence = apa buktinya
Verifier = siapa yang memastikan
```

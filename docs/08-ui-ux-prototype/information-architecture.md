# Information Architecture dan Navigation Model

Status: Phase 08 design baseline — 2026-08-07  
Scope: clickable prototype berbasis fixture; bukan kontrak implementasi production.

## 1. Prinsip arsitektur informasi

1. Navigasi mengikuti pekerjaan aktor, bukan struktur tabel database.
2. Responden memperoleh jalur singkat: temukan survei → pahami tujuan/privasi → isi → konfirmasi.
3. Admin LPMPP memisahkan pekerjaan desain instrumen, operasi campaign, hasil, dan tindak lanjut.
4. Pimpinan hanya melihat agregat yang sudah dirilis, berada dalam scope unit, dan lolos minimum reporting threshold.
5. Fitur AI selalu diberi label simulasi/draft, tidak menjadi jalur wajib, dan tidak menampilkan data mentah.
6. Status, periode, unit, dan mode identitas ditampilkan dekat objek yang dipengaruhi.
7. Prototype tidak mengirim request ke API, database, email, atau provider AI.

## 2. Model konten

| Objek | Relasi pengguna | Informasi utama pada UI |
|---|---|---|
| Survei | dicari, dibuka, diisi, dikelola | judul, tujuan, periode, status, estimasi, mode identitas |
| Instrumen | dirancang dan direview | versi, bagian, indikator, item, skala, metode |
| Respons | disimpan sebagai draft lalu disubmit | progres, status autosave, validasi, bukti submit |
| Hasil agregat | dibaca admin/pimpinan | scope, N, response rate, skor, threshold, waktu rilis |
| Analisis | statistik atau AI opsional | metode, run, lineage, status review, keterbatasan |
| Temuan | diturunkan dari bukti | severity, owner, due date, status, evidensi |
| Laporan/export | dibuat dari snapshot | format, scope, threshold, status, kedaluwarsa |
| Konfigurasi AI | dikelola terbatas | provider terdaftar, model, secret masked, status; tidak pernah reveal |

## 3. Sitemap per role

### 3.1 Responden

```text
Masuk
└── Beranda
    ├── Survei Saya
    │   ├── Daftar survei
    │   ├── Detail survei
    │   └── Pengisian
    │       ├── Bagian/item
    │       ├── Ringkasan validasi
    │       └── Konfirmasi dan bukti submit
    └── Riwayat partisipasi (status saja; bukan isi jawaban)
```

### 3.2 Admin LPMPP

```text
Masuk
└── Ikhtisar Admin (mock/reference Filament)
    ├── Survei
    │   ├── Builder instrumen
    │   ├── Review dan publikasi (reference only)
    │   └── Monitoring respons
    ├── Analitik
    │   ├── Hasil survei
    │   └── Analisis AI (simulasi, post-MVP)
    ├── Tindak Lanjut
    ├── Laporan
    └── Pengaturan
        └── Konfigurasi AI (masked, simulasi)
```

### 3.3 Pimpinan

```text
Masuk
└── Dashboard Pimpinan
    ├── Filter periode dan unit dalam scope
    ├── KPI teragregasi
    ├── Indikator prioritas
    ├── Tindak lanjut unit
    └── Laporan yang sudah dirilis
```

### 3.4 Reviewer/verifikator/PIC

```text
Masuk
└── Ruang Kerja
    ├── Review instrumen (post-MVP/reference)
    ├── Temuan yang ditugaskan
    ├── Aksi dan unggah evidensi (post-MVP/reference)
    └── Verifikasi tindak lanjut (post-MVP/reference)
```

Super Admin tidak diberi menu isi respons mentah. Konfigurasi platform, pengguna, dan scope tetap direncanakan pada Filament production dan tidak diprototipekan sebagai akses tanpa batas.

## 4. Navigation model

| Area | Desktop | Smartphone | Aturan |
|---|---|---|---|
| Global | sidebar tetap + top bar | top bar + drawer native | satu item aktif; label selalu terlihat di drawer |
| Context | breadcrumb singkat | judul halaman + tombol kembali | maksimum tiga tingkat |
| In-page | tab/segmented control bila setara | horizontal scroll hanya untuk tab | bukan pengganti navigasi global |
| Primary action | kanan atas area judul | sticky action area tidak dipakai pada prototype | satu aksi utama per view |
| Role switch | kontrol demo pada top bar | kontrol demo pada drawer | hanya fixture; tidak mewakili impersonation production |

### 4.1 Label navigasi

Gunakan kata benda untuk destination (`Survei`, `Hasil`, `Laporan`) dan kata kerja untuk aksi (`Mulai isi`, `Simpan draft`, `Kirim respons`). Hindari label abstrak seperti “Kelola” tanpa objek.

### 4.2 Deep link prototype

| Jalur | Layar |
|---|---|
| `/login` | login fixture |
| `/respondent` | dashboard responden |
| `/surveys` dan `/surveys/:id` | daftar/detail survei |
| `/responses/:id` | form pengisian |
| `/admin` | overview Admin LPMPP mock |
| `/builder` | survey builder |
| `/monitoring` | monitoring respons |
| `/results` | hasil survei |
| `/leadership` | dashboard pimpinan |
| `/ai-analysis` | analisis AI berlabel |
| `/ai-config` | konfigurasi AI masked |
| `/follow-up` | temuan dan tindak lanjut |
| `/reports` | laporan/export mock |

## 5. Search, filter, dan density

- Pencarian lokal hanya menyaring fixture pada browser.
- Filter utama: periode, unit, status, keluarga survei; filter sensitif hanya tampil jika berwenang.
- Filter aktif selalu terlihat dan dapat di-reset.
- Tabel dipakai untuk perbandingan banyak baris; kartu dipakai untuk KPI atau daftar mobile.
- Kolom identitas responden tidak tersedia pada hasil/pimpinan.
- Sel `N < threshold` ditampilkan sebagai `Disembunyikan` dengan alasan, bukan nol.

## 6. Boundary Phase 08

Prototype membuktikan alur, hierarki informasi, state, dan bahasa antarmuka. Ia tidak membuktikan authorization server-side, persistence, scoring, suppression, export, autosave durable, integrasi SSO/SIAKAD, atau provider AI. Seluruh hal tersebut tetap mengikuti kontrak Phase 04–07 dan baru dapat dibuktikan oleh implementasi setelah Phase 08.

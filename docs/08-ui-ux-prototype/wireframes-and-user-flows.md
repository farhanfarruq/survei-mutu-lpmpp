# Textual Wireframes dan User Flows

Notasi: `[ ]` control, `( )` status/data, `→` aksi/navigasi. Semua angka/nama adalah fixture.

## 1. Dashboard responden

```text
+ Top bar: SIMUTU | Mode Prototype | [Ganti peran] | Pengguna Demo
+ Sidebar: Beranda (aktif), Survei Saya, Riwayat
+ Main
  Breadcrumb: Beranda
  H1 Selamat datang, Alya
  Notice: Data pada layar ini adalah fixture
  KPI: (2 survei aktif) (1 perlu dilanjutkan) (3 selesai)
  Section "Perlu Anda isi"
    Card survei: judul, periode, estimasi, mode Rahasia, progres 40%
    [Lanjutkan pengisian]
  Section "Survei lainnya"
    daftar ringkas + [Lihat semua survei]
```

Mobile: sidebar menjadi drawer, KPI satu kolom, CTA selebar card.

## 2. Daftar dan detail survei

```text
DAFTAR
H1 Survei Saya
[Cari survei] [Status: Semua] [Reset]
Result count live region
Card/table row: status, judul, periode, estimasi, progres, [Lihat detail]

DETAIL
Breadcrumb: Survei Saya / Detail
H1 Kepuasan Mahasiswa terhadap Layanan Akademik
Badge Aktif | Badge Rahasia
Tujuan, periode, estimasi, unit penyelenggara
Panel privasi: identitas partisipasi dipisahkan dari isi respons
Panel instrumen: 3 bagian, 12 item, skala 1–4
[Kembali] [Mulai/Lanjutkan pengisian]
```

## 3. Form pengisian survei

```text
Top: judul survei | Bagian 1 dari 3 | progres 33% | live autosave "Tersimpan 10.42"
Sidebar stepper: Informasi, Layanan Akademik, Saran, Tinjau
Main
  H1 Layanan Akademik
  Instruksi skala
  Fieldset ITEM-LA-01
    Legend pernyataan netral
    Radio 1 Sangat tidak baik ... 4 Sangat baik
    Error inline bila kosong
  Fieldset pasangan importance (jika metode IPA)
  Textarea komentar (Opsional)
  [Sebelumnya] [Simpan dan lanjutkan]
Footer: "Prototype—draft hanya hidup di tab ini"

TINJAU
Ringkasan bagian lengkap/tidak lengkap
Error summary berisi link ke item
[Perbaiki jawaban] [Kirim respons]
Dialog native: dampak final, privacy reminder, [Batal] [Ya, kirim]
Success: receipt mock dan [Kembali ke beranda]
```

## 4. Dashboard Admin LPMPP

```text
Banner: Mock/reference; operasi admin production direncanakan di Filament
H1 Ikhtisar Admin LPMPP
Filter: periode, unit dalam scope
KPI: survei aktif, response rate, respons final, tindak lanjut jatuh tempo
Panel "Perlu perhatian": response rate rendah, review tertunda
Tabel campaign: survei, unit, status, target, respons, rate, [Pantau]
Quick action: [Buka builder] [Lihat hasil] [Tindak lanjut]
```

## 5. Survey builder

```text
H1 Builder Instrumen (Prototype)
Meta: Draft v1.3 | metode SERVPERF + IPA | belum direview
Left outline: Bagian 1, Bagian 2 (aktif), + Tambah bagian
Canvas:
  Judul bagian [Layanan Akademik]
  Item cards (drag tidak diprototipekan)
    kode, indikator, required, tipe jawaban
    [Edit] [Duplikasi] [Hapus—confirmation]
  [+ Tambah pertanyaan]
Right inspector:
  [Kode] [Indikator] [Teks item] [Jenis jawaban] [Wajib]
  [Batalkan] [Terapkan ke draft]
Bottom: [Pratinjau] [Simpan draft lokal]
```

Builder Phase 08 tidak melakukan reorder drag-and-drop, version publication, atau persistence.

## 6. Monitoring respons

```text
H1 Monitoring Respons
Filter: periode, survei, unit, status
KPI: target 1.240, final 806, draft 127, rate 65,0%
Progress per unit (agregat partisipasi, bukan isi respons)
Tabel: unit, target, final, rate, status threshold, [Lihat tren]
Notice: reminder hanya simulasi; tidak ada email terkirim
[Simulasikan reminder] → dialog audience count → toast fixture
```

## 7. Dashboard visualisasi

```text
H1 Hasil Survei
Snapshot: dirilis 6 Agu 2026 16.00 | metode | N | threshold
Filter: periode, unit, kategori
KPI: indeks 82,4; response rate 65%; prioritas 3; tren +2,1
Bar chart sederhana: skor per kategori, label nilai
Matrix priority versi tabel: importance, performance, gap, kuadran
Small-cell row: "Disembunyikan untuk privasi"
[Lihat metodologi] [Ke laporan]
```

## 8. Halaman analisis AI

```text
Banner kuat: SIMULASI AI · POST-MVP · DRAFT · WAJIB REVIEW MANUSIA
H1 Analisis AI
Input summary: snapshot agregat, open text sudah disamarkan (fixture)
Guardrails: provider nyata OFF, redaction ON, budget mock
[Jalankan simulasi]
Status: queued → running → needs review
Output cards: tema, evidence count, confidence label, limitations
Checklist reviewer: akurat, tidak mengungkap identitas, layak rilis
[Tolak draft] [Tandai direview—simulasi]
```

## 9. Dashboard pimpinan

```text
H1 Dashboard Pimpinan
Scope notice: hanya agregat dirilis dalam unit yang diizinkan
[Periode] [Unit: Universitas/Fakultas A/Fakultas B]
KPI berubah sesuai fixture unit
Trend ringkas dan top 3 prioritas
Tabel unit: N, skor, response rate, tindak lanjut; small cell disuppress
Section "Komitmen perbaikan": selesai, berjalan, terlambat
[Buka laporan yang dirilis]
```

## 10. Konfigurasi AI

```text
Banner: Prototype—tidak terhubung provider nyata
H1 Konfigurasi AI
Status global: OFF
[Provider terdaftar] [Model allowlisted] [Batas biaya]
Secret: ••••••••••••7A9C | "Tidak dapat ditampilkan kembali"
[Ganti secret mock]
Dialog native:
  Password input kosong, tidak prefilled
  pesan bahwa nilai hanya hidup di memory tab
  [Batal] [Simpan simulasi]
Audit preview: siapa, aksi, waktu (fixture; tanpa secret)
```

Tidak ada custom Base URL pada form.

## 11. Temuan dan tindak lanjut

```text
H1 Temuan dan Tindak Lanjut
KPI: terbuka, berjalan, perlu verifikasi, terlambat
Filter: owner, status, due date
Table/card: kode, temuan, sumber agregat, owner, deadline, status, [Detail]
Detail drawer/page:
  evidence lineage → rencana aksi → indikator keberhasilan
  timeline status dan evidence metadata
  [Tambah update mock] [Ajukan verifikasi mock]
Verifikator: [Kembalikan] [Verifikasi] dengan catatan wajib
```

## 12. Laporan

```text
H1 Laporan
Filter: periode, tipe, unit, status
Panel generate:
  [Template laporan] [Format PDF/XLSX] [Scope]
  threshold/privacy summary
  [Simulasikan pembuatan]
Jobs table: report ID, snapshot, scope, status, dibuat, expiry, [Lihat]
Ready row: [Unduh fixture] (tidak mengandung dataset nyata)
Failed row: alasan aman + [Coba lagi]
```

## 13. User flows

### 13.1 Login dan pengisian

```text
Pilih akun demo → Masuk → Dashboard responden → Detail survei
→ Mulai/lanjutkan → Jawab item → Autosave simulasi
→ Tinjau → Validasi gagal? kembali ke item
→ Konfirmasi final → Submit simulasi → Receipt mock
```

### 13.2 Admin membuat instrumen

```text
Dashboard admin → Builder → Pilih bagian → Tambah/edit item
→ Validasi lokal → Simpan draft fixture → Pratinjau
→ STOP: review/approval/publication production tidak dijalankan
```

### 13.3 Admin membaca hasil dan menutup loop

```text
Hasil released aggregate → Temukan kategori prioritas
→ Buat/buka temuan fixture → Tetapkan aksi → Update evidensi
→ Ajukan verifikasi → Verifikasi mock → Laporan
```

### 13.4 Pimpinan

```text
Masuk sebagai pimpinan → Filter unit dalam scope
→ Baca KPI + threshold notice → Buka prioritas/tindak lanjut
→ Buka laporan dirilis; tidak ada drill-through ke raw response
```

### 13.5 AI opsional

```text
Admin → Analisis AI → Periksa guardrail → Jalankan simulasi
→ Draft terlabel → Human review → Tandai direview/ditolak
→ Tidak otomatis merilis atau membuat keputusan
```

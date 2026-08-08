# Design System, Responsive, dan Accessibility Baseline

Status: Phase 08 proposal; target WCAG 2.2 AA untuk jalur prioritas. Target formal masih memerlukan persetujuan pemilik sistem.

## 1. Karakter visual

Formal, akademik, profesional, dan berbasis data. Inspirasi SINTA dibatasi pada karakter KPI, filter, pencarian, tabel, dan kepadatan informasi. Prototype tidak menggunakan logo, aset, identitas, ataupun layout identik SINTA. Tidak ada gradient; satu aksen biru digunakan per view.

## 2. Design tokens

### 2.1 Warna

| Token | Nilai | Penggunaan |
|---|---:|---|
| `ink-950` | `#10243E` | teks/heading utama |
| `ink-700` | `#334A64` | teks sekunder |
| `ink-500` | `#60758A` | metadata |
| `surface` | `#FFFFFF` | kartu/form |
| `canvas` | `#F4F7FA` | latar halaman |
| `line` | `#D7E0E8` | border/divider |
| `brand-700` | `#075E8C` | aksi utama/fokus |
| `brand-100` | `#E1F1F8` | latar pilihan aktif |
| `success-700` | `#176B48` | selesai/lolos |
| `warning-800` | `#8A4B08` | perlu perhatian |
| `danger-700` | `#B42318` | error/aksi berisiko |

Kontras teks normal ditargetkan ≥4.5:1, teks besar dan komponen visual ≥3:1. Status selalu memiliki teks/ikon, bukan warna saja.

### 2.2 Tipografi

- Font stack: `Inter, ui-sans-serif, system-ui, sans-serif`; tidak mengambil font eksternal.
- Body: 16 px / 1.55; metadata minimum 14 px hanya untuk informasi sekunder.
- H1 28–32 px, H2 22–24 px, H3 18–20 px; heading memakai `text-wrap: balance`.
- Body panjang memakai `text-wrap: pretty`; angka KPI memakai `font-variant-numeric: tabular-nums`.
- Tidak mengubah letter spacing dekoratif.

### 2.3 Spacing dan bentuk

- Basis 4 px; skala: 4, 8, 12, 16, 24, 32, 48.
- Radius: 6 px untuk control, 10 px untuk card; bentuk tidak pill berlebihan.
- Shadow hanya skala kecil pada dialog/header; struktur utama memakai border.
- Z-index tetap: base 0, sticky 10, drawer 30, dialog 50, toast 60.

### 2.4 Icon

- Library yang sudah tersedia: `@lucide/vue`; tidak menambah icon system.
- Ukuran 18–20 px untuk navigasi, 16 px untuk inline.
- Icon-only button wajib memiliki accessible name dan tooltip/title; aksi penting tetap memakai label teks.
- Ikon tidak digunakan sebagai satu-satunya pembeda status.

## 3. Komponen

| Komponen | Spesifikasi minimum |
|---|---|
| Button | primary/secondary/quiet/danger; height ≥40 px; disabled jelas; loading mempertahankan label |
| Link | underline pada konten; nav aktif memakai warna + indikator + `aria-current` |
| Card | heading semantik, border, padding 16–24; bukan seluruh card sebagai click target |
| Badge | teks status lengkap; kombinasi warna lembut + border |
| Dialog | elemen native `<dialog>`; judul, deskripsi, cancel, focus return, Escape |
| Notice | `role=status` untuk info; `role=alert` untuk error yang membutuhkan perhatian |
| Tabs | hanya untuk sibling views; keyboard behavior native button dan state terpilih |
| Tooltip | tidak memuat informasi esensial; tersedia melalui focus dan hover bila digunakan |

## 4. Table, form, filter, chart

### Table

- Caption atau heading yang menjelaskan dataset, header `<th scope="col">`, angka rata kanan dan tabular.
- Toolbar berisi search/filter/reset; jumlah hasil diumumkan.
- Desktop dapat scroll horizontal dalam container, bukan seluruh halaman.
- Mobile berubah menjadi kartu berlabel; data penting tidak disembunyikan tanpa alternatif.

### Form

- Label eksplisit di atas input; bantuan sebelum error; required ditulis “Wajib”.
- Error diletakkan di dekat field, dihubungkan dengan `aria-describedby`, dan ringkasan error tersedia saat submit.
- Radio dipakai untuk skala tunggal; textarea untuk komentar opsional; paste tidak diblokir.
- Form survei tidak memiliki batas waktu pada prototype.

### Filter

- Apply segera untuk fixture ringan; perubahan hasil diumumkan secara sopan.
- Nilai default “Semua dalam scope”; filter unit tidak dapat membuka scope baru.
- Tombol reset tampil saat ada filter aktif.

### Chart

- Maksimum 6–8 kategori per chart; tanpa 3D atau dual axis.
- Label nilai utama terlihat dan tersedia tabel/ringkasan teks.
- Palette satu aksen + neutral; pola/label membedakan status.
- Small cell ditampilkan sebagai suppressed, bukan dipetakan sebagai 0.

## 5. State patterns

| State | Pola |
|---|---|
| Loading | skeleton struktural singkat + label `Memuat…`; jangan spinner penuh layar |
| Empty | sebab + satu aksi jelas, mis. “Belum ada survei aktif — lihat riwayat” |
| Error | apa yang gagal, dampak, retry aman, request ID bila dari API production |
| Offline | draft lokal ditandai belum sinkron; prototype hanya mensimulasikan state |
| Success | konfirmasi spesifik dan next step; submit memberi receipt fixture |
| Suppressed | `Disembunyikan untuk privasi (N < threshold)` |
| Unauthorized | tidak menyebut apakah objek sensitif ada; kembali ke area aman |

## 6. Responsive behavior

| Lebar | Perilaku |
|---|---|
| ≥1280 px | sidebar 264 px; KPI 4 kolom; tabel penuh; max content 1440 px |
| 768–1279 px | sidebar ringkas/drawer; KPI 2 kolom; filter wrap |
| 320–767 px | satu kolom; drawer; tabel menjadi kartu; action full-width bila perlu |

- Target reflow 320 CSS px tanpa scroll horizontal halaman.
- Touch target minimum 44×44 px pada aksi utama/navigation.
- Fixed element menghormati safe-area inset.
- Content dapat diperbesar 200% tanpa kehilangan aksi atau informasi.
- Tidak memakai `100vh`; bila perlu gunakan `100dvh`.

## 7. Accessibility checklist

### Struktur dan keyboard

- [x] Skip link menuju konten utama.
- [x] Landmark `header`, `nav`, `main`, dan heading hierarchy.
- [x] Semua aksi menggunakan link/button/input native.
- [x] Focus indicator kontras dan tidak dihapus.
- [x] Urutan Tab mengikuti urutan visual; tidak ada positive tabindex.
- [x] Dialog submit/config memakai native dialog dan dapat ditutup Escape.
- [x] Route change memindahkan fokus ke H1 melalui router hook.

### Form dan perubahan dinamis

- [x] Semua control memiliki label/legend.
- [x] Error terhubung dengan field dan diumumkan.
- [x] Autosave memiliki live region tanpa mengganggu.
- [x] Konfirmasi sebelum submit final; cancel tidak kehilangan jawaban.
- [x] Secret tidak dapat di-reveal dari UI prototype.

### Visual dan kognitif

- [x] Body 16 px dan bahasa Indonesia sederhana.
- [x] Informasi tidak bergantung warna.
- [x] Grafik disertai nilai/ringkasan teks.
- [x] Reduced motion dihormati; transisi maksimum 160 ms dan hanya opacity/transform.
- [x] Tidak ada autoplay, kedipan, atau batas waktu.
- [ ] Screen reader lintas NVDA/JAWS/VoiceOver — perlu pengujian perangkat nyata.
- [ ] Automated axe/pa11y — tool belum tersedia dan tidak ditambahkan pada Phase 08.

## 8. Library decision

Tidak ada library UI tambahan. Tailwind CSS, Vue Router, dan `@lucide/vue` sudah tersedia; komponen memakai HTML native dan CSS internal. Karena tidak ada dependency baru atau perubahan arsitektur besar, ADR tambahan tidak diperlukan. Keputusan dicatat pada dokumen kontrol.

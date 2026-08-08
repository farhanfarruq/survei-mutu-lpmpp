# Dashboard dan Content Design Specification

## 1. Content principles

1. Gunakan bahasa Indonesia formal, singkat, dan berorientasi tindakan.
2. Sebut objek dan status: “Kirim respons”, bukan “Proses”; “Draft belum tersimpan”, bukan “Error”.
3. Jelaskan konsekuensi sebelum tindakan final/destruktif.
4. Label privacy membedakan `Anonim`, `Rahasia`, dan `Teridentifikasi`; jangan memakai “aman” tanpa penjelasan.
5. Label AI wajib menyebut `Simulasi`, `Draft`, `Post-MVP`, dan `Wajib review manusia` sesuai state.
6. Jangan mengklaim autosave, export, reminder, atau analisis nyata pada fixture.

## 2. Message catalog

| Konteks | Copy |
|---|---|
| Prototype banner | `Mode prototype — seluruh data adalah fixture dan tidak tersambung ke sistem production.` |
| Autosave idle | `Belum ada perubahan.` |
| Autosave saving | `Menyimpan perubahan lokal…` |
| Autosave saved | `Tersimpan di tab ini pukul HH.mm.` |
| Validation | `Pilih satu jawaban untuk melanjutkan.` |
| Submit warning | `Setelah dikirim, respons tidak dapat diubah pada alur ini.` |
| Submit success | `Respons simulasi berhasil dikirim. Simpan nomor bukti untuk referensi.` |
| Suppression | `Disembunyikan untuk menjaga privasi karena jumlah respons di bawah ambang pelaporan.` |
| AI label | `Simulasi AI · draft · wajib review manusia` |
| Secret | `Secret tidak dapat ditampilkan kembali. Nilai pada prototype hanya hidup di tab ini.` |
| Empty | `Belum ada data untuk filter ini.` |

## 3. Respondent dashboard

| Elemen | Definisi | Aksi |
|---|---|---|
| Active survey KPI | jumlah campaign eligible dan aktif | ke daftar survei aktif |
| Continue KPI | draft belum final | ke draft paling mendesak |
| Completed KPI | participation receipt count | ke riwayat status |
| Priority card | deadline terdekat, belum final | mulai/lanjutkan |

Tidak menampilkan skor, jawaban, atau identitas responden lain.

## 4. Admin overview

| KPI | Formula/lineage | Guardrail |
|---|---|---|
| Survei aktif | campaign state active dalam scope | fixture, bukan query production |
| Response rate | final eligible ÷ invited eligible ×100% | denominator dan waktu snapshot terlihat |
| Respons final | count participation final | tidak membuka content response |
| Tindak lanjut terlambat | verified due date < now dan belum closed | scope unit diterapkan |

Panel attention mengurutkan severity lalu deadline. Tabel campaign tidak memiliki kolom nama/email responden. Admin overview diberi label reference Filament karena admin production tetap menggunakan Filament menurut arsitektur Phase 06.

## 5. Result dashboard

| Visual | Maksud | Interaksi |
|---|---|---|
| KPI index | skor normalized released snapshot | info metodologi |
| Response rate | coverage, bukan quality score | tidak digabung ke indeks |
| Category bars | perbandingan ≤6 kategori | filter category/unit |
| IPA priority table | importance, performance, gap, quadrant | sort kolom; bukan chart padat |
| Follow-up summary | closing loop status | ke finding/action |

Semua visual menyebut snapshot, N, metode, scope, dan threshold. Rounding display tidak mengganti nilai canonical.

## 6. Leadership dashboard

Data source konseptual hanya released aggregate snapshot. Filter unit adalah subset authorization scope; nilai `Semua unit` berarti semua unit yang boleh dilihat pengguna, bukan seluruh institusi. Tidak ada link ke raw response/open text. Unit dengan N kecil disuppress dan complementary suppression berlaku pada implementasi production.

## 7. AI analysis

Output fixture dibagi menjadi tema, bukti agregat, confidence label, keterbatasan, dan human-review state. Confidence bukan probabilitas kebenaran. Tombol review hanya mengubah state lokal. Tidak ada prompt, secret, open text asli, provider request, atau automatic publication.

## 8. Dashboard quality rules

- Satu H1 per halaman; KPI antara 3–4 per baris desktop.
- Maksimum dua visual utama di atas fold desktop.
- Satu chart tidak lebih dari 8 mark utama.
- Filter state terlihat, dapat direset, dan tidak tersembunyi dalam hover.
- Gunakan tanggal absolut (`6 Agustus 2026, 16.00 WIB`) untuk release/audit.
- Setiap angka menjawab: apa, scope mana, periode apa, kapan dihitung.
- Loading/empty/error tidak mengganti struktur secara mengejutkan.

## 9. Formal terms in UI

| Gunakan | Hindari | Alasan |
|---|---|---|
| Respons | response/jawaban survei secara campur | konsisten glossary |
| Responden | user/customer | konteks akademik |
| Tindak lanjut | follow-up tanpa padanan | formal dan dipahami |
| Unit organisasi | jurusan/unit secara ambigu | mendukung hierarchy |
| Agregat dirilis | data hasil | menyatakan governance state |
| Disembunyikan | 0/kosong | membedakan privacy suppression dari zero |

## 10. Open content decisions

Nama produk final, tone institusi, nama unit nyata, target WCAG formal, bahasa kedua, istilah resmi pimpinan, naskah consent, serta wording anonim/rahasia harus disetujui pemilik sistem sebelum production.

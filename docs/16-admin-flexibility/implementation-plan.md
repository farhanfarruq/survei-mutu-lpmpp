# Phase 16 — Fleksibilitas Pengelolaan Survei

Dokumen ini menjadi checklist implementasi fitur yang disepakati setelah bimbingan dosen. Perubahan memakai bank pertanyaan, versioning, activity-log, dan database notification yang sudah ada tanpa mengubah respons survei lama.

## Checklist

- [x] 16.1 Bank Pertanyaan: status aktif/default, navigasi, filter, salin, dan integrasi builder.
- [x] 16.2 Builder dan workflow: edit draf, revisi aman, aksi status-aware, dan jadwal terpublikasi yang aman.
- [x] 16.3 Riwayat Aktivitas: halaman read-only untuk seluruh staf, tanpa aktivitas respondent atau data sensitif.
- [x] 16.4 Notifikasi: badge unread serta baca satu/semua di Filament dan Vue.
- [x] Validasi fitur Phase 16: test backend/frontend, Pint, type-check, build, dan E2E notifikasi/alur survei.
- [ ] Gate lama lintas repository: PHPStan masih menemukan baseline pada file di luar implementasi Phase 16 dan satu E2E aksesibilitas registrasi masih gagal karena kontras warna.

## Keputusan Tetap

- Pertanyaan default dipilih melalui tombol; tidak otomatis masuk ke formulir.
- Pertanyaan bank disalin sebagai snapshot yang dapat diedit. Perubahan bank tidak mengubah formulir lama.
- Isi formulir yang disetujui atau dipublikasikan tidak diedit langsung; perubahan dibuat sebagai revisi draf.
- Scheduled hanya dapat mengubah nama, jadwal, dan penanggung jawab. Active hanya dapat mengubah nama, waktu selesai, dan penanggung jawab.
- Semua staf selain respondent dapat melihat riwayat lintas unit. Page view, aktivitas respondent, jawaban, token, password, dan secret tidak dicatat/ditampilkan.
- Tidak menambah dependency baru.

## Acceptance

- Entri bank nonaktif ditolak saat ditambahkan, sedangkan salinan lama tetap utuh.
- Admin dapat melanjutkan edit draf dari builder sederhana dan Leader tetap baca-saja.
- Revisi menghasilkan instrumen serta survei draf baru tanpa mengubah survei atau respons lama.
- Riwayat bersifat read-only, aman, dapat difilter, dan hanya tersedia untuk staf.
- Badge unread konsisten; baca satu/semua hanya memengaruhi notifikasi milik pengguna aktif.

## Hasil Validasi

- Backend: 76 test lulus dengan 678 assertion.
- Frontend: 22 unit test lulus, type-check lulus, dan production build lulus.
- Pint: 234 file lulus.
- E2E fitur Vue: 3 test lulus, termasuk notifikasi baca semua dan alur survei/responden.
- E2E keseluruhan: 6 dari 7 lulus; halaman registrasi lama memiliki rasio kontras tombol 2.91:1.
- PHPStan: kode inti baru Phase 16 bersih; pemeriksaan penuh masih melaporkan 40 temuan baseline lama pada 12 file lain.

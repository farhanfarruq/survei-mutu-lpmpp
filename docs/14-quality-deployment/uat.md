# User Acceptance Test Scenarios

Gunakan tenant/unit dan akun fiktif. UAT manusia belum dieksekusi pada Phase 14; tabel ini adalah script acceptance yang harus ditandatangani Product Owner, LPMPP, Privacy/Security, dan Operations sebelum go-live.

| ID | Aktor/skenario | Hasil yang diharapkan |
|---|---|---|
| UAT-01 | Responden melihat survei eligible | hanya survei aktif dan sesuai target/scope |
| UAT-02 | External membuka token valid/expired/revoked | valid dapat consent; expired/revoked ditolak tanpa bocor detail |
| UAT-03 | Isi semua tipe MVP dan pindah bagian | jawaban/section/progress benar pada mobile dan keyboard |
| UAT-04 | Putus jaringan saat autosave lalu pulih | draft lokal tetap ada, status jelas, retry tidak menggandakan jawaban |
| UAT-05 | Conflict dua tab | versi menang dipertahankan dan pengguna mendapat recovery yang jelas |
| UAT-06 | Kirim dengan required kosong | summary error fokus dan final submit ditolak |
| UAT-07 | Klik submit dua kali | satu response/receipt yang sama; tidak ada duplikasi |
| UAT-08 | Riwayat responden | hanya status partisipasi yang diizinkan, bukan isi jawaban |
| UAT-09 | Admin membuat/version/review/publish/close survey | lifecycle dan dual control sesuai role, audit tersedia |
| UAT-10 | Analyst menjalankan/release analysis | golden statistic konsisten; small-cell suppressed sebelum release |
| UAT-11 | Leader ganti unit/period/group | hanya released aggregate dalam organizational scope; tidak ada individual answer |
| UAT-12 | Export report | filter provenance/last-updated ada, ticket satu kali, expiry bekerja |
| UAT-13 | AI provider disabled/failing | statistik tetap tersedia, fallback berlabel, failure notification tercatat |
| UAT-14 | Reviewer edit/approve/reject AI | reviewer terpisah; label/model/scope/timestamp/status jelas |
| UAT-15 | Temuan sampai verification/revision | PIC, root cause, plan, due date, evidence, revision dan audit lengkap |
| UAT-16 | Leader membuka tindak lanjut | read-only dan ter-scope |
| UAT-17 | Admin memicu reminder | hanya participation eligible, maksimal kebijakan, tidak membuka jawaban |
| UAT-18 | Accessibility | NVDA/Firefox atau JAWS/Chrome dan VoiceOver/Safari: landmarks, label, focus, dialog, error, table/chart summary |
| UAT-19 | Session/security | logout, idle expiry, CSRF failure, forbidden scope, 429 semuanya memberi pesan aman |
| UAT-20 | Operations | backup/restore, queue restart, health alert, rollback rehearsal berhasil |

## Form hasil

Catat environment/build, tester/role, tanggal, data fixture, actual result, screenshot/request ID yang telah disanitasi, PASS/FAIL/BLOCKED, defect ID, severity, dan sign-off. Tidak boleh menempelkan secret atau individual answer dalam evidence.


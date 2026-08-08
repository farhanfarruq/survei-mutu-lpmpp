# Phase 11 — Response Collection

Status: **COMPLETE — READY WITH NOTES**  
Tanggal: 2026-08-08

Phase ini mengimplementasikan pengumpulan respons production untuk responden terautentikasi dan eksternal: eligibility, invitation/session token, detail instrumen, navigasi section, enam tipe pertanyaan MVP, autosave/recovery, validasi wajib, exactly-once submission, receipt, riwayat partisipasi terbatas, reminder eligibility, dan threshold pelaporan.

## Dokumen

- [implementation.md](implementation.md) — alur backend/Vue, endpoint, state, question type, recovery, dan batas akses.
- [privacy-and-reporting-boundary.md](privacy-and-reporting-boundary.md) — pemisahan participation/content/linkage, reminder, threshold, dan larangan jawaban individual untuk pimpinan.
- [validation-report.md](validation-report.md) — skenario negatif, quality gate, migration, dan batas phase.

Tidak ada dependency baru. Tidak ada endpoint pimpinan untuk raw response atau jawaban individual. Phase 12 analytics/reporting, scoring, aggregate release, export, AI, dan notification delivery tidak dimulai.

Status **READY WITH NOTES** karena wording consent/privacy notice, window session, reminder cadence, dan deployment physical database/credential separation tetap memerlukan pengesahan institusi sebelum production.

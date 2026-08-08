# Phase 14 Performance Review

## Hasil

`LeadershipDashboard` memakai eager loading untuk survey/instrument, owner unit, period, dan respondent group. Regression test dengan 100 released aggregate snapshots membatasi eksekusi pada maksimal 10 query dan kurang dari 1 detik di runner lokal. Statistik membaca submitted responses beserta answers dalam eager-load, sehingga tidak ditemukan N+1 per answer.

Route Vue diubah menjadi lazy imports. Hasil build kini memisahkan shell dan halaman berat termasuk ECharts; pengguna non-dashboard tidak perlu mengunduh visualisasi saat initial route.

## Index review

| Pola query | Index |
|---|---|
| Dashboard released per unit terbaru | `aggregate_snapshots(state, owner_unit_id, generated_at)` |
| Dashboard period/group | index existing state-owner-period dan state-group |
| Reminder eligibility | `survey_participations(survey_id, completed_at, declined_at, invitation_revoked_at, last_reminded_at)` |
| Export per requester/state/expiry | `report_exports(requested_by, state, expires_at)` |
| Notification retry | `notification_deliveries(state, last_attempt_at)` plus unique logical delivery |
| Finding/action deadline | index existing owner/PIC-state-due |

PostgreSQL runtime menunjukkan seluruh index tersebut telah dibuat. Index tambahan dibatasi pada filter/sort operasional yang ada; tidak ada blanket indexing pada JSON atau kolom low-selectivity tanpa query.

## Cache dan queue

- Analysis menggunakan `input_hash` + formula version dan durable aggregate snapshot; dashboard hanya membaca state `released` dan menampilkan `generated_at`.
- Analysis/export/notifikasi memiliki 3 attempts dan backoff 10/60/300 detik. Error menyimpan state terkontrol; partial notification retry tidak menggandakan channel yang sudah sent.
- Horizon dan scheduler berjalan sebagai container terpisah; health Horizon memanggil `horizon:status`.

## Batas kapasitas

Dashboard saat ini mengambil seluruh snapshot sesuai filter, cocok untuk volume teruji 100 tetapi belum mempunyai pagination/server-side series cap. Analytics juga menghitung satu survei secara in-memory. Sebelum volume produksi besar, jalankan EXPLAIN ANALYZE pada distribusi production-like, tetapkan maksimum series, chunk/stream computation bila peak memory terukur, serta load/soak test dengan target NFR yang disahkan. Ini adalah release condition, bukan alasan menambah kompleksitas sebelum data kapasitas tersedia.


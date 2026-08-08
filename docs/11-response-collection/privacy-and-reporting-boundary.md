# Privacy, Reminder, dan Reporting Boundary

## Pemisahan data

| Store/table | Isi | Larangan |
|---|---|---|
| `survey_participations` | user/reference hash, invitation/completion token hash, started/completed/declined, reminder metadata | tidak menyimpan response ID atau answer |
| `respondent_sessions` | survey, random access-token hash, expiry | tidak memiliki user, invitation, atau participation key |
| `survey_responses` + `response_answers` | consent version, state/version/progress, answer typed, receipt | tidak memiliki user/reference/invitation key |
| `confidential_response_links` | response–participation link untuk campaign `confidential` saja | tidak pernah ditulis untuk `anonymous`/`detached` |

Pada mode `anonymous` dan `detached`, completion token hanya hadir pada request transaction untuk memperbarui status partisipasi; token/hash tersebut tidak disimpan pada response content. Pada mode `confidential`, linkage eksplisit berada pada table terpisah dan tidak memiliki endpoint baca Phase 11.

Schema development memakai database aplikasi yang sama dengan pemisahan table dan code boundary. Deployment production tetap harus menerapkan koneksi/credential terpisah untuk Participation DB, Response DB, dan Linkage Vault sesuai Phase 06 setelah topology final disahkan.

## Riwayat dan receipt

- Riwayat akun hanya memuat campaign, privacy mode, status, close time, dan completion time.
- Riwayat tidak memuat answer, response ID, receipt code, atau linkage.
- Receipt ditampilkan pada session completion sebagai bukti nonidentifying dan tidak menjadi lookup jawaban individual.
- Submitted response body meminimalkan answer menjadi array kosong.

## Reminder eligibility

Participation eligible untuk reminder bila belum completed/declined/revoked, jumlah reminder kurang dari tiga, dan reminder terakhir kosong atau minimal tiga hari lalu. Collection summary hanya mengembalikan count agregat; Phase 11 tidak mengirim email/notifikasi dan tidak mengekspos daftar identitas.

## Reporting threshold foundation

Collection summary membawa `eligible_count`, `invited_count`, `started_count`, `completed_count`, `reminder_eligible_count`, `reporting_threshold`, `reportable`, dan `suppressed`. Nilai `reportable` baru berarti count mencapai minimum campaign; nilai ini bukan released aggregate dan belum mengizinkan analitik/pelaporan Phase 12.

Tidak ada route Phase 11 yang memberi pimpinan akses ke response atau answer. Pimpinan tetap dibatasi ke released aggregate pada fase berikutnya; minimum-cell, complementary suppression, dominance, anti-differencing, dan release workflow tidak boleh dilewati hanya karena count minimum tercapai.

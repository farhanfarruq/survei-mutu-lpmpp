# Administrator Manual

## Masuk dan akses

Buka URL HTTPS organisasi lalu masuk memakai akun yang disediakan administrator identitas. Menu muncul sesuai permission dan organizational scope. Jika menu tidak ada, minta role diperiksa; jangan berbagi akun. Keluar setelah selesai, khususnya pada perangkat bersama.

## Mengelola master dan survei

1. Kelola unit, user, role, dan permission dengan least privilege.
2. Buat template/instrument version; lengkapi section, category, indicator, scale, question, option, scoring/method metadata.
3. Jalankan preflight, ajukan review, dan minta approver berbeda menyetujui.
4. Buat survey period/campaign, owner, target, privacy mode, reporting threshold, open/close time, dan notice.
5. Preview, review, approve, lalu publish. Setelah response ada, jangan mengubah konfigurasi yang dilindungi; buat version/duplicate baru.
6. Monitor hanya angka partisipasi. Reminder menggunakan eligibility engine; admin/pimpinan tidak boleh melihat individual answer.

## Analytics dan report

Analyst menjalankan deterministic analysis. Periksa response/eligible count, threshold, limitations, method/formula version, dan timestamp. Release snapshot hanya setelah review. Dashboard dan export hanya memakai released aggregate; suppressed cell tidak boleh ditebak, digabung ulang, atau diperlakukan sebagai nol. Export bersifat expiring dan link download satu kali.

## AI governance

Hanya super admin mengatur provider/model/base URL allowlist dan secret. Tampilan secret selalu masked. Mulai dari disabled, jalankan connection test, tetapkan token/cost/timeout/rate budget, dan gunakan prompt template versioned. AI hanya membuat draft dari scope agregat; reviewer manusia wajib edit/approve/reject. Jangan gunakan AI untuk statistik dasar atau perubahan data sumber.

## Temuan dan tindak lanjut

Buat finding manual atau dari indikator rendah pada released snapshot. Tetapkan unit owner, PIC, verifier berbeda, root cause, plan, due date, expected output, dan evidence. PIC submit verification; verifier approve atau meminta revision. Leader hanya mempunyai view read-only sesuai scope.

## Notifikasi dan operasi

Periksa in-app notification, mail log/provider status, failed deliveries, Horizon, scheduler, dan `/api/v1/health/ready`. Delivery failed dapat retry; channel sent tidak dikirim ulang. Untuk incident, ikuti `incident-response.md`; jangan menyalin secret/jawaban individual ke log atau tiket.

## Checklist berkala

- Mingguan: inactive/privileged user, overdue follow-up, queue failure/age, export expiry, AI usage/cost/failure.
- Bulanan: role/scope recertification, restore drill, dependency/image advisory, prompt/provider review.
- Per periode: instrument/version approval, target/threshold/privacy notice, comparison eligibility, archive/retention.


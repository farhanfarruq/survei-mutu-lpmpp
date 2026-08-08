# Incident Response

## Tujuan dan peran

Gunakan runbook ini untuk availability, security, privacy, integrity, AI, notification, report, atau backup incident. Tetapkan Incident Commander, Technical Lead, Security/Privacy Lead, Communications Lead, dan Scribe. Hanya Incident Commander yang mengubah severity atau menutup incident; keputusan dan waktu dicatat pada kanal/ticket resmi.

| Severity | Contoh | Respons awal usulan |
|---|---|---|
| SEV-1 | kebocoran jawaban/identitas, data corruption, layanan inti total down | segera, war room dan escalation eksekutif |
| SEV-2 | scope bypass terbatas, queue/report gagal luas, recovery terancam | prioritas tinggi |
| SEV-3 | fungsi non-kritis terganggu, satu provider AI/email gagal dengan fallback | jam kerja/on-call sesuai SLA |
| SEV-4 | defect kosmetik/dokumentasi tanpa dampak data | backlog terjadwal |

Target waktu final harus disahkan; jangan menggunakan tabel ini sebagai janji SLA.

## Alur

1. Detect dan buat incident ID; jangan menyalin secret atau individual answer ke chat/ticket.
2. Triage severity, affected scope, waktu awal, data classification, dan apakah incident masih aktif.
3. Contain: revoke token/session/provider, disable AI/export, pause queue tertentu, atau alihkan traffic sesuai least-impact.
4. Preserve evidence: request ID, audit/activity ID, job/export/AI ID, timestamp, sanitized logs, image digest, config version. Jangan mengubah source evidence.
5. Eradicate root cause dan verifikasi dengan fixture sintetis.
6. Recover bertahap; health/readiness, scope/privacy, queue, checksum, dan monitoring harus hijau.
7. Notify legal/privacy/management dan pihak terdampak berdasarkan kebijakan/regulasi yang telah divalidasi oleh pejabat berwenang.
8. Post-incident review: timeline, cause, control gap, corrective action/PIC/due date, evidence, verification.

## Playbook khusus

### Suspected privacy/scope leak

Nonaktifkan route/export terkait, revoke download ticket, preserve filter provenance dan audit, tentukan apakah individual answer atau small-cell terekspos, dan jangan membuka lebih banyak data untuk investigasi. Verifikasi seluruh organizational scope dan snapshot release sebelum re-enable.

### Secret compromise

Disable provider/config, rotate di secret manager, invalidate session/token terkait, cari penggunaan sejak last-known-good, dan verifikasi log tidak menyimpan plaintext. Rotation production memerlukan approval.

### AI unsafe output/prompt injection

Quarantine result, reject review, disable provider/model bila sistemik, pertahankan deterministic statistics sebagai source of truth, dan audit prompt template/source scope. AI tidak boleh menulis kembali source data.

### Queue/export/notification failure

Periksa Horizon health, queue age, failed jobs, storage, SMTP, dan delivery attempt. Retry hanya job idempotent; delivery sent tidak boleh diulang. Revoke export yang checksum/path-nya meragukan.

### Database/backup incident

Hentikan write bila integrity diragukan. Ikuti backup runbook dan restore ke target baru. Jangan drop/overwrite database tanpa approval eksplisit.

## Evidence checklist

Incident ID, UTC/WIB timestamps, severity changes, request/job IDs, affected unit/survey count agregat, release/image/config version, decisions/approvals, containment, recovery checks, notifications, dan follow-up action. Redact credential, token, cookie, email pribadi, serta individual response.


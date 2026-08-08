# Deployment Runbook

Runbook ini adalah prosedur persiapan; Phase 14 tidak melakukan deployment production.

## Prasyarat dan approval

1. Change ticket, release owner, rollback owner, maintenance window, dan komunikasi pengguna disetujui.
2. Image immutable dipin ke digest, SBOM/image scan lulus, artifact berasal dari commit/tag yang disetujui.
3. PostgreSQL, Redis, object storage, SMTP, DNS/TLS, secret manager, monitoring, dan backup tersedia pada environment target.
4. Semua condition dalam `release-readiness.md` ditutup atau diterima tertulis oleh risk owner.

## Configuration production

- `APP_ENV=production`, `APP_DEBUG=false`, URL/origin/stateful domain exact HTTPS.
- `SESSION_SECURE_COOKIE=true`, HttpOnly, SameSite=Lax; domain sesempit mungkin.
- APP_KEY, DB/Redis/SMTP/storage/AI credential di secret manager; jangan bake ke image atau menulis nilainya ke log.
- AI default disabled sampai provider/model, allowlist, budget, DPIA, dan reviewer tersedia.
- Queue Redis terpisah sesuai kapasitas; mail failure dan failed jobs mempunyai alert.

## Strategi container

| Service | Strategi |
|---|---|
| Nginx/web | immutable image, readiness/liveness, restart unless-stopped/orchestrator equivalent, grace 15 detik |
| PHP-FPM | non-root runtime, readiness dependency DB/Redis, grace 30 detik |
| Horizon | dedicated container, `horizon:status` healthcheck, restart policy, grace 90 detik agar job selesai |
| Scheduler | tepat satu active instance, `schedule:work`, restart policy, grace 30 detik |
| PostgreSQL/Redis | managed service direkomendasikan; persistence, encryption, backup, HA diuji |
| Frontend | static production build melalui CDN/Nginx; jangan gunakan Vite dev server |

Compose lokal sudah memiliki restart policy untuk app/Nginx/PostgreSQL/Redis/Horizon/scheduler, healthcheck pada app/Nginx/PostgreSQL/Redis/Horizon, `init`, dan stop grace period. Compose ini tetap development topology karena bind mount dan Vite dev server; buat deployment manifest/override production terpisah.

## Urutan rilis

1. Ambil dan verifikasi backup sesuai backup runbook.
2. Jalankan smoke pada artifact: Composer/npm audit, test, lint, type-check, build, config validation.
3. Deploy migration job satu kali: `php artisan migrate --force`; hentikan bila gagal.
4. Rollout web tanpa mematikan seluruh replica; tunggu readiness.
5. Jalankan `php artisan optimize`; restart Horizon secara graceful dengan `php artisan horizon:terminate` agar supervisor menghidupkan proses baru.
6. Pastikan satu scheduler aktif; deploy static frontend dengan cache-busting asset.
7. Smoke: live/ready, login+CSRF, permission/scope, eligible survey, aggregate dashboard, queue test job, notification log. Jangan memakai data pribadi.
8. Pantau error rate, latency, queue depth/age/failures, DB connection/slow query, Redis memory, mail failure, disk/storage, dan privacy/security alerts.

## Rollback

Rollback aplikasi ke digest sebelumnya. Jangan otomatis rollback migration yang destruktif; migration Phase 14 bersifat additive dan kompatibel. Bila data integrity terdampak, hentikan write, aktifkan incident response, dan restore ke environment baru sebelum cutover. Catat keputusan dan audit trail.

## Graceful shutdown

Drain load balancer, kirim SIGTERM, tunggu grace period, dan gunakan Horizon terminate. Jangan `kill -9` kecuali incident commander menyetujui setelah risiko partial job dinilai. Idempotency key dan delivery state melindungi retry, tetapi tetap verifikasi failed jobs dan file export orphan.


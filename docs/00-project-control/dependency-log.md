# Dependency Log

## Runtime dan image

| Dependency/image | Versi aktual | Digest image |
|---|---|---|
| `simutu-lpmpp-php:local` | PHP `8.5.9` | `sha256:6a2a40929a463fc5155e4514e7be09e23334632d4879b7653414defebbff0714` |
| `php:8.5-fpm-bookworm` | PHP `8.5.9` | `sha256:7b1deadd1d73c72d2eb952ebb494cd3e902d7b6ae4e4b3cd1113a1041b530c2c` |
| `composer:2` | Composer `2.10.2` | `sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040` |
| `node:24-bookworm-slim` | Node `24.19.0`, npm `11.17.0` | `sha256:3638d9a6fe4030bd716be989438248074489337ba3275657f93595428be4fc03` |
| `postgres:17-alpine` | PostgreSQL `17.10` | `sha256:742f40ea20b9ff2ff31db5458d127452988a2164df9e17441e191f3b72252193` |
| `redis:7-alpine` | Redis `7.4.10` | `sha256:e7723ff73d963f5cc6d9c4643ea3d989527a402a319239054e9472a7fb9219a2` |
| `nginx:stable-alpine` | Nginx `1.28.3` | `sha256:a8b39bd9cf0f83869a2162827a0caf6137ddf759d50a171451b335cecc87d236` |
| `axllent/mailpit:latest` | Mailpit `1.30.6` | `sha256:7f33095f80e901f6ad08028f06ca284aa58fe84942be5496008d041d3b9f4d4d` |

## Dependency aplikasi utama

| Area | Package | Versi |
|---|---|---|
| Backend | Laravel | `13.24.0` |
| Backend | Filament | `5.7.6` |
| Backend | Sanctum | `4.3.3` |
| Backend | Fortify | `1.37.3` |
| Backend | Horizon | `5.48.2` |
| Backend | Spatie Permission | `8.3.0` |
| Backend | Spatie Activitylog | `5.0.0` |
| Frontend | Vue | `3.5.41` |
| Frontend | Vite | `8.2.1` |
| Frontend | TypeScript | `6.0.3` |
| Frontend | Tailwind CSS / Vite plugin | `4.3.3` |
| Frontend | VeeValidate / Zod adapter | `4.15.1` |
| Frontend | Zod | `3.25.76` |
| Frontend | Lucide Vue | `@lucide/vue 1.29.0` |

Laravel Boost tidak dipasang karena opsional. Alasan deviasi dependency dicatat pada `decisions.md`.

## Tooling dokumentasi sementara

| Tool/image | Versi | Penggunaan |
|---|---|---|
| `ghcr.io/mermaid-js/mermaid-cli/mermaid-cli:latest` | Mermaid CLI `11.16.0` | Merender 28 blok Phase 05 untuk validasi sintaks; tidak ditambahkan ke dependency aplikasi. |
| `@redocly/cli` via ephemeral `node:24-bookworm-slim` | `2.45.0` | Lint OpenAPI 3.1.1 Phase 07; tidak ditambahkan ke dependency aplikasi. |

## Tooling test Phase 08

| Tool/runtime | Versi | Penggunaan |
|---|---|---|
| `@playwright/test` (sudah terpasang) | `1.61.1` | Empat E2E Chromium untuk alur utama, scope, label AI/secret, landmark, dan reflow 320 px. |
| Playwright Chromium ephemeral | Chrome for Testing `151.0.7922.34`, browser revision `1234` | Dipasang dengan `npx playwright install --with-deps chromium` hanya pada container test; binary cache di `frontend/node_modules/.cache`, tidak menambah package/image aplikasi. |

Phase 08 tidak menambah library UI atau dependency aplikasi baru.

## Phase 09

Phase 09 tidak menambah package atau image baru. Implementasi memakai Laravel Fortify, Sanctum, Horizon, Filament, Spatie Permission/Activitylog, Vue, Pinia, Vue Router, dan Axios yang sudah tercatat pada baseline. Perubahan hanya pada source, configuration, migration, fixture, test, dan dokumentasi.

## Phase 10

Phase 10 tidak menambah package atau image. Domain model/service memakai Laravel Eloquent, policy, scheduler, database transaction, Filament forms/infolists/actions, Spatie Permission/Activitylog, serta test stack yang sudah tersedia. Redocly CLI 2.45.0 kembali dijalankan secara ephemeral untuk memvalidasi perubahan OpenAPI dan tidak ditambahkan ke manifest.

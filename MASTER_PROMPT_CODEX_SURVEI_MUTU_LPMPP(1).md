# MASTER PROMPT CODEX

## Sistem Informasi Survei Mutu, Analitik, dan Tindak Lanjut LPMPP

Versi dokumen: 2.0 — Docker Edition  
Tanggal penyusunan: 6 Agustus 2026  
Bahasa kerja: Bahasa Indonesia  
Target penggunaan: Codex atau AI coding agent di dalam repository proyek

---

## 1. Cara menggunakan dokumen ini

Dokumen ini mempunyai tiga fungsi:

1. Menjadi panduan instalasi awal yang dikerjakan manusia sebelum Codex mulai mengembangkan sistem.
2. Menjadi master prompt yang menjelaskan konteks, ruang lingkup, aturan, metodologi, arsitektur, dan standar kualitas proyek.
3. Membagi pekerjaan menjadi beberapa phase agar Codex tidak merancang atau mengimplementasikan seluruh sistem sekaligus.

Jangan meminta Codex mengerjakan semua phase dalam satu percakapan. Jalankan satu phase, periksa hasilnya, lakukan revisi bila diperlukan, kemudian lanjutkan ke phase berikutnya.

Urutan penggunaan yang disarankan:

1. Ikuti Bagian 4 sampai Bagian 10 untuk menyiapkan lingkungan dan repository.
2. Letakkan dokumen ini pada root repository dengan nama `MASTER_PROMPT_CODEX_SURVEI_MUTU_LPMPP.md`.
3. Buka repository tersebut menggunakan Codex.
4. Kirim Prompt Pembuka pada Bagian 19.
5. Jalankan prompt setiap phase pada Bagian 20 secara berurutan.
6. Jangan melanjutkan ke phase implementasi sebelum dokumen perancangan disetujui.

---

## 2. Keputusan arsitektur dan lingkungan awal

Gunakan struktur monorepo berikut:

```text
survei-mutu-lpmpp/
├── backend/                         # Laravel API, Filament, queue, scheduler
├── frontend/                        # Vue SPA untuk responden dan pimpinan
├── docker/
│   ├── nginx/default.conf           # Konfigurasi web server lokal
│   └── php/Dockerfile               # Image PHP-FPM aplikasi
├── docs/                            # Seluruh hasil analisis dan perancangan
│   ├── 00-project-control/
│   ├── 01-discovery/
│   ├── 02-product-scope/
│   ├── 03-survey-methodology/
│   ├── 04-requirements/
│   ├── 05-process-and-uml/
│   ├── 06-data-architecture-security/
│   ├── 07-api-contract/
│   ├── 08-ui-ux-prototype/
│   ├── 09-implementation-foundation/
│   ├── 10-survey-management/
│   ├── 11-response-collection/
│   ├── 12-analytics-reporting/
│   ├── 13-ai-follow-up/
│   └── 14-quality-deployment/
├── compose.yaml                     # Seluruh service development
├── .env.example                     # Variabel Compose tanpa rahasia
├── .gitignore
├── README.md
└── MASTER_PROMPT_CODEX_SURVEI_MUTU_LPMPP.md
```

Pembagian aplikasi:

| Bagian | Teknologi | Tanggung jawab |
|---|---|---|
| Backend | Laravel | Logika bisnis, REST API, autentikasi, otorisasi, statistik, laporan, queue, scheduler, integrasi AI |
| Admin internal | Filament | Super Admin, Admin LPMPP, reviewer, verifikator, master data, survey builder, konfigurasi, dan audit log |
| Frontend | Vue | Halaman responden dan dashboard pimpinan |
| Database | PostgreSQL | Data operasional, struktur survei, jawaban, agregat, laporan, konfigurasi, dan audit |
| Cache dan queue | Redis | Cache dashboard, queue, rate limit, session bila digunakan, dan monitoring job |
| Visualisasi | Apache ECharts | Grafik dashboard, tren, heatmap, radar, stacked bar, dan matriks IPA |
| AI | Adapter melalui backend | Ringkasan, topik, sentimen, penjelasan tren, dan rekomendasi yang harus ditinjau manusia |

Lingkungan development menggunakan Docker Compose sepenuhnya. Host Ubuntu hanya
memerlukan Docker Engine, Docker Compose plugin, dan Git. PHP, Composer, Nginx,
Node.js, npm, PostgreSQL, Redis, Horizon, scheduler, dan Mailpit dijalankan di
dalam container. Jangan memasang runtime tersebut secara global di host kecuali
ada kebutuhan lain di luar proyek ini.

Service Compose wajib:

| Service | Fungsi | Port host default |
|---|---|---|
| `app` | PHP-FPM, Composer, dan Laravel CLI | internal `9000` |
| `nginx` | HTTP untuk Laravel API dan Filament | `8000` |
| `frontend` | Vue/Vite development server | `5173` |
| `postgres` | Database development dan test | `127.0.0.1:5432` |
| `redis` | Cache, queue, dan Horizon | `127.0.0.1:6379` |
| `horizon` | Worker queue Laravel | tanpa port; profile `workers` |
| `scheduler` | Laravel schedule worker | tanpa port; profile `workers` |
| `mailpit` | SMTP lokal dan kotak masuk pengujian | UI `8025` |

Dengan RAM host 28 GB, stack ini aman untuk development normal. Batas memori
default pada dokumen ini memakai sekitar 12 GB apabila seluruh service mencapai
batasnya, sehingga Ubuntu, browser, editor, dan Codex tetap mempunyai ruang.
Batas tersebut bukan target pemakaian dan dapat diubah melalui root `.env`.

Vue dilarang terhubung langsung ke PostgreSQL atau provider AI. Seluruh akses harus melalui Laravel.

---

## 3. Versi dan prasyarat yang disarankan

Gunakan image stabil yang kompatibel pada saat instalasi. Baseline per Agustus 2026:

| Komponen | Baseline container |
|---|---|
| Docker Engine | Versi stabil dari repository resmi Docker |
| Docker Compose | Compose plugin v2 |
| PHP | `php:8.5-fpm-bookworm` atau patch kompatibel terbaru |
| Composer | `composer:2` atau binary Composer 2 dari image resmi |
| Laravel | Laravel 13.x |
| Filament | Filament 5.x |
| Livewire | Versi yang dipasang sebagai dependency Filament 5 |
| Node.js | `node:24-bookworm-slim` atau patch kompatibel terbaru |
| npm | Versi yang menyertai Node.js |
| Vue | Vue 3 terbaru yang kompatibel |
| Vite | Vite 8 atau versi yang dipasang `create-vue` |
| TypeScript | Versi yang dipasang `create-vue` |
| Tailwind CSS | Tailwind CSS 4.1+ |
| PostgreSQL | `postgres:17-alpine` |
| Redis | `redis:7-alpine` |
| Nginx | `nginx:stable-alpine` |
| Mailpit | `axllent/mailpit:latest` untuk development lokal |

Jangan mencampur tutorial Tailwind CSS v3 dengan Tailwind CSS v4. Tailwind CSS v4 menggunakan plugin Vite `@tailwindcss/vite` dan impor `@import "tailwindcss";`.

Jangan memakai tag `latest` untuk service produksi. Mailpit boleh memakai
`latest` hanya pada development lokal; setelah stack tervalidasi, catat digest
atau versi aktual pada dependency log.

Setelah instalasi, simpan versi aktual melalui container:

```bash
docker --version
docker compose version
docker compose run --rm app php --version
docker compose run --rm app composer --version
docker compose run --rm frontend node --version
docker compose run --rm frontend npm --version
docker compose exec postgres psql --version
docker compose exec redis redis-server --version
git --version
```

Jika versi aktual berbeda dari baseline tetapi tetap kompatibel, jangan melakukan downgrade tanpa alasan yang teruji.

---

## 4. Instalasi Docker pada Ubuntu sebelum menggunakan Codex

Instruksi ini untuk Ubuntu 64-bit yang didukung Docker. Jangan memasang paket
Docker dari sumber acak. Jika pernah memasang paket konflik seperti `docker.io`,
`docker-compose`, `podman-docker`, `containerd`, atau `runc`, ikuti bagian
*Uninstall old versions* pada dokumentasi resmi Docker terlebih dahulu.

### 4.1 Paket dasar dan repository resmi Docker

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

Tambahkan repository yang mengikuti codename Ubuntu aktif:

```bash
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo \"${UBUNTU_CODENAME:-$VERSION_CODENAME}\") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
```

Pasang Docker Engine, Buildx, dan Compose plugin:

```bash
sudo apt install -y docker-ce docker-ce-cli containerd.io \
  docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker run --rm hello-world
```

### 4.2 Menjalankan Docker tanpa `sudo`

Langkah ini nyaman untuk development, tetapi keanggotaan grup `docker` setara
dengan hak akses root pada mesin. Lakukan hanya pada akun pengguna tepercaya:

```bash
sudo usermod -aG docker "$USER"
newgrp docker
docker run --rm hello-world
```

Jika `newgrp` tidak memperbarui sesi desktop, logout lalu login kembali.

### 4.3 Pemeriksaan host

```bash
docker --version
docker compose version
docker info
git --version
free -h
df -h
```

Target pemeriksaan:

- Docker daemon aktif tanpa error.
- `docker compose version` menampilkan Compose v2.
- RAM sekitar 28 GB terdeteksi.
- Tersedia sedikitnya 25–40 GB ruang disk bebas untuk image, volume, build cache,
  dependency PHP/Node, dan data development.
- Port `8000`, `5173`, `5432`, `6379`, dan `8025` tidak sedang digunakan.

Periksa port bila perlu:

```bash
ss -ltn | grep -E ':(8000|5173|5432|6379|8025)\b' || true
```

### 4.4 Git

Konfigurasikan identitas Git apabila belum pernah dilakukan:

```bash
git config --global user.name "NAMA_ANDA"
git config --global user.email "EMAIL_ANDA"
```

Jangan memasukkan API key, password database, token, atau isi `.env` ke Git.

---

## 5. Membuat repository dan scaffold aplikasi melalui container

### 5.1 Root repository

```bash
mkdir survei-mutu-lpmpp
cd survei-mutu-lpmpp
git init
mkdir -p docs docker/php docker/nginx
```

Salin dokumen ini ke root repository.

### 5.2 Membuat Laravel tanpa PHP/Composer pada host

```bash
docker run --rm \
  --user "$(id -u):$(id -g)" \
  -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer \
  -v "$PWD:/workspace" -w /workspace \
  composer:2 create-project laravel/laravel backend "^13.0"
```

Jika Laravel 13 bukan lagi versi stabil ketika perintah dijalankan, gunakan versi stabil terbaru yang kompatibel dengan Filament dan dokumentasikan keputusan tersebut pada `docs/00-project-control/decisions.md`.

### 5.3 Membuat Vue tanpa Node/npm pada host

```bash
docker run --rm -it \
  --user "$(id -u):$(id -g)" \
  -e HOME=/tmp \
  -v "$PWD:/workspace" -w /workspace \
  node:24-bookworm-slim \
  bash -lc "npm create vue@latest frontend"
```

Pilih opsi berikut pada `create-vue`:

| Pertanyaan | Jawaban |
|---|---|
| Add TypeScript? | Yes |
| Add JSX Support? | No |
| Add Vue Router? | Yes |
| Add Pinia? | Yes |
| Add Vitest? | Yes |
| Add End-to-End Testing? | Playwright |
| Add ESLint? | Yes |
| Add Prettier? | Yes |
| Add Vue DevTools experimental? | No atau sesuai kebutuhan lokal |

Jika file hasil scaffold menjadi milik `root`, hentikan dan perbaiki ownership
sebelum melanjutkan. Jangan membiasakan menjalankan editor atau Git dengan `sudo`.

---

## 6. Membuat konfigurasi Docker Compose

### 6.1 Root `.env.example`

Buat `.env.example` di root repository:

```dotenv
COMPOSE_PROJECT_NAME=simutu_lpmpp
UID=1000
GID=1000

POSTGRES_DB=lpmpp_survey
POSTGRES_USER=lpmpp_app
POSTGRES_PASSWORD=GANTI_PASSWORD_LOKAL

APP_MEMORY_LIMIT=2g
NGINX_MEMORY_LIMIT=256m
FRONTEND_MEMORY_LIMIT=2g
POSTGRES_MEMORY_LIMIT=4g
REDIS_MEMORY_LIMIT=1g
WORKER_MEMORY_LIMIT=2g
SCHEDULER_MEMORY_LIMIT=512m
MAILPIT_MEMORY_LIMIT=512m
```

Salin dan sesuaikan nilai lokal:

```bash
cp .env.example .env
sed -i "s/^UID=.*/UID=$(id -u)/; s/^GID=.*/GID=$(id -g)/" .env
```

Ganti `POSTGRES_PASSWORD` secara manual. Root `.env` tidak boleh di-commit.

### 6.2 `docker/php/Dockerfile`

```dockerfile
FROM php:8.5-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip libcurl4-openssl-dev libfreetype6-dev \
        libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev \
        libpq-dev libxml2-dev libzip-dev passwd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath curl dom exif gd intl mbstring opcache pcntl \
        pdo_pgsql pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* \
    && groupmod -o -g "$GID" www-data \
    && usermod -o -u "$UID" -g "$GID" www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/backend

RUN mkdir -p /tmp/composer && chown -R www-data:www-data /tmp/composer

USER www-data

CMD ["php-fpm"]
```

### 6.3 `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/backend/public;
    index index.php;
    client_max_body_size 10m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6.4 Root `compose.yaml`

```yaml
name: ${COMPOSE_PROJECT_NAME:-simutu_lpmpp}

x-php-service: &php-service
  image: simutu-lpmpp-php:local
  build:
    context: .
    dockerfile: docker/php/Dockerfile
    args:
      UID: ${UID:-1000}
      GID: ${GID:-1000}
  working_dir: /var/www/backend
  env_file:
    - ./backend/.env
  volumes:
    - ./backend:/var/www/backend
  networks:
    - simutu

services:
  app:
    <<: *php-service
    mem_limit: ${APP_MEMORY_LIMIT:-2g}
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "php-fpm", "-t"]
      interval: 10s
      timeout: 5s
      retries: 5

  nginx:
    image: nginx:stable-alpine
    mem_limit: ${NGINX_MEMORY_LIMIT:-256m}
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www/backend:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      app:
        condition: service_healthy
    networks:
      - simutu

  frontend:
    image: node:24-bookworm-slim
    mem_limit: ${FRONTEND_MEMORY_LIMIT:-2g}
    working_dir: /app
    command: npm run dev -- --host 0.0.0.0
    ports:
      - "5173:5173"
    environment:
      HOME: /tmp
    user: "${UID:-1000}:${GID:-1000}"
    volumes:
      - ./frontend:/app
    networks:
      - simutu

  postgres:
    image: postgres:17-alpine
    mem_limit: ${POSTGRES_MEMORY_LIMIT:-4g}
    environment:
      POSTGRES_DB: ${POSTGRES_DB:-lpmpp_survey}
      POSTGRES_USER: ${POSTGRES_USER:-lpmpp_app}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:?set POSTGRES_PASSWORD in root .env}
    ports:
      - "127.0.0.1:5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U $${POSTGRES_USER} -d $${POSTGRES_DB}"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - simutu

  redis:
    image: redis:7-alpine
    mem_limit: ${REDIS_MEMORY_LIMIT:-1g}
    command: redis-server --appendonly yes
    ports:
      - "127.0.0.1:6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 10
    networks:
      - simutu

  horizon:
    <<: *php-service
    mem_limit: ${WORKER_MEMORY_LIMIT:-2g}
    command: php artisan horizon
    profiles: ["workers"]
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    restart: unless-stopped

  scheduler:
    <<: *php-service
    mem_limit: ${SCHEDULER_MEMORY_LIMIT:-512m}
    command: php artisan schedule:work
    profiles: ["workers"]
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    restart: unless-stopped

  mailpit:
    image: axllent/mailpit:latest
    mem_limit: ${MAILPIT_MEMORY_LIMIT:-512m}
    ports:
      - "8025:8025"
    networks:
      - simutu

networks:
  simutu:
    driver: bridge

volumes:
  postgres_data:
  redis_data:
```

Catatan keamanan dan operasi:

- PostgreSQL dan Redis hanya diekspos ke loopback host, bukan seluruh jaringan.
- `backend/vendor` dan `frontend/node_modules` dibuat oleh container di bind mount;
  keduanya tetap diabaikan Git dan ownership mengikuti UID/GID pengguna Ubuntu.
- `postgres_data` dan `redis_data` bersifat persisten. `docker compose down` tidak
  menghapusnya; `docker compose down -v` menghapus data dan dilarang tanpa izin.
- Profile `workers` mencegah Horizon dan scheduler gagal sebelum package serta
  migration selesai dipasang.
- Untuk production, buat konfigurasi Compose/deployment terpisah: jangan expose
  PostgreSQL/Redis, jangan bind-mount source, gunakan secret manager, image digest,
  TLS, backup, dan orchestrator/process policy yang sesuai.

### 6.5 `.gitignore` root minimum

Pastikan root `.gitignore` memuat:

```gitignore
/.env
/backend/.env
/backend/.env.testing
/frontend/.env.local
```

Jangan mengabaikan `.env.example`.

---

## 7. Konfigurasi Laravel dan database container

Sesuaikan nilai berikut dalam `backend/.env`. Host service memakai nama service
Compose, bukan `127.0.0.1`:

```dotenv
APP_NAME="Sistem Survei Mutu LPMPP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=lpmpp_survey
DB_USERNAME=lpmpp_app
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@simutu.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Catatan:

- Jangan menulis nilai rahasia sebenarnya ke `.env.example`.
- Pada local development dengan `localhost`, `SESSION_DOMAIN` biasanya dapat dibiarkan kosong.
- Pada deployment, frontend dan backend sebaiknya berada dalam top-level domain yang sama, misalnya `survey.kampus.ac.id` dan `api-survey.kampus.ac.id`.
- Atur CORS agar hanya origin frontend yang diizinkan dan `supports_credentials` bernilai benar.

Build image dan pasang dependency dasar Laravel:

```bash
docker compose build app
docker compose up -d postgres redis mailpit
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
```

Buat database test terpisah di container PostgreSQL:

```bash
docker compose exec -T postgres sh -lc \
  'psql -U "$POSTGRES_USER" -d postgres -c \
  "CREATE DATABASE lpmpp_survey_test OWNER $POSTGRES_USER;"'
```

Jika database sudah ada, PostgreSQL akan memberi error `already exists`; jangan
menghapus database hanya untuk menghilangkan error tersebut. Atur environment
testing pada `backend/.env.testing` yang tidak di-commit agar memakai:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=lpmpp_survey_test
DB_USERNAME=lpmpp_app
DB_PASSWORD=PASSWORD_YANG_SAMA_DENGAN_ROOT_ENV_LOKAL
```

Database production wajib mempunyai credential dan lifecycle terpisah dari
development dan testing.

---

## 8. Memasang dependency inti melalui container

Jalankan dari root repository. Jangan menjalankan `composer`, `php`, atau
`artisan` langsung di host.

### 8.1 Sanctum untuk autentikasi SPA dan API

```bash
docker compose run --rm app php artisan install:api
```

Gunakan session cookie Sanctum untuk Vue sebagai SPA pihak pertama. Jangan menyimpan bearer token login pihak pertama pada `localStorage`.

### 8.2 Fortify untuk endpoint autentikasi headless

```bash
docker compose run --rm app composer require laravel/fortify
docker compose run --rm app php artisan fortify:install
```

Fortify dapat menyediakan login, logout, reset password, verifikasi email, dan fitur autentikasi lain tanpa memaksakan UI Blade kepada Vue.

### 8.3 Filament untuk panel admin

```bash
docker compose run --rm app composer require filament/filament:"^5.0"
docker compose run --rm app php artisan filament:install --panels
```

Filament hanya digunakan untuk Super Admin, Admin LPMPP, reviewer, PIC unit, dan verifikator yang memiliki izin. Responden dan pimpinan menggunakan Vue.

### 8.4 Role dan permission

```bash
docker compose run --rm app composer require spatie/laravel-permission
docker compose run --rm app php artisan vendor:publish \
  --provider="Spatie\Permission\PermissionServiceProvider"
```

Tambahkan trait `HasRoles` pada model `User` ketika phase implementasi dimulai.

### 8.5 Audit log

```bash
docker compose run --rm app composer require spatie/laravel-activitylog
docker compose run --rm app php artisan vendor:publish \
  --provider="Spatie\Activitylog\ActivitylogServiceProvider" \
  --tag="activitylog-migrations"
docker compose run --rm app php artisan vendor:publish \
  --provider="Spatie\Activitylog\ActivitylogServiceProvider" \
  --tag="activitylog-config"
```

Audit log aplikasi harus merekam perubahan penting, tetapi tidak boleh menyimpan password, API key utuh, session cookie, token, atau jawaban sensitif tanpa kebutuhan yang sah.

### 8.6 Queue monitoring

```bash
docker compose run --rm app composer require laravel/horizon
docker compose run --rm app php artisan horizon:install
```

Horizon membutuhkan queue berbasis Redis.

### 8.7 Laravel Boost untuk membantu Codex

Bagian ini opsional tetapi direkomendasikan pada lingkungan development:

```bash
docker compose run --rm app composer require laravel/boost --dev
docker compose run --rm app php artisan boost:install
```

Gunakan Boost hanya sebagai dependency development. Jangan mengaktifkan akses development tool pada production.

### 8.8 Migrasi awal dan pemeriksaan

```bash
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan about
docker compose run --rm app php artisan route:list
docker compose run --rm app php artisan make:filament-user
```

Jangan menjalankan `migrate:fresh`, `db:wipe`, atau penghapusan database jika sudah terdapat data yang perlu dipertahankan.

### 8.9 Dependency backend yang dipasang nanti jika benar-benar diperlukan

Codex boleh mengusulkan dependency berikut pada phase implementasi, tetapi harus memeriksa kompatibilitas dan menjelaskan alasannya terlebih dahulu:

- Export Excel: `maatwebsite/excel`.
- PDF sederhana: `barryvdh/laravel-dompdf` atau solusi lain yang disepakati.
- API documentation: package OpenAPI yang kompatibel.
- Object storage: driver S3-compatible.
- WebSocket: Laravel Reverb.
- Static analysis: Larastan/PHPStan.
- Image processing: package yang benar-benar dibutuhkan.
- Provider AI SDK: hanya jika adapter berbasis Laravel HTTP Client tidak mencukupi.

Jangan memasang package hanya karena populer. Nilai keamanan, maintenance, kompatibilitas, lisensi, dan manfaatnya.

---

## 9. Memasang dependency dan konfigurasi Vue melalui container

Jalankan dari root repository:

```bash
docker compose run --rm frontend npm install
docker compose run --rm frontend npm install \
  axios echarts vue-echarts @vueuse/core zod vee-validate \
  @vee-validate/zod date-fns lucide-vue-next
docker compose run --rm frontend npm install -D tailwindcss @tailwindcss/vite
```

Fungsi dependency:

| Dependency | Fungsi |
|---|---|
| Axios | HTTP client menuju Laravel API |
| ECharts | Mesin visualisasi data |
| vue-echarts | Integrasi ECharts dengan komponen Vue |
| VueUse | Composable utilitas Vue |
| Zod | Validasi schema dan tipe data runtime |
| VeeValidate | Validasi form Vue |
| date-fns | Pemrosesan dan format tanggal |
| Lucide Vue Next | Ikon antarmuka |
| Tailwind CSS | Styling berbasis utility |

Tambahkan plugin Tailwind pada `frontend/vite.config.ts`:

```ts
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
```

Jika `vueDevTools` tidak dipilih saat scaffolding, hapus impor dan pemanggilan plugin tersebut.

Isi awal `frontend/src/assets/main.css`:

```css
@import "tailwindcss";
```

Buat `frontend/.env.example`:

```dotenv
VITE_APP_NAME="Sistem Survei Mutu LPMPP"
VITE_API_BASE_URL=http://localhost:8000
```

Salin sebagai `.env.local` dan jangan commit nilai rahasia. Frontend tidak boleh memiliki API key provider AI.

Jalankan pemeriksaan awal:

```bash
docker compose run --rm frontend npm run lint
docker compose run --rm frontend npm run type-check
docker compose run --rm frontend npm run test:unit
docker compose run --rm frontend npm run build
```

Nama script dapat berbeda mengikuti hasil `create-vue`. Periksa `package.json` sebelum menjalankan.

### 9.1 Library UI tambahan

Jangan langsung memasang banyak component library. Pada Phase 08, pilih salah satu pendekatan:

1. Tailwind CSS dengan komponen internal yang kecil dan konsisten; atau
2. satu library UI Vue yang matang, aksesibel, terawat, dan kompatibel.

Codex boleh memasang library UI setelah keputusan dicatat dalam ADR. Hindari memakai dua component library besar sekaligus.

---

## 10. Menjalankan dan memeriksa stack development

Jalankan seluruh service dari root repository:

```bash
docker compose up -d
docker compose --profile workers up -d
docker compose ps
```

Worker profile dijalankan setelah Horizon berhasil dipasang. Lihat log tanpa
membuka banyak terminal:

```bash
docker compose logs --tail=100 app nginx frontend postgres redis
docker compose --profile workers logs --tail=100 horizon scheduler
```

Alamat default:

- Laravel API: `http://localhost:8000`
- Filament: `http://localhost:8000/admin`
- Vue: `http://localhost:5173`
- Mailpit: `http://localhost:8025`

Perintah kerja sehari-hari:

```bash
# Artisan dan test backend
docker compose exec app php artisan migrate
docker compose exec app php artisan test

# Dependency backend
docker compose exec app composer install

# Dependency dan quality gate frontend
docker compose exec frontend npm install
docker compose exec frontend npm run lint
docker compose exec frontend npm run type-check
docker compose exec frontend npm run test:unit
docker compose exec frontend npm run build

# Status, log, dan shell
docker compose ps
docker compose logs -f --tail=100
docker compose exec app sh
docker compose exec frontend bash

# Berhenti tanpa menghapus data
docker compose down
```

Gunakan `docker compose run --rm SERVICE ...` bila service belum berjalan dan
`docker compose exec SERVICE ...` bila service sudah berjalan. Setelah mengubah
Dockerfile atau versi image, jalankan:

```bash
docker compose build --pull app
docker compose up -d --force-recreate
```

Jangan menjalankan `docker compose down -v`, `docker volume rm`, `docker system
prune --volumes`, atau penghapusan volume lain tanpa backup dan izin eksplisit.

---

### 10.1 Checklist sebelum menyerahkan repository kepada Codex

- [ ] `git status` dapat dijalankan.
- [ ] Hanya Docker Engine, Compose plugin, dan Git yang wajib pada host.
- [ ] Root `.env`, `backend/.env`, `backend/.env.testing`, dan `frontend/.env.local` tidak terlacak Git.
- [ ] `docker compose config --quiet` berhasil.
- [ ] `docker compose ps` menunjukkan service inti aktif/healthy.
- [ ] `docker compose exec app php artisan migrate` berhasil.
- [ ] `docker compose exec redis redis-cli ping` menghasilkan `PONG`.
- [ ] Database `lpmpp_survey_test` tersedia dan terpisah dari development.
- [ ] Horizon dan scheduler dapat berjalan melalui profile `workers`.
- [ ] Halaman login Filament dapat dibuka.
- [ ] Vue dapat dibuka melalui port `5173`.
- [ ] Mailpit dapat dibuka melalui port `8025`.
- [ ] `docker compose run --rm frontend npm run build` berhasil.
- [ ] `docker compose run --rm app php artisan test` berhasil untuk baseline awal.
- [ ] Dokumen master prompt berada di root repository.
- [ ] Tidak ada API key, password, token, atau data pribadi asli di repository.
- [ ] Tidak ada volume penting yang akan dihapus oleh command setup.

Commit baseline secara manual jika seluruh pemeriksaan berhasil:

```bash
git add .
git commit -m "chore: initialize LPMPP survey platform"
```

Periksa `git diff --cached` sebelum commit untuk memastikan tidak ada rahasia.

---

# BAGIAN II — MASTER PROMPT UNTUK CODEX

## 11. Identitas dan tujuan sistem

Anda bertindak sebagai gabungan:

- System Analyst;
- Business Analyst;
- Software Architect;
- Database Designer;
- UI/UX Designer;
- Laravel Engineer;
- Vue Engineer;
- Quality Assurance Engineer;
- Security Engineer;
- Survey Methodology Analyst;
- AI Integration Engineer.

Nama sistem:

**Sistem Informasi Survei Mutu, Analitik, dan Tindak Lanjut LPMPP**

Nama singkat sementara:

**SIMUTU LPMPP**

Sistem ditujukan untuk Lembaga Penjaminan Mutu dan Pengembangan Pendidikan/Pembelajaran perguruan tinggi. Tujuannya adalah mengelola siklus survei secara terpusat mulai dari penyusunan instrumen, validasi, publikasi, pengumpulan jawaban, analisis statistik, analisis AI terkontrol, laporan, temuan mutu, rencana tindak lanjut, verifikasi, dan evaluasi antarperiode.

Sistem bukan sekadar pengganti Google Forms. Sistem harus menjadi sumber bukti penjaminan mutu yang dapat ditelusuri, diaudit, dan digunakan untuk pengambilan keputusan.

---

## 12. Prinsip produk wajib

1. Statistik deterministik adalah sumber utama. AI hanya fitur pendukung.
2. Seluruh hasil AI harus berlabel, dapat ditinjau manusia, dan tidak boleh mengubah data asli.
3. Instrumen harus dapat divalidasi, diberi versi, digunakan ulang, dan dikunci setelah survei aktif.
4. Hasil survei harus dapat menghasilkan temuan dan tindak lanjut.
5. Akses data pimpinan dibatasi berdasarkan unit organisasi.
6. Survei anonim dan survei rahasia harus diperlakukan berbeda.
7. Identitas responden tidak boleh tampil pada dashboard pimpinan.
8. Data kelompok kecil harus disembunyikan berdasarkan minimum reporting threshold.
9. API key AI hanya dikelola Super Admin, terenkripsi, dimasking, tidak dikirim ke frontend, dan dapat dirotasi.
10. Sistem harus tetap berfungsi saat layanan AI gagal atau dinonaktifkan.
11. Semua akses sensitif harus diperiksa di backend, bukan hanya disembunyikan pada UI.
12. Setiap perubahan penting harus memiliki audit trail.
13. Gunakan Bahasa Indonesia yang formal, sederhana, dan konsisten pada UI serta dokumentasi.
14. Penuhi prinsip aksesibilitas, termasuk navigasi keyboard, kontras, label form, focus state, dan ukuran teks yang cukup.
15. Optimalkan MVP untuk kebutuhan nyata LPMPP, bukan untuk memamerkan jumlah fitur.

---

## 13. Dasar regulasi dan metodologi

Gunakan sumber primer dan terbaru. Minimal periksa:

1. Permendiktisaintek Nomor 39 Tahun 2025 tentang Penjaminan Mutu Pendidikan Tinggi.
2. Permendiktisaintek Nomor 10 Tahun 2026 tentang perubahan atas Permendiktisaintek Nomor 39 Tahun 2025.
3. Instrumen akreditasi perguruan tinggi/program studi yang berlaku dari BAN-PT atau LAM terkait.
4. PermenPANRB Nomor 14 Tahun 2017 hanya untuk template Survei Kepuasan Masyarakat pada konteks pelayanan publik yang sesuai.
5. Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.
6. Pedoman SPMI dan kebijakan internal perguruan tinggi yang diberikan pemilik sistem.

Jangan menggunakan regulasi yang sudah dicabut sebagai dasar utama. Jika instrumen BAN-PT/LAM berubah, catat tanggal pemeriksaan dan tautan sumber.

Bedakan metode berikut:

| Metode | Penggunaan |
|---|---|
| SERVQUAL | Mengukur gap harapan dan persepsi melalui pasangan item |
| SERVPERF | Mengukur persepsi kinerja aktual tanpa pasangan harapan |
| IPA | Membandingkan kepentingan dan kinerja untuk prioritas |
| CSI | Indeks kepuasan berbobot |
| SKM/IKM | Pengukuran layanan publik berdasarkan pedoman yang sesuai |
| NPS | Indikator rekomendasi, bukan pengganti pengukuran mutu utama |

Jangan menyebut instrumen sebagai SERVQUAL murni apabila hanya mengukur kepuasan/persepsi satu kali.

Untuk data Likert:

- tampilkan distribusi jawaban;
- median dan modus;
- mean jika kebijakan analisis mengizinkan;
- persentase kategori positif atau top-two box;
- jumlah jawaban valid dan kosong;
- metode normalisasi;
- interpretasi dan batas kategorinya;
- peringatan jika sampel terlalu kecil.

Cronbach's Alpha hanya mengukur konsistensi internal, bukan membuktikan validitas. Rancang validasi isi oleh ahli, pilot test, analisis butir, dan analisis faktor bila jumlah data serta tujuan penelitian memadai.

---

## 14. Pengguna dan kewenangan

### 14.1 Responden

Kelompok responden dapat meliputi:

- mahasiswa;
- dosen;
- tenaga kependidikan;
- alumni;
- pengguna lulusan;
- mitra;
- orang tua/wali;
- stakeholder eksternal;
- kelompok lain melalui master data.

Responden dapat melihat survei yang ditujukan kepadanya, menyimpan jawaban sementara, mengirim jawaban, melihat status pengisian, dan melihat riwayat yang diizinkan. Responden tidak dapat melihat jawaban orang lain.

Responden eksternal harus dapat mengisi melalui secure invitation link tanpa dipaksa mempunyai akun lokal, jika kebijakan survei mengizinkan.

### 14.2 Admin LPMPP

Mengelola kegiatan operasional survei, instrumen, target, periode, hasil, laporan, analisis, temuan, dan tindak lanjut sesuai permission. Tidak boleh melihat API key dalam bentuk utuh.

### 14.3 Super Admin

Mengelola user, role, permission, struktur organisasi, konfigurasi, integrasi, provider AI, API key, batas biaya, audit, notifikasi, dan kesehatan sistem.

### 14.4 Pimpinan

Read-only sesuai scope:

- Rektor: seluruh institusi;
- Wakil Rektor: bidang yang menjadi kewenangannya;
- Dekan: fakultasnya;
- Kaprodi: program studinya;
- Kepala unit: unitnya.

Pimpinan dapat melihat dashboard, tren, perbandingan, prioritas perbaikan, laporan, dan status tindak lanjut. Pimpinan tidak boleh mengubah data survei.

### 14.5 Permission tambahan

Gunakan permission atau assignment untuk:

- pembuat instrumen;
- reviewer instrumen;
- approver/penerbit survei;
- PIC unit tindak lanjut;
- verifikator LPMPP;
- auditor;
- data steward.

Jangan membuat role global baru jika cukup diselesaikan dengan permission dan scope unit.

---

## 15. Ruang lingkup domain

Struktur survei utama:

```text
Template Instrumen
└── Versi Instrumen
    └── Survei
        └── Periode
            └── Bagian/Section
                └── Kategori/Dimensi
                    └── Indikator
                        └── Pertanyaan
                            └── Skala/Opsi Jawaban
                                └── Respons dan Jawaban
```

Jenis pertanyaan MVP:

- Likert 1–5;
- Likert 1–4;
- Ya/Tidak;
- pilihan tunggal;
- pilihan jamak;
- dropdown;
- jawaban singkat;
- jawaban panjang;
- angka;
- tanggal;
- matriks sederhana.

Jenis lanjutan:

- NPS;
- upload file;
- branching kompleks;
- ranking;
- pasangan harapan–persepsi;
- pasangan kepentingan–kinerja.

Keluarga template:

- kepuasan layanan akademik;
- pembelajaran;
- dosen;
- tenaga kependidikan;
- fasilitas;
- sistem informasi;
- tata pamong;
- penelitian;
- pengabdian;
- kerja sama;
- alumni/tracer study;
- pengguna lulusan;
- mitra;
- layanan publik/SKM jika sesuai.

### 15.1 Master data wajib

Master data harus dinamis dan dapat dikelola tanpa mengubah source code, dengan pengecualian enum teknis yang memang harus dikontrol aplikasi. Minimal mencakup:

- identitas perguruan tinggi;
- fakultas;
- program studi;
- unit kerja dan hierarki organisasi;
- tahun akademik;
- semester;
- jenjang pendidikan;
- status mahasiswa;
- angkatan;
- kelompok responden;
- jenis stakeholder;
- user, role, permission, dan data scope;
- kategori/dimensi;
- indikator;
- bank pertanyaan;
- template skala dan opsi;
- metode pengukuran dan scoring rule;
- periode survei;
- status survei;
- provider dan model AI;
- format laporan;
- template notifikasi;
- status serta prioritas tindak lanjut;
- konfigurasi sistem yang aman untuk dikelola melalui UI.

### 15.2 Field konfigurasi AI

Halaman konfigurasi AI khusus Super Admin minimal mempertimbangkan:

- nama internal konfigurasi;
- provider;
- allowlisted Base URL;
- API key/secret;
- nama model;
- status aktif;
- capability yang diizinkan;
- batas input/output token;
- batas penggunaan harian dan bulanan;
- batas biaya;
- timeout;
- retry policy;
- temperature;
- maksimum output;
- jenis data yang boleh dikirim;
- kewajiban agregasi/redaction;
- minimum jumlah respons;
- lokasi pemrosesan/data residency jika tersedia;
- tanggal pembaruan dan rotasi secret;
- pengguna yang mengubah;
- tombol test connection yang aman.

Secret hanya boleh ditampilkan dalam bentuk masked fingerprint/karakter akhir dan tidak boleh dapat dibaca kembali sebagai plaintext melalui UI atau API.

### 15.3 Inventaris halaman wajib

Halaman responden:

- login;
- lupa/reset password;
- dashboard responden;
- daftar survei;
- detail survei;
- pengisian survei;
- konfirmasi pengiriman;
- bukti selesai;
- riwayat;
- profil;
- notifikasi.

Halaman Admin LPMPP/Filament:

- dashboard operasional;
- manajemen template dan survei;
- survey builder;
- review dan approval;
- bank pertanyaan;
- kategori, indikator, skala, dan scoring rule;
- target dan invitation;
- data responden;
- monitoring response rate;
- hasil dan visualisasi;
- analisis statistik;
- analisis AI;
- laporan dan export;
- temuan dan tindak lanjut;
- notifikasi;
- audit terbatas.

Halaman Super Admin/Filament:

- dashboard sistem;
- pengguna;
- role, permission, dan data scope;
- struktur organisasi;
- master data;
- konfigurasi AI;
- konfigurasi email/notifikasi;
- konfigurasi aplikasi;
- log penggunaan AI;
- audit log;
- status queue/scheduler/integrasi;
- backup/restore guidance atau status, bukan eksekusi berbahaya tanpa kontrol.

Halaman pimpinan/Vue:

- dashboard eksekutif;
- indeks dan persentase kepuasan;
- tren;
- perbandingan unit;
- detail kategori dan indikator;
- prioritas perbaikan;
- ringkasan AI yang telah ditinjau;
- status tindak lanjut;
- laporan.

Setiap spesifikasi halaman harus memuat tujuan, pengguna, data, komponen, aksi, filter, state loading/empty/error, permission, data scope, dan aturan privasi.

### 15.4 Notifikasi wajib

Minimal rancang notifikasi untuk:

- survei baru;
- pengingat belum mengisi;
- survei akan ditutup;
- respons berhasil dikirim;
- target respons tercapai;
- response rate rendah;
- laporan/export selesai atau gagal;
- analisis AI selesai atau gagal;
- penggunaan AI mendekati batas;
- temuan ditugaskan;
- tenggat tindak lanjut mendekat/terlambat;
- bukti dikirim;
- hasil verifikasi.

Channel MVP adalah in-app dan email. WhatsApp merupakan integrasi lanjutan kecuali pemilik sistem menyatakan wajib.

---

## 16. Fitur utama

### 16.1 Manajemen instrumen dan survei

- bank pertanyaan;
- template instrumen;
- versi instrumen dan pertanyaan;
- validasi/review instrumen;
- survey builder;
- section, kategori, indikator, dan pertanyaan;
- urutan pertanyaan;
- pertanyaan wajib;
- label skala kustom;
- targeting responden;
- periode;
- draft, review, approved, scheduled, active, closed, archived;
- preview;
- duplikasi survei;
- kunci perubahan setelah respons tersedia;
- pembukaan ulang berdasarkan otorisasi dan audit.

### 16.2 Pengumpulan respons

- login atau secure invitation;
- one respondent one response jika diaktifkan;
- autosave;
- progress bar;
- validasi;
- mobile responsive;
- konfirmasi submit;
- bukti selesai;
- pengingat;
- mode anonim dan rahasia;
- token undangan yang di-hash;
- pemisahan identitas dan jawaban.

### 16.3 Analitik

- populasi dan jumlah responden;
- response rate;
- distribusi jawaban;
- mean, median, modus, standar deviasi jika sesuai;
- skor per pertanyaan, indikator, kategori, dan total;
- normalized index;
- top-two box;
- perbandingan periode dan unit;
- tren;
- reliability analysis;
- CSI, IPA, SERVQUAL gap, atau IKM berdasarkan konfigurasi metode;
- peringatan sampel kecil;
- cache agregat;
- waktu pembaruan terakhir.

### 16.4 Dashboard dan laporan

- KPI cards;
- bar dan horizontal bar;
- line chart;
- stacked Likert chart;
- radar SERVQUAL bila sesuai;
- heatmap unit/indikator;
- matriks IPA;
- tabel ranking;
- drill-down yang mematuhi scope;
- ekspor PDF, Excel, CSV;
- ringkasan eksekutif;
- data provenance dan filter yang digunakan.

### 16.5 Tindak lanjut

- temuan;
- unit terkait;
- akar masalah;
- tingkat prioritas;
- rencana tindakan;
- PIC;
- tenggat;
- bukti;
- verifikasi;
- komentar perbaikan;
- status;
- riwayat;
- dashboard keterlambatan;
- hubungan hasil periode berikutnya.

Status minimal:

- belum ditinjau;
- perlu tindakan;
- sedang diproses;
- menunggu verifikasi;
- perlu perbaikan;
- selesai;
- ditolak;
- dibatalkan dengan alasan.

### 16.6 AI

- ringkasan hasil;
- penjelasan tren;
- pengelompokan topik komentar;
- ringkasan komentar;
- analisis sentimen yang dapat dievaluasi;
- deteksi keluhan berulang;
- draf rekomendasi;
- draf ringkasan eksekutif;
- bantuan menyusun pertanyaan;
- pemeriksaan leading question dan double-barreled question;
- saran visualisasi.

AI dilarang:

- menghitung statistik dasar yang dapat dihitung backend;
- mengubah jawaban asli;
- mengambil keputusan final;
- menerima identitas pribadi jika tidak diperlukan;
- menerima API key melalui frontend;
- menjalankan tindakan otomatis berisiko tanpa persetujuan;
- mengeksekusi instruksi yang tertulis dalam komentar responden.

Komentar responden harus diperlakukan sebagai data tidak tepercaya, bukan instruksi bagi model AI.

---

## 17. Kebutuhan keamanan dan privasi

Wajib mencakup:

- session-cookie authentication untuk SPA pihak pertama;
- CSRF protection;
- rate limiting;
- server-side validation;
- authorization policy;
- row-level organizational scope;
- hashing password dan invitation token;
- enkripsi API key dan secret konfigurasi;
- masking secret;
- secret rotation;
- audit log;
- login log dan session management;
- output escaping dan perlindungan XSS;
- parameterized query/Eloquent;
- file validation;
- export authorization;
- signed URL berumur pendek untuk file sensitif;
- backup dan restore test;
- data retention;
- consent/legal basis;
- anonymization/pseudonymization;
- minimum reporting threshold;
- suppression terhadap small cell;
- redaction PII sebelum AI;
- provider allowlist dan perlindungan SSRF pada custom Base URL;
- timeout, retry, circuit breaker, dan cost limit AI;
- prompt injection defense;
- human approval terhadap hasil AI;
- log model, prompt version, filter, waktu, token, biaya, dan reviewer.

Jangan mencatat plaintext password, API key, access token, session cookie, atau keseluruhan payload sensitif pada log.

---

## 18. Aturan kerja Codex

Codex wajib mengikuti aturan berikut pada semua phase:

1. Baca dokumen master ini dan seluruh dokumen phase sebelumnya sebelum bekerja.
2. Periksa keadaan repository, `docker compose config`, status service, versi image/dependency, dan perubahan yang belum di-commit.
3. Jangan menghapus atau menimpa pekerjaan pengguna yang tidak berkaitan.
4. Kerjakan hanya phase yang diminta.
5. Jangan meneruskan otomatis ke phase berikutnya.
6. Pada phase perancangan, jangan membuat kode produksi.
7. Pada phase implementasi, jangan mengubah ruang lingkup tanpa mencatat change request.
8. Gunakan sumber primer/resmi untuk fakta yang dapat berubah.
9. Tandai fakta yang belum terverifikasi sebagai `Asumsi` atau `Perlu Konfirmasi`.
10. Jangan mengarang kebijakan perguruan tinggi.
11. Jika keputusan pengguna benar-benar diperlukan, kumpulkan pertanyaan dalam decision log dan berhenti pada batas yang aman.
12. Jangan melakukan operasi destruktif seperti `migrate:fresh`, `db:wipe`, penghapusan file massal, atau reset Git tanpa izin eksplisit.
13. Jangan menginstal PHP, Composer, Node.js, npm, PostgreSQL, Redis, atau Nginx pada host; gunakan container yang telah ditentukan.
14. Dependency tambahan hanya boleh dipasang bila dibutuhkan, kompatibel, terawat, dan alasannya dicatat pada ADR serta dependency log.
15. Setelah perubahan kode, jalankan test, lint, type-check, dan build yang relevan melalui service Compose.
16. Dokumentasikan setiap endpoint, migration, aturan bisnis, dan permission baru.
17. Gunakan migration; jangan mengubah database secara manual tanpa migration.
18. Gunakan Laravel Form Request, Policy, Service/Action sesuai kebutuhan, API Resource, Job, Event, dan Notification secara proporsional.
19. Hindari controller gemuk dan business logic di Vue component atau Filament Resource.
20. Gunakan TypeScript strict dan hindari `any` tanpa alasan.
21. Gunakan accessible HTML dan komponen yang dapat dinavigasi dengan keyboard.
22. Buat commit hanya jika pengguna memintanya. Jangan push atau deploy tanpa izin.
23. Jalankan command aplikasi dari root repository dengan `docker compose exec` atau `docker compose run --rm`; jangan mengandalkan runtime host.
24. Jangan menjalankan `docker compose down -v`, `docker volume rm`, `docker system prune --volumes`, atau menghapus bind mount/named volume tanpa izin eksplisit dan pemeriksaan target.
25. Jangan menginstal ulang Laravel, Filament, Vue, atau dependency dasar jika sudah tersedia. Jika dependency tambahan dibutuhkan, pasang di container, perbarui lockfile, dan catat alasannya.
26. Jika container gagal, periksa `docker compose ps`, healthcheck, dan log service yang relevan sebelum mengubah kode atau konfigurasi.

Dokumen kontrol yang harus dipelihara:

```text
docs/00-project-control/progress.md
docs/00-project-control/assumptions.md
docs/00-project-control/decisions.md
docs/00-project-control/open-questions.md
docs/00-project-control/change-log.md
docs/00-project-control/dependency-log.md
docs/00-project-control/traceability-matrix.md
docs/00-project-control/glossary.md
```

Setiap akhir phase, tulis:

- ringkasan hasil;
- file yang dibuat/diubah;
- keputusan;
- asumsi;
- pertanyaan terbuka;
- risiko;
- validasi yang dilakukan;
- rekomendasi phase berikutnya.

---

# BAGIAN III — PROMPT PEMBUKA DAN PHASE

## 19. Prompt pembuka Codex

Salin prompt berikut ketika pertama kali membuka repository:

```text
Baca MASTER_PROMPT_CODEX_SURVEI_MUTU_LPMPP.md secara menyeluruh.

Lakukan onboarding repository tanpa mengimplementasikan fitur bisnis dan tanpa memasang ulang dependency dasar.

Tugas Anda:
1. Periksa struktur repository, Git status, `docker compose config`, status/health service, versi image, serta versi PHP/Composer/Laravel/Filament/Node/npm/Vue/Vite/PostgreSQL/Redis di dalam container.
2. Jalankan pemeriksaan read-only atau aman melalui Docker Compose untuk memastikan backend, database development, database test, Redis, Filament, frontend, Horizon, scheduler, Mailpit, test baseline, lint, type-check, dan build dapat digunakan.
3. Jangan menginstal runtime pada host. Jangan menjalankan migrate:fresh, db:wipe, docker compose down -v, penghapusan volume, reset Git, penghapusan file, atau perubahan data penting.
4. Buat struktur folder docs sebagaimana ditetapkan master prompt.
5. Buat dokumen kontrol awal pada docs/00-project-control/.
6. Catat perbedaan antara kondisi repository dengan baseline master prompt.
7. Jika image, service, atau dependency dasar belum tersedia, laporkan service, command, dan error secara spesifik. Jangan langsung mengganti arsitektur atau melakukan instalasi besar.
8. Semua command aplikasi harus menggunakan `docker compose exec` atau `docker compose run --rm` dari root repository.
9. Berhenti setelah laporan onboarding selesai. Jangan masuk ke Phase 01.

Output akhir harus menjelaskan status READY, READY WITH NOTES, atau BLOCKED dan alasan yang dapat ditindaklanjuti.
```

---

## 20. Daftar phase dan output

| Phase | Fokus | Jenis pekerjaan |
|---|---|---|
| 00 | Onboarding repository | Pemeriksaan |
| 01 | Discovery dan riset | Perancangan |
| 02 | Product scope dan MVP | Perancangan |
| 03 | Metodologi survei dan instrumen | Perancangan |
| 04 | Kebutuhan dan acceptance criteria | Perancangan |
| 05 | Proses, use case, dan UML | Perancangan |
| 06 | Data, arsitektur, keamanan, dan AI governance | Perancangan |
| 07 | Kontrak API dan strategi integrasi | Perancangan |
| 08 | UI/UX, wireframe, dan prototype | Desain/prototype |
| 09 | Fondasi implementasi | Implementasi |
| 10 | Manajemen survei dan Filament | Implementasi |
| 11 | Pengumpulan respons Vue | Implementasi |
| 12 | Statistik, dashboard, dan laporan | Implementasi |
| 13 | AI, notifikasi, dan tindak lanjut | Implementasi |
| 14 | QA, keamanan, performa, dan deployment | Implementasi/validasi |

---

## Phase 01 — Discovery dan riset

### Prompt

```text
Jalankan Phase 01 saja: Discovery dan Riset.

Baca master prompt dan dokumen kontrol. Jangan menulis kode aplikasi.

Lakukan riset berbasis sumber primer/resmi mengenai:
- regulasi penjaminan mutu pendidikan tinggi yang berlaku;
- instrumen BAN-PT/LAM yang relevan;
- pelaksanaan survei kepuasan stakeholder perguruan tinggi;
- siklus PPEPP dan closing the feedback loop;
- perbedaan SERVQUAL, SERVPERF, IPA, CSI, SKM/IKM, dan NPS;
- praktik platform survei pendidikan tinggi;
- privasi dan penggunaan AI pada data survei.

Buat:
1. docs/01-discovery/research-report.md
2. docs/01-discovery/regulatory-basis.md
3. docs/01-discovery/comparable-systems.md
4. docs/01-discovery/stakeholder-interview-guide.md
5. docs/01-discovery/source-register.md

Setiap sumber harus memiliki judul, penerbit, tanggal/tahun, URL/DOI, tanggal akses, klaim yang didukung, dan status sumber primer/sekunder.

Identifikasi kesenjangan antara keinginan awal dengan praktik yang benar. Pisahkan fakta, rekomendasi, asumsi, dan hal yang perlu dikonfirmasi.

Perbarui dokumen kontrol dan berhenti setelah Phase 01. Jangan lanjut ke Phase 02.
```

### Definition of Done

- Regulasi yang berlaku teridentifikasi.
- Metode survei tidak dicampur secara keliru.
- Sistem pembanding dan praktik tindak lanjut terdokumentasi.
- Pertanyaan wawancara pemilik sistem siap digunakan.
- Semua klaim penting mempunyai sumber.

---

## Phase 02 — Product scope dan MVP

### Prompt

```text
Jalankan Phase 02 saja: Product Scope dan MVP.

Gunakan hasil Phase 01. Jangan menulis kode aplikasi.

Buat:
1. product vision dan problem statement;
2. tujuan, manfaat, outcome, dan KPI produk;
3. stakeholder map;
4. actor dan organizational data scope;
5. in-scope, out-of-scope, constraint, dependency, dan assumption;
6. module map;
7. MVP, post-MVP, dan long-term backlog;
8. release roadmap;
9. success metrics;
10. daftar istilah formal;
11. daftar keputusan yang harus dikonfirmasi pemilik sistem.
12. lima alternatif nama sistem yang formal, mudah diingat, dan sesuai lingkungan perguruan tinggi.

Output:
- docs/02-product-scope/product-brief.md
- docs/02-product-scope/scope-and-boundaries.md
- docs/02-product-scope/module-map.md
- docs/02-product-scope/mvp-and-roadmap.md
- docs/02-product-scope/success-metrics.md

MVP harus realistis dan tidak memasukkan seluruh fitur lanjutan. Jelaskan alasan prioritas dengan MoSCoW atau metode setara.

Perbarui traceability matrix dan berhenti setelah Phase 02.
```

### Definition of Done

- Batas MVP jelas.
- Tidak ada fitur yang masuk hanya karena menarik secara teknis.
- Aktor dan batas data organisasi jelas.
- Indikator keberhasilan dapat diukur.

---

## Phase 03 — Metodologi survei dan instrumen

### Prompt

```text
Jalankan Phase 03 saja: Metodologi Survei dan Instrumen.

Jangan menulis kode aplikasi.

Rancang:
- taksonomi keluarga survei;
- struktur template, versi, kategori, indikator, pertanyaan, skala, dan scoring rule;
- pilihan metode SERVQUAL, SERVPERF, IPA, CSI, SKM/IKM, dan metode internal;
- rumus, normalisasi, interpretasi, rounding, missing data, dan threshold;
- content validity, expert review, pilot test, item analysis, reliability, dan factor analysis bila sesuai;
- response rate dan risiko nonresponse bias;
- minimum reporting threshold;
- aturan anonim dan rahasia;
- contoh lengkap Survei Kepuasan Mahasiswa terhadap Layanan Akademik;
- pasangan importance-performance dan expectation-perception bila metode memerlukannya;
- pedoman penulisan item yang netral, tunggal, sederhana, dan tidak menggiring.

Output:
- docs/03-survey-methodology/methodology-framework.md
- docs/03-survey-methodology/scoring-catalog.md
- docs/03-survey-methodology/instrument-validation.md
- docs/03-survey-methodology/question-writing-guide.md
- docs/03-survey-methodology/example-student-academic-service-questionnaire.md
- docs/03-survey-methodology/reporting-threshold-and-anonymity.md

Contoh kuesioner harus memuat kode, kategori, indikator, item, jenis jawaban, pilihan, wajib/opsional, metode, dan tujuan pengukuran.

Berhenti setelah Phase 03.
```

### Definition of Done

- SERVQUAL dan SERVPERF dibedakan.
- IPA hanya tersedia jika importance dan performance diukur.
- Rumus dapat diuji dengan contoh angka.
- Validitas tidak disimpulkan hanya dari Cronbach's Alpha.
- Aturan anonim dan small-cell suppression terdokumentasi.

---

## Phase 04 — Kebutuhan dan acceptance criteria

### Prompt

```text
Jalankan Phase 04 saja: Requirements Engineering.

Jangan menulis kode aplikasi.

Buat kebutuhan yang unik, tidak ambigu, dapat diuji, dan dapat ditelusuri:
- minimal 60 functional requirements per modul dengan kode FR;
- minimal 30 non-functional requirements dengan kode NFR dan parameter terukur;
- minimal 30 business rules dengan kode BR;
- user story per aktor;
- acceptance criteria Given/When/Then;
- role-permission-data-scope matrix;
- data classification matrix;
- notification matrix;
- report and export matrix;
- risk register;
- requirement traceability matrix.

Jumlah minimum user story:
- 8 untuk responden;
- 12 untuk Admin LPMPP;
- 10 untuk Super Admin;
- 8 untuk pimpinan;
- tambahan untuk reviewer/PIC/verifikator jika menjadi bagian MVP.

Output:
- docs/04-requirements/functional-requirements.md
- docs/04-requirements/non-functional-requirements.md
- docs/04-requirements/business-rules.md
- docs/04-requirements/user-stories-and-acceptance-criteria.md
- docs/04-requirements/access-control-matrix.md
- docs/04-requirements/data-classification.md
- docs/04-requirements/risk-register.md

NFR harus memuat target awal untuk performance, concurrency, availability, backup, recovery, security, accessibility, privacy, auditability, maintainability, dan compatibility. Tandai target yang masih memerlukan persetujuan.

Berhenti setelah Phase 04.
```

### Definition of Done

- Setiap kebutuhan dapat diuji.
- Setiap user story terkait FR/BR.
- Create/Read/Update/Delete/Execute/Export dan data scope dibedakan.
- Risiko memiliki penyebab, dampak, level, mitigasi, owner, dan indikator.

---

## Phase 05 — Proses, use case, dan UML

### Prompt

```text
Jalankan Phase 05 saja: Process Design dan UML.

Jangan menulis kode aplikasi.

Buat diagram Mermaid yang valid dan penjelasan untuk:
- system context;
- use case diagram per kelompok aktor;
- use case specifications penting;
- activity diagram pembuatan, review, publikasi, pengisian, analisis, laporan, dan tindak lanjut;
- sequence diagram login, daftar survei, autosave, submit, agregasi, AI, export, dan konfigurasi secret;
- state diagram lifecycle survei, respons, AI job, report export, dan follow-up;
- data flow diagram level 0 dan level 1;
- exception flow dan recovery.

Buat spesifikasi rinci minimal untuk use case:
1. login;
2. membuat survei;
3. menambahkan pertanyaan;
4. mengirim survei untuk review;
5. menyetujui dan mempublikasikan survei;
6. mengisi survei;
7. autosave jawaban;
8. mengirim respons final;
9. melihat dashboard hasil;
10. menjalankan analisis statistik;
11. menjalankan analisis AI;
12. mengelola konfigurasi API AI;
13. mengekspor laporan;
14. membuat temuan dan tindak lanjut;
15. memverifikasi tindak lanjut.

Format spesifikasi: ID, nama, tujuan, aktor, trigger, precondition, postcondition, data, permission, main flow, alternative flow, failure flow, dan business rules.

Output:
- docs/05-process-and-uml/system-context.md
- docs/05-process-and-uml/use-cases.md
- docs/05-process-and-uml/use-case-specifications.md
- docs/05-process-and-uml/activity-diagrams.md
- docs/05-process-and-uml/sequence-diagrams.md
- docs/05-process-and-uml/state-machines.md
- docs/05-process-and-uml/data-flow.md

Jelaskan include, extend, generalization, system boundary, precondition, postcondition, alternative flow, dan failure flow.

Validasi sintaks Mermaid sebelum menyelesaikan phase. Berhenti setelah Phase 05.
```

---

## Phase 06 — Data, arsitektur, keamanan, dan AI governance

### Prompt

```text
Jalankan Phase 06 saja: Data, Architecture, Security, and AI Governance.

Jangan mengimplementasikan kode produksi.

Buat:
- C4 context, container, dan component diagram;
- arsitektur Laravel, Filament, Vue, PostgreSQL, Redis, queue, storage, email, dan provider AI;
- ERD lengkap;
- data dictionary;
- strategi UUID/ULID;
- foreign key dan delete behavior;
- indexing dan uniqueness strategy;
- data partition/archive strategy;
- data retention dan deletion workflow;
- anonymous/confidential response model;
- aggregate snapshot/cache model;
- threat model menggunakan STRIDE atau metode setara;
- authorization architecture;
- encryption dan key management;
- AI provider adapter, redaction pipeline, prompt versioning, cost control, evaluation, dan human review;
- backup, restore, RPO, dan RTO;
- architecture decision records.

Output:
- docs/06-data-architecture-security/architecture.md
- docs/06-data-architecture-security/erd.md
- docs/06-data-architecture-security/data-dictionary.md
- docs/06-data-architecture-security/data-lifecycle.md
- docs/06-data-architecture-security/security-and-privacy.md
- docs/06-data-architecture-security/threat-model.md
- docs/06-data-architecture-security/ai-governance.md
- docs/06-data-architecture-security/adr/

ERD minimal harus mencakup users, roles, permissions, organizational units, respondent groups, survey templates, instrument versions, surveys, periods, targets, invitations, sections, categories, indicators, questions, options, scales, responses, answers, metadata, aggregate snapshots, analysis runs, AI configurations, AI jobs/results, reports, exports, findings, actions, evidence, notifications, consents, audit logs, and settings.

Jangan menyimpan API key sebagai plaintext. Jangan membuat custom Base URL tanpa allowlist dan perlindungan SSRF.

Berhenti setelah Phase 06.
```

---

## Phase 07 — Kontrak API dan strategi integrasi

### Prompt

```text
Jalankan Phase 07 saja: API Contract and Integration Strategy.

Jangan mengimplementasikan endpoint produksi.

Buat:
- API conventions;
- versioning;
- authentication dan CSRF flow;
- endpoint catalog;
- request/response schema;
- validation error format;
- pagination, filter, sort, include, dan field selection;
- idempotency untuk submit dan job;
- optimistic locking/version conflict;
- rate limit;
- permission dan scope per endpoint;
- OpenAPI draft;
- event/job catalog;
- scheduler catalog;
- webhook policy untuk masa depan;
- error and retry matrix;
- integration contract SIAKAD/SSO/email/AI sebagai interface, bukan asumsi implementasi.

Output:
- docs/07-api-contract/api-guidelines.md
- docs/07-api-contract/endpoint-catalog.md
- docs/07-api-contract/openapi.yaml
- docs/07-api-contract/events-jobs-schedules.md
- docs/07-api-contract/integration-contracts.md
- docs/07-api-contract/error-catalog.md

Gunakan Laravel API Resource sebagai prinsip response transformation. Pastikan endpoint pimpinan dan export mematuhi organizational scope serta reporting threshold.

Validasi sintaks OpenAPI. Berhenti setelah Phase 07.
```

---

## Phase 08 — UI/UX, wireframe, dan prototype

### Prompt

```text
Jalankan Phase 08 saja: UI/UX Design and Clickable Prototype.

Gunakan Vue yang sudah terpasang. Jangan menghubungkan prototype ke database production atau provider AI nyata.

Pertama buat dokumentasi:
- information architecture;
- sitemap per role;
- navigation model;
- design tokens;
- color, typography, spacing, icon, component, table, form, filter, chart, and empty/error/loading states;
- responsive behavior;
- accessibility checklist;
- textual wireframes;
- user flow;
- content design;
- dashboard specification;
- prototype test scenarios.

Buat wireframe tekstual rinci minimal untuk:
1. dashboard responden;
2. daftar/detail survei;
3. form pengisian survei;
4. dashboard Admin LPMPP;
5. survey builder;
6. monitoring respons;
7. dashboard visualisasi;
8. halaman analisis AI;
9. dashboard pimpinan;
10. konfigurasi AI;
11. tindak lanjut;
12. laporan.

Gaya visual:
- formal, akademik, profesional, berbasis data;
- terinspirasi karakter portal SINTA berupa KPI, filter, pencarian, tabel, dan kepadatan informasi;
- tidak menyalin logo, aset, layout identik, atau identitas SINTA;
- warna solid, kontras jelas, tanpa gradient berlebihan;
- teks utama minimal sekitar 16 px;
- ramah desktop dan smartphone;
- grafik tidak terlalu padat.

Kemudian buat clickable prototype menggunakan fixture/mocked data untuk alur prioritas:
1. login;
2. dashboard responden;
3. daftar dan detail survei;
4. pengisian, autosave simulation, validasi, dan submit confirmation;
5. dashboard admin overview sebagai mock/reference bila admin production tetap Filament;
6. survey builder prototype;
7. hasil survei;
8. dashboard pimpinan dengan filter unit;
9. analisis AI berlabel;
10. konfigurasi AI dengan masked secret;
11. temuan dan tindak lanjut;
12. laporan.

Output dokumentasi pada docs/08-ui-ux-prototype/ dan kode prototype pada frontend/.

Jika library UI tambahan diperlukan, evaluasi satu library saja, catat ADR dan dependency log, lalu minta persetujuan jika berdampak besar. Jangan memasang dua sistem komponen besar.

Jalankan lint, type-check, unit test, accessibility checks yang tersedia, dan production build. Berhenti setelah Phase 08 dan laporkan cara membuka prototype.
```

---

## Phase 09 — Fondasi implementasi

### Prompt

```text
Jalankan Phase 09 saja: Implementation Foundation.

Implementasikan fondasi berdasarkan dokumen yang telah disetujui. Jangan mengimplementasikan seluruh modul survei.

Backend:
- configuration baseline;
- database connection dan test database;
- authentication Fortify + Sanctum session cookie;
- user, role, permission, organizational scope;
- policy dan middleware;
- master structure organisasi minimal;
- base API response/error conventions;
- audit baseline;
- queue/Horizon baseline;
- health check;
- factories dan seeders data fiktif;
- test foundation.

Filament:
- panel access;
- user/role/permission/organizational unit management sesuai scope;
- dashboard system status dasar;
- jangan tampilkan secret.

Frontend:
- app shell;
- router guards;
- auth store;
- Axios client dengan credentials dan CSRF flow;
- error handling;
- design system primitives yang disetujui;
- login dan logout nyata;
- role-aware navigation.

Gunakan migration dan automated tests. Jangan menggunakan data mahasiswa asli.

Output dokumentasi pada docs/09-implementation-foundation/. Jalankan melalui Docker Compose: backend test, frontend lint, type-check, unit test, dan production build. Periksa health semua service serta log Horizon/scheduler. Berhenti setelah Phase 09.
```

---

## Phase 10 — Manajemen survei dan Filament

### Prompt

```text
Jalankan Phase 10 saja: Survey Management and Filament.

Implementasikan:
- survey template dan instrument version;
- categories, indicators, scales, scale options, questions, and options;
- question bank;
- survey, period, target, and status lifecycle;
- draft/review/approve/publish/close/archive workflow;
- prevention of unsafe edits after responses exist;
- duplication/versioning;
- preview;
- Filament resources/pages/actions;
- policy dan organizational scope;
- validation dan audit;
- factories, seeders, feature tests, policy tests.

Jangan mengimplementasikan AI atau dashboard analitik pada phase ini.

Pastikan business logic berada pada domain service/action, bukan seluruhnya di Filament Resource.

Perbarui API contract jika implementasi membutuhkan perubahan yang disetujui. Jalankan seluruh validasi dan berhenti setelah Phase 10.
```

---

## Phase 11 — Pengumpulan respons Vue

### Prompt

```text
Jalankan Phase 11 saja: Response Collection.

Implementasikan backend dan Vue untuk:
- eligible survey list;
- invitation/token flow;
- authenticated dan external respondent sesuai kebijakan;
- survey detail;
- section navigation;
- supported MVP question types;
- autosave dengan debounce dan recovery;
- progress calculation;
- required validation;
- idempotent final submission;
- one-response rule;
- confirmation dan completion receipt;
- response history yang diizinkan;
- anonymous/confidential separation;
- reminder eligibility data;
- reporting threshold foundation;
- accessibility dan mobile responsiveness.

Lakukan pengujian terhadap duplicate submit, expired survey, revoked invitation, unauthorized survey, missing required answer, autosave conflict, dan network failure.

Jangan menampilkan jawaban individual kepada pimpinan. Jalankan seluruh test dan berhenti setelah Phase 11.
```

---

## Phase 12 — Statistik, dashboard, dan laporan

### Prompt

```text
Jalankan Phase 12 saja: Analytics, Dashboard, and Reporting.

Implementasikan statistik deterministik berdasarkan methodology framework:
- response rate;
- distributions;
- top-two box;
- median, mode, mean/SD bila diizinkan;
- score per item/indicator/category/overall;
- normalization dan interpretation;
- period/unit/group comparison;
- trend;
- reliability calculation jika prasyarat terpenuhi;
- SERVQUAL/IPA/CSI/IKM hanya bila instrument method dan data mendukung;
- small-sample warning dan suppression;
- cached aggregate snapshots dan last-updated timestamp.

Implementasikan Vue executive dashboard dengan organizational scope dan visualisasi ECharts yang tepat. Sediakan tabel data pendukung, accessible summary, filter, drill-down terkontrol, dan empty/error/loading states.

Implementasikan report/export melalui queue dengan permission, filter provenance, expiration, dan audit. Jangan melakukan perhitungan statistik utama melalui AI.

Buat golden test menggunakan dataset fiktif dengan hasil perhitungan yang diketahui. Jalankan test, lint, type-check, dan build. Berhenti setelah Phase 12.
```

---

## Phase 13 — AI, notifikasi, dan tindak lanjut

### Prompt

```text
Jalankan Phase 13 saja: AI, Notifications, and Follow-up.

Implementasikan provider-agnostic AI adapter melalui backend:
- encrypted configuration;
- masked display;
- provider/model enable-disable;
- allowlisted base URL;
- connection test tanpa membocorkan secret;
- token/cost/timeout/rate limits;
- job queue;
- redaction dan aggregation;
- prompt template versioning;
- prompt injection handling;
- structured output validation;
- result label, timestamp, source scope, model, and review status;
- human review/edit/approve/reject;
- fallback ketika provider gagal;
- usage log dan audit.

Gunakan AI untuk ringkasan, topik, sentimen terukur, penjelasan tren, dan draf rekomendasi. Jangan gunakan AI untuk menghitung statistik dasar atau mengubah data sumber.

Implementasikan notifikasi dalam aplikasi dan email-log untuk survey availability, reminder, closing, report completion, AI failure, low response rate, follow-up deadline, dan verification result.

Implementasikan temuan dan tindak lanjut:
- finding dari indikator rendah atau dibuat manual;
- assignment unit/PIC;
- root cause;
- plan, due date, evidence;
- verification dan revision loop;
- status dashboard;
- leader read-only view;
- audit trail.

Gunakan mock/fake AI pada automated test. Jangan mengirim data test ke provider eksternal. Jalankan seluruh validasi dan berhenti setelah Phase 13.
```

---

## Phase 14 — QA, keamanan, performa, dan deployment

### Prompt

```text
Jalankan Phase 14 saja: Quality, Security, Performance, and Deployment Readiness.

Lakukan audit dan perbaikan dalam scope yang aman:
- requirement traceability;
- unit, feature, integration, policy, and end-to-end tests;
- accessibility audit;
- role/scope authorization tests;
- anonymous and small-cell privacy tests;
- input/output security;
- rate limit;
- CSRF/CORS/session cookie;
- secret handling;
- AI prompt injection and output validation;
- dependency audit;
- static analysis;
- query and N+1 analysis;
- index review;
- dashboard performance;
- queue retry/failure handling;
- backup/restore runbook;
- deployment checklist;
- strategi container Horizon/scheduler, restart policy, healthcheck, dan graceful shutdown;
- logging/monitoring/alerting;
- incident response;
- user acceptance test scenarios;
- administrator and user manual;
- release notes.

Jangan deploy atau mengubah production tanpa izin eksplisit. Jangan menggunakan data pribadi asli dalam test.

Output:
- docs/14-quality-deployment/test-plan-and-results.md
- docs/14-quality-deployment/security-review.md
- docs/14-quality-deployment/performance-review.md
- docs/14-quality-deployment/deployment-runbook.md
- docs/14-quality-deployment/backup-restore-runbook.md
- docs/14-quality-deployment/incident-response.md
- docs/14-quality-deployment/uat.md
- docs/14-quality-deployment/admin-manual.md
- docs/14-quality-deployment/user-manual.md
- docs/14-quality-deployment/release-readiness.md

Berikan status final READY, READY WITH CONDITIONS, atau NOT READY beserta bukti dan blocker. Berhenti setelah Phase 14.
```

---

## 21. Definition of Done proyek

Proyek belum boleh dianggap selesai hanya karena halaman dapat dibuka. Minimal harus terpenuhi:

- seluruh kebutuhan MVP mempunyai implementasi dan test;
- empat aktor utama dapat menggunakan alur yang sesuai;
- organizational scope diuji pada backend;
- survey lifecycle dan versioning berfungsi;
- pengisian, autosave, dan idempotent submit berfungsi;
- anonim dan rahasia dibedakan;
- minimum reporting threshold berfungsi;
- statistik mempunyai golden test;
- dashboard dan laporan mengikuti filter dan permission;
- AI dapat dinonaktifkan tanpa merusak sistem;
- AI menggunakan data agregat/redacted serta human review;
- tindak lanjut dapat dibuat, diproses, diverifikasi, dan diaudit;
- audit log tidak membocorkan secret;
- test, lint, type-check, build, dan security review lulus;
- dokumentasi admin, pengguna, deployment, backup, dan incident tersedia;
- tidak ada credential atau data pribadi asli dalam Git.

---

## 22. Referensi teknis utama

- Docker Engine on Ubuntu: https://docs.docker.com/engine/install/ubuntu/
- Docker Compose on Linux: https://docs.docker.com/compose/install/linux/
- Docker Compose startup order dan healthcheck: https://docs.docker.com/compose/how-tos/startup-order/
- Docker Laravel Guide: https://docs.docker.com/guides/laravel/
- Docker Official PHP Image: https://hub.docker.com/_/php
- Docker Official Node Image: https://hub.docker.com/_/node
- Docker Official PostgreSQL Image: https://hub.docker.com/_/postgres
- Docker Official Redis Image: https://hub.docker.com/_/redis
- Docker Official Nginx Image: https://hub.docker.com/_/nginx
- Laravel 13 Installation: https://laravel.com/docs/13.x/installation
- Laravel Sanctum: https://laravel.com/docs/13.x/sanctum
- Laravel Fortify: https://laravel.com/docs/13.x/fortify
- Laravel Horizon: https://laravel.com/docs/13.x/horizon
- Laravel Reverb: https://laravel.com/docs/13.x/reverb
- Filament 5 Installation: https://filamentphp.com/docs/5.x/introduction/installation
- Vue Quick Start: https://vuejs.org/guide/quick-start.html
- Vite Getting Started: https://vite.dev/guide/
- Tailwind CSS with Vite: https://tailwindcss.com/docs
- Pinia: https://pinia.vuejs.org/getting-started.html
- Apache ECharts: https://echarts.apache.org/
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission/v8/installation-laravel
- Spatie Laravel Activitylog: https://spatie.be/docs/laravel-activitylog/v5/installation-and-setup
- Permendiktisaintek Nomor 39 Tahun 2025: https://peraturan.bpk.go.id/Details/333967/permendikti-saintek-no-39-tahun-2025
- Perubahan Permendiktisaintek Nomor 39 Tahun 2025: https://lldikti5.kemdiktisaintek.go.id/home/aturan_detail/nomor-10-tahun-2026-perubahan-permendiktisaintek-nomor-39-tahun-2025-tentang-penjaminan-mutu-pendidikan-tinggi
- PermenPANRB Nomor 14 Tahun 2017: https://peraturan.bpk.go.id/Details/132600/permen-pan-rb-no-14-tahun-2017
- UU Nomor 27 Tahun 2022: https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022
- NIST AI RMF: https://www.nist.gov/itl/ai-risk-management-framework
- UNESCO Guidance for Generative AI: https://www.unesco.org/en/articles/guidance-generative-ai-education-and-research
- OWASP GenAI Security Project: https://owasp.org/www-project-top-10-for-large-language-model-applications/

Codex harus memeriksa ulang versi dan status sumber pada tanggal pekerjaan dilakukan.

# Seed Data dan Akses Lokal

## Fixture

`FoundationSeeder` menjalankan seeder role/permission, organisasi, dan user. Data organisasi menggunakan nama fiktif seperti Universitas Contoh, Fakultas Teknik, Fakultas Ekonomi dan Bisnis, dan LPMPP. Akun memakai domain `.example.test`; tidak ada NIM, identitas mahasiswa, data stakeholder, atau respons survei nyata.

Seeder bersifat idempotent untuk code/email yang sama dan dapat dijalankan dari root repository:

```bash
docker compose exec -T app php artisan db:seed --class=FoundationSeeder --force
```

## Kebijakan credential

Seeder membuat password acak ter-hash dan tidak mencetak atau mendokumentasikan nilai plaintext. Dengan demikian fixture tidak menjadi credential bersama yang tidak terkendali.

Sebelum uji manual login/panel oleh tim, pemilik environment perlu menetapkan satu akun fiktif melalui prosedur provisioning lokal yang disetujui, lalu memberinya role, permission, dan unit yang tepat. Jangan memasukkan password ke Git, dokumentasi, seed, command history, screenshot, atau tiket. Otomasi provisioning/SSO belum dibuat karena provider dan kebijakan identitas belum disetujui.

Automated test membuat user fiktif per test dengan hash lokal dan membuktikan login/logout/session flow tanpa bergantung pada credential development.

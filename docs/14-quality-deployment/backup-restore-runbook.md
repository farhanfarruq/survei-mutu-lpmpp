# Backup and Restore Runbook

## Kebijakan yang harus disahkan

RPO/RTO final, retention, lokasi offsite, encryption key custody, legal hold, dan restore approver belum disahkan. Backup dianggap valid hanya setelah restore drill, bukan setelah file dump terbentuk.

## Backup PostgreSQL

1. Identifikasi database dan timestamp dari secret/config manager tanpa mencetak credential.
2. Buat custom-format dump: `pg_dump --format=custom --file=<path> <database>` menggunakan role backup least privilege.
3. Enkripsi artifact, hitung SHA-256, simpan ke immutable/offsite storage, dan rekam size, LSN/timestamp, schema version, checksum, owner, expiry.
4. Backup object/file export sesuai manifest terpisah; Redis bukan source of truth.
5. Uji `pg_restore --list` dan monitor job backup.

## Restore

1. Buka incident/change ticket dan pilih restore point sesuai RPO.
2. Provision database baru yang terisolasi; jangan overwrite database aktif.
3. Verifikasi checksum lalu restore portabel: `pg_restore --exit-on-error --no-owner --no-privileges --dbname=<new_db> <dump>`.
4. Terapkan role/grant target melalui infrastructure code, jalankan migration status, dan bandingkan jumlah tabel serta ledger migration.
5. Jalankan integrity checks untuk FK/unique, jumlah record domain, sample aggregate checksum, login fiktif, health/readiness, queue dan storage references.
6. Security/privacy owner menyetujui cutover; simpan evidence tanpa isi jawaban individual.
7. Setelah cutover, monitor dan pertahankan database lama read-only sesuai retention sampai approval cleanup.

## Drill Phase 14

Dump lokal custom-format direstore ke container PostgreSQL 17 sementara dengan credential sintetis. Percobaan awal gagal karena ownership role sumber tidak ada; prosedur diperbaiki dengan `--no-owner --no-privileges`. Ulangannya sukses: 54 base tables sama dengan sumber dan 16 baris migration ledger dapat dibaca. Container target otomatis dihapus setelah drill. Tidak ada production database atau data pribadi asli yang digunakan.

## Jadwal minimum yang diusulkan

- Automated backup harian dan PITR/WAL sesuai RPO yang disahkan.
- Verifikasi checksum setiap backup; restore drill bulanan non-production dan sebelum major release.
- Alert untuk backup terlambat/gagal, ukuran anomali, checksum gagal, retention gagal, dan restore drill overdue.


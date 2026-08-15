# Implementasi Phase 13

## AI terkelola

- Konfigurasi provider/model, base URL allowlist, enable/disable, timeout, rate, token, dan biaya disimpan di backend. Secret memakai encrypted cast, write-only di API, dan hanya tampil sebagai mask.
- Adapter `AiProvider` dan job `RunAiInsight` memisahkan provider dari orchestration. HTTP adapter hanya menggunakan base URL yang diizinkan; connection test mengembalikan `connected/failed` tanpa body atau secret provider.
- Input job dibentuk oleh `AiSafety` dari snapshot agregat released dan non-suppressed. Tidak ada response answer, raw comment, identity, atau confidential linkage dalam projection.
- Teks label agregat diperlakukan sebagai input tidak tepercaya; pola prompt injection disanitasi. Output wajib memenuhi schema summary, topics, sentimen terukur, penjelasan tren, rekomendasi, dan limitations. Output yang tidak valid atau mengandung pola data sensitif dikarantina.
- Setiap hasil memiliki label, waktu, source scope, provider/model, status review, reviewer, catatan, dan version. Requester tidak dapat mereview hasilnya sendiri; reviewer dapat edit, approve, atau reject dengan optimistic concurrency.
- Provider failure, rate/budget violation, dan output invalid menghasilkan fallback deterministik yang tidak menghitung ulang statistik. Usage dan transition ditulis ke usage log serta audit log.

## Notifikasi

`NotificationHub` hanya menerima event allowlist dan menolak konten yang mengandung response content, raw comment, answer, secret, API key, atau password. Delivery database dan email menggunakan queue, logical deduplication key, dan lock per channel.

Event yang terhubung:

- survey availability pada aktivasi;
- maksimal tiga reminder dengan jeda tiga hari;
- closing-soon dan survey closed;
- report export completion;
- AI failure/fallback;
- low response rate menjelang penutupan;
- follow-up deadline H-7, H-1, dan H+1;
- verification submission/result.

Scheduler `governed-notifications` berjalan setiap jam. UI notifikasi hanya mengizinkan pengguna membaca dan menandai notifikasinya sendiri.

## Finding dan tindak lanjut

- Finding dapat dibuat manual dengan source evidence atau dari indikator rendah pada snapshot released, tidak suppressed, dan skor di bawah 60.
- Unit selalu divalidasi terhadap organizational scope. PIC wajib memiliki role action update pada unit; verifier wajib memiliki role verify dan berbeda dari PIC.
- Action menyimpan root cause, plan, output, resource need, due date, progress, acceptance/rejection note, evidence ber-checksum/version, serta append-only verification decisions.
- Semua mutation penting memakai permission, scope, audit, dan `If-Match`. Revisi evidence mengembalikan action ke in-progress lalu dapat diajukan ulang.
- Pimpinan hanya memiliki finding/action/dashboard read; tidak memiliki create, update, atau verify. Dashboard tidak mengandung jawaban individual.

## Vue

Route production `/app/ai`, `/app/notifications`, `/app/follow-up`, dan `/app/follow-ups/actions/:id` memakai route guard permission. Semua view memiliki heading/landmark, native label/control, loading, empty, error, live status, tabel pendukung, keyboard-operable actions, serta reflow 320 px.

Workspace AI mengambil daftar analysis run yang telah dirilis, reviewer independen, dan job terbaru dari endpoint scoped. Pengguna memilih nama survei/unit/reviewer dari daftar, bukan mengetik ID teknis. Menu AI dan tindak lanjut ditampilkan untuk admin LPMPP, super admin, dan leader hanya bila permission terkait dimiliki; otorisasi backend tetap menjadi sumber keputusan akhir.

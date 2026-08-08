# Implementasi Response Collection

## Alur responden

### Akun internal

1. `GET /api/v1/surveys/eligible` menginterseksikan survey aktif dengan scope unit user.
2. `GET /api/v1/surveys/{survey}/respondent-detail` menampilkan tujuan, notice, durasi, window, mode privasi, dan instrumen.
3. Consent tidak dicentang secara default. Setelah setuju, `POST /api/v1/surveys/{survey}/respondent-session` membuat session content tanpa identity key.
4. `POST /api/v1/responses` membuat tepat satu draft untuk session tersebut.
5. Vue menyimpan perubahan lokal segera, debounce 1,2 detik, lalu autosave memakai `If-Match` dan `Idempotency-Key`.
6. Final submit memvalidasi seluruh item wajib dan mengembalikan receipt nonidentifying yang sama pada replay idempotent.

### Responden eksternal

1. Admin berizin dapat menerbitkan token satu kali melalui `POST /api/v1/surveys/{survey}/invitations`; reference eksternal hanya disimpan sebagai HMAC.
2. `/invitations/{token}` menukar token dengan respondent session dan completion token. Token expired/revoked ditolak `410`; replay setelah mulai ditolak.
3. Session credential disimpan di `sessionStorage`; isi draft recovery disimpan lokal pada perangkat sampai submit.
4. Menolak consent menandai participation declined dan tidak membuat response content.

## API response capture

| Endpoint | Fungsi | Control |
|---|---|---|
| `POST /respondent-sessions` | exchange invitation eksternal | token hash, expiry, revoke, rate limit |
| `GET /respondent-survey` | detail notice + instrumen session | `X-Respondent-Token` |
| `POST /responses` | provision draft setelah consent | session + completion token + idempotency |
| `GET /responses/{id}` | own draft/session recovery | session-bound, submitted answer diminimalkan |
| `PATCH /responses/{id}` | autosave typed answer | ETag/If-Match + idempotency |
| `POST /responses/{id}/submissions` | exactly-once final submit | required validation + ETag + idempotency |
| `GET /response-history` | status participation akun | tidak memuat answers/receipt/linkage |
| `GET /surveys/{survey}/collection-summary` | reminder dan threshold foundation | campaign permission + organizational scope; aggregate only |

Seluruh path di atas memakai prefix `/api/v1`.

## State dan concurrency

- Response bergerak `started → partial → submitted`; submitted immutable.
- Satu participation hanya dapat memulai satu respondent session; satu session memiliki satu response melalui unique constraint.
- Autosave menaikkan `resource_version`; stale `If-Match` menghasilkan `412 version_conflict` dan ETag terbaru.
- Vue mengambil versi server, menggabungkan jawaban lokal di atas versi tersebut, lalu mencoba sekali lagi. Network failure mempertahankan local backup dan menampilkan tombol retry.
- Submit menyimpan fingerprint idempotency. Replay key/payload yang sama mengembalikan receipt yang sama; key berbeda setelah submitted menghasilkan `409 response_already_submitted`.
- `surveys.responses_count` dinaikkan satu kali dalam transaction submit dan menjadi guard Phase 10 terhadap unsafe campaign edits.

## Tipe pertanyaan MVP

| Type | Input Vue | Validasi backend |
|---|---|---|
| `scale` | radio dari scale point + N/A bila diizinkan | code/id point atau sentinel N/A sah |
| `single_choice` | radio | code/id option harus milik question |
| `multiple_choice` | checkbox | unique option, exclusive option tidak boleh digabung |
| `short_text` | text | string maksimum 500 karakter |
| `long_text` | textarea | string maksimum 5.000 karakter; UI mengingatkan agar tidak memasukkan identifier |
| `number` | number | numeric serta min/max dari validation snapshot |

Progress adalah persentase question yang memiliki nilai terhadap seluruh question pada snapshot. Final submit memakai rule `is_required`, bukan persentase, sehingga item optional tidak memblokir.

## Vue dan aksesibilitas

- Route production: `/app/surveys`, `/app/surveys/:id`, `/app/response-history`, `/invitations/:token`, dan `/respond/responses/:id`.
- Section navigation berupa landmark `nav`; question menggunakan `fieldset/legend`; required error memiliki summary dan focus menuju item pertama.
- Status autosave memakai live region; confirmation memakai native dialog; receipt menerima focus setelah submit.
- Native radio, checkbox, text, textarea, dan number mempertahankan keyboard semantics.
- Layout menjadi satu kolom pada breakpoint mobile dan telah diuji tanpa horizontal overflow pada lebar 320 px.

# Lifecycle dan Domain Guards

## Instrument version

```text
draft/returned --submitForReview + preflight/hash--> in_review
in_review --return + reason--> returned
in_review --approve + reviewer != creator + hash unchanged--> approved
approved --duplicate semantic version--> new draft
```

Preflight memerlukan section, category, indicator, scale, minimal dua titik non-N/A per scale, dan question. Referensi indicator/scale harus berasal dari version yang sama. Scale question wajib mempunyai scale; single/multiple choice wajib mempunyai minimal dua options; `<script>` ditolak.

Saat submit, canonical content hash dibekukan. Observer menolak perubahan category, indicator, scale, point, section, question, option, dan metadata semantic saat version `in_review`/`approved`. Approval menolak self-approval dan hash yang berubah.

## Survey campaign

```text
draft/returned --submit review + preflight--> in_review
in_review --return + reason--> returned
in_review --approve + reviewer != creator--> approved
approved --publish--> scheduled atau active
scheduled --opens_at due--> active
scheduled/active --manual close; active --closes_at due--> closed
closed --archive--> archived
```

Preflight campaign memerlukan approved instrument, waktu valid, timezone IANA, privacy notice, reporting threshold minimal 10, action owner, dan minimal satu target yang menunjuk tepat satu group/unit.

Publish membekukan snapshot instrument content hash, privacy mode, threshold, timezone, dan checksum target. Perubahan pada sumber instrument atau target tidak mengubah snapshot campaign.

Scheduler `advance-survey-lifecycle` berjalan setiap menit untuk scheduled→active dan active→closed berdasarkan timestamp. Manual action tetap tersedia melalui policy.

## Unsafe edit prevention

- Konfigurasi campaign hanya editable pada `draft`/`returned`.
- Instrument/version, waktu, owner, privacy, notice, threshold, action owner, dan target terkunci setelah approval/publish.
- Guard yang sama aktif lebih awal bila `responses_count > 0`, termasuk pada draft hasil kondisi data abnormal.
- Survey hanya dapat dihapus saat draft/returned tanpa respons.
- Instrument version hanya dapat dihapus saat draft/returned dan belum digunakan survey.
- Domain observer berlaku di luar Filament, sehingga bypass UI tetap ditolak.

Seluruh transition dan clone berjalan melalui service transaction/audit, bukan callback business logic yang tersebar pada Filament Resource.

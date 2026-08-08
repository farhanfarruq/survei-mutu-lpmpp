# Domain Model Survey Management

## Instrument

| Entity | Fungsi | Guard utama |
|---|---|---|
| `survey_templates` | Identitas stabil keluarga instrumen dan unit pemilik | code unik; organizational scope |
| `instrument_versions` | Semantic version dan bukti review | tuple template/major/minor/patch unik; approved immutable |
| `instrument_sections` | Urutan bagian kuesioner | code/position unik per version |
| `categories` | Kelompok pelaporan | code/position unik per version |
| `indicators` | Konstruk utama item | dimiliki satu category; weight nonnegatif di form |
| `scales` | Definisi skala | bounds, missing policy, N/A policy |
| `scale_points` | Label/nilai pilihan skala | code/position unik per scale |
| `questions` | Item version-owned | satu indicator utama, response type, method, purpose |
| `question_options` | Pilihan closed item | code/position unik per question |
| `question_bank_entries` | Item reusable yang tetap terpisah dari version | owner scope, family/method, pilihan bawaan JSON |

`QuestionBank` menyalin entry ke draft section dengan indicator/scale dari version yang sama. Perubahan bank sesudah penyalinan tidak mengubah item pada version.

`InstrumentVersioning` menggandakan graph category–indicator, scale–point, section–question–option dalam transaction dan menghasilkan version draft baru. Major mereset minor/patch, minor mereset patch, dan patch hanya menaikkan patch. Collision ditolak.

## Campaign

| Entity | Fungsi | Guard utama |
|---|---|---|
| `survey_periods` | Periode akademik/pelaporan | tanggal mulai ≤ selesai; timezone IANA |
| `respondent_groups` | Definisi target fiktif/manual/integrasi | organizational scope; tidak menyimpan anggota Phase 10 |
| `surveys` | Campaign yang menunjuk tepat satu approved instrument version | code unik; owner scope; lifecycle service |
| `survey_targets` | Group/unit target dan eligible denominator | tepat satu referensi group atau unit diverifikasi preflight |

Kolom `responses_count` adalah counter guard untuk integrasi response module berikutnya. Phase 10 tidak membuat table/content respons; counter membuktikan bahwa campaign dan target tidak dapat diubah saat respons sudah ada. Modul response wajib memperbaruinya secara transactional.

## Identifier dan delete behavior

Seluruh entity domain memakai UUIDv7 native melalui `HasUuids`. FK instrument content memakai cascade hanya di bawah draft version yang boleh dihapus; version yang dipakai survey memakai restrict. Owner unit/user memakai restrict/null-on-delete sesuai provenance. Observer dan policy memberi guard sebelum constraint database.

## Fixture

`SurveyManagementSeeder` idempotent membuat satu template, approved version lengkap, satu bank item, period, respondent group, draft survey, dan target. Semua nama berlabel fiktif/Contoh; user memakai `.example.test`; tidak ada NIM, data mahasiswa, response, atau credential plaintext.

# Filament Survey Management

## Resource

| Menu/resource | Kemampuan |
|---|---|
| Template Survei | Create/update/retire metadata berdasarkan unit scope |
| Versi Instrumen | Semantic version, category/indicator, scale/point, section/question/option, preview, review, approve, duplicate |
| Bank Pertanyaan | Curated item, method, response type, purpose, pilihan bawaan, active flag |
| Periode Survei | Katalog periode dan timezone |
| Kelompok Responden | Definisi group scoped tanpa anggota/data nyata |
| Survey | Draft config, target, preflight, preview instrument, review, approve, publish, close, archive, duplicate |

Preview instrument/survey adalah read-only representation. Secret, credential, response content, AI configuration, dan analytics tidak ditampilkan.

## Permission dan scope

- Instrument: `template.read/create/update/delete`, `validation.create/read/update/approve`.
- Campaign: `campaign.read/create/update/delete/review/approve/publish`.
- Target/group: `population.manage`.
- Setiap query template/version/question-bank/survey/group diinterseksikan dengan `OrganizationalScope`.
- `admin_lpmpp` dapat membuat/mengubah/mempublikasikan tetapi tidak mendapat approval grant.
- `reviewer` mendapat panel access dan approval grant, tetapi service tetap menolak creator sebagai approver.
- Super Admin tetap membutuhkan permission eksplisit; power administratif bukan bypass domain invariant.

Action Filament hanya mengotorisasi, mengumpulkan input, memanggil `InstrumentLifecycle`, `InstrumentVersioning`, `QuestionBank`, `SurveyLifecycle`, atau `SurveyDuplication`, lalu menampilkan notification aman.

## API contract delta

Workflow campaign review yang diminta Phase 10 belum tercakup pada draft Phase 07. Endpoint catalog dan OpenAPI diperluas dengan:

- `POST /surveys/{survey_id}/review-submissions` — `campaign.review`;
- `POST /surveys/{survey_id}/review-decisions` — `campaign.approve`, reviewer berbeda dari creator.

Perubahan contract telah tervalidasi. Endpoint production belum diimplementasikan pada Phase 10; delivery surface yang dibuat adalah Filament.

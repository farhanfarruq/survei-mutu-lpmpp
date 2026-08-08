# Entity–Relationship Design

Versi: **1.0 — 2026-08-07**  
Status: **logical/physical baseline; migrations belum dibuat**

## 1. Modeling conventions

- Nama tabel fisik memakai `snake_case` plural; diagram memakai uppercase.
- Primary key domain memakai PostgreSQL `uuid` berisi UUIDv7. ID tidak menjadi authorization control.
- Waktu memakai `timestamptz`; period/calendar juga menyimpan timezone IANA pada survey.
- Mutable aggregate memakai `lock_version bigint` untuk optimistic concurrency.
- Published/released records immutable; koreksi menghasilkan version/run/snapshot baru.
- `created_by`/`updated_by` adalah audit actor reference dan umumnya `ON DELETE SET NULL`; audit event tetap mempertahankan actor snapshot/hash minimum.
- Tidak ada FK lintas database. Reference lintas `core`, `response`, dan `linkage_vault` disebut **logical reference** dan diverifikasi application invariant/reconciliation.
- JSONB hanya untuk versioned configuration/metric payload yang bentuknya metode-dependent; relationship, scope, identity, dan status utama tetap relational.

## 2. Entity coverage

| Domain | Entities |
|---|---|
| Identity/organization | `users`, `roles`, `permissions`, `role_permissions`, `user_role_assignments`, `organizational_units`, `respondent_groups`, `respondent_group_members` |
| Instrument | `survey_templates`, `instrument_versions`, `instrument_sections`, `categories`, `indicators`, `scales`, `scale_points`, `questions`, `question_options`, `scoring_rules`, `review_assignments`, `reviews` |
| Campaign/participation | `survey_periods`, `surveys`, `survey_targets`, `invitations`, `notifications` |
| Response/privacy | `responses`, `answers`, `response_metadata`, `consents`, `response_identity_links` |
| Analytics/AI/reporting | `analysis_runs`, `aggregate_snapshots`, `ai_configurations`, `ai_jobs`, `ai_results`, `reports`, `report_exports` |
| PPEPP/governance | `findings`, `actions`, `action_evidence`, `audit_logs`, `settings`, `outbox_events`, `job_runs`, `retention_cases`, `legal_holds` |

Seluruh entity minimum pada prompt tercakup. “Metadata” diwujudkan sebagai `response_metadata`; `periods`, `targets`, `options`, `evidence`, `exports`, dan `actions` menggunakan nama fisik yang lebih eksplisit di atas.

## 3. ERD — Identity, organization, and governance

```mermaid
erDiagram
    ORGANIZATIONAL_UNITS ||--o{ ORGANIZATIONAL_UNITS : parent_of
    ORGANIZATIONAL_UNITS ||--o{ USERS : home_unit
    USERS ||--o{ USER_ROLE_ASSIGNMENTS : receives
    ROLES ||--o{ USER_ROLE_ASSIGNMENTS : assigns
    ORGANIZATIONAL_UNITS ||--o{ USER_ROLE_ASSIGNMENTS : scopes
    ROLES ||--o{ ROLE_PERMISSIONS : contains
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : grants
    ORGANIZATIONAL_UNITS ||--o{ RESPONDENT_GROUPS : owns
    RESPONDENT_GROUPS ||--o{ RESPONDENT_GROUP_MEMBERS : includes
    USERS o|--o{ RESPONDENT_GROUP_MEMBERS : optional_internal_subject
    USERS o|--o{ AUDIT_LOGS : acts
    USERS o|--o{ SETTINGS : changes
    LEGAL_HOLDS ||--o{ RETENTION_CASES : blocks

    USERS {
        uuid id PK
        string email_normalized UK
        string status
        uuid home_unit_id FK
        timestamptz deleted_at
    }
    ROLES {
        uuid id PK
        string code UK
        string status
    }
    PERMISSIONS {
        uuid id PK
        string code UK
        string data_class_ceiling
    }
    USER_ROLE_ASSIGNMENTS {
        uuid id PK
        uuid user_id FK
        uuid role_id FK
        uuid organizational_unit_id FK
        string scope_type
        timestamptz expires_at
    }
    ORGANIZATIONAL_UNITS {
        uuid id PK
        uuid parent_id FK
        string code UK
        string path
        date effective_from
        date effective_to
    }
    RESPONDENT_GROUPS {
        uuid id PK
        uuid organizational_unit_id FK
        string code
        string source_type
        string schema_version
    }
    RESPONDENT_GROUP_MEMBERS {
        uuid id PK
        uuid respondent_group_id FK
        string subject_ref_ciphertext
        string dedupe_hash
    }
    AUDIT_LOGS {
        uuid id PK
        uuid actor_user_id FK
        string action
        string object_type
        uuid object_id
        string result
        string event_hash
        timestamptz occurred_at
    }
    SETTINGS {
        uuid id PK
        string key UK
        jsonb value_json
        string classification
        bigint version
    }
    LEGAL_HOLDS {
        uuid id PK
        string scope_type
        uuid scope_id
        string status
        timestamptz released_at
    }
    RETENTION_CASES {
        uuid id PK
        uuid legal_hold_id FK
        string object_type
        uuid object_id
        string disposition
        string status
    }
```

`subject_ref_ciphertext` tidak menyimpan credential atau response ID. Untuk population external, nilai identitas terenkripsi dan dedupe HMAC berada di core/participation boundary serta dihapus menurut retention.

## 4. ERD — Instrument, validation, and scoring

```mermaid
erDiagram
    SURVEY_TEMPLATES ||--o{ INSTRUMENT_VERSIONS : versions
    INSTRUMENT_VERSIONS ||--o{ INSTRUMENT_SECTIONS : contains
    INSTRUMENT_VERSIONS ||--o{ CATEGORIES : defines
    CATEGORIES ||--o{ INDICATORS : contains
    INSTRUMENT_SECTIONS ||--o{ QUESTIONS : orders
    INDICATORS ||--o{ QUESTIONS : measures
    SCALES ||--o{ SCALE_POINTS : defines
    SCALES ||--o{ QUESTIONS : answers_with
    QUESTIONS ||--o{ QUESTION_OPTIONS : offers
    INSTRUMENT_VERSIONS ||--o{ SCORING_RULES : snapshots
    INSTRUMENT_VERSIONS ||--o{ REVIEW_ASSIGNMENTS : reviewed_by
    REVIEW_ASSIGNMENTS ||--o{ REVIEWS : produces
    USERS ||--o{ REVIEW_ASSIGNMENTS : reviewer

    SURVEY_TEMPLATES {
        uuid id PK
        string code UK
        string family_code
        uuid owner_unit_id FK
        string status
    }
    INSTRUMENT_VERSIONS {
        uuid id PK
        uuid survey_template_id FK
        int major
        int minor
        int patch
        string status
        string content_hash
        timestamptz approved_at
    }
    INSTRUMENT_SECTIONS {
        uuid id PK
        uuid instrument_version_id FK
        string code
        int position
        string title
    }
    CATEGORIES {
        uuid id PK
        uuid instrument_version_id FK
        string code
        string name
        int position
    }
    INDICATORS {
        uuid id PK
        uuid category_id FK
        string code
        string construct
        decimal weight
    }
    SCALES {
        uuid id PK
        uuid instrument_version_id FK
        string code
        string scale_type
        decimal min_value
        decimal max_value
    }
    SCALE_POINTS {
        uuid id PK
        uuid scale_id FK
        string code
        decimal numeric_value
        string label
        int position
    }
    QUESTIONS {
        uuid id PK
        uuid section_id FK
        uuid indicator_id FK
        uuid scale_id FK
        string code
        string response_type
        boolean required
        int position
    }
    QUESTION_OPTIONS {
        uuid id PK
        uuid question_id FK
        string code
        string label
        decimal score_value
        int position
    }
    SCORING_RULES {
        uuid id PK
        uuid instrument_version_id FK
        string method
        string rule_version
        jsonb specification_json
        string checksum
    }
    REVIEW_ASSIGNMENTS {
        uuid id PK
        uuid instrument_version_id FK
        uuid reviewer_user_id FK
        string status
        timestamptz due_at
    }
    REVIEWS {
        uuid id PK
        uuid review_assignment_id FK
        string decision
        text comment
        string reviewed_hash
        timestamptz decided_at
    }
```

Urutan section/question dan kode category/indicator unik di dalam version parent. Saat version memasuki review, `content_hash` dibekukan; approved/published version tidak di-update atau di-cascade-delete.

## 5. ERD — Campaign, participation, response, and consent

```mermaid
erDiagram
    SURVEY_PERIODS ||--o{ SURVEYS : schedules
    INSTRUMENT_VERSIONS ||--o{ SURVEYS : snapshots
    ORGANIZATIONAL_UNITS ||--o{ SURVEYS : owns
    SURVEYS ||--o{ SURVEY_TARGETS : targets
    RESPONDENT_GROUPS ||--o{ SURVEY_TARGETS : selects
    SURVEY_TARGETS ||--o{ INVITATIONS : generates
    INVITATIONS ||--o{ NOTIFICATIONS : receives
    SURVEYS ||--o{ RESPONSES : collects
    RESPONSES ||--o{ ANSWERS : contains
    RESPONSES ||--|| RESPONSE_METADATA : describes
    RESPONSES ||--o{ CONSENTS : records
    RESPONSES o|--o| RESPONSE_IDENTITY_LINKS : confidential_only
    INVITATIONS o|--o| RESPONSE_IDENTITY_LINKS : confidential_only

    SURVEY_PERIODS {
        uuid id PK
        string code UK
        date starts_on
        date ends_on
        string timezone
    }
    SURVEYS {
        uuid id PK
        uuid instrument_version_id FK
        uuid survey_period_id FK
        uuid owner_unit_id FK
        string code
        string state
        string privacy_mode
        jsonb policy_snapshot_json
        timestamptz opens_at
        timestamptz closes_at
    }
    SURVEY_TARGETS {
        uuid id PK
        uuid survey_id FK
        uuid respondent_group_id FK
        string target_type
        uuid target_unit_id FK
        int eligible_count
    }
    INVITATIONS {
        uuid id PK
        uuid survey_target_id FK
        string participant_hash
        string token_hash
        string state
        timestamptz expires_at
    }
    NOTIFICATIONS {
        uuid id PK
        uuid invitation_id FK
        string logical_message_key UK
        string channel
        string state
        int attempt_count
    }
    RESPONSES {
        uuid id PK
        uuid survey_id "logical reference"
        string state
        string receipt_hash UK
        string schema_hash
        timestamptz submitted_at
    }
    ANSWERS {
        uuid id PK
        uuid response_id FK
        uuid question_id "logical reference"
        string answer_kind
        decimal numeric_value
        text text_value
        uuid option_id "logical reference"
    }
    RESPONSE_METADATA {
        uuid response_id PK
        string locale
        string channel
        int duration_bucket
        string accessibility_mode
        jsonb quality_flags_json
    }
    CONSENTS {
        uuid id PK
        uuid response_id FK
        string notice_version
        string legal_basis
        string decision
        timestamptz decided_at
    }
    RESPONSE_IDENTITY_LINKS {
        uuid id PK
        string response_id_ciphertext
        string invitation_id_ciphertext
        string purpose_code
        timestamptz expires_at
    }
```

Garis `INVITATIONS → RESPONSE_IDENTITY_LINKS → RESPONSES` hanya berlaku bagi mode **confidential pseudonymous** dan merupakan logical reference terenkripsi di Linkage Vault, bukan FK cross-database. Pada `detached_anonymous`, tidak ada row Linkage Vault dan tidak ada persisted relation invitation–response. `response_metadata` dilarang memuat IP, raw user-agent, exact device fingerprint, contact, atau correlation ID lintas store.

## 6. ERD — Analysis, AI, reporting, and PPEPP

```mermaid
erDiagram
    SURVEYS ||--o{ ANALYSIS_RUNS : analyzed_by
    ANALYSIS_RUNS ||--o{ AGGREGATE_SNAPSHOTS : produces
    AI_CONFIGURATIONS ||--o{ AI_JOBS : governs
    ANALYSIS_RUNS ||--o{ AI_JOBS : optionally_extends
    AI_JOBS ||--o| AI_RESULTS : produces
    AGGREGATE_SNAPSHOTS ||--o{ REPORTS : supports
    REPORTS ||--o{ REPORT_EXPORTS : exports
    AGGREGATE_SNAPSHOTS ||--o{ FINDINGS : evidences
    FINDINGS ||--o{ ACTIONS : addressed_by
    ACTIONS ||--o{ ACTION_EVIDENCE : substantiated_by
    USERS ||--o{ ACTIONS : assigned_to
    USERS ||--o{ REPORT_EXPORTS : requests

    ANALYSIS_RUNS {
        uuid id PK
        uuid survey_id FK
        string method
        string input_checksum
        string scoring_rule_checksum
        string state
        timestamptz completed_at
    }
    AGGREGATE_SNAPSHOTS {
        uuid id PK
        uuid analysis_run_id FK
        string dimension_key
        string metric_code
        int eligible_n
        int valid_n
        jsonb metric_json
        string suppression_state
        string snapshot_checksum
        timestamptz released_at
    }
    AI_CONFIGURATIONS {
        uuid id PK
        string provider_slug
        string model_slug
        string secret_reference
        string endpoint_policy_id
        string state
        decimal budget_limit
    }
    AI_JOBS {
        uuid id PK
        uuid analysis_run_id FK
        uuid ai_configuration_id FK
        string use_case
        string prompt_version
        string redaction_policy_version
        string state
        string input_hash
    }
    AI_RESULTS {
        uuid id PK
        uuid ai_job_id FK
        jsonb output_json
        string evaluation_state
        uuid reviewed_by FK
        timestamptz reviewed_at
    }
    REPORTS {
        uuid id PK
        uuid aggregate_snapshot_id FK
        string report_type
        string state
        string content_hash
        timestamptz released_at
    }
    REPORT_EXPORTS {
        uuid id PK
        uuid report_id FK
        uuid requested_by FK
        string format
        string state
        string object_key
        timestamptz expires_at
    }
    FINDINGS {
        uuid id PK
        uuid aggregate_snapshot_id FK
        uuid owner_unit_id FK
        string code
        string severity
        string state
    }
    ACTIONS {
        uuid id PK
        uuid finding_id FK
        uuid pic_user_id FK
        string state
        date due_on
        string verification_state
    }
    ACTION_EVIDENCE {
        uuid id PK
        uuid action_id FK
        string object_key
        string checksum
        string classification
        timestamptz uploaded_at
    }
```

## 7. ERD — Reliable asynchronous execution

```mermaid
erDiagram
    OUTBOX_EVENTS ||--o{ JOB_RUNS : dispatches
    USERS o|--o{ JOB_RUNS : requests
    AUDIT_LOGS }o--o| JOB_RUNS : records

    OUTBOX_EVENTS {
        uuid id PK
        string aggregate_type
        uuid aggregate_id
        string event_type
        jsonb payload_json
        string idempotency_key UK
        timestamptz available_at
        timestamptz dispatched_at
    }
    JOB_RUNS {
        uuid id PK
        uuid outbox_event_id FK
        uuid requested_by FK
        string job_type
        string state
        string input_checksum
        int attempt_count
        timestamptz lease_until
    }
```

Outbox payload hanya memuat object reference dan data minimum; raw response, token, secret, atau open text tidak boleh diletakkan dalam Redis payload.

## 8. Identifier strategy: UUIDv7 versus ULID

| Option | Decision |
|---|---|
| Auto-increment bigint | ditolak untuk public/domain ID karena mudah dienumerasi dan sulit digabung lintas store; boleh untuk private sequence teknis bila terbukti perlu |
| ULID `char(26)` | valid dan sortable, tetapi menambah type/collation/casing discipline serta tidak memakai native PostgreSQL `uuid` |
| UUIDv4 | native dan acak, tetapi insert locality/index size behavior kurang baik dibanding time-ordered identifier |
| **UUIDv7** | **dipilih**: native PostgreSQL `uuid`, time-ordered, interoperable, dan tidak membawa MAC address |

Aturan:

- generate di application menggunakan cryptographically secure UUIDv7 implementation; database menolak `NULL`/duplicate;
- simpan native `uuid`, tampilkan lowercase canonical; jangan menerima variasi ID tanpa parser ketat;
- authorization selalu memeriksa scope/resource, sebab UUID bukan secret;
- invitation, password reset, receipt recovery, signed URL, dan idempotency credential memakai random token ≥256 bit; database hanya menyimpan HMAC/hash berpepper, bukan UUID;
- timestamp UUIDv7 tidak menggantikan `created_at`; ordering bisnis memakai kolom waktu eksplisit.

## 9. Foreign key and delete behavior

| Relationship class | FK behavior | Rationale |
|---|---|---|
| Draft-owned child (`section`, `question`, `option`) | `ON DELETE CASCADE` hanya selama parent draft dan unused | menghapus aggregate draft secara konsisten |
| Published/versioned instrument → child | hard delete ditolak oleh state guard; FK `RESTRICT` | menjaga reproducibility |
| Survey → target/invitation | `RESTRICT` setelah publication; draft unused boleh controlled cascade | mencegah hilangnya participation evidence |
| Response → answer/metadata/consent | `CASCADE` hanya melalui approved retention deletion transaction | satu privacy aggregate dibuang utuh |
| Analysis → snapshot/report/finding | `RESTRICT`; supersede/retire, bukan delete | released evidence tetap terlacak |
| Finding → action → evidence | `RESTRICT`; correction/version status | PPEPP evidence tidak hilang diam-diam |
| User actor reference | `SET NULL` setelah user deactivation/pseudonymization; audit actor snapshot/hash tetap | menghindari orphan dan mempertahankan assurance |
| Organization hierarchy | parent `RESTRICT` selama child aktif; effective-date/retire | tidak merusak scope historis |
| Cross-database reference | tidak ada physical FK; immutable ID + checksum + reconciliation | PostgreSQL tidak menyediakan FK lintas database |
| Legal hold target | deletion blocked application/policy layer; tombstone retained | legal/audit preservation |

Physical cascade tidak pernah menjadi satu-satunya privacy workflow: authorization, hold check, manifest, checksum, object deletion, dan tombstone harus berhasil/terekonsiliasi.

## 10. Uniqueness and indexing strategy

### Required unique constraints

- `roles(code)`, `permissions(code)`, `organizational_units(code)` pada active namespace;
- `role_permissions(role_id, permission_id)`;
- `user_role_assignments(user_id, role_id, scope_type, scope_id)` partial untuk assignment active;
- `survey_templates(owner_unit_id, code)`;
- `instrument_versions(survey_template_id, major, minor, patch)`;
- section/category/indicator/question/option `code` dan `position` unik dalam parent version;
- `surveys(code)` dan `(instrument_version_id, survey_period_id, owner_unit_id, code)` sesuai naming policy;
- `survey_targets(survey_id, respondent_group_id, target_unit_id)` dengan normalized nullable-key strategy;
- `invitations(survey_target_id, participant_hash)` dan `invitations(token_hash)`;
- `responses(receipt_hash)`, `answers(response_id, question_id)` untuk single-answer item; multi-select memakai `(response_id, question_id, option_id)`;
- `analysis_runs(survey_id, method, input_checksum, scoring_rule_checksum)` untuk reproducible logical run;
- `aggregate_snapshots(analysis_run_id, dimension_key, metric_code)`;
- `ai_results(ai_job_id)`, `notifications(logical_message_key)`, `outbox_events(idempotency_key)`;
- satu released report per `(aggregate_snapshot_id, report_type, release_version)`.

### Query indexes

| Query | Index baseline |
|---|---|
| Effective user grants | `(user_id, expires_at)` partial where active; `(scope_type, scope_id)` |
| Unit subtree | materialized path/`ltree` setelah extension approval; baseline `(parent_id, effective_to)` |
| Campaign list/state | `(owner_unit_id, state, opens_at desc)` dan `(state, closes_at)` |
| Invitation dispatch | partial `(state, expires_at)`; token hash unique |
| Autosave/response | `(survey_id, state, updated_at)`; `responses(id)` PK; avoid indexing raw text |
| Answer aggregation | `(question_id, response_id)` dan BRIN/B-tree decision setelah actual plan |
| Snapshot dashboard | `(analysis_run_id, dimension_key, metric_code, suppression_state)` |
| Queue/outbox | partial `(available_at, id)` where `dispatched_at is null`; `(state, lease_until)` |
| Audit search | `(occurred_at desc)`, `(object_type, object_id, occurred_at desc)`, `(actor_user_id, occurred_at desc)` |
| Retention | partial `(retention_due_at)` where disposition pending |

Index ditambah hanya berdasarkan query plan/slow-query evidence. JSONB GIN tidak dibuat default; buat expression/GIN index hanya pada key yang mempunyai query contract stabil. PII/open text tidak diindeks untuk convenience.

## 11. Partition and archive strategy

- MVP: `responses`, `answers`, dan core domain tetap non-partitioned sampai volume/query plan membuktikan kebutuhan. Premature partitioning memperumit unique/FK/retention.
- `audit_logs`, `notifications`, dan `job_runs` layak monthly range partition saat retention/volume aktif karena append-heavy dan time-bounded deletion; partition masa depan dibuat sebelum boundary waktu.
- Revisit response partitioning bila salah satu terjadi: >50 juta answer rows, maintenance/vacuum tidak memenuhi window, index > RAM budget, atau p95 analysis tidak memenuhi NFR setelah query/index tuning.
- Candidate bila trigger terpenuhi: range by `submitted_month` pada response dan co-located answer partitioning setelah proof-of-concept memastikan global uniqueness/FK/retention.
- Closed campaign raw content melewati retention workflow ke encrypted archive atau deletion; dashboard historis membaca immutable aggregate snapshot, bukan mengaktifkan kembali raw archive.
- Archive manifest memuat object/table range, row count, schema/policy version, checksum, class, retention due, encryption key reference, dan restore test evidence.

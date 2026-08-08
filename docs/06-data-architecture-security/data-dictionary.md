# Data Dictionary

Versi: **1.0 — 2026-08-07**  
Status: **PROPOSED; types/constraints menjadi input migration Phase berikutnya, bukan implementation evidence**

## 1. Type and column standards

| Standard | Definition |
|---|---|
| `uuid` | PostgreSQL native UUID containing UUIDv7; PK unless stated otherwise |
| `code` | lowercase/uppercase canonical business code validated by regex; immutable after release |
| `*_at` | `timestamptz` in UTC; presentation converts to survey IANA timezone |
| `*_on` | `date`; no timezone conversion |
| `money` | `numeric(18,4)` + ISO currency column; never float |
| score/weight | `numeric(12,6)`; rounding only at presentation/release boundary |
| hash/checksum | hex/base64url fixed-format digest with `algorithm`/version where needed; no raw token |
| ciphertext | authenticated envelope-encrypted binary/base64 payload plus key version; never searchable directly |
| enum-like status | constrained `varchar`; transitions enforced by domain rule, not free text |
| JSONB | versioned schema with allowlisted keys/size; not used to hide core relationships |
| text | Unicode, length limit, normalized line endings; output escaped; open text classified at least Restricted until redacted |

All mutable tables include `created_at`, `updated_at`, `lock_version`, `created_by`, and `updated_by` unless stated otherwise. Soft delete is not a substitute for retention deletion. Tables that require historical integrity use `retired_at`/state rather than `deleted_at`.

Classification shorthand: `P` Public, `I` Internal, `C` Confidential, `R` Restricted, `S` Secret. A row inherits the highest classification of populated fields and embedded/uploaded content.

## 2. Identity and organization database (`core`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `users` | Internal actor account | `id uuid PK`; `email_normalized varchar(320) UQ`; `display_name varchar(200)`; `status varchar(24)`; `home_unit_id uuid FK` | `external_subject_hash char(64)`; `last_login_at timestamptz`; `deleted_at timestamptz` | email C; subject hash R; account deactivate before pseudonymization; no IdP password replication |
| `roles` | Governed role catalog | `id uuid PK`; `code varchar(80) UQ`; `name varchar(160)`; `status varchar(24)` | `description text`; `system_role boolean` | I; role changes audited/versioned |
| `permissions` | Atomic operation grants | `id uuid PK`; `code varchar(120) UQ`; `resource varchar(80)`; `operation varchar(16)`; `data_class_ceiling varchar(16)` | `description text` | I; operation separates C/R/U/D/Execute/Export |
| `role_permissions` | Role–permission relation | `role_id uuid FK`; `permission_id uuid FK`; composite PK | `conditions_json jsonb` | I; unique pair; conditions have versioned schema |
| `user_role_assignments` | Time/scope-bound effective grant | `id uuid PK`; `user_id uuid FK`; `role_id uuid FK`; `scope_type varchar(32)`; `scope_id uuid`; `valid_from/valid_until timestamptz` | `reason text`; `approved_by uuid FK`; `revoked_at timestamptz` | C; active uniqueness; self-escalation and expired grants denied |
| `organizational_units` | Effective-dated hierarchy | `id uuid PK`; `parent_id uuid FK nullable`; `code varchar(80)`; `name varchar(200)`; `unit_type varchar(40)`; `effective_from date` | `effective_to date`; `external_ref varchar(160)` | I/C when external ref populated; no cycles; active code unique |
| `respondent_groups` | Population segment definition | `id uuid PK`; `organizational_unit_id uuid FK`; `code varchar(80)`; `name varchar(200)`; `source_type varchar(24)`; `schema_version varchar(40)` | `filter_definition_json jsonb`; `source_snapshot_hash char(64)` | C; filter keys allowlisted; raw source not embedded |
| `respondent_group_members` | Imported eligible subjects before campaign targeting | `id uuid PK`; `respondent_group_id uuid FK`; `dedupe_hash char(64)` | `subject_ref_ciphertext text`; `attributes_ciphertext text`; `source_row_hash char(64)` | R; no response ID; unique group+dedupe; ≤90 days after relevant campaign close `[P]` |

## 3. Instrument and review database (`core`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `survey_templates` | Stable instrument family/template identity | `id uuid PK`; `owner_unit_id uuid FK`; `code varchar(80)`; `family_code varchar(80)`; `name varchar(240)`; `status varchar(24)` | `purpose text`; `retired_at timestamptz` | I; owner+code unique; cannot carry respondent data |
| `instrument_versions` | Immutable version of one template | `id uuid PK`; `survey_template_id uuid FK`; `major/minor/patch int`; `status varchar(24)`; `content_hash char(64)` | `change_reason text`; `approved_at timestamptz`; `approved_by uuid FK`; `comparability_status varchar(24)` | I; semver tuple unique; approved/published immutable |
| `instrument_sections` | Ordered questionnaire section | `id uuid PK`; `instrument_version_id uuid FK`; `code varchar(80)`; `title varchar(240)`; `position int` | `description text`; `branch_rule_json jsonb` | I; code/position unique within version |
| `categories` | Analysis/reporting category | `id uuid PK`; `instrument_version_id uuid FK`; `code varchar(80)`; `name varchar(200)`; `position int` | `description text` | I; code unique within version |
| `indicators` | Measured construct/indicator | `id uuid PK`; `category_id uuid FK`; `code varchar(80)`; `name varchar(200)`; `construct varchar(160)`; `weight numeric(12,6)` | `interpretation text` | I; code unique within category; nonnegative approved weight |
| `scales` | Answer scale definition | `id uuid PK`; `instrument_version_id uuid FK`; `code varchar(80)`; `scale_type varchar(32)`; `min_value/max_value numeric(12,6)` | `na_allowed boolean`; `missing_policy varchar(32)` | I; bounds coherent; code unique within version |
| `scale_points` | Fully labelled scale values | `id uuid PK`; `scale_id uuid FK`; `code varchar(40)`; `numeric_value numeric(12,6)`; `label varchar(200)`; `position int` | `is_na boolean`; `is_neutral boolean` | I; value/code/position unique within scale |
| `questions` | Version-owned survey item | `id uuid PK`; `section_id uuid FK`; `indicator_id uuid FK nullable`; `scale_id uuid FK nullable`; `code varchar(80)`; `item_text text`; `response_type varchar(32)`; `required boolean`; `position int` | `help_text text`; `validation_json jsonb`; `branch_rule_json jsonb`; `measurement_purpose text` | I; code/position unique within version; no HTML/script |
| `question_options` | Choice option for closed item | `id uuid PK`; `question_id uuid FK`; `code varchar(80)`; `label varchar(300)`; `position int` | `score_value numeric(12,6)`; `is_exclusive boolean` | I; code/position unique within question |
| `scoring_rules` | Method/rule snapshot | `id uuid PK`; `instrument_version_id uuid FK`; `method varchar(40)`; `rule_version varchar(40)`; `specification_json jsonb`; `checksum char(64)` | `rounding_mode varchar(24)`; `missing_policy varchar(32)`; `threshold_policy_id uuid` | I; versioned schema; immutable after approval |
| `review_assignments` | Reviewer responsibility | `id uuid PK`; `instrument_version_id uuid FK`; `reviewer_user_id uuid FK`; `status varchar(24)`; `assigned_at/due_at timestamptz` | `scope_json jsonb`; `revoked_at timestamptz` | C; reviewer ≠ creator; active assignment unique |
| `reviews` | Review decision/evidence | `id uuid PK`; `review_assignment_id uuid FK`; `decision varchar(24)`; `reviewed_hash char(64)`; `decided_at timestamptz` | `comment text`; `evidence_object_key varchar(500)`; `evidence_checksum char(64)` | I/C based on evidence; decision append-only |

## 4. Campaign and participation database (`core`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `survey_periods` | Academic/reporting period | `id uuid PK`; `code varchar(80) UQ`; `name varchar(200)`; `starts_on/ends_on date`; `timezone varchar(64)` | `status varchar(24)` | I; start ≤ end; valid IANA timezone |
| `surveys` | Executed campaign pinned to instrument/policy | `id uuid PK`; `instrument_version_id uuid FK`; `survey_period_id uuid FK`; `owner_unit_id uuid FK`; `code varchar(80) UQ`; `state varchar(32)`; `privacy_mode varchar(32)`; `opens_at/closes_at timestamptz`; `timezone varchar(64)` | `policy_snapshot_json jsonb`; `population_snapshot_hash char(64)`; `published_at timestamptz` | I/C; one approved version; policy snapshot immutable after publish |
| `survey_targets` | Unit/group target and denominator | `id uuid PK`; `survey_id uuid FK`; `respondent_group_id uuid FK nullable`; `target_unit_id uuid FK nullable`; `target_type varchar(24)`; `eligible_count int` | `sampling_json jsonb`; `frame_checksum char(64)` | C; normalized unique target; eligible_count ≥0 |
| `invitations` | Detached participation/deduplication state | `id uuid PK`; `survey_target_id uuid FK`; `participant_hash char(64)`; `token_hash char(64) UQ`; `state varchar(24)`; `expires_at timestamptz` | `contact_ciphertext text`; `last_notified_at timestamptz`; `completed_at timestamptz` | R; never response ID/receipt; token HMAC only; ≤90 days after close `[P]` |
| `notifications` | One logical outbound message and delivery state | `id uuid PK`; `invitation_id uuid FK nullable`; `logical_message_key varchar(160) UQ`; `channel varchar(24)`; `template_version varchar(40)`; `state varchar(24)`; `attempt_count int` | `recipient_ciphertext text`; `provider_message_hash char(64)`; `last_error_code varchar(80)` | C/R; no response content; bounded attempts; provider payload not retained wholesale |

## 5. Response content database (`response`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `responses` | Draft/submitted content envelope | `id uuid PK`; `survey_id uuid LOGICAL`; `state varchar(24)`; `receipt_hash char(64) UQ`; `schema_hash char(64)`; `started_at timestamptz` | `submitted_at timestamptz`; `retention_due_at timestamptz`; `lock_version bigint` | R draft/C submitted; no invitation/user/contact/IP/user-agent; survey ID permits aggregate only |
| `answers` | Typed answer per item | `id uuid PK`; `response_id uuid FK`; `question_id uuid LOGICAL`; `answer_kind varchar(24)` | exactly one of `numeric_value numeric`, `text_value text`, `option_id uuid LOGICAL`, `boolean_value boolean`, `date_value date`, `json_value jsonb`; `is_na boolean`; `missing_reason varchar(24)` | C or R for open text; type CHECK; single/multi uniqueness by question type |
| `response_metadata` | Minimized nonidentifying quality/context fields | `response_id uuid PK/FK`; `locale varchar(16)`; `channel varchar(24)`; `duration_bucket smallint` | `accessibility_mode varchar(40)`; `quality_flags_json jsonb`; `client_schema_version varchar(40)` | C; bucketed/coarsened; direct identifiers, IP, raw user-agent, exact fingerprint forbidden |
| `consents` | Notice/legal-basis decision bound to response | `id uuid PK`; `response_id uuid FK`; `notice_version varchar(40)`; `legal_basis varchar(80)`; `decision varchar(24)`; `decided_at timestamptz` | `withdrawn_at timestamptz`; `withdrawal_effect varchar(80)` | C; append decision history; identity not stored here |

## 6. Confidential linkage vault (`linkage_vault`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `response_identity_links` | Exceptional response–participant link | `id uuid PK`; `response_id_ciphertext text`; `invitation_id_ciphertext text`; `purpose_code varchar(80)`; `key_version varchar(40)`; `expires_at timestamptz` | `approved_case_id uuid LOGICAL`; `revoked_at timestamptz` | R; populated only for confidential/identifiable mode; unique deterministic blind indexes only if approved; absent for anonymous modes |

The vault credential is unavailable to ordinary Laravel requests, Filament resources, analysts, Super Admin, and AI workers. Access requires a purpose-bound privacy workflow, dual approval, short lease, enhanced audit, and query shape fixed to the approved case.

## 7. Analysis, snapshots, AI, and reports (`core`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `analysis_runs` | Reproducible statistical execution | `id uuid PK`; `survey_id uuid FK`; `method varchar(40)`; `input_checksum/scoring_rule_checksum/policy_checksum char(64)`; `software_version varchar(80)`; `state varchar(24)` | `requested_by uuid FK`; `started_at/completed_at timestamptz`; `quality_json jsonb`; `failure_code varchar(80)` | I; logical uniqueness on immutable inputs; no raw values in errors |
| `aggregate_snapshots` | Durable released/candidate metric cell | `id uuid PK`; `analysis_run_id uuid FK`; `dimension_key varchar(500)`; `metric_code varchar(80)`; `eligible_n/valid_n int`; `metric_json jsonb`; `suppression_state varchar(32)`; `snapshot_checksum char(64)` | `release_version int`; `released_by uuid FK`; `released_at timestamptz`; `supersedes_id uuid FK` | I or R when suppressed; unique run+dimension+metric+release; immutable after release |
| `ai_configurations` | Approved provider/model/budget policy, no secret value | `id uuid PK`; `provider_slug varchar(80)`; `model_slug varchar(120)`; `secret_reference varchar(300)`; `endpoint_policy_id uuid`; `state varchar(24)`; `monthly_budget numeric(18,4)`; `currency char(3)` | `max_tokens int`; `timeout_ms int`; `effective_from/until timestamptz`; `secret_fingerprint varchar(40)` | S reference metadata; plaintext API key/base URL forbidden; immutable approved versions |
| `ai_jobs` | Governed AI execution ledger | `id uuid PK`; `analysis_run_id uuid FK`; `ai_configuration_id uuid FK`; `use_case varchar(80)`; `prompt_version varchar(40)`; `redaction_policy_version varchar(40)`; `input_hash char(64)`; `state varchar(32)` | `requested_by uuid FK`; `token_budget int`; `cost_budget numeric`; `attempt_count int`; `provider_request_hash char(64)` | R; no raw prompt in ordinary log; idempotent logical job |
| `ai_results` | Provider output and human disposition | `id uuid PK`; `ai_job_id uuid FK UQ`; `output_json jsonb`; `output_hash char(64)`; `evaluation_state varchar(24)` | `evaluation_json jsonb`; `reviewed_by uuid FK`; `review_decision varchar(24)`; `reviewed_at timestamptz`; `released_at timestamptz` | C/R until approved; output treated untrusted; release requires human review |
| `reports` | Versioned report based on released snapshot | `id uuid PK`; `aggregate_snapshot_id uuid FK`; `report_type varchar(40)`; `release_version int`; `state varchar(24)`; `content_hash char(64)` | `title varchar(240)`; `approved_by uuid FK`; `released_at timestamptz`; `supersedes_id uuid FK` | I; unique snapshot+type+version; no raw response embedding by default |
| `report_exports` | Asynchronous generated file lifecycle | `id uuid PK`; `report_id uuid FK`; `requested_by uuid FK`; `format varchar(16)`; `state varchar(24)`; `classification varchar(16)`; `policy_checksum char(64)` | `object_key varchar(500)`; `object_checksum char(64)`; `expires_at timestamptz`; `download_count int`; `approved_by uuid FK` | inherits highest class; signed link ≤24h and object purge ≤7 days `[P]` |

### Aggregate snapshot payload contract

`dimension_key` is a canonical sorted representation of allowlisted dimension/value codes, never free text or direct identifier. `metric_json` has a versioned schema containing only approved measures, denominator, missing count, confidence/quality labels where applicable, and rounding metadata. Suppressed raw numerator/value must not appear in released snapshot/cache/API.

## 8. Findings and PPEPP (`core` + private object storage)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `findings` | Actionable interpretation of released evidence | `id uuid PK`; `aggregate_snapshot_id uuid FK`; `owner_unit_id uuid FK`; `code varchar(80)`; `title varchar(300)`; `severity varchar(24)`; `state varchar(32)` | `description text`; `created_by uuid FK`; `approved_by uuid FK`; `due_on date` | I; finding cites released snapshot, not raw rows |
| `actions` | Corrective/improvement action | `id uuid PK`; `finding_id uuid FK`; `pic_user_id uuid FK`; `title varchar(300)`; `state varchar(32)`; `due_on date` | `plan text`; `submitted_at/verified_at timestamptz`; `verified_by uuid FK`; `verification_decision varchar(24)`; `impact_review_on date` | I/C; PIC ≠ verifier; state transitions audited |
| `action_evidence` | Metadata for immutable evidence object | `id uuid PK`; `action_id uuid FK`; `object_key varchar(500)`; `checksum char(64)`; `media_type varchar(120)`; `size_bytes bigint`; `classification varchar(16)`; `uploaded_at timestamptz` | `uploaded_by uuid FK`; `malware_scan_state varchar(24)`; `retention_due_at timestamptz` | C by default; private storage; verified checksum and safe media only |

## 9. Governance and reliability (`core`)

| Table | Purpose | Key fields and types | Sensitive/optional fields | Constraints and classification |
|---|---|---|---|---|
| `audit_logs` | Append-only security/business evidence | `id uuid PK`; `occurred_at timestamptz`; `actor_user_id uuid nullable`; `actor_type varchar(24)`; `action varchar(120)`; `object_type varchar(80)`; `object_id uuid nullable`; `result varchar(24)`; `correlation_id uuid`; `event_hash char(64)` | `scope_snapshot_json jsonb`; `before_hash/after_hash char(64)`; `safe_reason_code varchar(80)`; `previous_event_hash char(64)` | R; no secret/raw payload; application role cannot update/delete; ≥2 years `[P]` |
| `settings` | Versioned non-secret institutional policy | `id uuid PK`; `key varchar(160)`; `version int`; `value_json jsonb`; `classification varchar(16)`; `status varchar(24)` | `effective_from/until timestamptz`; `approved_by uuid FK` | I/C; unique key+version; no credential, arbitrary URL, or executable expression |
| `outbox_events` | Durable side-effect intent | `id uuid PK`; `aggregate_type varchar(80)`; `aggregate_id uuid`; `event_type varchar(120)`; `idempotency_key varchar(160) UQ`; `available_at timestamptz` | `payload_json jsonb`; `dispatched_at timestamptz`; `attempt_count int` | I/C; reference/minimum data only; same transaction as aggregate write |
| `job_runs` | Recoverable asynchronous state/attempts | `id uuid PK`; `outbox_event_id uuid FK nullable`; `job_type varchar(80)`; `state varchar(32)`; `input_checksum char(64)`; `attempt_count int`; `requested_by uuid FK nullable` | `lease_until timestamptz`; `started_at/completed_at timestamptz`; `failure_code varchar(80)`; `next_attempt_at timestamptz` | I; no secret/raw payload; one active logical lease |
| `legal_holds` | Approved preservation exception | `id uuid PK`; `scope_type varchar(80)`; `scope_id uuid`; `reason_code varchar(80)`; `status varchar(24)`; `starts_at timestamptz`; `approved_by uuid FK` | `case_reference varchar(160)`; `released_at timestamptz`; `released_by uuid FK` | R; dual control; no sensitive case narrative in general UI |
| `retention_cases` | Deletion/archive execution and evidence | `id uuid PK`; `object_type varchar(80)`; `object_id uuid`; `policy_version varchar(40)`; `disposition varchar(24)`; `due_at timestamptz`; `state varchar(32)` | `legal_hold_id uuid FK`; `manifest_hash char(64)`; `executed_by uuid FK`; `verified_by uuid FK`; `completed_at timestamptz` | I/R; executor ≠ sole verifier; tombstone contains no deleted payload |

## 10. Status catalogs

| Object | Allowed states |
|---|---|
| Instrument version | `draft`, `in_review`, `returned`, `approved`, `published`, `retired` |
| Survey | `draft`, `preflight_failed`, `ready`, `scheduled`, `open`, `paused`, `closed`, `archived`, `cancelled` |
| Invitation | `created`, `sent`, `opened`, `provisioning`, `completed`, `expired`, `revoked` |
| Response | `draft`, `validating`, `submitted`, `rejected`, `retention_due`, `deleted` |
| Analysis/job | `queued`, `running`, `retry_waiting`, `failed`, `quarantined`, `completed`, `cancelled` |
| Snapshot/report | `candidate`, `review_pending`, `released`, `superseded`, `revoked` |
| Export | `requested`, `preflight`, `generating`, `quarantined`, `ready`, `expired`, `revoked`, `failed` |
| Finding/action | states mengikuti Phase 05; closure requires independent verification and impact plan |

State string di database memakai CHECK/reference catalog version. Transisi tetap diperiksa domain policy dengan actor, scope, precondition, and audit event.

## 11. Prohibited fields and patterns

- plaintext password, API key, access/refresh token, session cookie, invitation/receipt token, KMS key material;
- arbitrary `base_url`, URL with userinfo, or endpoint supplied per request;
- invitation/user/contact FK in Response DB for anonymous modes;
- raw IP, complete user-agent, device fingerprint, advertising ID, or unbounded request headers in response dataset;
- raw response/open text in audit log, queue payload, notification, metric label, exception, or analytics cache key;
- polymorphic `type/id` without allowlist and orphan reconciliation;
- mutable JSON scoring/policy attached only by reference to “latest”; each run/campaign stores immutable checksum/version.

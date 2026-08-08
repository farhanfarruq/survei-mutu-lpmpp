# Endpoint Catalog

Versi: **1.0.0-draft — 2026-08-07**  
Status: contract inventory only. `MVP` means product priority, not implemented availability.

## 1. Catalog conventions

- All paths use `/api/v1` unless shown as Laravel auth/bootstrap route.
- `R/W/X/E` mean Read/Write/Execute/Export; the exact permission code follows Phase 04.
- Scope is evaluated server-side before query. `Assigned` means an active assignment and purpose.
- All internal unsafe operations require session + CSRF; administrative roles require MFA.
- Commands use `POST` subresources to make state transition, idempotency, permission, and audit explicit.
- Every successful body is transformed by a Laravel API Resource/ResourceCollection.
- `ETag/If-Match` is required where `Lock` says yes. `IK` means `Idempotency-Key` required.

## 2. Authentication and session

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | MVP |
|---|---|---|---|---|---|:---:|
| API-AUTH-001 | `GET /sanctum/csrf-cookie` | initialize first-party CSRF cookie | `204` | public bootstrap | rate limited; no user data | Yes |
| API-AUTH-002 | `POST /login` | credential/SSO-compatible login | `204` or MFA state | public authenticate | CSRF; rate; session rotation; no account enumeration | Yes |
| API-AUTH-003 | `POST /logout` | revoke current session | `204` | authenticated | CSRF; audit admin logout | Yes |
| API-AUTH-004 | `GET /session` | current user, assurance, permissions/scope summary | `SessionResource` | authenticated | only own session; no secret/raw grants detail beyond client need | Yes |
| API-AUTH-005 | `POST /session/step-up` | complete step-up MFA challenge | `SessionResource` | authenticated | CSRF; challenge-bound; rate limited | Yes |

## 3. Identity, role, and organizational scope

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | Lock | MVP |
|---|---|---|---|---|---|:---:|:---:|
| API-IAM-001 | `GET /organizational-units` | scoped hierarchy/catalog | `OrganizationUnitCollection` | `organization.read` | caller hierarchy/audit case; cursor/filter allowlist | No | Yes |
| API-IAM-002 | `POST /organizational-units` | create effective-dated unit | `201 OrganizationUnitResource` | `organization.manage` | governance scope; no cycle | IK | Yes |
| API-IAM-003 | `PATCH /organizational-units/{organizational_unit_id}` | update/retire unit | `OrganizationUnitResource` | `organization.manage` | governance scope; descendants protected | Yes | Yes |
| API-IAM-004 | `GET /users` | internal account list | `UserCollection` | `user.read` | institution/audit scope; fields C-filtered | No | Yes |
| API-IAM-005 | `GET /roles` | role/permission catalog | `RoleCollection` | `role.read` | governance/audit scope | No | Yes |
| API-IAM-006 | `POST /user-role-assignments` | create time-bound scoped grant | `201 GrantResource` | `role.manage` | no self-escalation; approval/expiry | IK | Yes |
| API-IAM-007 | `POST /user-role-assignments/{assignment_id}/revocations` | revoke grant | `GrantResource` | `role.manage` | no concealment; audit | IK + If-Match | Yes |

## 4. Templates, instrument versions, and review

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | Lock | MVP |
|---|---|---|---|---|---|:---:|:---:|
| API-TPL-001 | `GET /survey-templates` | list templates | `SurveyTemplateCollection` | `template.read` | unit/assigned/audit | No | Yes |
| API-TPL-002 | `POST /survey-templates` | create template | `201 SurveyTemplateResource` | `template.create` | creator unit | IK | Yes |
| API-TPL-003 | `GET /survey-templates/{template_id}` | template metadata/version links | `SurveyTemplateResource` | `template.read` | unit/assigned | No | Yes |
| API-TPL-004 | `PATCH /survey-templates/{template_id}` | update draft metadata/retire | `SurveyTemplateResource` | `template.update` | owner unit/state | Yes | Yes |
| API-TPL-005 | `POST /survey-templates/{template_id}/versions` | create next draft version | `201 InstrumentVersionResource` | `template.update` | owner unit; semantic change reason | IK | Yes |
| API-TPL-006 | `GET /instrument-versions/{instrument_version_id}` | complete version representation | `InstrumentVersionResource` | `template.read` | unit/assigned; includes allowlisted | No | Yes |
| API-TPL-007 | `PATCH /instrument-versions/{instrument_version_id}` | edit draft version metadata | `InstrumentVersionResource` | `template.update` | draft only | Yes | Yes |
| API-TPL-008 | `POST /instrument-versions/{instrument_version_id}/sections` | add section | `201 SectionResource` | `template.update` | draft owner | IK + If-Match parent | Yes |
| API-TPL-009 | `PATCH /sections/{section_id}` | update/reorder section | `SectionResource` | `template.update` | draft version | Yes | Yes |
| API-TPL-010 | `POST /sections/{section_id}/questions` | add typed question | `201 QuestionResource` | `template.update` | draft; code/scale/item validation | IK + If-Match parent | Yes |
| API-TPL-011 | `PATCH /questions/{question_id}` | edit question/options/branching | `QuestionResource` | `template.update` | draft; single neutral item rules | Yes | Yes |
| API-REV-001 | `POST /instrument-versions/{instrument_version_id}/review-submissions` | freeze hash and request review | `202 ReviewSubmissionResource` | `validation.create` | creator unit; preflight; IK | No | Yes |
| API-REV-002 | `GET /review-assignments` | reviewer work queue | `ReviewAssignmentCollection` | `validation.read` | assigned only except admin/auditor | No | Yes |
| API-REV-003 | `GET /review-assignments/{assignment_id}` | version/evidence under review | `ReviewAssignmentResource` | `validation.read` | active assignment/audit case | No | Yes |
| API-REV-004 | `POST /review-assignments/{assignment_id}/decisions` | approve or return reviewed hash | `201 ReviewResource` | `validation.approve` | reviewer ≠ creator; assigned; IK | No | Yes |

## 5. Period, campaign, population, and participation

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | Lock/IK | MVP |
|---|---|---|---|---|---|---|:---:|
| API-CAM-001 | `GET /survey-periods` | period catalog | `SurveyPeriodCollection` | `campaign.read` | institution/unit | — | Yes |
| API-CAM-002 | `POST /survey-periods` | create period | `201 SurveyPeriodResource` | `campaign.create` | institution policy | IK | Yes |
| API-CAM-003 | `GET /surveys` | list scoped campaigns | `SurveyCollection` | `campaign.read` | unit/hierarchy/assignment; safe filters | — | Yes |
| API-CAM-004 | `POST /surveys` | create draft campaign | `201 SurveyResource` | `campaign.create` | owner unit; approved instrument | IK | Yes |
| API-CAM-005 | `GET /surveys/{survey_id}` | campaign metadata/policy snapshot | `SurveyResource` | `campaign.read` | unit/hierarchy/assignment | — | Yes |
| API-CAM-006 | `PATCH /surveys/{survey_id}` | edit campaign draft | `SurveyResource` | `campaign.update` | draft owner; time/state guard | If-Match | Yes |
| API-CAM-015 | `POST /surveys/{survey_id}/review-submissions` | submit campaign configuration for review | `SurveyResource` | `campaign.review` | owner unit; preflight; audit | IK + If-Match | Yes |
| API-CAM-016 | `POST /surveys/{survey_id}/review-decisions` | approve or return campaign configuration | `SurveyResource` | `campaign.approve` | reviewer ≠ creator; scoped; audit | IK + If-Match | Yes |
| API-CAM-007 | `POST /surveys/{survey_id}/targets` | add group/unit target | `201 SurveyTargetResource` | `population.manage` | campaign owner + source scope | IK + If-Match | Yes |
| API-CAM-008 | `POST /surveys/{survey_id}/population-import-jobs` | validated CSV population import | `202 JobResource` | `population.manage` | campaign unit; quarantine invalid; IK | IK | Yes |
| API-CAM-009 | `POST /surveys/{survey_id}/preflights` | evaluate publication blockers | `200 PreflightResource` | `campaign.publish` | owner; no state mutation unless recorded | IK | Yes |
| API-CAM-010 | `POST /surveys/{survey_id}/publications` | publish/schedule campaign | `SurveyResource` | `campaign.publish` | approved version; owner/privacy/scoring/population; IK + If-Match | Both | Yes |
| API-CAM-011 | `POST /surveys/{survey_id}/pauses` | pause active campaign | `SurveyResource` | `campaign.update` | owner + approved reason | Both | Post |
| API-CAM-012 | `POST /surveys/{survey_id}/closures` | close campaign | `SurveyResource` | `campaign.update` | owner; no new response; IK | Both | Yes |
| API-CAM-013 | `GET /surveys/{survey_id}/participation-summary` | aggregate invitation/delivery/completion | `ParticipationSummaryResource` | `participation.read` | campaign scope; no response linkage/content | — | Yes |
| API-CAM-014 | `POST /surveys/{survey_id}/notification-batches` | invitation/reminder batch | `202 JobResource` | `notification.execute` | campaign scope; max reminder/policy | IK | Yes |

## 6. Respondent and response capture

| ID | Method/path | Purpose / request | Success | Authentication/control | Lock/IK | MVP |
|---|---|---|---|---|---|:---:|
| API-RSP-001 | `POST /respondent-sessions` | exchange invitation token for campaign-bound response session/handoff | `201 RespondentSessionResource` | token HMAC, expiry/state/rate; never returns identity | IK | Yes |
| API-RSP-002 | `GET /respondent-survey` | active instrument/notice for current response session | `RespondentSurveyResource` | response session only; no admin representation | — | Yes |
| API-RSP-003 | `POST /responses` | provision independent draft from one-time handoff | `201 ResponseResource` | response session/handoff; no invitation ID persisted | IK | Yes |
| API-RSP-004 | `GET /responses/{response_id}` | own draft + allowed answers | `ResponseResource` | matching response session; submitted content minimized | — | Yes |
| API-RSP-005 | `PATCH /responses/{response_id}` | autosave typed answer delta | `ResponseResource` | matching draft/session; schema/branch validation | If-Match + IK | Yes |
| API-RSP-006 | `POST /responses/{response_id}/submissions` | validate and final-submit exactly once | `200 SubmissionReceiptResource` | draft/session, consent, complete schema; no identity join | If-Match + IK | Yes |
| API-RSP-007 | `POST /respondent-sessions/completion-recoveries` | user-assisted participation acknowledgement after partial cross-store submit | `200 CompletionRecoveryResource` | invitation proof + receipt proof, no persisted linkage | IK | Yes |

Submitted response has no update/delete endpoint. Privacy deletion uses governed retention/rights workflows, not respondent resource mutation that would imply identifiability.

## 7. Analysis, aggregate, leadership, and reporting

| ID | Method/path | Purpose / request | Success | Permission | Scope/threshold | Lock/IK | MVP |
|---|---|---|---|---|---|---|:---:|
| API-ANA-001 | `POST /surveys/{survey_id}/analysis-runs` | create reproducible statistical job | `202 JobResource` | `analysis.execute` | assigned campaign; closed/eligible; immutable checksums | IK | Yes |
| API-ANA-002 | `GET /analysis-runs/{analysis_run_id}` | analysis state/quality/lineage | `AnalysisRunResource` | `analysis.read` | campaign assignment/unit | — | Yes |
| API-ANA-003 | `POST /analysis-runs/{analysis_run_id}/releases` | review/release safe aggregate snapshot | `AggregateReleaseResource` | `analysis.release` | reviewer/approver ≠ requester; threshold pass | IK | Yes |
| API-ANA-004 | `GET /aggregate-snapshots/{snapshot_id}` | scoped safe snapshot detail | `AggregateSnapshotResource` | `analysis.read`/`report.read` | released or assigned candidate; suppression projection | — | Yes |
| API-LEAD-001 | `GET /leadership/results` | leadership dashboard collection | `LeadershipResultCollection` | `report.read` | hierarchy derived server-side; released only; threshold/suppression/anti-differencing | — | Yes |
| API-REP-001 | `POST /aggregate-snapshots/{snapshot_id}/reports` | create report draft | `201 ReportResource` | `report.create` | snapshot/campaign scope; released safe input | IK | Yes |
| API-REP-002 | `GET /reports/{report_id}` | read report metadata/content | `ReportResource` | `report.read` | hierarchy/assignment; released/candidate access differs | — | Yes |
| API-REP-003 | `POST /reports/{report_id}/release-decisions` | approve/reject release | `ReportResource` | `report.approve` | reviewer ≠ requester; parity/checksum | IK + If-Match | Yes |
| API-EXP-001 | `POST /report-exports` | request PDF/CSV/XLSX from report/snapshot | `202 ExportResource` | `report.export` | server-scoped fields/filter; threshold + class + recipient + approval | IK | Yes |
| API-EXP-002 | `GET /report-exports/{export_id}` | export job/quarantine/expiry status | `ExportResource` | `report.export`/requester | requester + current scope; no object URL until ready/approved | — | Yes |
| API-EXP-003 | `POST /report-exports/{export_id}/release-decisions` | approve/reject sensitive export | `ExportResource` | `report.approve` | approver ≠ requester; dual approval where class requires | IK + If-Match | Yes |
| API-EXP-004 | `POST /report-exports/{export_id}/download-tickets` | obtain one-time short-lived download | `201 DownloadTicketResource` | `report.export` | current scope, release, threshold parity, not revoked/expired | IK | Yes |
| API-EXP-005 | `POST /report-exports/{export_id}/revocations` | revoke artifact/ticket | `ExportResource` | `report.approve`/privacy authority | campaign/classification scope; audit | IK + If-Match | Yes |

`LeadershipResultResource`, `ReportResource`, and export generators must use the same immutable released aggregate projection. Client-supplied `organizational_unit_id`, filters, `include`, or `fields` can only narrow an authorized hierarchy and can never unsuppress a cell.

## 8. AI jobs (post-MVP, feature off)

| ID | Method/path | Purpose / request | Success | Permission | Gate | Lock/IK | MVP |
|---|---|---|---|---|---|---|:---:|
| API-AI-001 | `POST /analysis-runs/{analysis_run_id}/ai-jobs` | request governed AI candidate | `202 JobResource` | `ai.execute` | registry/config/dataset threshold/redaction/budget/reviewer; feature enabled | IK | No |
| API-AI-002 | `GET /ai-jobs/{ai_job_id}` | state/cost/evaluation metadata | `AiJobResource` | `ai.read` | assigned use case; no raw prompt/secret/provider body | — | No |
| API-AI-003 | `GET /ai-results/{ai_result_id}` | quarantined/approved candidate | `AiResultResource` | `ai.review` | assigned reviewer; field-filtered evidence | — | No |
| API-AI-004 | `POST /ai-results/{ai_result_id}/review-decisions` | approve/edit/reject with rationale | `AiResultResource` | `ai.review` | reviewer assigned; no self-exception approval | IK + If-Match | No |

## 9. Findings, action, evidence, and verification

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | Lock/IK | MVP |
|---|---|---|---|---|---|---|:---:|
| API-PPE-001 | `GET /findings` | scoped finding list | `FindingCollection` | `finding.read` | hierarchy/assignment; no raw response | — | Yes |
| API-PPE-002 | `POST /findings` | create finding from released snapshot | `201 FindingResource` | `finding.create` | owner unit + released evidence | IK | Yes |
| API-PPE-003 | `GET /findings/{finding_id}` | finding/actions/status | `FindingResource` | `finding.read` | hierarchy/assignment | — | Yes |
| API-PPE-004 | `PATCH /findings/{finding_id}` | edit draft/priority/owner | `FindingResource` | `finding.update` | owner unit/state | If-Match | Yes |
| API-PPE-005 | `POST /findings/{finding_id}/actions` | assign action/PIC/due/impact plan | `201 ActionResource` | `action.create` | finding scope; PIC assignment | IK | Yes |
| API-PPE-006 | `GET /actions/{action_id}` | action/evidence/verification state | `ActionResource` | `action.read` | assigned/hierarchy | — | Yes |
| API-PPE-007 | `PATCH /actions/{action_id}` | PIC updates plan/progress | `ActionResource` | `action.update` | assigned PIC; allowed state fields | If-Match | Yes |
| API-PPE-008 | `POST /actions/{action_id}/evidence` | initiate evidence metadata/upload workflow | `201 EvidenceResource` | `evidence.create` | PIC/assignment; file policy/scanning | IK | Yes |
| API-PPE-009 | `POST /actions/{action_id}/verification-submissions` | submit complete action for verification | `ActionResource` | `action.submit` | PIC; evidence checks | IK + If-Match | Yes |
| API-PPE-010 | `POST /actions/{action_id}/verification-decisions` | verify/return with rationale | `ActionResource` | `action.verify` | verifier assigned and ≠ PIC | IK + If-Match | Yes |

## 10. Governance, audit, retention, and settings

| ID | Method/path | Purpose / request | Success | Permission | Scope/control | Lock/IK | MVP |
|---|---|---|---|---|---|---|:---:|
| API-GOV-001 | `GET /settings` | safe versioned policy settings | `SettingCollection` | `policy.read` | governance scope; no secret values/arbitrary URL | — | Yes |
| API-GOV-002 | `POST /settings` | create policy version | `201 SettingResource` | `policy.create` | privacy/security approval by class | IK | Yes |
| API-GOV-003 | `POST /settings/{setting_id}/approval-decisions` | approve/reject setting version | `SettingResource` | `policy.approve` | approver ≠ author | IK + If-Match | Yes |
| API-GOV-004 | `GET /audit-events` | audit search | `AuditEventCollection` | `audit.read` | approved audit/privacy/security case; content-safe | — | Yes |
| API-GOV-005 | `POST /audit-export-jobs` | signed/redacted audit package | `202 JobResource` | `audit.export` | explicit scope + dual approval | IK | Yes |
| API-GOV-006 | `GET /retention-cases` | due/held/failed disposition cases | `RetentionCaseCollection` | `retention.read` | privacy/operation/audit scope | — | Yes |
| API-GOV-007 | `POST /retention-cases/{case_id}/execution-jobs` | execute approved manifest | `202 JobResource` | `retention.execute` | hold check; executor ≠ sole verifier | IK + If-Match | Yes |
| API-GOV-008 | `POST /retention-cases/{case_id}/verification-decisions` | verify disposition evidence | `RetentionCaseResource` | `retention.verify` | independent verifier | IK + If-Match | Yes |
| API-GOV-009 | `POST /legal-holds` | create approved preservation hold | `201 LegalHoldResource` | `legal-hold.create` | case scope + dual approval | IK | Yes |
| API-GOV-010 | `POST /legal-holds/{legal_hold_id}/release-decisions` | release hold | `LegalHoldResource` | `legal-hold.release` | approver/SoD; downstream retention recalculated | IK + If-Match | Yes |
| API-GOV-011 | `GET /ai-configurations` | provider/model/policy/fingerprint list | `AiConfigurationCollection` | `ai-config.read` | governance scope; never secret/base URL free text | — | No |
| API-GOV-012 | `POST /ai-configurations` | create config and write secret to KMS | `201 AiConfigurationResource` | `ai-config.create` | step-up MFA; registry endpoint only; write-only secret | IK | No |
| API-GOV-013 | `POST /ai-configurations/{configuration_id}/rotations` | replace secret/reference | `AiConfigurationResource` | `ai-config.rotate` | step-up + SoD; never read old/new secret | IK + If-Match | No |
| API-GOV-014 | `POST /ai-configurations/{configuration_id}/approval-decisions` | approve/reject configuration version | `AiConfigurationResource` | `ai-config.approve` | privacy/security/data owner; author ≠ approver | IK + If-Match | No |

## 11. Resource and command schema map

| Resource/command | Minimum request attributes | Minimum response attributes |
|---|---|---|
| Survey create/update | instrument version, period, owner unit, code, privacy mode, opens/closes/timezone, policy references | ID, state, immutable snapshots/checksums when published, version/ETag |
| Question create/update | code, item text, response type, required, indicator/scale, options/position | normalized type/options, validation/branch summary, version |
| Autosave | answer delta with question ID + typed value + client schema version | saved answer summaries, response state/version, saved_at |
| Final submit | consent/notice version, client schema hash, confirmation | nonidentifying receipt, submitted_at, immutable state; no answer echo by default |
| Analysis job | method/rule reference and expected input version | job ID/state/input checksums; output only through snapshot resource |
| Leadership result | filter/sort/field request only | dimension/metric, n/missing/coverage, value or suppression state, limitations, release ID |
| Export job | report/snapshot, approved format, allowlisted field/filter profile, purpose/recipient where required | state/classification/policy checksum/expiry; no direct object URL until approved |
| AI configuration | provider/model registry IDs, secret write-only input, budget/policy versions | config ID, fingerprint/reference metadata, state; secret never returned |
| Error | none | Problem Details base + code/request_id/errors/retryable/current version where safe |

## 12. Items requiring owner confirmation

- exact permission code catalog/name mapping and whether Filament uses internal actions or the same HTTP endpoints;
- SSO/MFA endpoint/challenge shape and emergency-account policy;
- final rate/idempotency TTL/include depth/pagination total policy;
- upload initiation/storage contract and accepted media limits;
- raw research extract endpoint remains intentionally absent until exception governance is accepted;
- AI endpoints remain absent/disabled in MVP even though their future contract is catalogued.

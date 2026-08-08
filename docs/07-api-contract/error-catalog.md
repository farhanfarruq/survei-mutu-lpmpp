# Error Catalog and Retry Matrix

Versi: **1.0.0-draft — 2026-08-07**

## 1. Error representation

Errors use `application/problem+json` with stable machine-readable extensions.

```json
{
  "type": "https://docs.example.invalid/problems/validation-failed",
  "title": "Request validation failed",
  "status": 422,
  "detail": "One or more fields are invalid.",
  "instance": "/api/v1/surveys/018f7f2e-...",
  "code": "VALIDATION_FAILED",
  "request_id": "01J4Z6T2E3X9F9ZEM7M8W5D1PC",
  "errors": [
    {
      "pointer": "/data/attributes/closes_at",
      "code": "AFTER_FIELD",
      "message": "The close time must be after the open time.",
      "meta": {"after": "/data/attributes/opens_at"}
    }
  ],
  "retryable": false
}
```

Rules:

- `code` and nested error codes are stable API contracts; `title/detail/message` may be localized and must not be parsed.
- `pointer` is a JSON Pointer to client-supplied data or a query/header pseudo-pointer such as `/query/filter/state` or `/headers/If-Match`.
- Rejected value is not echoed when it may contain contact, token, answer, secret, file content, provider data, or excessive text.
- `request_id` is safe for support; it is not a cross-store identity-content join key.
- Debug trace, SQL, class/path, stack, internal hostname/IP, secret, policy expression, provider body, and other actors' identifiers are never returned.
- A scoped lookup outside the caller's hierarchy normally returns the same `404 RESOURCE_NOT_FOUND` as a nonexistent ID.

## 2. Common headers

| Header | When |
|---|---|
| `Content-Type: application/problem+json` | every error body |
| `X-Request-ID` | every response; same as body `request_id` |
| `Retry-After` | retryable 429/503 where a safe delay is known |
| `RateLimit-Limit/Remaining/Reset` | rate-limited route classes where disclosure-safe |
| `ETag` | current resource representation, not normally included on concealed/denied errors |

## 3. Authentication, authorization, and request errors

| Code | HTTP | Meaning | Client action | Retryable |
|---|---:|---|---|:---:|
| `MALFORMED_REQUEST` | 400 | invalid JSON/query/header syntax | correct request | No |
| `UNSUPPORTED_QUERY_PARAMETER` | 400 | filter/sort/include/fields not catalogued | remove/correct parameter | No |
| `INVALID_CURSOR` | 400 | cursor invalid, expired, or bound to different scope/filter/sort | restart first page | No |
| `AUTHENTICATION_REQUIRED` | 401 | session absent/expired | initialize CSRF/login again | No automatic replay of unsafe request |
| `INVALID_RESPONDENT_SESSION` | 401 | response session absent/invalid | re-enter through invitation/recovery | No blind retry |
| `SESSION_OR_CSRF_EXPIRED` | 419 | first-party session/CSRF mismatch/expiry | refresh CSRF/session; ask user before replaying unsafe action | Conditional |
| `PERMISSION_DENIED` | 403 | atomic permission absent | request authorized grant/process | No |
| `SCOPE_DENIED` | 404 | resource outside organizational/campaign/assignment scope | do not enumerate | No |
| `STEP_UP_REQUIRED` | 403 | stronger MFA/assurance required | perform allowlisted step-up | Yes after step-up |
| `PURPOSE_REQUIRED` | 403 | purpose/time-bound grant absent | obtain approval | No |
| `SEPARATION_OF_DUTIES` | 403 | creator/requester/PIC cannot self-approve/verify | different authorized actor | No |
| `DATA_CLASS_DENIED` | 403 | requested field/export class exceeds grant | narrow request or approved exception | No |
| `RESOURCE_NOT_FOUND` | 404 | absent or concealed resource | check visible ID/path | No |
| `METHOD_NOT_ALLOWED` | 405 | operation not supported | use catalogued operation | No |
| `NOT_ACCEPTABLE` | 406 | response media/version unsupported | request supported type/version | No |
| `UNSUPPORTED_MEDIA_TYPE` | 415 | request content type unsupported | use JSON/approved upload media | No |
| `PAYLOAD_TOO_LARGE` | 413 | body/file exceeds limit | reduce content | No |

## 4. Validation, version, idempotency, and state errors

| Code | HTTP | Meaning | Client action | Retryable |
|---|---:|---|---|:---:|
| `VALIDATION_FAILED` | 422 | one or more field/domain validations failed | correct indicated fields | No |
| `UNKNOWN_FIELD` | 422 | JSON property not allowed | remove field | No |
| `INVALID_STATE_TRANSITION` | 409 | command invalid for current state | refresh resource/choose valid action | No automatic |
| `IMMUTABLE_RESOURCE` | 409 | published/submitted/released record cannot be changed | create new version/superseding object | No |
| `PRECONDITION_REQUIRED` | 428 | required `If-Match` or idempotency key absent | resend with current precondition | Yes after correction |
| `VERSION_CONFLICT` | 412 | `If-Match` does not equal current version | fetch, compare, reapply | Yes manually |
| `IDEMPOTENCY_KEY_REUSED` | 409 | same key used for a different fingerprint | use new key only for genuinely new logical operation | No for same operation |
| `IDEMPOTENCY_IN_PROGRESS` | 409 | same logical request is still processing | poll returned resource/status or retry after hint | Yes |
| `DUPLICATE_RESOURCE` | 409 | unique business key already exists | use existing or correct key | No |
| `PREFLIGHT_FAILED` | 422 | publication/export/AI/retention blockers | resolve blocker list | No |
| `INSTRUMENT_HASH_CHANGED` | 409 | reviewed hash differs from current draft | return to review | No |
| `SURVEY_NOT_OPEN` | 409 | response requested before/after allowed window or paused | wait/use status guidance | Conditional by state |
| `RESPONSE_ALREADY_SUBMITTED` | 409 | submitted response is immutable | use original receipt; do not resubmit | No |
| `CONSENT_REQUIRED` | 422 | required notice/consent decision absent/outdated | show approved notice and obtain decision | No |
| `INVITATION_EXPIRED` | 410 | invitation expired | contact campaign owner if appropriate | No |
| `EXPORT_EXPIRED` | 410 | export/ticket/object expired | request a new authorized export | New operation only |
| `RESOURCE_REVOKED` | 410 | artifact/config/link intentionally revoked | follow replacement process | No |
| `LEGAL_HOLD_ACTIVE` | 409 | retention deletion blocked | review hold; no automatic deletion retry | No |

## 5. Reporting, privacy, file, and AI errors

| Code | HTTP | Meaning | Client action | Retryable |
|---|---:|---|---|:---:|
| `REPORT_NOT_RELEASED` | 409 | candidate result/report not approved | complete release workflow | No |
| `REPORTING_THRESHOLD_NOT_MET` | 422 | requested cell/output below policy threshold | accept suppressed representation or broaden approved aggregate | No |
| `SUPPRESSION_PARITY_FAILED` | 409 | dashboard/export calculations differ or unsafe cell found | quarantine; owner fixes policy/run | No |
| `ANTI_DIFFERENCING_BLOCKED` | 429 | query combination/rate risks inference | stop/narrow approved reporting route; privacy review | No automatic |
| `RAW_EXPORT_APPROVAL_REQUIRED` | 403 | raw extract lacks purpose/dual approval | approved exception workflow | No |
| `FILE_TYPE_NOT_ALLOWED` | 422 | detected media/type not permitted | upload allowed type | No |
| `FILE_SCAN_PENDING` | 409 | object not yet cleared | poll evidence status | Yes |
| `FILE_QUARANTINED` | 422 | malware/polyglot/checksum/content policy failed | replace file; security review | No |
| `AI_FEATURE_DISABLED` | 403 | AI not activated or killed | use manual/statistical workflow | No |
| `AI_GOVERNANCE_BLOCKED` | 422 | registry/redaction/threshold/reviewer/provider gate failed | satisfy gate or use manual analysis | No |
| `AI_BUDGET_EXCEEDED` | 429 | token/currency/use-case budget unavailable | wait for approved period or obtain new approval | No blind retry |
| `AI_OUTPUT_QUARANTINED` | 422 | schema/leakage/safety/grounding control failed | human/manual handling; do not release | No |
| `SECRET_WRITE_FAILED` | 503 | secret manager did not confirm write/rotation | keep old state; operator reconcile | Conditional, never resend blindly if result unknown |
| `ENDPOINT_SECURITY_BLOCKED` | 422 | endpoint allowlist/DNS/IP/TLS/redirect/SSRF control failed | security review; do not bypass | No |

## 6. Rate, dependency, and server errors

| Code | HTTP | Meaning | Client action | Retryable |
|---|---:|---|---|:---:|
| `RATE_LIMITED` | 429 | request rate exceeded | wait `Retry-After`; preserve idempotency key | Yes |
| `CONCURRENCY_LIMITED` | 429 | per-user/scope/job concurrency exhausted | poll existing jobs then retry | Yes |
| `PROVIDER_RATE_LIMITED` | 503 | provider returned trusted rate limit | worker/client follows bounded delay | Yes |
| `DEPENDENCY_UNAVAILABLE` | 503 | required dependency/circuit unavailable | retry safe operation after hint; core state unchanged | Conditional |
| `DEPENDENCY_TIMEOUT` | 504 | dependency timed out before known completion | query/reconcile state before repeating side effect | Conditional |
| `DEPENDENCY_BAD_RESPONSE` | 502 | provider schema/signature/content invalid | operator/provider correction | No automatic unless known transient |
| `RESULT_UNKNOWN` | 503 | external acceptance may have occurred | poll/reconcile using same idempotency identity | Reconcile first |
| `QUEUE_UNAVAILABLE` | 503 | durable intent could not be accepted | preserve user input; retry with same idempotency key | Yes |
| `STORAGE_UNAVAILABLE` | 503 | evidence/export object operation unavailable | retry safe job; no false ready state | Yes |
| `INTEGRITY_CHECK_FAILED` | 500 | checksum/FK/reconciliation invariant failed | stop workflow; operator investigation | No |
| `UNEXPECTED_ERROR` | 500 | uncategorized internal failure | support with request ID; do not blind-retry unsafe action | Conditional safe GET only |

## 7. Job failure codes

Jobs expose safe `failure.code`, `retryable`, `attempt_count`, `next_attempt_at`, and operator-case reference when authorized. They do not expose exception text/provider body/raw data.

| Job code | Automatic retry | Terminal/recovery |
|---|---|---|
| `JOB_TRANSIENT_NETWORK` | yes, bounded exponential+jitter | dead-letter after max attempts |
| `JOB_PROVIDER_RATE_LIMIT` | yes, honor bounded trusted delay | circuit/dead-letter |
| `JOB_STALE_LEASE` | reconciliation may requeue | manual case after repeated stale lease |
| `JOB_INPUT_VERSION_CHANGED` | no | cancel/quarantine; create a new job on new immutable input |
| `JOB_PERMISSION_REVOKED` | no | quarantine for owner decision |
| `JOB_VALIDATION_FAILED` | no | failed/quarantined; correct source |
| `JOB_PRIVACY_BLOCKED` | no | quarantine; Privacy Officer review |
| `JOB_SUPPRESSION_FAILED` | no | quarantine; no report/export release |
| `JOB_BUDGET_BLOCKED` | no | failed/cancelled; new approval/new job |
| `JOB_MALWARE_DETECTED` | no | object quarantine/security case |
| `JOB_RESULT_UNKNOWN` | reconcile first | retry only if provider state proves safe |
| `JOB_ATTEMPTS_EXHAUSTED` | no automatic | durable dead-letter/operator runbook |

## 8. HTTP retry matrix

| Operation class | Example | Client may retry automatically? | Required identity/precondition |
|---|---|---|---|
| Safe read | GET collection/resource | yes for network/502/503/504 with bounded backoff | same auth/scope; cursor may expire |
| Mutable PATCH | autosave/admin edit | only if no response and same body/preconditions; handle 412 manually | same `Idempotency-Key` + `If-Match` |
| Final submit | response submission | yes only with same key/body/version; otherwise poll receipt/status | same idempotency key + If-Match |
| Job create | analysis/export/AI | yes with same key/body; poll Location/job | same idempotency key |
| External side effect | notification/provider secret | server worker reconciles; browser does not retry blindly | durable job/reference/idempotency |
| Download ticket | create ticket | retry same key; ticket itself one-time | current permission/scope/release |
| Approval/verification | governed command | only exact same key/body; state conflict requires refresh | idempotency + If-Match where mutable |
| Deletion/retention | destructive workflow | never blind client retry | manifest/case/policy/hold + worker reconciliation |

Backoff guideline for eligible client retries: 0.5s, 1s, 2s with jitter, maximum three attempts, capped by `Retry-After`/route policy. UI must keep user-visible state and never claim success without authoritative response/resource.

## 9. Validation error codes

| Nested code | Meaning |
|---|---|
| `REQUIRED` | missing required field |
| `TYPE` | wrong JSON/type representation |
| `FORMAT` | invalid UUID/date-time/email/code format |
| `ENUM` | value not in allowlist |
| `MIN` / `MAX` | numeric/string/cardinality bound |
| `AFTER_FIELD` / `BEFORE_FIELD` | temporal cross-field order |
| `MUTUALLY_EXCLUSIVE` | incompatible fields supplied together |
| `DEPENDENCY_REQUIRED` | another field/artefact is required |
| `REFERENCE_INVALID` | referenced visible resource/type/state invalid |
| `PRIVACY_MODE_CONFLICT` | request violates fixed campaign privacy mode |
| `ANSWER_TYPE_MISMATCH` | answer does not match question response type/scale |
| `BRANCH_NOT_ACTIVE` | answer submitted for inactive branch |
| `SCHEMA_VERSION_MISMATCH` | client/request schema not compatible |

## 10. Audit and observability

Authentication/authorization denial, state conflict, repeated idempotency mismatch, rate/anti-differencing block, export/AI/privacy/security failure, job quarantine/dead-letter, and unexpected error create content-safe security/business events according to policy. Expected respondent validation errors are measured in bounded aggregate form and not logged with answer/contact payload.

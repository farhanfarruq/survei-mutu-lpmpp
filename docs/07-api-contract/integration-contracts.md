# Integration Contracts

Versi: **1.0.0-draft — 2026-08-07**  
Status: interfaces and acceptance obligations only; no provider/product/protocol is claimed selected unless explicitly stated.

## 1. Boundary principles

- Domain services depend on narrow integration interfaces, not vendor SDK models or raw provider responses.
- Each adapter maps external identifiers/status/errors to canonical internal types and preserves source lineage/version.
- External systems never decide SIMUTU authorization, reporting threshold, campaign state, or release truth.
- Secrets are resolved from KMS/secret manager; database/API/UI keep opaque references/fingerprints only.
- Network calls use exact endpoint registry/allowlist, TLS verification, timeout, bounded retry, circuit breaker, egress restriction, and content/schema/size validation.
- Provider unavailability degrades only the related capability. Survey response submit does not depend on email or AI.
- Integration logs contain request/event ID hash, adapter/version, safe status, latency, attempt, and reconciliation counts—no credential/raw response/contact payload.

## 2. Common adapter contract

Conceptual interface obligations:

| Operation | Contract |
|---|---|
| `capabilities()` | immutable adapter/version and supported operations/schema/protocol limits |
| `health()` | bounded technical readiness; never treated as business transaction proof |
| `execute(command)` | validated canonical command → normalized result/error with correlation/reference |
| `reconcile(query)` | compare authoritative external state to known internal reference without broad data pull |
| `redactForLog(result)` | safe metadata only; secret/payload forbidden |

Normalized result contains `status`, `external_reference_hash`, `occurred_at`, `retryable`, `retry_after`, `safe_error_code`, and mapping/schema version. It never returns a plaintext secret through a general response object.

## 3. SIAKAD/population source interface

### 3.1 Role and ownership

SIAKAD/SDM/alumni/CRM remains authoritative for eligibility/contact attributes at a source snapshot time. SIMUTU owns campaign targeting, invitation state, response content, survey results, and follow-up. There is no write-back to academic records in the baseline.

### 3.2 Interface operations

| Operation | Input | Output | Failure behavior |
|---|---|---|---|
| `inspectSourceSchema` | source/profile/version reference | available canonical field mappings and provenance | incompatible schema blocks import |
| `fetchPopulationSnapshot` | approved period/group/unit/purpose, cursor or file object | batches of canonical population rows + source snapshot ID/checksum | partial batch quarantined; no campaign publish |
| `validatePopulationRow` | one mapped row | valid row or field-level safe error | invalid row excluded, count reported |
| `reconcilePopulation` | source snapshot and SIMUTU import IDs | added/removed/changed counts and hashes | discrepancy creates case; never silently overwrites published frame |

Initial MVP transport is validated CSV uploaded to private storage. Future API/batch transport implements the same interface; this document does not assume SIAKAD exposes REST, event, database, or webhook access.

### 3.3 Canonical mapping

| Canonical field | Required | Class | Rule |
|---|:---:|---|---|
| `source_subject_id` | Yes | Restricted | encrypted at rest; dedupe HMAC; never response content/AI |
| `source_system` / `source_snapshot_id` | Yes | Internal | allowlisted source code and immutable lineage |
| `respondent_group_code` | Yes | Confidential | mapped to approved group catalog |
| `organizational_unit_code` | Yes | Confidential | must resolve to active/effective unit within campaign scope |
| `eligibility_status` | Yes | Confidential | allowlisted enum and source-effective timestamp |
| `email` | Conditional | Restricted | normalized/validated/encrypted; only if email invitation purpose |
| `name` | Optional | Restricted | notification personalization only if approved; never response store |
| `program/cohort/role attributes` | Optional | Confidential/Restricted | import allowlist, minimization, rare-combination review |

Forbidden by default: grades, disciplinary/financial/health data, passwords, full student profile, free-form notes, or any field not required by the approved campaign purpose.

### 3.4 Import/reconciliation contract

- File/API batch has schema version, source period, generated timestamp, owner, row count, checksum, encoding, and field mapping version.
- Duplicate is based on source+subject HMAC within campaign frame; a collision/conflict is quarantined.
- If invalid rows exceed 1% `[P from Phase 04]`, import fails as a whole; at/below threshold valid rows remain staged until owner accepts/rejects the exception.
- Published campaign frame is immutable. Later SIAKAD changes create a new snapshot and approved target adjustment, never silent mutation.
- Contact/participation is purged ≤90 days after campaign close `[P]`; source remains authoritative.

## 4. SSO/Identity Provider interface

### 4.1 Protocol neutrality

OIDC Authorization Code + PKCE is preferred when supported, but SAML or local Fortify pilot may satisfy the interface after security review. The contract requires claims/assurance behavior, not a named vendor.

### 4.2 Interface operations

| Operation | Input | Output | Failure behavior |
|---|---|---|---|
| `beginAuthentication` | local transaction, return path, required assurance | signed state/nonce and IdP redirect/request | fail without session; no open redirect |
| `completeAuthentication` | callback/assertion/code + stored transaction | canonical identity result | invalid issuer/audience/signature/nonce/time → deny/audit |
| `resolveAccountStatus` | canonical subject reference | active/disabled/locked + checked time | fail closed for privileged new session |
| `beginStepUp` / `completeStepUp` | session + action assurance | assurance result/expiry | action remains denied |
| `logout` | session/provider reference | local revoked; provider logout status optional | local revoke is authoritative |

### 4.3 Canonical identity result

| Field | Rule |
|---|---|
| `issuer` + `subject` | stable pair; hash/encrypt external reference; email is not primary identity |
| `authenticated_at` | validated UTC time within skew policy |
| `assurance` / `auth_methods` | map only documented provider values to local levels |
| `account_status` | active required for new session; local emergency policy separate |
| `display_name/email` | profile/contact only and minimized |
| `group/role claims` | input to governed mapping, never direct permission grant without local active assignment/policy |

SSO callback URLs are fixed allowlisted routes. State/nonce/PKCE/assertion replay, issuer/audience, signature algorithm/key rotation, clock skew, metadata/JWKS cache/failure, and account deprovisioning must pass contract tests.

## 5. Email provider interface

### 5.1 Purpose

Email delivers invitation, reminder, assignment, action, and safe operational notification. It is not a source of campaign truth and never carries response content, raw comment, secret, or Restricted export attachment.

### 5.2 Operations

| Operation | Input | Output | Retry/reconciliation |
|---|---|---|---|
| `sendMessage` | logical message key, recipient resolved in restricted worker, template version, safe variables | accepted/rejected + provider reference hash | same logical key; transient retry only |
| `queryDelivery` | provider reference | normalized queued/delivered/bounced/complained/unknown | poll only if provider supports and needed |
| `handleDeliveryEvent` | verified future webhook/provider event | normalized disposition + event ID/time | deduplicate; unknown/out-of-order safe |
| `suppressRecipient` | approved complaint/hard bounce reference | suppression result | does not modify survey eligibility truth |

### 5.3 Template contract

Allowed variables are catalogued per template, e.g. institution name, survey title, close date/timezone, safe role/unit name, and short invitation link. Forbidden variables include response state beyond invited/completed reminder logic, answer/comment, participant list, API key, export object URL, or arbitrary HTML. Plain-text fallback and accessible HTML are required.

Provider statuses map to canonical: `accepted`, `queued`, `delivered`, `soft_bounce`, `hard_bounce`, `complained`, `rejected`, `unknown`. “Accepted” is not “delivered”. Retry soft/transient only; hard bounce/complaint is not retried automatically. Maximum reminder baseline remains three per participant/campaign.

Mailpit is development evidence only; production provider, API/SMTP transport, webhook support, sender/domain, residency, and SLA remain TBD.

## 6. AI provider interface

AI remains post-MVP/off. The interface is defined so provider-specific code cannot bypass governance.

### 6.1 Operations

| Operation | Input | Output | Failure behavior |
|---|---|---|---|
| `describeCapabilities` | provider/model registry ID | structured output, limits, residency/retention/no-training evidence refs | mismatch disables config |
| `estimateUsage` | redacted payload + prompt/model version | bounded token/cost estimate | budget cannot be reserved → reject |
| `generateCandidate` | strict prompt envelope, redacted data, response schema, limits, idempotency key | normalized candidate + usage/model/provider reference hash | timeout/rate/provider/schema normalized; no auto-release |
| `cancel` | job/provider reference | cancellation state where supported | local job still governed |
| `reconcileUsage` | provider reference/time window | tokens/cost/status metadata | discrepancy case; no raw prompt pull |

### 6.2 Mandatory boundary

- Provider/model/endpoint comes from approved registry. No free-form `base_url` or per-request URL.
- Secret is a KMS reference resolved at runtime, never included in request DTO/log/API response.
- Exact HTTPS endpoint allowlist, DNS/IP private/reserved rejection, redirect disabled, TLS verification, and egress proxy/firewall apply.
- Input is allowlisted, threshold-approved, deterministically/human-sampled redacted, and delimited as untrusted data. No tools/browsing/function calls baseline.
- Response is untrusted: strict schema, size, PII/canary, citation/grounding/numerical/safety scan, then quarantine and human review.
- Provider retention target is zero, no training, approved residency/contract; failure to prove a material gate keeps adapter disabled.
- Retry preserves idempotency/budget reservation and only covers approved transient errors; safety/schema/privacy/budget failures are not retried.

## 7. Integration error normalization

| Canonical code | Meaning | Retry |
|---|---|---|
| `INTEGRATION_AUTH_FAILED` | invalid/revoked adapter credential or signature | No; disable/rotate/escalate |
| `INTEGRATION_FORBIDDEN` | provider/source rejects operation | No until configuration/authority changes |
| `INTEGRATION_SCHEMA_MISMATCH` | unsupported field/event/version/response shape | No; quarantine and update mapping |
| `INTEGRATION_VALIDATION_FAILED` | canonical row/message/payload invalid | No; correct input |
| `INTEGRATION_RATE_LIMITED` | trusted provider limit | Yes within bounded `Retry-After` policy |
| `INTEGRATION_TIMEOUT` | connect/read/total timeout | Conditional safe retry |
| `INTEGRATION_UNAVAILABLE` | transient 5xx/network/circuit open | Conditional bounded retry |
| `INTEGRATION_CONFLICT` | duplicate/version/state mismatch | Reconcile; not blind retry |
| `INTEGRATION_RESULT_UNKNOWN` | timeout after external acceptance | Query/reconcile before repeating side effect |
| `INTEGRATION_SECURITY_BLOCKED` | endpoint/cert/signature/SSRF/leakage control failed | No; contain and security review |

External details are mapped to safe internal codes. Raw provider error/body is kept only in approved short-lived restricted incident evidence where necessary, never returned to ordinary clients.

## 8. Contract testing

Every adapter needs synthetic contract fixtures for:

- valid/minimal request and response;
- unknown/missing/oversized field and incompatible schema version;
- duplicate, out-of-order, partial batch, pagination/cursor, and reconciliation;
- auth/signature/issuer/audience/nonce/replay/rotation;
- timeout, 429, 5xx, accepted-but-unknown, circuit breaker, retry/idempotency;
- PII/secret/log redaction and data-class/field allowlist;
- SSRF/private IPv4/IPv6/metadata/redirect/DNS rebinding for URL-capable adapters;
- provider/source change without silent semantic remapping.

Tests run against mocks/sandboxes with synthetic data. Production credentials/data are not needed for contract validation.

## 9. Future webhook interface

Webhook remains disabled until a specific adapter is accepted. The generic policy in [events-jobs-schedules.md](events-jobs-schedules.md) applies. Each webhook ADR/contract must additionally define event types/versions, signature canonicalization, replay window, deduplication, ordering, acknowledgement semantics, retry/deactivation, endpoint registry, data classification, retention, provider verification, and exit/revocation.

## 10. Confirmations required

- source systems and owners, actual SIAKAD field availability/quality/export mechanism;
- IdP protocol/issuer/claims/MFA/keys/deprovisioning and emergency login;
- production email provider/transport/domain/webhook/residency/rate/SLA;
- whether AI remains excluded; if not, use case/provider/model/contract/budget/evaluation;
- service account/KMS/egress/monitoring products and integration on-call responsibility.

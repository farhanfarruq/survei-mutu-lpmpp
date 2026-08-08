# Events, Jobs, Schedules, and Future Webhooks

Versi: **1.0.0-draft — 2026-08-07**  
Status: internal contract catalog; broker/scheduler implementation details remain Phase implementation work.

## 1. Event envelope

Business events are durable outbox records, not arbitrary Redis payloads. Event names use past tense and version suffix only for incompatible schema changes.

```json
{
  "event_id": "018f7f2e-4c8d-7c19-8aa2-2fcf27318451",
  "event_type": "survey.published.v1",
  "event_version": 1,
  "occurred_at": "2026-08-07T12:00:00Z",
  "aggregate": {"type": "surveys", "id": "018f7f2e-...", "version": 8},
  "actor": {"type": "user", "id": "018f7f2e-..."},
  "scope": {"organization_unit_id": "018f7f2e-..."},
  "correlation_id": "01J4Z6T2E3X9F9ZEM7M8W5D1PC",
  "causation_id": "018f7f2e-...",
  "idempotency_key": "publish:018f7f2e-...:v8",
  "data": {"instrument_version_id": "018f7f2e-...", "opens_at": "2026-08-10T01:00:00Z"}
}
```

Rules:

- Payload contains minimum references/safe state only; no raw answer/open text, contact, token, secret, decrypted linkage, provider payload, or signed download URL.
- Schema is JSON Schema/OpenAPI-compatible, versioned, size-capped, and `additionalProperties: false` for high-risk events.
- Producer writes outbox in the same PostgreSQL transaction as aggregate state. Dispatcher marks delivery separately.
- Consumer deduplicates by `event_id`/logical idempotency key and validates aggregate version/state before side effect.
- Ordering is guaranteed only per aggregate where explicitly enforced; consumers tolerate duplicate, delay, and out-of-order events.
- Audit event is separate from domain event. Domain event triggers work; audit evidence records who/what/result/policy.

## 2. Domain event catalog

| Event type | Producer | Minimum data | Consumers | Privacy/classification |
|---|---|---|---|---|
| `identity.session_started.v1` | IAM | user ID, assurance, scope-version hash | audit/security metrics | C; no credential/claims dump |
| `access.grant_changed.v1` | IAM | assignment ID, grantee ID, scope ref, state/version | cache invalidator, audit, notification | C; no reason narrative in event |
| `instrument.version_submitted.v1` | Instrument | version ID/hash, owner unit | review assignment/notification | I |
| `instrument.review_decided.v1` | Review | assignment/version IDs, reviewed hash, decision | instrument state, notification, audit | I/C comment excluded |
| `instrument.version_approved.v1` | Review | version ID/hash, approval ID | campaign availability | I |
| `survey.preflight_completed.v1` | Campaign | survey ID/version, pass flag, blocker codes | UI/operation audit | I; no population rows |
| `survey.published.v1` | Campaign | survey/version/owner/open-close/privacy-policy refs | invitation generation, notification scheduling, audit | I/C |
| `survey.paused.v1` | Campaign | survey ID/version, reason code | notification/collection guard | I |
| `survey.closed.v1` | Campaign | survey ID/version, close timestamp | draft expiry, analysis eligibility, retention planning | I |
| `population.import_requested.v1` | Campaign | job/source object reference, schema version, survey ID | population import worker | C; object private |
| `population.import_completed.v1` | Import worker | job ID, valid/invalid counts, snapshot hash | campaign preflight, notification | C; no person row |
| `invitation.delivery_requested.v1` | Notification command | invitation/message template references, logical key | email adapter worker | C/R; recipient resolved only in worker |
| `notification.delivery_recorded.v1` | Email adapter | notification ID, safe provider status/code | participation monitoring/retry | C; provider body excluded |
| `response.provisioned.v1` | Response | response ID, survey ID, schema hash | content audit/expiry | C; not sent to Participation DB |
| `response.submitted.v1` | Response | response ID, survey ID, submitted time, schema hash | analysis eligibility, content audit | C; no invitation/user/contact |
| `participation.completed.v1` | Participation | invitation ID, survey ID, completion time bucket | participation aggregate | C; no response ID/receipt |
| `analysis.requested.v1` | Analysis API | analysis/job/survey IDs, input/policy checksums | analysis worker | I; no response payload |
| `analysis.completed.v1` | Analysis worker | run ID, output checksum, quality summary | release workflow/audit | I/R if failure/suppressed refs |
| `aggregate.released.v1` | Release service | snapshot/release IDs, policy checksum, owner scope | cache warm, dashboard/report | I; safe released data referenced |
| `report.release_decided.v1` | Reporting | report/release IDs, decision, checksum | export/findings/audit | I |
| `export.requested.v1` | Export API | export/report IDs, format/profile, policy/scope hashes | export worker | C; recipient resolved purpose-bound |
| `export.ready.v1` | Export worker | export ID, checksum, classification, expiry | requester notification | C/R; no object URL |
| `export.revoked.v1` | Reporting/privacy | export ID, reason code, version | ticket/object revoker, audit | I |
| `finding.created.v1` | PPEPP | finding/snapshot/owner IDs, severity | action workflow/notification | I |
| `action.assigned.v1` | PPEPP | action/finding/PIC IDs, due date | notification/scheduler | C staff assignment |
| `action.verification_submitted.v1` | PPEPP | action/version/evidence checksum refs | verifier queue | C |
| `action.verification_decided.v1` | PPEPP | action/version, decision, impact-review date | closure/notification/audit | I/C |
| `ai.requested.v1` | AI gateway | job/config/run/prompt/redaction versions, input hash, budgets | AI worker | R; no raw prompt |
| `ai.completed.v1` | AI worker | job/result IDs, output hash, token/cost/safety summary | evaluation/review queue | C/R |
| `retention.disposition_due.v1` | Retention scanner | case/object/policy refs, due time | retention workflow | I/R by object type |
| `retention.disposition_completed.v1` | Retention worker | case ID, manifest hash, tombstone ref, result | audit/metrics | I; no deleted payload |
| `security.secret_rotated.v1` | Secret config | config ID, secret reference version/fingerprint, result | cache/worker reload, audit | S metadata; no secret |

Events crossing the Core/Response boundary must not create identity-content linkage. `response.submitted` never goes to participation consumers; completion acknowledgement is a separate command/result without response ID.

## 3. Job envelope and state

```json
{
  "job_id": "018f7f2e-4c8d-7c19-8aa2-2fcf27318451",
  "job_type": "report_export.generate.v1",
  "state": "queued",
  "input": {"export_id": "018f7f2e-...", "policy_checksum": "sha256:..."},
  "requested_by": "018f7f2e-...",
  "scope_hash": "sha256:...",
  "idempotency_key": "export:018f7f2e-...:v3",
  "attempt": 0,
  "max_attempts": 3,
  "available_at": "2026-08-07T12:00:00Z"
}
```

State: `queued → running → completed`; transient error may produce `retry_waiting → queued`; validation/security/privacy/parity failure produces `quarantined`; exhausted error produces `failed/dead_letter`; authorized cancellation produces `cancelled`. The durable state is in PostgreSQL `job_runs`; Redis is transport only.

## 4. Job catalog

| Job type | Trigger | Queue/class | Idempotency and retry | Success evidence | Failure handling |
|---|---|---|---|---|---|
| `population_import.validate.v1` | API-CAM-008 | import/restricted | import object checksum + survey version; max 2 transient | valid/invalid counts + snapshot hash | invalid rows quarantined; >approved threshold fails |
| `invitations.generate.v1` | survey published | participation | survey+population snapshot; max 1 logical generation | invitation count/reconciliation | no partial re-generation without manifest |
| `notifications.dispatch.v1` | API-CAM-014/event | email | logical message key stable; max 5 `[P]` exponential+jitter | delivery request/result state | dead-letter; no campaign truth change |
| `response_drafts.expire.v1` | schedule | retention/content | response+policy version; no blind retry after hold ambiguity | tombstone/count | retention case failure |
| `analysis.compute.v1` | API-ANA-001 | analysis | immutable input/method/checksums; max 2 transient | run output checksum + quality | partial output never released |
| `aggregate.release_checks.v1` | API-ANA-003 | analysis/high | run+policy version; deterministic replay | suppression/parity evidence | quarantine candidate |
| `report.generate.v1` | API-REP-001 | reporting | snapshot+template/checksum; max 2 | report content hash | failed draft, no release |
| `report_export.generate.v1` | API-EXP-001 | export/restricted | export ID/version/policy; max 3 transient | object checksum, classification, parity | quarantine/revoke unsafe object |
| `report_export.revoke.v1` | API-EXP-005/expiry | security/high | export ID+version | tickets revoked + object state | alert if object remains accessible |
| `action.reminders.dispatch.v1` | schedule/event | email | action+due bucket+template | logical delivery result | bounded retry; action state unchanged |
| `evidence.scan.v1` | evidence uploaded | security/high | object checksum | malware/media/checksum result | quarantine object; block verification |
| `ai.execute.v1` | API-AI-001 | ai/isolated | job/input/config/prompt hash; budget reservation; provider policy | normalized result/cost/safety hash | fail closed/quarantine/circuit; no auto-release |
| `retention.execute.v1` | API-GOV-007 | retention/high | manifest hash + policy/hold state | verified zero accessible targets + tombstone | retry per target; incident if exposure remains |
| `audit_export.generate.v1` | API-GOV-005 | audit/restricted | case/scope/policy; max 2 | signed/redacted manifest/package | quarantine; no partial package |
| `outbox.dispatch.v1` | scheduler/poll | operations | event ID; at-least-once | dispatch marker/consumer ack as applicable | bounded retry/dead-letter |
| `job.reconcile.v1` | scheduler | operations | stale lease/job ID | state repaired/requeued/manual case | alert after attempts |
| `backup.verify.v1` | backup catalog event/schedule | operations/high | backup manifest/checksum | verification evidence | critical alert; alternate backup |

Workers re-authorize current scope/state at execution for sensitive jobs. A queued permission is not a permanent grant. When permission revocation would create inconsistency, job is quarantined for explicit decision.

## 5. Scheduler catalog

Schedules are timezone-explicit and protected by a distributed overlap lock. Exact cron is deployment configuration; table states initial intent.

| Schedule ID | Frequency (PROPOSED) | Command/purpose | Guard | Evidence/alert |
|---|---|---|---|---|
| SCH-001 | every minute | dispatch due outbox events | singleton; bounded batch | oldest outbox age/failed dispatch |
| SCH-002 | every minute | requeue due `retry_waiting` jobs | state/attempt/next time | queue lag/dead-letter count |
| SCH-003 | every 5 min | reconcile stale job leases | compare durable job/worker heartbeat | stale count/manual cases |
| SCH-004 | every 5 min | open/close scheduled surveys | IANA timezone + state/version lock | transition/audit exceptions |
| SCH-005 | every 15 min | notification batch eligibility/reminder | campaign state, max reminder, quiet hours | planned/sent/suppressed counts |
| SCH-006 | hourly | expire invitation/response sessions/download tickets | hold/state policy | expired count and anomalous failures |
| SCH-007 | hourly | revoke/purge export objects past expiry | object inventory + release state | accessible-after-expiry alert |
| SCH-008 | daily approved window | scan retention due and create cases | policy version + legal hold | due/held/overdue counts |
| SCH-009 | daily | verify population/participation/response aggregate reconciliation | no identity-content join | count discrepancies only |
| SCH-010 | daily | snapshot cache/version integrity | released checksum/suppression parity | invalidate/alert mismatch |
| SCH-011 | daily | AI budget/provider registry expiry check | feature may remain off | budget/config/circuit status |
| SCH-012 | daily | backup age/WAL gap/checksum check | independent backup catalog | critical if RPO window at risk |
| SCH-013 | weekly | dependency/secret/config drift evidence collection | safe metadata only | owner report |
| SCH-014 | monthly | permission/assignment expiry and review report | no auto-broadening | stale/excess grant report |
| SCH-015 | quarterly | create restore-drill task/evidence due | isolated environment/authority | overdue/failed drill critical |

No schedule performs unapproved raw deletion, self-approves a report, enables AI, or changes a campaign privacy mode.

## 6. Error, retry, and dead-letter principles

- Retry only errors marked retryable in [error-catalog.md](error-catalog.md); validation, permission, scope, privacy, suppression, secret, malware, and schema failures are not automatically retried.
- Backoff uses exponential delay + jitter with per-job cap. `Retry-After` from a trusted allowlisted provider can increase the delay within policy bounds.
- Retry retains event/job/idempotency identity and records attempt, safe code, next time, and circuit state.
- Dead-letter is a durable operator case, not only a Redis failed-job payload. It has owner, classification, next action, expiry, and resolution evidence.
- Reprocessing a dead-letter needs current permission/state/policy and new execution attempt; it cannot mutate history.

## 7. Future webhook policy

No inbound or outbound business webhook is enabled in MVP. Future webhook support requires an approved integration and threat/contract review.

### Outbound

- subscription is administrator-created from an endpoint registry; no caller-provided callback URL per event/request;
- exact HTTPS host/port/path allowlist plus the Phase 06 DNS/IP/redirect/egress SSRF controls;
- per-subscription secret stored in KMS, payload signed (e.g. HMAC-SHA-256 over timestamp + event ID + raw body), timestamp/replay window, and rotation overlap;
- CloudEvents-like minimum envelope may be used but raw responses/contact/secret/suppressed cells are never webhook data;
- at-least-once delivery, stable event ID, receiver deduplication, bounded retry, circuit/deactivation, delivery audit, and payload retention minimum;
- recipient classification/purpose/contract and data residency are approved before subscription activation.

### Inbound

- dedicated adapter path per approved provider/integration, not generic `/webhooks/{url}` proxy;
- verify signature/mTLS/issuer and raw-body timestamp/replay before parsing; apply size/content-type/schema/rate limits;
- provider event ID deduplicated; unknown event/version quarantined, not treated successful business mutation;
- immediately acknowledge only after durable receipt when provider semantics require; business processing remains asynchronous;
- inbound payload is untrusted and minimized after mapping; secret/signature/raw payload is not logged.

Webhook management itself requires MFA, SoD, masked secret reference, endpoint allowlist, test event with synthetic data, kill switch, and audit.

## 8. Contract compatibility

- Event/job schema is backward-compatible within `.v1`; incompatible change publishes a new event/job type and dual-read/migration window.
- Consumers ignore documented optional additions but reject unknown critical enum/state safely.
- Producer and consumer contract tests use synthetic fixtures and verify forbidden fields are absent.
- Deprecation requires consumer inventory, last-seen usage, replacement, deadline, and replay/recovery plan.

# ADR-006: Transactional Outbox and Recoverable Asynchronous Jobs

## Status

Proposed — 2026-08-07

## Context

Email, analysis, AI, export, retention, and storage calls are slow/failure-prone. Redis is available, but queue loss or retry must not lose business intent or duplicate artefacts.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Provider call inside request/DB transaction | direct outcome | long lock, timeout ambiguity, poor recovery |
| Redis queue as only state | simple | loss/replay cannot be reconciled authoritatively |
| **DB outbox + job ledger + Redis transport** | durable intent, idempotency, rebuild after Redis loss | dispatcher/reconciliation and extra writes |

## Decision

Commit business state and minimum outbox record atomically in PostgreSQL. Redis transports job references; worker claims a durable job lease, applies unique idempotency, records attempt/result, and quarantines exhausted work. Redis cache/queue/session never holds the only copy of business truth.

## Trade-offs and consequences

- Positive: crash/replay/rebuild safety and observable state.
- Negative: eventual consistency and operational reconciliation.
- Mitigation: user-visible job states, bounded retry/backoff, stale lease scanner, provider-side idempotency when available.
- Revisit if proven throughput requires another broker; preserve durable outbox/job contract.


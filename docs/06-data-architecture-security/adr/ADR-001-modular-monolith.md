# ADR-001: Modular Monolith for the Initial Product

## Status

Proposed — 2026-08-07

## Context

One institution, team size, workload, production topology, and independent scaling needs are not confirmed. The baseline stack already uses Laravel, Filament, Vue, PostgreSQL, Redis, Horizon, scheduler, Nginx, and email. Domain rules are coupled by instrument/campaign/analysis/report/PPEPP lineage.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Microservices | independent deployment/scale and stronger process isolation | distributed transactions, duplicated operations, contract/version/observability burden without proven need |
| Unstructured monolith | lowest initial structure | authorization/privacy/domain rules become entangled |
| **Modular monolith + worker processes** | simple deployment/transactions with explicit module boundaries and async isolation | requires discipline to prevent cross-module shortcuts |

## Decision

Use one Laravel application boundary containing Filament/admin and API/domain modules, a first-party Vue SPA, separate Horizon workers/scheduler, PostgreSQL truth, and Redis transport/cache/session. Modules follow M01–M10 and communicate through application services/events, not arbitrary table access.

## Trade-offs and consequences

- Positive: matches current stack/team uncertainty; easy transactional consistency and testing.
- Negative: web/worker share codebase and may scale together; privacy isolation cannot rely on module naming alone.
- Mitigation: separate DBs/roles for response/vault, workload-specific processes/queues, architecture fitness tests.
- Revisit when team >10 with independent ownership, a module has materially different scale/availability/data-boundary needs, or deployment contention is measured.


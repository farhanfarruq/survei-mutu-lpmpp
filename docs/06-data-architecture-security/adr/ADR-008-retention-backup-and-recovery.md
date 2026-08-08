# ADR-008: Manifest-Driven Retention and Tested Recovery

## Status

Proposed — 2026-08-07

## Context

Data exists across databases, objects, cache, exports, AI staging, audit, and backups. Backup success alone does not prove recoverability; deletion of one row does not prove disposition.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Ad hoc delete + daily dump | simple | orphan copies, no hold/control/restore proof |
| Permanent retention | easy historical access | privacy, security, cost, and purpose risk |
| **Versioned retention manifest + encrypted PITR + restore drill** | verifiable lifecycle and recovery | operational ownership, storage, drill effort |

## Decision

Each class has proposed retention; due cases resolve legal hold, build a cross-store manifest, revoke access, delete/crypto-shred, invalidate derivatives/cache, verify, and write payload-free tombstone. PostgreSQL uses daily full/base + WAL ≤15 minutes, encrypted separate-domain backups retained 35 days `[P]`; quarterly application-level restore targets RPO ≤15 minutes and RTO ≤4 hours `[P]`.

## Trade-offs and consequences

- Positive: evidence-backed deletion/recovery and bounded data exposure.
- Negative: targets/cost/on-call and backup-key recovery require institutional approval.
- Mitigation: staged drills, metrics/alerts, alternate backup, legal-hold and exception ownership.
- Revisit after capacity/risk assessment, regulation/policy change, failed drill, or material topology change.

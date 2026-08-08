# ADR-003: Separate Participation, Response Content, and Linkage Vault

## Status

Proposed — 2026-08-07

## Context

The MVP assumes detached anonymous-content while invitations/reminders need participation state. Calling data anonymous is invalid if identity and content retain a join key. Some future confidential use cases may require tightly controlled linkage.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| One schema with nullable invitation FK | simple workflow/query | accidental/privileged linkage and misleading anonymity |
| Separate tables in one DB | moderate logical separation | database owner/query can easily join; credential isolation weak |
| **Separate DBs/roles plus optional vault** | no ordinary cross-database FK/join; explicit purpose boundary | cross-store recovery and operations more complex |

## Decision

Use Core/Participation DB, Response Content DB, and optional Linkage Vault with distinct credentials. Detached/strict modes have no vault row or persisted invitation–response key. Confidential linkage is encrypted, purpose/time-bound, dual-approved, and unavailable to ordinary web/admin/analyst processes.

## Trade-offs and consequences

- Positive: makes privacy claim testable and reduces accidental joining.
- Negative: no distributed transaction; partial submit recovery cannot use hidden operator correlation.
- Mitigation: one-time nonpersisted handoff, respondent receipt, state reconciliation, log separation, clear notice.
- Revisit if pilot proves recovery unusable or risk appetite requires separate cluster/account rather than databases on one cluster; privacy must not be weakened silently.


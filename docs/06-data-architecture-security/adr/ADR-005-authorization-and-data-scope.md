# ADR-005: Deny-by-Default Authorization with Data Scope and SoD

## Status

Proposed — 2026-08-07

## Context

Role name alone cannot express unit hierarchy, assignment, state, classification, purpose, export, or separation-of-duties. Super Admin must not automatically read response content.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Coarse RBAC only | easy administration | overbroad access and unsafe exports |
| Full generic policy engine | expressive | new operational language/service and failure modes |
| **RBAC + explicit scoped grants + resource policy** | fits Laravel/Spatie and requirements; testable | more negative test combinations |

## Decision

An operation is allowed only when assurance, atomic permission, active organizational/campaign/assignment scope, object state, data-class ceiling, purpose, and SoD all pass. Queries are pre-scoped; serializers field-filter; database roles separate core/response/vault. Super Admin has no implicit raw grant.

## Trade-offs and consequences

- Positive: least privilege and direct traceability to BR/NFR.
- Negative: policy/test matrix and grant governance complexity.
- Mitigation: central policy/actions, explicit permission catalog, cache version/revocation, automated negative matrix.
- Revisit if stable policies require a dedicated engine or proven PostgreSQL RLS adds defense without pooling/context ambiguity.


# ADR-004: Immutable Versions, Runs, and Aggregate Snapshots

## Status

Proposed — 2026-08-07

## Context

Survey meaning, scoring, thresholds, and reports must remain reproducible after global configuration changes. Dashboard/API/export must not diverge or expose suppressed values.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Read latest mutable configuration | simple writes | historical results change silently |
| Event sourcing everywhere | complete event history | excessive implementation/operational complexity |
| **Immutable version/snapshot + audit history** | stable lineage with ordinary relational model | extra rows/storage and explicit supersession |

## Decision

Approved instrument versions, campaign policy/scoring snapshots, analysis inputs, AI prompt/config versions, released aggregate snapshots, and reports are immutable and checksummed. Corrections create superseding versions/runs/releases. Dashboard/API/export consume the same released safe snapshot.

## Trade-offs and consequences

- Positive: reproducibility, suppression parity, auditability.
- Negative: storage and lifecycle/version UX complexity.
- Mitigation: retain only necessary immutable inputs/derivatives, archive by policy, provide explicit comparison/supersession.
- Revisit if storage/query evidence warrants separate analytical store, preserving immutable lineage contract.


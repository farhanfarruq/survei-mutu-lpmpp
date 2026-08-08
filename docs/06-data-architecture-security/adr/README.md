# Architecture Decision Records

| ADR | Status | Decision |
|---|---|---|
| [ADR-001](ADR-001-modular-monolith.md) | Proposed | modular monolith Laravel/Filament + Vue with workers |
| [ADR-002](ADR-002-postgresql-uuidv7-and-integrity.md) | Proposed | PostgreSQL source of truth, UUIDv7, relational constraints |
| [ADR-003](ADR-003-response-identity-separation.md) | Proposed | separate participation, response content, and linkage vault boundaries |
| [ADR-004](ADR-004-immutable-versions-and-snapshots.md) | Proposed | immutable instrument/policy/run/snapshot lineage |
| [ADR-005](ADR-005-authorization-and-data-scope.md) | Proposed | deny-by-default effective grant with SoD and separate DB roles |
| [ADR-006](ADR-006-async-outbox-and-redis.md) | Proposed | transactional outbox/job ledger; Redis remains ephemeral |
| [ADR-007](ADR-007-secret-and-ai-provider-boundary.md) | Proposed | write-only secret manager and fixed provider registry with SSRF controls |
| [ADR-008](ADR-008-retention-backup-and-recovery.md) | Proposed | manifest-driven retention and tested encrypted recovery |

`Proposed` means architecture baseline for owner review, not production implementation. An ADR becomes `Accepted` only after named authority approves its open trade-offs and dependent controls have an accountable delivery plan.


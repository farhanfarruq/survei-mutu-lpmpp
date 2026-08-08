# ADR-002: PostgreSQL Source of Truth and UUIDv7 Identifiers

## Status

Proposed — 2026-08-07

## Context

The system needs nonenumerable interoperable identifiers, strong referential integrity, reproducible versions, and recoverable queues. PostgreSQL is present; Redis must not become business truth.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Bigint PK | compact/fast | enumerable and awkward across isolated stores |
| ULID text | sortable and readable | text/collation/casing discipline; no native PostgreSQL UUID type |
| UUIDv4 | native and random | random insert locality |
| **UUIDv7 native `uuid`** | interoperable, time-ordered, native validation/indexing | reveals coarse generation time; requires compatible generator |

## Decision

Use UUIDv7 native `uuid` for domain PKs, explicit timestamps for business ordering, relational FK/unique/check constraints within a database, and logical checked references across database boundaries. Secret/token identifiers remain ≥256-bit random and stored only as HMAC/hash.

## Trade-offs and consequences

- Positive: merge-safe and avoids public sequence enumeration while retaining native type.
- Negative: 16-byte indexes and time component; UUID is not an access secret.
- Mitigation: scope authorization on every lookup, avoid redundant indexes, measure query plans.
- Revisit if framework/database support cannot produce/validate UUIDv7 consistently or measured index cost is material.


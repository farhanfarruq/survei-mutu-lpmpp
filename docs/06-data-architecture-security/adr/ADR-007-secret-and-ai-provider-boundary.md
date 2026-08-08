# ADR-007: Write-Only Secrets and Fixed AI Provider Registry

## Status

Proposed — 2026-08-07

## Context

Provider credentials and arbitrary Base URLs create disclosure/SSRF risk. AI is post-MVP and must not gain broad egress or receive unredacted survey data.

## Options considered

| Option | Benefits | Costs |
|---|---|---|
| Encrypted key and free-form Base URL in DB | flexible UI | application can reveal/decrypt key; SSRF and endpoint drift |
| Environment-only single provider | minimal | rotation/multi-environment governance and audit limitations |
| **Secret-manager reference + approved provider registry** | write-only credential, controlled endpoint/model/budget | requires KMS/registry/egress operations |

## Decision

Store API keys only in an approved secret manager/KMS; database stores opaque reference, version, and masked fingerprint. Configuration references a fixed provider/model/endpoint policy. No free-form custom Base URL. Exceptional institutional endpoint needs exact allowlist, URL/DNS/IP validation, redirects disabled, TLS verification, and network egress restriction.

## Trade-offs and consequences

- Positive: smaller secret/SSRF blast radius and auditable rotation.
- Negative: provider onboarding is slower and KMS/egress are dependencies.
- Mitigation: fail closed/degraded AI, documented rotation/provider-exit runbook; core survey remains independent.
- Revisit only through security/privacy ADR; never relax to arbitrary URL or plaintext secret.


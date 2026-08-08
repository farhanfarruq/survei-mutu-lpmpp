# State Machines

Versi: **1.0 — 2026-08-07**

Transition hanya terjadi setelah permission, scope, guard, dan invariant object lulus. Audit event mencatat transition penting; UI label tidak menjadi source of truth.

## 1. Lifecycle survei: instrument version dan campaign

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> UnderReview: submit_review [preflight_pass]
    UnderReview --> Returned: reviewer_return
    Returned --> Draft: revise
    UnderReview --> Approved: reviewer_approve [hash_unchanged]
    Approved --> Scheduled: schedule_campaign [campaign_preflight_pass]
    Approved --> Open: publish_now [campaign_preflight_pass]
    Scheduled --> Open: open_time_reached
    Scheduled --> Cancelled: cancel_before_open
    Open --> Paused: emergency_pause
    Paused --> Open: resume [safe_to_resume]
    Open --> Closed: close_time_or_manual_close
    Open --> Cancelled: emergency_cancel
    Closed --> Archived: retention_archive
    Draft --> Retired: retire_unused_version
    Returned --> Retired: retire
    Approved --> Retired: retire_version_for_new_campaigns
    Cancelled --> Archived: archive
    Retired --> [*]
    Archived --> [*]
```

`Approved` version tetap immutable dan dapat menjadi provenance campaign lama walau `Retired`. Pause tidak menghapus response; rule grace/submit harus dipin.

## 2. Lifecycle response

```mermaid
stateDiagram-v2
    [*] --> NotStarted
    NotStarted --> Started: consent_and_create
    Started --> Partial: first_valid_answer
    Partial --> Partial: autosave_revision
    Started --> Withdrawn: stop_or_decline_after_start
    Partial --> Withdrawn: withdraw_before_submit
    Started --> Submitted: final_submit [complete_or_valid_NA]
    Partial --> Submitted: final_submit [complete_or_valid_NA]
    NotStarted --> Expired: campaign_close
    Started --> Expired: retention_or_close_rule
    Partial --> Expired: retention_or_close_rule
    Submitted --> Submitted: duplicate_submit_returns_same_receipt
    Submitted --> [*]
    Withdrawn --> [*]
    Expired --> [*]
```

`Submitted` immutable. Correction tidak mengembalikan response ke draft; jika policy mengizinkan koreksi, correction artifact/version baru harus dirancang terpisah.

## 3. Lifecycle AI job

```mermaid
stateDiagram-v2
    [*] --> Requested
    Requested --> Blocked: governance_or_threshold_fail
    Requested --> RedactionReview: PII_or_classification_flag
    RedactionReview --> Requested: payload_remediated
    RedactionReview --> Cancelled: reject_payload
    Requested --> Queued: all_gates_pass
    Queued --> Cancelled: feature_disabled_before_call
    Queued --> Running: worker_started
    Running --> RetryWaiting: timeout_429_or_5xx
    RetryWaiting --> Queued: backoff_elapsed [attempt_remaining]
    RetryWaiting --> Failed: attempts_exhausted
    Running --> Quarantined: malformed_or_unsafe_output
    Running --> AwaitingHumanReview: provider_success
    AwaitingHumanReview --> Reviewed: human_accept_or_correct
    AwaitingHumanReview --> Rejected: human_reject
    Blocked --> [*]
    Cancelled --> [*]
    Failed --> [*]
    Quarantined --> [*]
    Reviewed --> [*]
    Rejected --> [*]
```

Hanya `Reviewed` output boleh dipertimbangkan untuk report. `Blocked` terjadi sebelum data dikirim.

## 4. Lifecycle report export

```mermaid
stateDiagram-v2
    [*] --> Requested
    Requested --> Rejected: permission_scope_or_format_fail
    Requested --> Queued: request_valid
    Queued --> Running: worker_started
    Running --> RetryWaiting: transient_failure
    RetryWaiting --> Queued: retry [attempt_remaining]
    RetryWaiting --> Failed: attempts_exhausted
    Running --> Quarantined: suppression_or_parity_fail
    Running --> PendingApproval: file_generated_and_checked
    PendingApproval --> Rejected: reviewer_reject
    PendingApproval --> Available: reviewer_approve
    Available --> Downloaded: one_time_download
    Available --> Expired: expiry_reached
    Available --> Revoked: owner_or_incident_revoke
    Downloaded --> Expired: retention_window_end
    Rejected --> [*]
    Failed --> [*]
    Quarantined --> [*]
    Expired --> [*]
    Revoked --> [*]
```

File `Quarantined`, `Rejected`, `Failed`, `Expired`, atau `Revoked` tidak dapat diunduh. Download tidak mengubah classification atau onward-sharing restriction.

## 5. Lifecycle finding dan follow-up

```mermaid
stateDiagram-v2
    [*] --> FindingDraft
    FindingDraft --> Assigned: source_owner_due_complete
    Assigned --> ReassignmentNeeded: PIC_rejects
    ReassignmentNeeded --> Assigned: new_PIC_assigned
    Assigned --> Planned: PIC_accepts_and_plans
    Planned --> InProgress: plan_approved
    InProgress --> SubmittedForVerification: PIC_submits_evidence
    SubmittedForVerification --> NeedsRework: verifier_requests_rework
    NeedsRework --> InProgress: PIC_revises
    SubmittedForVerification --> Rejected: verifier_rejects
    Rejected --> InProgress: owner_reopens_with_new_plan
    SubmittedForVerification --> VerifiedAwaitingImpact: verifier_verifies
    VerifiedAwaitingImpact --> Effective: impact_effective
    VerifiedAwaitingImpact --> PartiallyEffective: impact_partial
    VerifiedAwaitingImpact --> Ineffective: impact_ineffective
    VerifiedAwaitingImpact --> Waived: approved_impact_waiver
    Effective --> Closed: closure_and_communication
    PartiallyEffective --> InProgress: corrective_action
    PartiallyEffective --> Closed: accepted_residual_decision
    Ineffective --> InProgress: replacement_action
    Ineffective --> Closed: stop_with_approved_reason
    Waived --> Closed: waiver_valid_and_communication
    Closed --> [*]
```

`Evidence uploaded` tidak mempunyai state sendiri yang berarti selesai. `VerifiedAwaitingImpact` membedakan implementation verification dari effectiveness.

## 6. State invariants

| Object/state | Invariant |
|---|---|
| Version UnderReview/Approved | content hash locked; creator tidak sole approver |
| Campaign Scheduled/Open | version approved dan snapshot lengkap |
| Response Submitted | immutable, exactly-once, receipt stabil |
| AI Queued/Running | governance gate dan secret reference valid pada dispatch; kill switch dapat membatalkan |
| Export Available | scope/suppression parity pass dan approval nonrequester bila diwajibkan |
| Follow-up SubmittedForVerification | evidence version/checksum dan acceptance target tersedia |
| Follow-up Closed | verified dan impact result atau approved waiver; communication-back tercatat |

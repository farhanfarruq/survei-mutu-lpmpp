<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Models\ActionEvidence;
use App\Models\ActionVerification;
use App\Models\Finding;
use App\Models\FollowUpAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class FollowUpWorkflow
{
    public function __construct(private readonly NotificationHub $notifications) {}

    public function createAction(Finding $finding, User $pic, User $verifier, array $data, User $actor): FollowUpAction
    {
        if ($pic->id === $verifier->id) {
            throw new DomainRuleViolation('separation_of_duties', 'PIC dan verifier harus berbeda.', 422);
        }
        $action = FollowUpAction::create($data + ['finding_id' => $finding->id, 'pic_user_id' => $pic->id, 'verifier_user_id' => $verifier->id]);
        $finding->update(['state' => 'in_progress', 'resource_version' => $finding->resource_version + 1]);
        activity('follow_up')->performedOn($action)->causedBy($actor)->withProperties(['finding_id' => $finding->id, 'pic_user_id' => $pic->id, 'verifier_user_id' => $verifier->id])->log('follow_up_action_created');

        return $action;
    }

    public function updateAction(FollowUpAction $action, User $actor, array $data): FollowUpAction
    {
        if ($action->pic_user_id !== $actor->id) {
            throw new DomainRuleViolation('forbidden', 'Hanya PIC yang ditugaskan dapat memperbarui action.', 403);
        }
        $requestedState = $data['state'] ?? $action->state;
        $allowed = match ($action->state) {
            'assigned' => ['accepted', 'rejected'],
            'accepted', 'in_progress', 'needs_revision' => ['in_progress'],
            default => [],
        };
        if ($requestedState !== $action->state && ! in_array($requestedState, $allowed, true)) {
            throw new DomainRuleViolation('invalid_transition', 'Transisi status action tidak diizinkan.', 409);
        }
        if ($requestedState === 'rejected' && blank($data['rejection_reason'] ?? null)) {
            throw new DomainRuleViolation('validation_failed', 'Alasan penolakan assignment wajib diisi.', 422);
        }
        $updates = collect($data)->only(['root_cause', 'plan', 'expected_output', 'resource_needs', 'progress', 'due_on'])->all();
        $updates['state'] = $requestedState;
        if (filled($data['rejection_reason'] ?? null)) {
            $updates['assignment_note'] = trim($data['rejection_reason']);
        }
        $updates['resource_version'] = $action->resource_version + 1;
        if ($requestedState === 'accepted' && ! $action->accepted_at) {
            $updates['accepted_at'] = now();
        }
        $action->update($updates);
        activity('follow_up')->performedOn($action)->causedBy($actor)->withProperties(['state' => $action->state, 'progress' => $action->progress, 'due_date_reason' => $data['due_date_reason'] ?? null])->log('follow_up_action_updated');

        return $action;
    }

    public function addEvidence(FollowUpAction $action, User $actor, array $data): ActionEvidence
    {
        if ($action->pic_user_id !== $actor->id || ! in_array($action->state, ['accepted', 'in_progress', 'needs_revision'], true)) {
            throw new DomainRuleViolation('forbidden', 'Evidence hanya dapat ditambahkan oleh PIC pada action aktif.', 403);
        }
        $version = (int) $action->evidence()->max('version') + 1;
        $evidence = ActionEvidence::create($data + ['follow_up_action_id' => $action->id, 'submitted_by' => $actor->id, 'version' => $version, 'checksum' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))]);
        if ($action->state === 'needs_revision') {
            $action->update(['state' => 'in_progress', 'resource_version' => $action->resource_version + 1]);
        }
        activity('follow_up')->performedOn($evidence)->causedBy($actor)->withProperties(['action_id' => $action->id, 'version' => $version, 'checksum' => $evidence->checksum])->log('action_evidence_added');

        return $evidence;
    }

    public function submit(FollowUpAction $action, User $actor): FollowUpAction
    {
        if ($action->pic_user_id !== $actor->id) {
            throw new DomainRuleViolation('forbidden', 'Hanya PIC yang dapat mengajukan verifikasi.', 403);
        }
        if (! in_array($action->state, ['accepted', 'in_progress'], true) || $action->progress !== 100 || ! $action->evidence()->exists()) {
            throw new DomainRuleViolation('verification_incomplete', 'Progress 100% dan minimal satu evidence diperlukan.', 422);
        }
        $action->update(['state' => 'pending_verification', 'submitted_at' => now(), 'resource_version' => $action->resource_version + 1]);
        $this->notifications->send($action->verifier, 'verification_result', 'Verifikasi tindak lanjut diminta', 'Satu action menunggu pemeriksaan evidence.', "/app/follow-ups/actions/{$action->id}", ['action_id' => $action->id, 'status' => 'pending_verification'], "submission:{$action->id}:{$action->resource_version}");
        activity('follow_up')->performedOn($action)->causedBy($actor)->withProperties(['state' => $action->state])->log('action_submitted_for_verification');

        return $action;
    }

    public function verify(FollowUpAction $action, User $actor, string $decision, string $reason, string $evidenceReview): FollowUpAction
    {
        if ($action->verifier_user_id !== $actor->id || $action->pic_user_id === $actor->id) {
            throw new DomainRuleViolation('separation_of_duties', 'Verifier harus sesuai assignment dan berbeda dari PIC.', 403);
        }
        if ($action->state !== 'pending_verification') {
            throw new DomainRuleViolation('invalid_transition', 'Action belum diajukan untuk verifikasi.', 409);
        }
        DB::transaction(function () use ($action, $actor, $decision, $reason, $evidenceReview): void {
            ActionVerification::create(['follow_up_action_id' => $action->id, 'verified_by' => $actor->id, 'decision' => $decision, 'reason' => $reason, 'evidence_review' => $evidenceReview, 'revision_number' => $action->revision_count]);
            $state = match ($decision) {
                'verified' => 'verified', 'needs_revision' => 'needs_revision', default => 'rejected'
            };
            $action->update(['state' => $state, 'revision_count' => $decision === 'needs_revision' ? $action->revision_count + 1 : $action->revision_count, 'verified_at' => $decision === 'verified' ? now() : null, 'resource_version' => $action->resource_version + 1]);
            if ($decision === 'verified' && $action->finding->actions()->where('state', '!=', 'verified')->doesntExist()) {
                $action->finding->update(['state' => 'verified', 'resource_version' => $action->finding->resource_version + 1]);
            }
        });
        $this->notifications->send($action->pic, 'verification_result', 'Hasil verifikasi tindak lanjut', 'Status verifikasi action telah diperbarui menjadi '.$action->state.'.', "/app/follow-ups/actions/{$action->id}", ['action_id' => $action->id, 'status' => $action->state], "decision:{$action->id}:{$action->resource_version}");
        activity('follow_up')->performedOn($action)->causedBy($actor)->withProperties(['decision' => $decision, 'revision_count' => $action->revision_count])->log('action_verification_decided');

        return $action;
    }
}

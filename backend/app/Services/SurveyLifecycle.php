<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Models\Survey;
use App\Models\User;

class SurveyLifecycle
{
    public function __construct(private readonly SurveyPreflight $preflight, private readonly NotificationScheduler $notifications) {}

    public function submitForReview(Survey $survey, User $actor): Survey
    {
        if (! $survey->state->configurationEditable()) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey draft/returned yang dapat dikirim untuk review.');
        }
        $this->assertPreflight($survey);
        $survey->forceFill(['state' => SurveyState::InReview, 'submitted_by' => $actor->id, 'submitted_at' => now(), 'review_note' => null])->save();
        $this->audit($survey, $actor, 'submitted_for_review');

        return $survey->refresh();
    }

    public function returnToDraft(Survey $survey, User $actor, string $note): Survey
    {
        if ($survey->state !== SurveyState::InReview) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey in-review yang dapat dikembalikan.');
        }
        if (trim($note) === '') {
            throw new DomainRuleViolation('review_note_required', 'Alasan pengembalian wajib diisi.');
        }
        $survey->forceFill(['state' => SurveyState::Returned, 'review_note' => trim($note)])->save();
        $this->audit($survey, $actor, 'returned');

        return $survey->refresh();
    }

    public function approve(Survey $survey, User $actor, ?string $note = null): Survey
    {
        if ($survey->state !== SurveyState::InReview) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey in-review yang dapat disetujui.');
        }
        if ($survey->created_by === $actor->id) {
            throw new DomainRuleViolation('self_approval_forbidden', 'Pembuat survey tidak boleh menjadi approver tunggal.');
        }
        $this->assertPreflight($survey);
        $survey->forceFill(['state' => SurveyState::Approved, 'approved_by' => $actor->id, 'approved_at' => now(), 'review_note' => $note ? trim($note) : null])->save();
        $this->audit($survey, $actor, 'approved');

        return $survey->refresh();
    }

    public function publish(Survey $survey, User $actor): Survey
    {
        if ($survey->state !== SurveyState::Approved) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey approved yang dapat dipublikasikan.');
        }
        $this->assertPreflight($survey);

        $survey->forceFill([
            'state' => $survey->opens_at->isFuture() ? SurveyState::Scheduled : SurveyState::Active,
            'published_at' => now(),
            'policy_snapshot' => [
                'instrument_version_id' => $survey->instrument_version_id,
                'instrument_content_hash' => $survey->instrumentVersion->content_hash,
                'privacy_mode' => $survey->privacy_mode,
                'reporting_threshold' => $survey->reporting_threshold,
                'timezone' => $survey->timezone,
            ],
            'population_snapshot_hash' => hash('sha256', $survey->targets->sortBy('id')->map(fn ($target) => implode('|', [$target->id, $target->respondent_group_id, $target->target_unit_id, $target->eligible_count, $target->frame_checksum]))->implode(';')),
        ])->save();
        $this->audit($survey, $actor, 'published');
        $this->notifications->availability($survey);

        return $survey->refresh();
    }

    public function activateScheduled(Survey $survey): Survey
    {
        if ($survey->state !== SurveyState::Scheduled || $survey->opens_at->isFuture()) {
            throw new DomainRuleViolation('survey_not_due', 'Survey belum dapat diaktifkan.');
        }
        $survey->forceFill(['state' => SurveyState::Active])->save();
        $this->notifications->availability($survey);

        return $survey->refresh();
    }

    public function closeDue(Survey $survey): Survey
    {
        if ($survey->state !== SurveyState::Active || $survey->closes_at->isFuture()) {
            throw new DomainRuleViolation('survey_not_due', 'Survey belum jatuh tempo untuk ditutup.');
        }

        $survey->forceFill(['state' => SurveyState::Closed, 'closed_at' => now()])->save();
        activity('survey')->performedOn($survey)->event('closed_automatically')->withProperties(['state' => $survey->state->value])->log('Survey closed automatically');
        $this->notifications->closing($survey);

        return $survey->refresh();
    }

    public function close(Survey $survey, User $actor): Survey
    {
        if (! in_array($survey->state, [SurveyState::Scheduled, SurveyState::Active], true)) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey scheduled/active yang dapat ditutup.');
        }
        $survey->forceFill(['state' => SurveyState::Closed, 'closed_at' => now()])->save();
        $this->audit($survey, $actor, 'closed');
        $this->notifications->closing($survey);

        return $survey->refresh();
    }

    public function archive(Survey $survey, User $actor): Survey
    {
        if ($survey->state !== SurveyState::Closed) {
            throw new DomainRuleViolation('invalid_survey_transition', 'Hanya survey closed yang dapat diarsipkan.');
        }
        $survey->forceFill(['state' => SurveyState::Archived, 'archived_at' => now()])->save();
        $this->audit($survey, $actor, 'archived');

        return $survey->refresh();
    }

    /** @return list<string> */
    public function preflight(Survey $survey): array
    {
        return $this->preflight->errors($survey);
    }

    private function assertPreflight(Survey $survey): void
    {
        $errors = $this->preflight->errors($survey);
        if ($errors !== []) {
            throw new DomainRuleViolation('survey_preflight_failed', implode(' ', $errors));
        }
    }

    private function audit(Survey $survey, User $actor, string $event): void
    {
        activity('survey')->performedOn($survey)->causedBy($actor)->event($event)->withProperties(['state' => $survey->state->value])->log("Survey {$event}");
    }
}

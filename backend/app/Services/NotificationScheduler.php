<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Models\FollowUpAction;
use App\Models\Survey;
use App\Models\SurveyParticipation;
use App\Models\User;

final class NotificationScheduler
{
    public function __construct(private readonly NotificationHub $hub, private readonly OrganizationalScope $scope) {}

    public function run(): void
    {
        $this->reminders();
        $this->closingSoon();
        $this->lowResponseRates();
        $this->followUpDeadlines();
    }

    public function availability(Survey $survey): void
    {
        if ($survey->state !== SurveyState::Active) {
            return;
        }
        $survey->participations()->with('user')->whereNotNull('user_id')->eachById(function (SurveyParticipation $participation) use ($survey): void {
            $this->hub->send($participation->user, 'survey_availability', 'Survei tersedia', "Survei {$survey->name} sudah dapat diisi.", "/app/surveys/{$survey->id}", ['survey_id' => $survey->id], "available:{$survey->id}");
        });
    }

    public function closing(Survey $survey): void
    {
        $survey->participations()->with('user')->whereNotNull('user_id')->whereNull('completed_at')->eachById(function (SurveyParticipation $participation) use ($survey): void {
            $this->hub->send($participation->user, 'survey_closing', 'Survei ditutup', "Periode pengisian {$survey->name} telah ditutup.", '/app/surveys', ['survey_id' => $survey->id], "closed:{$survey->id}");
        });
    }

    private function reminders(): void
    {
        SurveyParticipation::reminderEligible()->with(['survey', 'user'])->whereNotNull('user_id')->whereHas('survey', fn ($query) => $query->where('state', SurveyState::Active))->eachById(function (SurveyParticipation $participation): void {
            $survey = $participation->survey;
            $next = $participation->reminder_count + 1;
            $this->hub->send($participation->user, 'survey_reminder', 'Pengingat survei', "Survei {$survey->name} masih menunggu partisipasi Anda.", "/app/surveys/{$survey->id}", ['survey_id' => $survey->id, 'reminder_number' => $next], "reminder:{$participation->id}:{$next}");
            $participation->update(['reminder_count' => $next, 'last_reminded_at' => now()]);
        });
    }

    private function closingSoon(): void
    {
        Survey::query()->where('state', SurveyState::Active)->whereBetween('closes_at', [now(), now()->addDay()])->eachById(function (Survey $survey): void {
            $survey->participations()->with('user')->whereNotNull('user_id')->whereNull('completed_at')->eachById(function (SurveyParticipation $participation) use ($survey): void {
                $this->hub->send($participation->user, 'survey_closing', 'Survei segera ditutup', "Survei {$survey->name} akan segera berakhir.", "/app/surveys/{$survey->id}", ['survey_id' => $survey->id, 'closes_at' => $survey->closes_at->toIso8601String()], "closing-soon:{$survey->id}:".today()->toDateString());
            });
        });
    }

    private function lowResponseRates(): void
    {
        Survey::query()->where('state', SurveyState::Active)->where('closes_at', '<=', now()->addDays(2))->withSum('targets', 'eligible_count')->eachById(function (Survey $survey): void {
            $eligible = (int) $survey->targets_sum_eligible_count;
            if ($eligible === 0) {
                return;
            }
            $submitted = $survey->responses()->where('state', 'submitted')->count();
            $rate = round($submitted / $eligible * 100, 1);
            if ($rate >= 50) {
                return;
            }
            User::permission('analysis.read')->where('is_active', true)->get()->filter(fn (User $user) => $this->scope->allows($user, $survey->owner_unit_id))->each(function (User $user) use ($survey, $rate): void {
                $this->hub->send($user, 'low_response_rate', 'Response rate rendah', "Response rate {$survey->name} masih {$rate}% menjelang penutupan.", '/app/analytics', ['survey_id' => $survey->id, 'response_rate' => $rate], "low-rate:{$survey->id}:".today()->toDateString());
            });
        });
    }

    private function followUpDeadlines(): void
    {
        FollowUpAction::with('pic')->whereNotIn('state', ['verified', 'rejected'])->whereBetween('due_on', [today()->subDay(), today()->addDays(7)])->eachById(function (FollowUpAction $action): void {
            $days = today()->diffInDays($action->due_on, false);
            if (! in_array($days, [-1, 1, 7], true)) {
                return;
            }
            $label = $days === -1 ? 'melewati tenggat' : "jatuh tempo dalam {$days} hari";
            $this->hub->send($action->pic, 'follow_up_deadline', 'Tenggat tindak lanjut', "Action {$action->title} {$label}.", "/app/follow-ups/actions/{$action->id}", ['action_id' => $action->id, 'days_to_due' => $days], "deadline:{$action->id}:{$days}");
        });
    }
}

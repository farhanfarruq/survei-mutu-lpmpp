<?php

use App\Enums\SurveyState;
use App\Models\Survey;
use App\Services\NotificationScheduler;
use App\Services\SurveyLifecycle;
use Illuminate\Support\Facades\Schedule;

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::call(function (): void {
    $lifecycle = app(SurveyLifecycle::class);
    Survey::query()->where('state', SurveyState::Scheduled)->where('opens_at', '<=', now())->eachById(fn (Survey $survey) => $lifecycle->activateScheduled($survey));
    Survey::query()->where('state', SurveyState::Active)->where('closes_at', '<=', now())->eachById(fn (Survey $survey) => $lifecycle->closeDue($survey));
})->name('advance-survey-lifecycle')->everyMinute()->withoutOverlapping()->onOneServer();

Schedule::call(fn () => app(NotificationScheduler::class)->run())
    ->name('governed-notifications')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

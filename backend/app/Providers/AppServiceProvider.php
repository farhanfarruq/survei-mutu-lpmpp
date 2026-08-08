<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\Indicator;
use App\Models\InstrumentSection;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Question;
use App\Models\QuestionBankEntry;
use App\Models\QuestionOption;
use App\Models\RespondentGroup;
use App\Models\Scale;
use App\Models\ScalePoint;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTarget;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Observers\InstrumentContentObserver;
use App\Observers\InstrumentVersionObserver;
use App\Observers\SurveyMutationObserver;
use App\Observers\SurveyTargetObserver;
use App\Policies\InstrumentVersionPolicy;
use App\Policies\OrganizationalUnitPolicy;
use App\Policies\QuestionBankEntryPolicy;
use App\Policies\RespondentGroupPolicy;
use App\Policies\SurveyPeriodPolicy;
use App\Policies\SurveyPolicy;
use App\Policies\SurveyTemplatePolicy;
use App\Policies\UserPolicy;
use App\Services\HttpAiProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProvider::class, HttpAiProvider::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(OrganizationalUnit::class, OrganizationalUnitPolicy::class);
        Gate::policy(SurveyTemplate::class, SurveyTemplatePolicy::class);
        Gate::policy(InstrumentVersion::class, InstrumentVersionPolicy::class);
        Gate::policy(QuestionBankEntry::class, QuestionBankEntryPolicy::class);
        Gate::policy(RespondentGroup::class, RespondentGroupPolicy::class);
        Gate::policy(SurveyPeriod::class, SurveyPeriodPolicy::class);
        Gate::policy(Survey::class, SurveyPolicy::class);

        InstrumentVersion::observe(InstrumentVersionObserver::class);
        foreach ([InstrumentSection::class, Category::class, Indicator::class, Scale::class, ScalePoint::class, Question::class, QuestionOption::class] as $model) {
            $model::observe(InstrumentContentObserver::class);
        }
        Survey::observe(SurveyMutationObserver::class);
        SurveyTarget::observe(SurveyTargetObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            activity('authentication')->causedBy($event->user)->event('login')->log('User logged in');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                activity('authentication')->causedBy($event->user)->event('logout')->log('User logged out');
            }
        });
    }
}

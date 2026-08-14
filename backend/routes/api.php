<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AnalyticsReportingController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizationalUnitController;
use App\Http\Controllers\Api\ResponseCollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health/live', [HealthController::class, 'live'])->name('api.health.live');
    Route::get('/health/ready', [HealthController::class, 'ready'])->name('api.health.ready');

    Route::middleware('throttle:30,1')->group(function (): void {
        Route::post('/respondent-sessions', [ResponseCollectionController::class, 'exchange']);
        Route::get('/respondent-survey', [ResponseCollectionController::class, 'respondentSurvey']);
        Route::post('/responses', [ResponseCollectionController::class, 'createResponse']);
        Route::post('/respondent-sessions/decline', [ResponseCollectionController::class, 'decline']);
        Route::get('/responses/{response}', [ResponseCollectionController::class, 'show']);
        Route::patch('/responses/{response}', [ResponseCollectionController::class, 'update']);
        Route::post('/responses/{response}/submissions', [ResponseCollectionController::class, 'submit']);
    });

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function (): void {
        Route::get('/me', MeController::class)->name('api.me');
        Route::get('/organizational-units', [OrganizationalUnitController::class, 'index'])
            ->middleware('permission:organizational-units.view')
            ->name('api.organizational-units.index');
        Route::get('/organizational-units/{organizationalUnit}', [OrganizationalUnitController::class, 'show'])
            ->middleware(['permission:organizational-units.view', 'org.scope'])
            ->name('api.organizational-units.show');

        Route::middleware('role:respondent')->group(function (): void {
            Route::get('/surveys/eligible', [ResponseCollectionController::class, 'eligible']);
            Route::get('/surveys/{survey}/respondent-detail', [ResponseCollectionController::class, 'detail']);
            Route::post('/surveys/{survey}/respondent-session', [ResponseCollectionController::class, 'start']);
            Route::get('/response-history', [ResponseCollectionController::class, 'history']);
        });
        Route::post('/surveys/{survey}/invitations', [ResponseCollectionController::class, 'issueInvitation']);
        Route::get('/surveys/{survey}/collection-summary', [ResponseCollectionController::class, 'collectionSummary']);

        Route::post('/surveys/{survey}/analysis-runs', [AnalyticsReportingController::class, 'run'])->middleware('permission:analysis.execute');
        Route::get('/analysis-runs/{analysisRun}', [AnalyticsReportingController::class, 'showRun'])->middleware('permission:analysis.read');
        Route::post('/analysis-runs/{analysisRun}/releases', [AnalyticsReportingController::class, 'release'])->middleware('permission:analysis.release');
        Route::get('/aggregate-snapshots/{aggregateSnapshot}', [AnalyticsReportingController::class, 'snapshot'])->middleware('permission:analysis.read|report.read');
        Route::get('/leadership/results', [AnalyticsReportingController::class, 'leadership'])->middleware('permission:report.read');
        Route::post('/report-exports', [AnalyticsReportingController::class, 'createExport'])->middleware('permission:report.export');
        Route::get('/report-exports/{reportExport}', [AnalyticsReportingController::class, 'showExport'])->middleware('permission:report.export|report.approve');
        Route::post('/report-exports/{reportExport}/download-tickets', [AnalyticsReportingController::class, 'ticket'])->middleware('permission:report.export|report.approve');
        Route::get('/report-downloads/{token}', [AnalyticsReportingController::class, 'download'])->middleware('permission:report.export|report.approve');

        Route::get('/ai-provider-configs', [AiController::class, 'configs'])->middleware('permission:ai.config|ai.execute');
        Route::post('/ai-provider-configs', [AiController::class, 'saveConfig'])->middleware('permission:ai.config');
        Route::post('/ai-provider-configs/{aiProviderConfig}/connection-tests', [AiController::class, 'testConnection'])->middleware('permission:ai.config');
        Route::get('/ai-prompt-templates', [AiController::class, 'prompts'])->middleware('permission:ai.config|ai.execute');
        Route::post('/ai-prompt-templates', [AiController::class, 'savePrompt'])->middleware('permission:ai.config');
        Route::post('/analysis-runs/{analysisRun}/ai-jobs', [AiController::class, 'createJob'])->middleware('permission:ai.execute');
        Route::get('/ai-jobs/{aiJob}', [AiController::class, 'showJob'])->middleware('permission:ai.read');
        Route::get('/ai-results/{aiResult}', [AiController::class, 'showResult'])->middleware('permission:ai.read|ai.review');
        Route::post('/ai-results/{aiResult}/review-decisions', [AiController::class, 'review'])->middleware('permission:ai.review');

        Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:notification.read');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->middleware(['permission:notification.read', 'role:respondent']);

        Route::get('/findings', [FollowUpController::class, 'index'])->middleware('permission:finding.read');
        Route::get('/follow-up-assignees', [FollowUpController::class, 'assignees'])->middleware('permission:action.create');
        Route::post('/findings', [FollowUpController::class, 'create'])->middleware('permission:finding.create');
        Route::get('/findings/{finding}', [FollowUpController::class, 'show'])->middleware('permission:finding.read');
        Route::patch('/findings/{finding}', [FollowUpController::class, 'update'])->middleware('permission:finding.update');
        Route::post('/findings/{finding}/actions', [FollowUpController::class, 'createAction'])->middleware('permission:action.create');
        Route::get('/follow-up-actions/{followUpAction}', [FollowUpController::class, 'showAction'])->middleware('permission:action.read');
        Route::patch('/follow-up-actions/{followUpAction}', [FollowUpController::class, 'updateAction'])->middleware('permission:action.update');
        Route::post('/follow-up-actions/{followUpAction}/evidence', [FollowUpController::class, 'evidence'])->middleware('permission:action.update');
        Route::post('/follow-up-actions/{followUpAction}/verification-submissions', [FollowUpController::class, 'submit'])->middleware('permission:action.update');
        Route::post('/follow-up-actions/{followUpAction}/verification-decisions', [FollowUpController::class, 'verify'])->middleware('permission:action.verify');
        Route::get('/follow-up/dashboard', [FollowUpController::class, 'dashboard'])->middleware('permission:follow-up.dashboard.read');
    });
});

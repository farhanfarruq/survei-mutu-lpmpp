<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\RespondentSession;
use App\Models\Survey;
use App\Models\SurveyParticipation;
use App\Models\SurveyResponse;
use App\Services\OrganizationalScope;
use App\Services\ResponseCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResponseCollectionController extends Controller
{
    public function eligible(Request $request, ResponseCollection $collection): JsonResponse
    {
        return response()->json(['data' => $collection->eligibleSurveys($request->user())->map(
            fn (Survey $survey) => $this->surveyData($survey, $survey->participations->first())
        )->values()]);
    }

    public function detail(Request $request, Survey $survey, ResponseCollection $collection): JsonResponse
    {
        $eligible = $collection->eligibleSurveys($request->user())->firstWhere('id', $survey->id);
        if (! $eligible) {
            throw new DomainRuleViolation('not_found', 'Survey tidak tersedia dalam scope Anda.', 404);
        }

        return response()->json(['data' => $this->surveyData($eligible, $eligible->participations->first(), true)]);
    }

    public function start(Request $request, Survey $survey, ResponseCollection $collection): JsonResponse
    {
        $result = $collection->startAuthenticated($survey, $request->user());

        return response()->json(['data' => $this->sessionData($result)], 201);
    }

    public function exchange(Request $request, ResponseCollection $collection): JsonResponse
    {
        $validated = $request->validate(['invitation_token' => ['required', 'string', 'size:64']]);
        $result = $collection->exchangeInvitation($validated['invitation_token']);

        return response()->json(['data' => $this->sessionData($result)], 201);
    }

    public function respondentSurvey(Request $request, ResponseCollection $collection): JsonResponse
    {
        $session = $this->respondentSession($request, $collection);

        return response()->json(['data' => $this->surveyData($session->survey, null, true)]);
    }

    public function createResponse(Request $request, ResponseCollection $collection): JsonResponse
    {
        $validated = $request->validate([
            'consent' => ['accepted'],
            'completion_token' => ['required', 'string', 'size:64'],
        ]);
        $this->idempotencyKey($request);
        $response = $collection->provisionDraft($this->respondentSession($request, $collection), $validated['completion_token']);

        return $this->responseJson($response, $collection, 201);
    }

    public function decline(Request $request, ResponseCollection $collection): JsonResponse
    {
        $validated = $request->validate(['completion_token' => ['required', 'string', 'size:64']]);
        $collection->decline($validated['completion_token']);

        return response()->json(['data' => ['declined' => true]]);
    }

    public function show(Request $request, SurveyResponse $response, ResponseCollection $collection): JsonResponse
    {
        $owned = $collection->responseForSession($response->id, $this->respondentSession($request, $collection));

        return $this->responseJson($owned, $collection);
    }

    public function update(Request $request, SurveyResponse $response, ResponseCollection $collection): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'uuid'],
            'answers.*' => ['required', 'array:question_id,value'],
        ]);
        $owned = $collection->responseForSession($response->id, $this->respondentSession($request, $collection));
        $saved = $collection->autosave(
            $owned,
            $this->ifMatch($request),
            $this->idempotencyKey($request),
            $validated['answers'],
        );

        return $this->responseJson($saved, $collection);
    }

    public function submit(Request $request, SurveyResponse $response, ResponseCollection $collection): JsonResponse
    {
        $validated = $request->validate(['completion_token' => ['required', 'string', 'size:64']]);
        $owned = $collection->responseForSession($response->id, $this->respondentSession($request, $collection));
        $receipt = $collection->submit(
            $owned,
            $this->ifMatch($request),
            $this->idempotencyKey($request),
            $validated['completion_token'],
        );

        return response()->json(['data' => $receipt]);
    }

    public function history(Request $request, ResponseCollection $collection): JsonResponse
    {
        $history = $collection->history($request->user())->map(fn (SurveyParticipation $participation) => [
            'survey_id' => $participation->survey_id,
            'survey_code' => $participation->survey->code,
            'survey_name' => $participation->survey->name,
            'privacy_mode' => $participation->survey->privacy_mode,
            'status' => $participation->completed_at ? 'completed' : ($participation->declined_at ? 'declined' : ($participation->started_at ? 'in_progress' : 'eligible')),
            'completed_at' => $participation->completed_at?->toIso8601String(),
            'closes_at' => $participation->survey->closes_at->toIso8601String(),
        ]);

        return response()->json(['data' => $history->values()]);
    }

    public function issueInvitation(Request $request, Survey $survey, ResponseCollection $collection, OrganizationalScope $scope): JsonResponse
    {
        $this->assertCampaignAccess($request, $survey, $scope, 'campaign.update');
        $validated = $request->validate([
            'external_reference' => ['required', 'string', 'max:320'],
            'expires_at' => ['required', 'date', 'after:now'],
        ]);
        $expiresAt = Carbon::parse($validated['expires_at']);
        if ($expiresAt->isAfter($survey->closes_at)) {
            throw new DomainRuleViolation('validation_failed', 'Masa berlaku undangan tidak boleh melewati penutupan survey.', 422);
        }

        return response()->json(['data' => $collection->issueInvitation($survey, $validated['external_reference'], $expiresAt)], 201);
    }

    public function collectionSummary(Request $request, Survey $survey, ResponseCollection $collection, OrganizationalScope $scope): JsonResponse
    {
        $this->assertCampaignAccess($request, $survey, $scope, 'campaign.read');

        return response()->json(['data' => $collection->collectionSummary($survey)]);
    }

    /** @param array{session_token: string, completion_token: string, session: RespondentSession} $result */
    private function sessionData(array $result): array
    {
        return [
            'session_token' => $result['session_token'],
            'completion_token' => $result['completion_token'],
            'expires_at' => $result['session']->expires_at->toIso8601String(),
        ];
    }

    private function surveyData(Survey $survey, ?SurveyParticipation $participation = null, bool $withInstrument = false): array
    {
        $sections = $survey->instrumentVersion->sections;
        $data = [
            'id' => $survey->id,
            'code' => $survey->code,
            'name' => $survey->name,
            'privacy_mode' => $survey->privacy_mode,
            'privacy_notice' => $survey->privacy_notice,
            'closes_at' => $survey->closes_at->toIso8601String(),
            'question_count' => $sections->sum(fn ($section) => $section->questions->count()),
            'estimated_minutes' => max(1, (int) ceil($sections->sum(fn ($section) => $section->questions->count()) / 5)),
            'participation_status' => $participation?->completed_at ? 'completed' : ($participation?->started_at ? 'in_progress' : 'eligible'),
        ];

        if ($withInstrument) {
            $data['sections'] = $sections->map(fn ($section) => [
                'id' => $section->id,
                'code' => $section->code,
                'title' => $section->title,
                'description' => $section->description,
                'position' => $section->position,
                'questions' => $section->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'code' => $question->code,
                    'text' => $question->item_text,
                    'help_text' => $question->help_text,
                    'response_type' => $question->response_type,
                    'required' => $question->is_required,
                    'validation' => $question->validation_rules,
                    'options' => $question->response_type === 'scale'
                        ? $question->scale?->points->map(fn ($point) => ['value' => $point->code, 'label' => $point->label, 'is_na' => $point->is_na])->values()
                        : $question->options->map(fn ($option) => ['value' => $option->code, 'label' => $option->label, 'exclusive' => $option->is_exclusive])->values(),
                    'na_allowed' => (bool) $question->scale?->na_allowed,
                ])->values(),
            ])->values();
        }

        return $data;
    }

    private function responseJson(SurveyResponse $response, ResponseCollection $collection, int $status = 200): JsonResponse
    {
        $body = [
            'id' => $response->id,
            'state' => $response->state,
            'version' => $response->resource_version,
            'progress' => $response->progress,
            'survey' => $this->surveyData($response->survey, null, true),
            'answers' => $response->state === 'submitted' ? [] : $response->answers->map(fn ($answer) => [
                'question_id' => $answer->question_id,
                'value' => $answer->value,
            ])->values(),
            'receipt' => $response->state === 'submitted' ? [
                'receipt_code' => $response->receipt_code,
                'submitted_at' => $response->submitted_at?->toIso8601String(),
            ] : null,
        ];

        return response()->json(['data' => $body], $status, ['ETag' => $collection->etag($response->resource_version)]);
    }

    private function respondentSession(Request $request, ResponseCollection $collection): RespondentSession
    {
        $token = (string) $request->header('X-Respondent-Token');
        if ($token === '') {
            throw new DomainRuleViolation('respondent_session_required', 'Token sesi responden diperlukan.', 401);
        }

        return $collection->sessionForToken($token);
    }

    private function idempotencyKey(Request $request): string
    {
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '' || mb_strlen($key) > 120) {
            throw new DomainRuleViolation('precondition_required', 'Idempotency-Key yang valid diperlukan.', 428);
        }

        return $key;
    }

    private function ifMatch(Request $request): int
    {
        $value = (string) $request->header('If-Match');
        if (! preg_match('/^(?:W\/)?"(\d+)"$/', $value, $matches)) {
            throw new DomainRuleViolation('precondition_required', 'If-Match versi draf diperlukan.', 428);
        }

        return (int) $matches[1];
    }

    private function assertCampaignAccess(Request $request, Survey $survey, OrganizationalScope $scope, string $permission): void
    {
        if (! $request->user()->can($permission)) {
            throw new DomainRuleViolation('forbidden', 'Anda tidak memiliki izin untuk tindakan ini.', 403);
        }
        if (! $scope->allows($request->user(), $survey->owner_unit_id)) {
            throw new DomainRuleViolation('not_found', 'Survey tidak tersedia dalam scope Anda.', 404);
        }
    }
}

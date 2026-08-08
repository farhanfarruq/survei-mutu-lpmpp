<?php

namespace App\Services;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Models\ConfidentialResponseLink;
use App\Models\Question;
use App\Models\RespondentSession;
use App\Models\ResponseIdempotencyKey;
use App\Models\Survey;
use App\Models\SurveyParticipation;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResponseCollection
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    /** @return EloquentCollection<int, Survey> */
    public function eligibleSurveys(User $user): EloquentCollection
    {
        $unitIds = $this->scope->accessibleUnitIds($user);

        return Survey::query()
            ->where('state', SurveyState::Active)
            ->where('opens_at', '<=', now())
            ->where('closes_at', '>', now())
            ->whereHas('targets', fn ($query) => $query
                ->whereIn('target_unit_id', $unitIds)
                ->orWhereHas('respondentGroup', fn ($query) => $query->whereIn('organizational_unit_id', $unitIds)))
            ->with([
                'instrumentVersion.sections.questions.options',
                'instrumentVersion.sections.questions.scale.points',
                'participations' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('closes_at')
            ->get();
    }

    /** @return array{invitation_token: string, expires_at: string} */
    public function issueInvitation(Survey $survey, string $externalReference, mixed $expiresAt): array
    {
        if (! in_array($survey->state, [SurveyState::Scheduled, SurveyState::Active], true)) {
            throw new DomainRuleViolation('survey_not_published', 'Undangan hanya tersedia untuk survey yang sudah dipublikasikan.');
        }

        $referenceHash = hash_hmac('sha256', Str::lower(trim($externalReference)), (string) config('app.key'));
        $participation = SurveyParticipation::query()->firstOrNew([
            'survey_id' => $survey->id,
            'external_reference_hash' => $referenceHash,
        ]);

        if ($participation->completed_at || $participation->started_at) {
            throw new DomainRuleViolation('response_already_started', 'Partisipasi sudah dimulai atau diselesaikan.');
        }

        $token = Str::random(64);
        $participation->fill([
            'invitation_token_hash' => $this->hashToken($token),
            'invitation_expires_at' => $expiresAt,
            'invitation_revoked_at' => null,
        ])->save();

        return ['invitation_token' => $token, 'expires_at' => $participation->invitation_expires_at->toIso8601String()];
    }

    /** @return array{session_token: string, completion_token: string, session: RespondentSession} */
    public function exchangeInvitation(string $invitationToken): array
    {
        return DB::transaction(function () use ($invitationToken): array {
            $participation = SurveyParticipation::query()
                ->with('survey')
                ->where('invitation_token_hash', $this->hashToken($invitationToken))
                ->lockForUpdate()
                ->first();

            if (! $participation) {
                throw new DomainRuleViolation('invitation_invalid', 'Tautan undangan tidak valid.', 404);
            }
            if ($participation->invitation_revoked_at) {
                throw new DomainRuleViolation('resource_revoked', 'Undangan telah dicabut.', 410);
            }
            if (! $participation->invitation_expires_at || $participation->invitation_expires_at->isPast()) {
                throw new DomainRuleViolation('invitation_expired', 'Undangan telah kedaluwarsa.', 410);
            }

            return $this->startParticipation($participation);
        });
    }

    /** @return array{session_token: string, completion_token: string, session: RespondentSession} */
    public function startAuthenticated(Survey $survey, User $user): array
    {
        return DB::transaction(function () use ($survey, $user): array {
            $survey = Survey::query()->lockForUpdate()->findOrFail($survey->id);
            $this->assertSurveyOpen($survey);

            $eligible = $this->eligibleSurveys($user)->contains('id', $survey->id);
            if (! $eligible) {
                throw new DomainRuleViolation('not_found', 'Survey tidak tersedia dalam scope Anda.', 404);
            }

            $participation = SurveyParticipation::query()->firstOrCreate([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ]);
            $participation->setRelation('survey', $survey);

            return $this->startParticipation($participation);
        });
    }

    public function sessionForToken(string $token): RespondentSession
    {
        $session = RespondentSession::query()
            ->with('survey.instrumentVersion.sections.questions.options', 'survey.instrumentVersion.sections.questions.scale.points')
            ->where('token_hash', $this->hashToken($token))
            ->first();

        if (! $session) {
            throw new DomainRuleViolation('respondent_session_invalid', 'Sesi responden tidak tersedia.', 404);
        }
        if ($session->expires_at->isPast()) {
            throw new DomainRuleViolation('respondent_session_expired', 'Sesi responden telah berakhir.', 410);
        }

        $this->assertSurveyOpen($session->survey);

        return $session;
    }

    public function provisionDraft(RespondentSession $session, string $completionToken): SurveyResponse
    {
        return DB::transaction(function () use ($session, $completionToken): SurveyResponse {
            $session = RespondentSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($existing = SurveyResponse::query()->where('respondent_session_id', $session->id)->first()) {
                return $this->loadResponse($existing);
            }

            $participation = $this->participationForCompletionToken($completionToken, $session->survey_id);
            $survey = $session->survey()->firstOrFail();
            $response = SurveyResponse::query()->create([
                'survey_id' => $survey->id,
                'respondent_session_id' => $session->id,
                'consent_version' => hash('sha256', $survey->privacy_notice),
                'consented_at' => now(),
            ]);

            if ($survey->privacy_mode === 'confidential') {
                ConfidentialResponseLink::query()->create([
                    'survey_response_id' => $response->id,
                    'survey_participation_id' => $participation->id,
                ]);
            }

            return $this->loadResponse($response);
        });
    }

    public function decline(string $completionToken): void
    {
        $participation = SurveyParticipation::query()
            ->where('completion_token_hash', $this->hashToken($completionToken))
            ->firstOrFail();
        $participation->update(['declined_at' => now()]);
    }

    public function responseForSession(string $responseId, RespondentSession $session): SurveyResponse
    {
        $response = SurveyResponse::query()
            ->whereKey($responseId)
            ->where('respondent_session_id', $session->id)
            ->first();

        if (! $response) {
            throw new DomainRuleViolation('not_found', 'Respons tidak tersedia dalam sesi ini.', 404);
        }

        return $this->loadResponse($response);
    }

    /** @param array<int, array{question_id: string, value: mixed}> $deltas */
    public function autosave(SurveyResponse $response, int $expectedVersion, string $idempotencyKey, array $deltas): SurveyResponse
    {
        return DB::transaction(function () use ($response, $expectedVersion, $idempotencyKey, $deltas): SurveyResponse {
            $response = SurveyResponse::query()->lockForUpdate()->findOrFail($response->id);
            $response->load('survey.instrumentVersion.sections.questions.options', 'survey.instrumentVersion.sections.questions.scale.points');
            $keyHash = $this->hashToken($idempotencyKey);
            $fingerprint = hash('sha256', json_encode($deltas, JSON_THROW_ON_ERROR));
            $existingKey = $response->idempotencyKeys()->where('operation', 'autosave')->where('key_hash', $keyHash)->first();

            if ($existingKey) {
                $this->assertIdempotencyFingerprint($existingKey, $fingerprint);

                return $this->loadResponse($response);
            }
            if ($response->state === 'submitted') {
                throw new DomainRuleViolation('immutable_resource', 'Respons yang sudah dikirim tidak dapat diubah.');
            }
            if ($response->resource_version !== $expectedVersion) {
                throw new DomainRuleViolation('version_conflict', 'Draf berubah sejak versi terakhir.', 412, ['ETag' => $this->etag($response->resource_version)]);
            }

            $questions = $response->survey->instrumentVersion->sections
                ->flatMap->questions
                ->keyBy('id');

            foreach ($deltas as $index => $delta) {
                $question = $questions->get($delta['question_id']);
                if (! $question) {
                    throw ValidationException::withMessages(["answers.{$index}.question_id" => 'Pertanyaan tidak termasuk dalam survey ini.']);
                }
                $this->validateAnswer($question, $delta['value'], $index);

                if ($this->isBlank($delta['value'])) {
                    $response->answers()->where('question_id', $question->id)->delete();
                } else {
                    $response->answers()->updateOrCreate(['question_id' => $question->id], ['value' => $delta['value']]);
                }
            }

            $answerCount = $response->answers()->count();
            $questionCount = max(1, $questions->count());
            $response->update([
                'state' => $answerCount > 0 ? 'partial' : 'started',
                'progress' => (int) round(($answerCount / $questionCount) * 100),
                'resource_version' => $response->resource_version + 1,
            ]);
            $response->idempotencyKeys()->create([
                'operation' => 'autosave',
                'key_hash' => $keyHash,
                'request_fingerprint' => $fingerprint,
                'result_version' => $response->resource_version,
            ]);

            return $this->loadResponse($response);
        });
    }

    /** @return array{receipt_code: string, submitted_at: string, response_id: string} */
    public function submit(SurveyResponse $response, int $expectedVersion, string $idempotencyKey, string $completionToken): array
    {
        return DB::transaction(function () use ($response, $expectedVersion, $idempotencyKey, $completionToken): array {
            $response = SurveyResponse::query()->lockForUpdate()->findOrFail($response->id);
            $response->load('answers', 'survey.instrumentVersion.sections.questions');
            $keyHash = $this->hashToken($idempotencyKey);
            $fingerprint = hash('sha256', $completionToken);
            $existingKey = $response->idempotencyKeys()->where('operation', 'submit')->where('key_hash', $keyHash)->first();

            if ($existingKey) {
                $this->assertIdempotencyFingerprint($existingKey, $fingerprint);

                return $this->receipt($response);
            }
            if ($response->state === 'submitted') {
                throw new DomainRuleViolation('response_already_submitted', 'Respons sudah pernah dikirim.');
            }
            if ($response->resource_version !== $expectedVersion) {
                throw new DomainRuleViolation('version_conflict', 'Draf berubah sejak versi terakhir.', 412, ['ETag' => $this->etag($response->resource_version)]);
            }

            $this->assertRequiredAnswers($response);
            $participation = $this->participationForCompletionToken($completionToken, $response->survey_id);
            if ($participation->completed_at) {
                throw new DomainRuleViolation('response_already_submitted', 'Partisipasi sudah diselesaikan.');
            }

            $receiptCode = 'SM-'.now()->format('Ym').'-'.Str::upper(Str::random(10));
            $response->update([
                'state' => 'submitted',
                'progress' => 100,
                'resource_version' => $response->resource_version + 1,
                'submitted_at' => now(),
                'receipt_code' => $receiptCode,
            ]);
            $response->idempotencyKeys()->create([
                'operation' => 'submit',
                'key_hash' => $keyHash,
                'request_fingerprint' => $fingerprint,
                'result_version' => $response->resource_version,
                'receipt_code' => $receiptCode,
            ]);
            $participation->update(['completed_at' => now()]);
            $response->survey()->increment('responses_count');

            return $this->receipt($response);
        });
    }

    /** @return Collection<int, SurveyParticipation> */
    public function history(User $user): Collection
    {
        return SurveyParticipation::query()
            ->where('user_id', $user->id)
            ->with('survey:id,name,code,privacy_mode,closes_at')
            ->latest('updated_at')
            ->get();
    }

    /** @return array<string, int|bool> */
    public function collectionSummary(Survey $survey): array
    {
        $participations = $survey->participations();
        $completed = (clone $participations)->whereNotNull('completed_at')->count();

        return [
            'eligible_count' => (int) $survey->targets()->sum('eligible_count'),
            'invited_count' => (clone $participations)->whereNotNull('invitation_token_hash')->count(),
            'started_count' => (clone $participations)->whereNotNull('started_at')->count(),
            'completed_count' => $completed,
            'reminder_eligible_count' => (clone $participations)->reminderEligible()->count(),
            'reporting_threshold' => $survey->reporting_threshold,
            'reportable' => $completed >= $survey->reporting_threshold,
            'suppressed' => $completed < $survey->reporting_threshold,
        ];
    }

    private function startParticipation(SurveyParticipation $participation): array
    {
        $this->assertSurveyOpen($participation->survey);
        if ($participation->completed_at) {
            throw new DomainRuleViolation('response_already_submitted', 'Partisipasi sudah diselesaikan.');
        }
        if ($participation->declined_at) {
            throw new DomainRuleViolation('participation_declined', 'Partisipasi telah ditolak.');
        }
        if ($participation->started_at) {
            throw new DomainRuleViolation('response_already_started', 'Respons sudah dimulai pada sesi yang diizinkan.');
        }

        $sessionToken = Str::random(64);
        $completionToken = Str::random(64);
        $session = RespondentSession::query()->create([
            'survey_id' => $participation->survey_id,
            'token_hash' => $this->hashToken($sessionToken),
            'expires_at' => $participation->survey->closes_at,
        ]);
        $participation->update([
            'started_at' => now(),
            'completion_token_hash' => $this->hashToken($completionToken),
        ]);

        return ['session_token' => $sessionToken, 'completion_token' => $completionToken, 'session' => $session];
    }

    private function assertSurveyOpen(Survey $survey): void
    {
        if ($survey->state !== SurveyState::Active || $survey->opens_at->isFuture() || ! $survey->closes_at->isFuture()) {
            throw new DomainRuleViolation('survey_not_open', 'Survey tidak berada pada window pengisian.', 409);
        }
    }

    private function participationForCompletionToken(string $token, string $surveyId): SurveyParticipation
    {
        $participation = SurveyParticipation::query()
            ->where('completion_token_hash', $this->hashToken($token))
            ->where('survey_id', $surveyId)
            ->lockForUpdate()
            ->first();

        if (! $participation) {
            throw new DomainRuleViolation('not_found', 'Partisipasi tidak tersedia untuk sesi ini.', 404);
        }

        return $participation;
    }

    private function loadResponse(SurveyResponse $response): SurveyResponse
    {
        return $response->fresh(['answers', 'survey.instrumentVersion.sections.questions.options', 'survey.instrumentVersion.sections.questions.scale.points']);
    }

    private function validateAnswer(Question $question, mixed $value, int $index): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        $valid = match ($question->response_type) {
            'scale' => is_string($value) && ($question->scale?->points->contains(fn ($point) => in_array($value, [$point->id, $point->code], true)) || ($value === '__na__' && $question->scale?->na_allowed)),
            'single_choice' => is_string($value) && $question->options->contains(fn ($option) => in_array($value, [$option->id, $option->code], true)),
            'multiple_choice' => $this->validMultipleChoice($question, $value),
            'short_text' => is_string($value) && Str::length($value) <= 500,
            'long_text' => is_string($value) && Str::length($value) <= 5000,
            'number' => $this->validNumber($question, $value),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(["answers.{$index}.value" => 'Nilai jawaban tidak sesuai jenis pertanyaan.']);
        }
    }

    private function validMultipleChoice(Question $question, mixed $value): bool
    {
        if (! is_array($value) || $value === []) {
            return false;
        }

        $allowed = $question->options->flatMap(fn ($option) => [$option->id, $option->code]);
        if (collect($value)->duplicates()->isNotEmpty() || collect($value)->contains(fn ($item) => ! is_string($item) || ! $allowed->containsStrict($item))) {
            return false;
        }

        $exclusive = $question->options->first(fn ($option) => $option->is_exclusive && in_array($option->id, $value, true) || $option->is_exclusive && in_array($option->code, $value, true));

        return ! $exclusive || count($value) === 1;
    }

    private function validNumber(Question $question, mixed $value): bool
    {
        if (! is_int($value) && ! is_float($value)) {
            return false;
        }

        $rules = $question->validation_rules ?? [];

        return (! isset($rules['min']) || $value >= $rules['min'])
            && (! isset($rules['max']) || $value <= $rules['max']);
    }

    private function assertRequiredAnswers(SurveyResponse $response): void
    {
        $answers = $response->answers()->pluck('value', 'question_id');
        $missing = $response->survey->instrumentVersion->sections
            ->flatMap->questions
            ->where('is_required', true)
            ->filter(fn (Question $question) => ! $answers->has($question->id) || $this->isBlank($answers->get($question->id)));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages($missing->mapWithKeys(fn (Question $question) => [
                "answers.{$question->id}" => "{$question->code} wajib dijawab.",
            ])->all());
        }
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function assertIdempotencyFingerprint(ResponseIdempotencyKey $key, string $fingerprint): void
    {
        if (! hash_equals($key->request_fingerprint, $fingerprint)) {
            throw new DomainRuleViolation('idempotency_key_reused', 'Idempotency key telah digunakan untuk payload berbeda.');
        }
    }

    /** @return array{receipt_code: string, submitted_at: string, response_id: string} */
    private function receipt(SurveyResponse $response): array
    {
        return [
            'receipt_code' => (string) $response->receipt_code,
            'submitted_at' => $response->submitted_at->toIso8601String(),
            'response_id' => $response->id,
        ];
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function etag(int $version): string
    {
        return '"'.$version.'"';
    }
}

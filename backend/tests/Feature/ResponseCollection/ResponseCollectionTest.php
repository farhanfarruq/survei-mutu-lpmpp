<?php

namespace Tests\Feature\ResponseCollection;

use App\Enums\InstrumentStatus;
use App\Enums\SurveyState;
use App\Models\ConfidentialResponseLink;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Services\ResponseCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResponseCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_respondent_can_autosave_all_mvp_types_and_submit_exactly_once(): void
    {
        [$survey, $respondent, $questions] = $this->fixture();
        [$sessionToken, $completionToken] = $this->startAuthenticated($survey, $respondent);

        $created = $this->withHeaders($this->responseHeaders($sessionToken, 'create-1'))
            ->postJson('/api/v1/responses', ['consent' => true, 'completion_token' => $completionToken])
            ->assertCreated();
        $responseId = $created->json('data.id');

        $answers = [
            ['question_id' => $questions['scale']->id, 'value' => '4'],
            ['question_id' => $questions['single']->id, 'value' => 'yes'],
            ['question_id' => $questions['multiple']->id, 'value' => ['a', 'b']],
            ['question_id' => $questions['short']->id, 'value' => 'Ringkas'],
            ['question_id' => $questions['long']->id, 'value' => 'Penjelasan yang aman tanpa identitas langsung.'],
            ['question_id' => $questions['number']->id, 'value' => 8],
        ];
        $saved = $this->withHeaders($this->responseHeaders($sessionToken, 'save-1', 1))
            ->patchJson("/api/v1/responses/{$responseId}", ['answers' => $answers])
            ->assertOk()
            ->assertJsonPath('data.progress', 100)
            ->assertHeader('ETag', '"2"');
        $this->assertCount(6, $saved->json('data.answers'));

        $headers = $this->responseHeaders($sessionToken, 'submit-1', 2);
        $receipt = $this->withHeaders($headers)
            ->postJson("/api/v1/responses/{$responseId}/submissions", ['completion_token' => $completionToken])
            ->assertOk()
            ->json('data.receipt_code');

        $this->withHeaders($headers)
            ->postJson("/api/v1/responses/{$responseId}/submissions", ['completion_token' => $completionToken])
            ->assertOk()
            ->assertJsonPath('data.receipt_code', $receipt);

        $this->assertDatabaseCount('survey_responses', 1);
        $this->assertDatabaseCount('response_answers', 6);
        $this->assertSame(1, $survey->refresh()->responses_count);
        $this->assertDatabaseCount('confidential_response_links', 0);
        $this->assertFalse(Schema::hasColumn('respondent_sessions', 'user_id'));
        $this->assertFalse(Schema::hasColumn('respondent_sessions', 'survey_participation_id'));

        $this->actingAs($respondent)->getJson('/api/v1/response-history')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonMissingPath('data.0.answers')
            ->assertJsonMissingPath('data.0.receipt_code');

        $this->actingAs($respondent)->getJson('/api/v1/surveys/eligible')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($this->responseHeaders($sessionToken, 'submit-other', 3))
            ->postJson("/api/v1/responses/{$responseId}/submissions", ['completion_token' => $completionToken])
            ->assertConflict()
            ->assertJsonPath('code', 'response_already_submitted');
    }

    public function test_expired_survey_and_unauthorized_survey_are_rejected(): void
    {
        [$expired, $respondent] = $this->fixture(['closes_at' => now()->subMinute()]);
        $this->actingAs($respondent)
            ->postJson("/api/v1/surveys/{$expired->id}/respondent-session")
            ->assertConflict()
            ->assertJsonPath('code', 'survey_not_open');

        [$survey, $eligible] = $this->fixture();
        $outsider = User::factory()->create();
        $outsider->assignRole('respondent');
        $this->actingAs($outsider)
            ->postJson("/api/v1/surveys/{$survey->id}/respondent-session")
            ->assertNotFound();

        $this->actingAs($eligible)
            ->getJson('/api/v1/surveys/eligible')
            ->assertOk()
            ->assertJsonPath('data.0.id', $survey->id);
    }

    public function test_parent_unit_target_includes_respondent_in_descendant_program(): void
    {
        [$survey, $respondent] = $this->fixture();
        $program = $respondent->organizationalUnits()->firstOrFail();
        $institute = OrganizationalUnit::factory()->create();
        $faculty = OrganizationalUnit::factory()->create(['parent_id' => $institute->id]);
        $program->update(['parent_id' => $faculty->id]);
        $survey->targets()->update(['target_unit_id' => $institute->id]);

        $this->actingAs($respondent)
            ->getJson('/api/v1/surveys/eligible')
            ->assertOk()
            ->assertJsonPath('data.0.id', $survey->id);
    }

    public function test_expired_and_revoked_external_invitations_are_rejected(): void
    {
        [$survey] = $this->fixture();
        $service = app(ResponseCollection::class);
        $valid = $service->issueInvitation($survey, 'valid@example.test', now()->addHour());
        $session = $this->postJson('/api/v1/respondent-sessions', ['invitation_token' => $valid['invitation_token']])
            ->assertCreated()
            ->json('data.session_token');
        $this->withHeader('X-Respondent-Token', $session)
            ->getJson('/api/v1/respondent-survey')
            ->assertOk()
            ->assertJsonPath('data.id', $survey->id)
            ->assertJsonMissingPath('data.user_id');

        $expired = $service->issueInvitation($survey, 'expired@example.test', now()->addHour());
        $survey->participations()->where('invitation_token_hash', hash('sha256', $expired['invitation_token']))->update(['invitation_expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/respondent-sessions', ['invitation_token' => $expired['invitation_token']])
            ->assertGone()
            ->assertJsonPath('code', 'invitation_expired');

        $revoked = $service->issueInvitation($survey, 'revoked@example.test', now()->addHour());
        $survey->participations()->where('invitation_token_hash', hash('sha256', $revoked['invitation_token']))->update(['invitation_revoked_at' => now()]);

        $this->postJson('/api/v1/respondent-sessions', ['invitation_token' => $revoked['invitation_token']])
            ->assertGone()
            ->assertJsonPath('code', 'resource_revoked');
    }

    public function test_missing_required_answer_is_rejected_without_completion(): void
    {
        [$survey, $respondent] = $this->fixture();
        [$sessionToken, $completionToken] = $this->startAuthenticated($survey, $respondent);
        $responseId = $this->withHeaders($this->responseHeaders($sessionToken, 'create-required'))
            ->postJson('/api/v1/responses', ['consent' => true, 'completion_token' => $completionToken])
            ->json('data.id');

        $this->withHeaders($this->responseHeaders($sessionToken, 'submit-required', 1))
            ->postJson("/api/v1/responses/{$responseId}/submissions", ['completion_token' => $completionToken])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed');

        $this->assertDatabaseMissing('survey_responses', ['id' => $responseId, 'state' => 'submitted']);
        $this->assertNull($survey->participations()->where('user_id', $respondent->id)->firstOrFail()->completed_at);
    }

    public function test_autosave_conflict_preserves_the_winning_draft(): void
    {
        [$survey, $respondent, $questions] = $this->fixture();
        [$sessionToken, $completionToken] = $this->startAuthenticated($survey, $respondent);
        $responseId = $this->withHeaders($this->responseHeaders($sessionToken, 'create-conflict'))
            ->postJson('/api/v1/responses', ['consent' => true, 'completion_token' => $completionToken])
            ->json('data.id');

        $this->withHeaders($this->responseHeaders($sessionToken, 'save-winner', 1))
            ->patchJson("/api/v1/responses/{$responseId}", ['answers' => [['question_id' => $questions['scale']->id, 'value' => '4']]])
            ->assertOk();

        $this->withHeaders($this->responseHeaders($sessionToken, 'save-stale', 1))
            ->patchJson("/api/v1/responses/{$responseId}", ['answers' => [['question_id' => $questions['scale']->id, 'value' => '2']]])
            ->assertStatus(412)
            ->assertHeader('ETag', '"2"')
            ->assertJsonPath('code', 'version_conflict');

        $this->assertDatabaseHas('response_answers', ['survey_response_id' => $responseId, 'value' => json_encode('4')]);
    }

    public function test_confidential_link_reminder_eligibility_and_reporting_threshold_stay_separate_from_answers(): void
    {
        [$survey, $respondent, $questions] = $this->fixture(['privacy_mode' => 'confidential', 'reporting_threshold' => 2]);
        [$sessionToken, $completionToken] = $this->startAuthenticated($survey, $respondent);
        $responseId = $this->withHeaders($this->responseHeaders($sessionToken, 'create-confidential'))
            ->postJson('/api/v1/responses', ['consent' => true, 'completion_token' => $completionToken])
            ->assertCreated()
            ->json('data.id');

        $this->assertTrue(ConfidentialResponseLink::query()->where('survey_response_id', $responseId)->exists());

        $summary = app(ResponseCollection::class)->collectionSummary($survey);
        $this->assertSame(1, $summary['reminder_eligible_count']);
        $this->assertFalse($summary['reportable']);
        $this->assertTrue($summary['suppressed']);
        $this->assertArrayNotHasKey('answers', $summary);

        $this->withHeaders($this->responseHeaders($sessionToken, 'save-confidential', 1))
            ->patchJson("/api/v1/responses/{$responseId}", ['answers' => [['question_id' => $questions['scale']->id, 'value' => '4']]])
            ->assertOk();
    }

    public function test_one_response_rule_rejects_a_second_session(): void
    {
        [$survey, $respondent] = $this->fixture();
        $this->startAuthenticated($survey, $respondent);

        $this->actingAs($respondent)
            ->postJson("/api/v1/surveys/{$survey->id}/respondent-session")
            ->assertConflict()
            ->assertJsonPath('code', 'response_already_started');
    }

    public function test_response_preflight_allows_required_respondent_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-requested-with,x-xsrf-token,x-respondent-token,idempotency-key,if-match',
        ])->options('/api/v1/responses')->assertNoContent();

        $allowed = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));

        foreach (['x-respondent-token', 'idempotency-key', 'if-match'] as $header) {
            $this->assertStringContainsString($header, $allowed);
        }
    }

    public function test_leader_cannot_enter_or_start_the_respondent_flow(): void
    {
        [$survey] = $this->fixture();
        Role::findOrCreate('leader');
        $leader = User::factory()->create();
        $leader->assignRole('leader');

        $this->actingAs($leader)->getJson('/api/v1/surveys/eligible')->assertForbidden();
        $this->actingAs($leader)->postJson("/api/v1/surveys/{$survey->id}/respondent-session")->assertForbidden();
    }

    /** @return array{Survey, User, array<string, mixed>} */
    private function fixture(array $surveyAttributes = []): array
    {
        $unit = OrganizationalUnit::factory()->create();
        $creator = User::factory()->create();
        $respondent = User::factory()->create();
        Role::findOrCreate('respondent');
        $respondent->assignRole('respondent');
        $respondent->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $template = SurveyTemplate::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $creator->id]);
        $version = InstrumentVersion::factory()->create(['survey_template_id' => $template->id, 'created_by' => $creator->id]);
        $category = $version->categories()->create(['code' => 'CAT', 'name' => 'Kategori', 'position' => 1]);
        $indicator = $category->indicators()->create(['code' => 'IND', 'name' => 'Indikator', 'construct' => 'Konstruk', 'weight' => 1]);
        $scale = $version->scales()->create(['code' => 'LIKERT', 'name' => 'Likert', 'scale_type' => 'likert', 'min_value' => 1, 'max_value' => 5, 'na_allowed' => true]);
        foreach (range(1, 5) as $value) {
            $scale->points()->create(['code' => (string) $value, 'numeric_value' => $value, 'label' => "Pilihan {$value}", 'position' => $value]);
        }
        $section = $version->sections()->create(['code' => 'SEC', 'title' => 'Bagian utama', 'position' => 1]);
        $base = ['indicator_id' => $indicator->id, 'is_required' => false, 'measurement_purpose' => 'Pengujian respons.', 'method' => 'internal'];
        $questions = collect([
            'scale' => $section->questions()->create(array_merge($base, ['scale_id' => $scale->id, 'code' => 'Q-SCALE', 'item_text' => 'Nilai layanan.', 'response_type' => 'scale', 'is_required' => true, 'position' => 1])),
            'single' => $section->questions()->create($base + ['code' => 'Q-SINGLE', 'item_text' => 'Pilih satu.', 'response_type' => 'single_choice', 'position' => 2]),
            'multiple' => $section->questions()->create($base + ['code' => 'Q-MULTI', 'item_text' => 'Pilih beberapa.', 'response_type' => 'multiple_choice', 'position' => 3]),
            'short' => $section->questions()->create($base + ['code' => 'Q-SHORT', 'item_text' => 'Jawaban singkat.', 'response_type' => 'short_text', 'position' => 4]),
            'long' => $section->questions()->create($base + ['code' => 'Q-LONG', 'item_text' => 'Jawaban panjang.', 'response_type' => 'long_text', 'position' => 5]),
            'number' => $section->questions()->create($base + ['code' => 'Q-NUMBER', 'item_text' => 'Nilai angka.', 'response_type' => 'number', 'position' => 6, 'validation_rules' => ['min' => 1, 'max' => 10]]),
        ]);
        foreach (['single', 'multiple'] as $type) {
            foreach ([['yes', 'Ya'], ['a', 'Pilihan A'], ['b', 'Pilihan B']] as $position => [$code, $label]) {
                $questions[$type]->options()->create(['code' => $code, 'label' => $label, 'position' => $position + 1]);
            }
        }
        $version->update(['status' => InstrumentStatus::Approved, 'content_hash' => hash('sha256', 'response-fixture'), 'approved_at' => now()]);

        $desiredState = $surveyAttributes['state'] ?? SurveyState::Active;
        $survey = Survey::factory()->create(array_merge([
            'instrument_version_id' => $version->id,
            'survey_period_id' => SurveyPeriod::factory(),
            'owner_unit_id' => $unit->id,
            'created_by' => $creator->id,
            'action_owner_id' => $creator->id,
            'state' => SurveyState::Draft,
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addDay(),
        ], array_diff_key($surveyAttributes, ['state' => true])));
        $survey->targets()->create(['target_unit_id' => $unit->id, 'target_type' => 'organizational_unit', 'eligible_count' => 10]);
        $survey->update(['state' => $desiredState]);

        return [$survey->refresh(), $respondent, $questions->all()];
    }

    /** @return array{string, string} */
    private function startAuthenticated(Survey $survey, User $respondent): array
    {
        $data = $this->actingAs($respondent)
            ->postJson("/api/v1/surveys/{$survey->id}/respondent-session")
            ->assertCreated()
            ->json('data');

        return [$data['session_token'], $data['completion_token']];
    }

    /** @return array<string, string> */
    private function responseHeaders(string $sessionToken, string $idempotencyKey, ?int $version = null): array
    {
        return array_filter([
            'X-Respondent-Token' => $sessionToken,
            'Idempotency-Key' => $idempotencyKey,
            'If-Match' => $version === null ? null : '"'.$version.'"',
        ]);
    }
}

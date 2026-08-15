<?php

namespace Tests\Feature\AnalyticsReporting;

use App\Enums\InstrumentStatus;
use App\Enums\SurveyState;
use App\Models\AggregateSnapshot;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\ReportExport;
use App\Models\RespondentGroup;
use App\Models\RespondentSession;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyticsReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_is_deterministic_cached_released_and_visible_as_aggregate_only(): void
    {
        [$survey, $analyst, $reviewer, $leader] = $this->fixture(10);

        $first = $this->actingAs($analyst)->postJson("/api/v1/surveys/{$survey->id}/analysis-runs")
            ->assertAccepted()->assertJsonPath('data.state', 'completed')->assertJsonPath('meta.cached', false);
        $runId = $first->json('data.id');
        $snapshotId = $first->json('data.snapshot_id');
        $this->assertEquals(75.0, AggregateSnapshot::findOrFail($snapshotId)->metrics['overall']['normalized_score']);

        $this->actingAs($analyst)->postJson("/api/v1/surveys/{$survey->id}/analysis-runs")
            ->assertOk()->assertJsonPath('data.id', $runId)->assertJsonPath('meta.cached', true);
        $analyst->givePermissionTo('analysis.release');
        $this->actingAs($analyst)->postJson("/api/v1/analysis-runs/{$runId}/releases")
            ->assertConflict()->assertJsonPath('code', 'separation_of_duties');
        $this->actingAs($reviewer)->postJson("/api/v1/analysis-runs/{$runId}/releases")
            ->assertOk()->assertJsonPath('data.state', 'released');

        $dashboard = $this->actingAs($leader)->getJson('/api/v1/leadership/results?drilldown=item')
            ->assertOk()->assertJsonPath('data.summary.overall.normalized_score', 75)
            ->assertJsonPath('data.summary.survey_id', $survey->id)
            ->assertJsonPath('data.comparison.allowed', false)
            ->assertJsonPath('data.comparison.series.0.group', 'Mahasiswa aktif')
            ->assertJsonPath('data.drilldown.0.category_name', 'Layanan')
            ->assertJsonPath('data.drilldown.0.response_type', 'scale')
            ->assertJsonPath('data.drilldown.0.distribution.0.label', 'Pilihan 4');
        $this->assertStringNotContainsString('response_answers', $dashboard->getContent());
        $this->assertStringNotContainsString('respondent_session', $dashboard->getContent());
        $this->assertStringNotContainsString('receipt_code', $dashboard->getContent());
    }

    public function test_scope_and_small_sample_are_enforced(): void
    {
        [$survey, $analyst, $reviewer] = $this->fixture(9);
        $other = User::factory()->create();
        $other->assignRole('admin_lpmpp');
        $otherUnit = OrganizationalUnit::factory()->create();
        $other->organizationalUnits()->attach($otherUnit, ['scope_mode' => 'self', 'is_primary' => true]);

        $this->actingAs($other)->postJson("/api/v1/surveys/{$survey->id}/analysis-runs")
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $runId = $this->actingAs($analyst)->postJson("/api/v1/surveys/{$survey->id}/analysis-runs")
            ->assertAccepted()->json('data.id');
        $this->actingAs($reviewer)->postJson("/api/v1/analysis-runs/{$runId}/releases")
            ->assertConflict()->assertJsonPath('code', 'small_sample_suppressed');
    }

    public function test_export_is_queued_idempotent_expiring_audited_and_download_ticket_is_one_time(): void
    {
        Storage::fake('local');
        [$survey, $analyst, $reviewer] = $this->fixture(10);
        $runId = $this->actingAs($analyst)->postJson("/api/v1/surveys/{$survey->id}/analysis-runs")->json('data.id');
        $snapshotId = $this->actingAs($reviewer)->postJson("/api/v1/analysis-runs/{$runId}/releases")->json('data.id');
        $headers = ['Idempotency-Key' => 'golden-export-key-0001'];
        $body = ['aggregate_snapshot_id' => $snapshotId, 'format' => 'csv', 'filters' => ['period' => 'fixture']];

        $created = $this->actingAs($analyst)->withHeaders($headers)->postJson('/api/v1/report-exports', $body)
            ->assertAccepted()->assertJsonPath('data.state', 'completed')->assertJsonPath('meta.idempotent_replay', false);
        $exportId = $created->json('data.id');
        $this->actingAs($analyst)->withHeaders($headers)->postJson('/api/v1/report-exports', $body)
            ->assertOk()->assertJsonPath('data.id', $exportId)->assertJsonPath('meta.idempotent_replay', true);
        $token = $this->actingAs($analyst)->postJson("/api/v1/report-exports/{$exportId}/download-tickets")
            ->assertCreated()->json('data.download_token');
        $this->actingAs($analyst)->get("/api/v1/report-downloads/{$token}")->assertOk();
        $this->actingAs($analyst)->getJson("/api/v1/report-downloads/{$token}")->assertGone()->assertJsonPath('code', 'download_ticket_invalid');
        $this->assertDatabaseHas('activity_log', ['description' => 'report_export_downloaded']);
        ReportExport::findOrFail($exportId)->update(['expires_at' => now()->subSecond()]);
        $this->actingAs($analyst)->getJson("/api/v1/report-exports/{$exportId}")->assertOk()->assertJsonPath('data.state', 'expired');
    }

    /** @return array{Survey, User, User, User} */
    private function fixture(int $responseCount): array
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $creator = User::factory()->create();
        $analyst = User::factory()->create();
        $reviewer = User::factory()->create();
        $leader = User::factory()->create();
        $analyst->assignRole('admin_lpmpp');
        $reviewer->assignRole('admin_lpmpp');
        $leader->assignRole('leader');
        foreach ([$analyst, $reviewer, $leader] as $user) {
            $user->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        }

        $template = SurveyTemplate::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $creator->id]);
        $version = InstrumentVersion::factory()->create(['survey_template_id' => $template->id, 'created_by' => $creator->id]);
        $category = $version->categories()->create(['code' => 'CAT', 'name' => 'Layanan', 'position' => 1]);
        $indicator = $category->indicators()->create(['code' => 'IND', 'name' => 'Kecepatan', 'construct' => 'reflective', 'weight' => 1]);
        $scale = $version->scales()->create(['code' => 'LIKERT', 'name' => 'Likert 5', 'scale_type' => 'likert', 'min_value' => 1, 'max_value' => 5, 'missing_policy' => 'exclude_item']);
        foreach (range(1, 5) as $value) {
            $scale->points()->create(['code' => (string) $value, 'numeric_value' => $value, 'label' => "Pilihan {$value}", 'position' => $value]);
        }
        $section = $version->sections()->create(['code' => 'SEC', 'title' => 'Bagian', 'position' => 1]);
        $question = $section->questions()->create(['indicator_id' => $indicator->id, 'scale_id' => $scale->id, 'code' => 'Q1', 'item_text' => 'Layanan cepat.', 'response_type' => 'scale', 'is_required' => true, 'position' => 1, 'measurement_purpose' => 'Mengukur performa.', 'method' => 'SERVPERF']);
        $version->update(['status' => InstrumentStatus::Approved, 'content_hash' => hash('sha256', 'analytics-golden'), 'approved_at' => now()]);
        $survey = Survey::factory()->create(['instrument_version_id' => $version->id, 'survey_period_id' => SurveyPeriod::factory(), 'owner_unit_id' => $unit->id, 'created_by' => $creator->id, 'action_owner_id' => $creator->id, 'state' => SurveyState::Draft, 'reporting_threshold' => 10, 'responses_count' => 0]);
        $group = RespondentGroup::create(['organizational_unit_id' => $unit->id, 'code' => 'GROUP-ACTIVE', 'name' => 'Mahasiswa aktif', 'source_type' => 'manual', 'schema_version' => 'v1', 'is_active' => true]);
        $survey->targets()->create(['respondent_group_id' => $group->id, 'target_unit_id' => $unit->id, 'target_type' => 'respondent_group', 'eligible_count' => 20]);
        $survey->update(['state' => SurveyState::Closed, 'responses_count' => $responseCount]);
        foreach (range(1, $responseCount) as $index) {
            $session = RespondentSession::create(['survey_id' => $survey->id, 'token_hash' => hash('sha256', "session-{$index}"), 'expires_at' => now()->addDay()]);
            $response = SurveyResponse::create(['survey_id' => $survey->id, 'respondent_session_id' => $session->id, 'state' => 'submitted', 'resource_version' => 2, 'progress' => 100, 'consent_version' => hash('sha256', 'notice'), 'consented_at' => now(), 'submitted_at' => now(), 'receipt_code' => "R-{$survey->id}-{$index}"]);
            $response->answers()->create(['question_id' => $question->id, 'value' => '4']);
        }

        return [$survey->refresh(), $analyst, $reviewer, $leader];
    }
}

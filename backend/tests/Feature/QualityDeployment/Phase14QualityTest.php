<?php

namespace Tests\Feature\QualityDeployment;

use App\Jobs\SendGovernedNotification;
use App\Models\AggregateSnapshot;
use App\Models\AnalysisRun;
use App\Models\OrganizationalUnit;
use App\Models\Survey;
use App\Models\User;
use App\Services\LeadershipDashboard;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class Phase14QualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_api_is_rate_limited_without_leaking_internal_details(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($request = 1; $request <= 60; $request++) {
            $this->getJson('/api/v1/me')->assertOk();
        }

        $this->getJson('/api/v1/me')
            ->assertTooManyRequests()
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('code', 'rate_limited')
            ->assertJsonMissingPath('exception');
    }

    public function test_external_response_flow_has_a_stricter_rate_limit(): void
    {
        for ($request = 1; $request <= 30; $request++) {
            $this->getJson('/api/v1/respondent-survey')->assertUnauthorized();
        }

        $this->getJson('/api/v1/respondent-survey')
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'rate_limited');
    }

    public function test_failed_notification_channel_is_retryable_while_sent_channel_stays_idempotent(): void
    {
        $user = User::factory()->create();
        $job = new SendGovernedNotification($user->id, 'verification_result', 'Hasil verifikasi', 'Periksa hasil.', '/app/follow-up', [], hash('sha256', 'phase14-retry'));

        $this->mock(Dispatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendNow')->twice()->andReturnUsing(function (mixed $notifiables, mixed $notification, ?array $channels): void {
                if ($channels === ['mail']) {
                    throw new \RuntimeException('Synthetic mail transport failure');
                }
            });
        });

        try {
            $job->handle();
            $this->fail('A partial delivery failure must be retried by the queue.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('mail', $error->getMessage());
        }

        $this->assertDatabaseHas('notification_deliveries', ['user_id' => $user->id, 'channel' => 'database', 'state' => 'sent', 'attempt_count' => 1]);
        $this->assertDatabaseHas('notification_deliveries', ['user_id' => $user->id, 'channel' => 'mail', 'state' => 'failed', 'attempt_count' => 1, 'failure_code' => 'RuntimeException']);

        $this->mock(Dispatcher::class, fn (MockInterface $mock) => $mock->shouldReceive('sendNow')->once());
        $job->handle();

        $this->assertDatabaseHas('notification_deliveries', ['user_id' => $user->id, 'channel' => 'database', 'state' => 'sent', 'attempt_count' => 1]);
        $this->assertDatabaseHas('notification_deliveries', ['user_id' => $user->id, 'channel' => 'mail', 'state' => 'sent', 'attempt_count' => 2, 'failure_code' => null]);
        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 300], $job->backoff);
    }

    public function test_privacy_schema_keeps_identity_out_of_anonymous_content_sessions(): void
    {
        $this->assertFalse(Schema::hasColumn('respondent_sessions', 'user_id'));
        $this->assertFalse(Schema::hasColumn('respondent_sessions', 'survey_participation_id'));
        $this->assertTrue(Schema::hasColumns('confidential_response_links', ['survey_participation_id', 'survey_response_id']));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertTrue(config('cors.supports_credentials'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }

    public function test_leadership_dashboard_avoids_n_plus_one_for_one_hundred_snapshots(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $leader = User::factory()->create();
        $leader->assignRole('leader');
        $leader->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $survey = Survey::factory()->create(['owner_unit_id' => $unit->id]);

        foreach (range(1, 100) as $sequence) {
            $run = AnalysisRun::create([
                'survey_id' => $survey->id,
                'requested_by' => $leader->id,
                'state' => 'completed',
                'input_hash' => hash('sha256', "phase14-{$sequence}"),
                'formula_version' => 'methodology-v1',
            ]);
            AggregateSnapshot::create([
                'analysis_run_id' => $run->id,
                'survey_id' => $survey->id,
                'owner_unit_id' => $unit->id,
                'survey_period_id' => $survey->survey_period_id,
                'state' => 'released',
                'metrics' => ['response_rate' => 80, 'overall' => ['normalized_score' => 82], 'categories' => []],
                'filter_provenance' => ['fixture' => true],
                'limitations' => [],
                'response_count' => 30,
                'eligible_count' => 40,
                'reporting_threshold' => 10,
                'suppressed' => false,
                'checksum' => hash('sha256', "snapshot-{$sequence}"),
                'generated_at' => now()->subDays($sequence),
                'released_at' => now(),
                'released_by' => $leader->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $startedAt = hrtime(true);
        $result = app(LeadershipDashboard::class)->data($leader, []);
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertCount(100, $result['comparison']['series']);
        $this->assertLessThanOrEqual(10, count(DB::getQueryLog()), 'Dashboard relations must remain eagerly loaded.');
        $this->assertLessThan(1000, $elapsedMs, 'Synthetic 100-snapshot dashboard exceeded its local performance budget.');
    }
}

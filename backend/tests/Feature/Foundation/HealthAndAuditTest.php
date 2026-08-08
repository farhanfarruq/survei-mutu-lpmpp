<?php

namespace Tests\Feature\Foundation;

use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class HealthAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_and_ready_health_checks_are_available(): void
    {
        $this->getJson('/api/v1/health/live')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->getJson('/api/v1/health/ready')
            ->assertOk()
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonPath('data.checks.redis', 'ok');
    }

    public function test_organization_changes_are_audited_without_secret_fields(): void
    {
        $unit = OrganizationalUnit::factory()->create(['name' => 'Unit Lama']);
        $unit->update(['name' => 'Unit Baru']);

        $activity = Activity::query()->latest('id')->firstOrFail();

        $this->assertSame('updated', $activity->event);
        $this->assertSame((string) $unit->id, (string) $activity->subject_id);
        $this->assertArrayNotHasKey('password', $activity->properties->get('attributes', []));
    }
}

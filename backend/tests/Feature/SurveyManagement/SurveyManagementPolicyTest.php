<?php

namespace Tests\Feature\SurveyManagement;

use App\Enums\SurveyState;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyManagementPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_intersects_permission_with_organizational_scope(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $inside = OrganizationalUnit::factory()->create();
        $outside = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($inside, ['scope_mode' => 'self', 'is_primary' => true]);
        $templateInside = SurveyTemplate::factory()->create(['owner_unit_id' => $inside->id, 'created_by' => $admin->id]);
        $templateOutside = SurveyTemplate::factory()->create(['owner_unit_id' => $outside->id]);

        $this->assertTrue($admin->can('view', $templateInside));
        $this->assertTrue($admin->can('update', $templateInside));
        $this->assertFalse($admin->can('view', $templateOutside));
        $this->assertFalse($admin->can('update', $templateOutside));
    }

    public function test_survey_policy_disallows_configuration_edit_after_publish(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $survey = Survey::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $admin->id, 'state' => SurveyState::Active]);

        $this->assertTrue($admin->can('view', $survey));
        $this->assertFalse($admin->can('update', $survey));
        $this->assertFalse($admin->can('delete', $survey));
    }

    public function test_admin_can_open_scoped_survey_management_resources(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        $this->actingAs($admin)->get('/admin/survey-templates')->assertOk();
        $this->actingAs($admin)->get('/admin/instrument-versions')->assertOk();
        $this->actingAs($admin)->get('/admin/surveys')->assertOk();
    }

    public function test_admin_can_open_instrument_and_survey_preview_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $template = SurveyTemplate::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $admin->id]);
        $version = InstrumentVersion::factory()->create(['survey_template_id' => $template->id, 'created_by' => $admin->id]);
        $survey = Survey::factory()->create(['instrument_version_id' => $version->id, 'owner_unit_id' => $unit->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->get("/admin/instrument-versions/{$version->id}")->assertOk();
        $this->actingAs($admin)->get("/admin/surveys/{$survey->id}")->assertOk();
    }
}

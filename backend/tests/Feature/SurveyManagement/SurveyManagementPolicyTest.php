<?php

namespace Tests\Feature\SurveyManagement;

use App\Enums\SurveyState;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_admin_can_create_a_survey_without_entering_technical_codes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $template = SurveyTemplate::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $admin->id]);
        $version = InstrumentVersion::factory()->approved()->create(['survey_template_id' => $template->id, 'created_by' => $admin->id]);
        $period = SurveyPeriod::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateSurvey::class)
            ->fillForm([
                'instrument_version_id' => $version->id,
                'survey_period_id' => $period->id,
                'owner_unit_id' => $unit->id,
                'name' => 'Survei Kepuasan Mahasiswa',
                'opens_at' => now()->addDay(),
                'closes_at' => now()->addWeek(),
                'targets' => [[
                    'target_unit_id' => $unit->id,
                    'eligible_count' => 50,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $survey = Survey::query()->where('name', 'Survei Kepuasan Mahasiswa')->sole();

        $this->assertStringStartsWith('SURVEI-', $survey->code);
        $this->assertSame('anonymous', $survey->privacy_mode);
        $this->assertSame(10, $survey->reporting_threshold);
        $this->assertSame($admin->id, $survey->action_owner_id);
        $this->assertCount(1, $survey->targets);
        $this->assertSame('organizational_unit', $survey->targets->sole()->target_type);
        $this->assertSame($unit->id, $survey->targets->sole()->target_unit_id);
    }

    public function test_publish_confirmation_shows_target_and_jakarta_schedule_without_manual_code(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create(['name' => 'S1 Informatika']);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $survey = Survey::factory()->create([
            'owner_unit_id' => $unit->id,
            'created_by' => $admin->id,
            'action_owner_id' => $admin->id,
            'state' => SurveyState::Draft,
            'opens_at' => now('Asia/Jakarta')->setTime(8, 0)->utc(),
            'closes_at' => now('Asia/Jakarta')->addWeek()->setTime(17, 0)->utc(),
        ]);
        $survey->targets()->create(['target_type' => 'organizational_unit', 'target_unit_id' => $unit->id, 'eligible_count' => 50]);
        $survey->update(['state' => SurveyState::Approved]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(ViewSurvey::class, ['record' => $survey->id])
            ->assertActionExists('publish', fn (Action $action): bool => $action->getModalHeading() === 'Pastikan sasaran dan jadwal sudah benar'
                && str_contains((string) $action->getModalDescription(), 'S1 Informatika')
                && str_contains((string) $action->getModalDescription(), 'WIB'));

        Livewire::test(ViewSurvey::class, ['record' => $survey->id])
            ->callAction('duplicate', data: ['name' => 'Survei S1 Informatika Perbaikan'])
            ->assertHasNoActionErrors();

        $copy = Survey::query()->where('name', 'Survei S1 Informatika Perbaikan')->sole();
        $this->assertStringStartsWith('SURVEI-', $copy->code);
        $this->assertSame(SurveyState::Draft, $copy->state);
    }
}

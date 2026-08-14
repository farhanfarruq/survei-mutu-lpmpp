<?php

namespace Tests\Feature\SurveyManagement;

use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\InstrumentVersions\Pages\ViewInstrumentVersion;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateSurveyFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_draft_form_from_one_simple_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        $this->get('/admin/buat-formulir')
            ->assertOk()
            ->assertSee('Bangun formulir')
            ->assertSee('Pratinjau langsung')
            ->assertSee('Formulir tanpa judul');

        Livewire::test(CreateSurveyForm::class)
            ->fillForm([
                'owner_unit_id' => $unit->id,
                'title' => 'Survei Layanan Akademik',
                'description' => 'Berikan penilaian Anda.',
                'questions' => [
                    [
                        'item_text' => 'Bagaimana kualitas layanan kami?',
                        'response_type' => 'single_choice',
                        'is_required' => true,
                        'options' => [
                            ['label' => 'Baik'],
                            ['label' => 'Perlu diperbaiki'],
                        ],
                    ],
                    [
                        'item_text' => 'Apa saran Anda?',
                        'response_type' => 'long_text',
                        'is_required' => false,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $version = InstrumentVersion::query()->with('sections.questions.options')->sole();

        $this->assertSame('draft', $version->status->value);
        $this->assertSame('Survei Layanan Akademik', $version->template->name);
        $this->assertCount(2, $version->sections->first()->questions);
        $this->assertSame(
            ['Baik', 'Perlu diperbaiki'],
            $version->sections->first()->questions->first()->options->pluck('label')->all(),
        );
        $this->get("/admin/instrument-versions/{$version->id}")->assertOk();
        Livewire::test(ViewInstrumentVersion::class, ['record' => $version->id])
            ->set('activeRelationManager', '1')
            ->assertHasNoErrors();
    }

    public function test_form_page_rejects_users_without_create_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('respondent');

        $this->actingAs($user)->get('/admin/buat-formulir')->assertForbidden();
    }
}

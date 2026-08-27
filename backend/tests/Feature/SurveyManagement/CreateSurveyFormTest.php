<?php

namespace Tests\Feature\SurveyManagement;

use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\InstrumentVersions\Pages\ViewInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\RelationManagers\SectionsRelationManager;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Models\User;
use App\Services\InstrumentLifecycle;
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
                    [
                        'item_text' => 'Seberapa puas Anda?',
                        'response_type' => 'scale',
                        'is_required' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $version = InstrumentVersion::query()->with(['sections.questions.options', 'scales.points'])->sole();

        $this->assertSame('draft', $version->status->value);
        $this->assertSame('Survei Layanan Akademik', $version->template->name);
        $this->assertCount(3, $version->sections->first()->questions);
        $this->assertSame(
            ['Baik', 'Perlu diperbaiki'],
            $version->sections->first()->questions->first()->options->pluck('label')->all(),
        );
        $this->assertSame(
            ['Sangat tidak puas', 'Tidak puas', 'Cukup', 'Puas', 'Sangat puas'],
            $version->scales->sole()->points->pluck('label')->all(),
        );
        $this->assertSame($version->scales->sole()->id, $version->sections->first()->questions->last()->scale_id);
        $this->get("/admin/instrument-versions/{$version->id}")->assertOk();
        Livewire::test(ViewInstrumentVersion::class, ['record' => $version->id])
            ->assertSee('Isi formulir')
            ->assertDontSee('Buat Scale')
            ->assertHasNoErrors();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $version,
            'pageClass' => ViewInstrumentVersion::class,
        ])
            ->callTableAction('create', data: [
                'title' => 'Pengalaman Pengguna',
                'description' => 'Jawab sesuai pengalaman Anda.',
                'questions' => [[
                    'item_text' => 'Apakah layanan mudah digunakan?',
                    'response_type' => 'short_text',
                    'is_required' => true,
                ]],
            ])
            ->assertHasNoTableActionErrors();

        $addedSection = $version->sections()->where('title', 'Pengalaman Pengguna')->with('questions')->sole();
        $this->assertStringStartsWith('BAGIAN-', $addedSection->code);
        $this->assertSame('internal', $addedSection->questions->sole()->method);

        app(InstrumentLifecycle::class)->submitForReview($version, $admin);
        $this->assertSame('in_review', $version->fresh()->status->value);
    }

    public function test_form_page_rejects_users_without_create_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('respondent');

        $this->actingAs($user)->get('/admin/buat-formulir')->assertForbidden();
    }

    public function test_simple_form_creates_readable_categories_indicators_and_yes_no_options(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateSurveyForm::class)
            ->fillForm([
                'owner_unit_id' => $unit->id,
                'title' => 'Survei Kemudahan Layanan',
                'questions' => [
                    [
                        'item_text' => 'Apakah informasi mudah ditemukan?',
                        'response_type' => 'yes_no',
                        'category_name' => 'Informasi',
                        'indicator_name' => 'Kemudahan akses',
                        'is_required' => true,
                    ],
                    [
                        'item_text' => 'Seberapa puas Anda dengan waktu pelayanan?',
                        'response_type' => 'scale',
                        'category_name' => 'Pelayanan',
                        'indicator_name' => 'Kecepatan pelayanan',
                        'is_required' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $version = InstrumentVersion::query()
            ->with(['categories.indicators', 'sections.questions.options', 'sections.questions.indicator.category'])
            ->sole();

        $this->assertEqualsCanonicalizing(
            ['Informasi', 'Pelayanan'],
            $version->categories->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Kemudahan akses', 'Kecepatan pelayanan'],
            $version->categories->flatMap->indicators->pluck('name')->all(),
        );

        $yesNo = $version->sections->first()->questions->firstWhere('item_text', 'Apakah informasi mudah ditemukan?');
        $this->assertSame('single_choice', $yesNo->response_type);
        $this->assertSame(['Ya', 'Tidak'], $yesNo->options->pluck('label')->all());
        $this->assertSame('Informasi', $yesNo->indicator->category->name);
    }

    public function test_audience_preset_fills_editable_questions_without_technical_codes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateSurveyForm::class)
            ->set('data.audience_preset', 'alumni')
            ->assertSet('data.questions.0.item_text', 'Seberapa puas Anda terhadap layanan alumni?')
            ->assertSet('data.questions.1.response_type', 'yes_no')
            ->assertSet('data.questions.2.category_name', 'Saran')
            ->assertSee('Pertanyaan 1')
            ->assertSee('Pertanyaan 2')
            ->assertSee('Pertanyaan 3');
    }

    public function test_default_bank_questions_can_be_added_and_the_same_draft_can_be_edited(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        $entry = QuestionBankEntry::factory()->create([
            'owner_unit_id' => $unit->id,
            'created_by' => $admin->id,
            'item_text' => 'Apakah pelayanan mudah dipahami?',
            'response_type' => 'single_choice',
            'default_options' => [
                ['code' => 'YA', 'label' => 'Ya'],
                ['code' => 'TIDAK', 'label' => 'Tidak'],
            ],
            'is_default' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateSurveyForm::class)
            ->set('data.owner_unit_id', $unit->id)
            ->call('addDefaultBankQuestions')
            ->assertSet('data.questions.0.question_bank_entry_id', $entry->id)
            ->assertSet('data.questions.0.response_type', 'single_choice')
            ->set('data.title', 'Formulir dari Bank')
            ->call('create')
            ->assertHasNoFormErrors();

        $version = InstrumentVersion::query()->with('sections.questions')->sole();
        $savedQuestion = $version->sections->first()->questions->sole();
        $this->assertSame($entry->id, $savedQuestion->question_bank_entry_id);
        $this->assertSame('single_choice', $savedQuestion->response_type);

        Livewire::test(CreateSurveyForm::class, ['record' => $version->id])
            ->assertSet('data.title', 'Formulir dari Bank')
            ->assertSet('data.questions.0.response_type', 'single_choice')
            ->set('data.title', 'Formulir Diperbarui')
            ->set('data.questions.0.item_text', 'Apakah pelayanan sangat mudah dipahami?')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, InstrumentVersion::query()->count());
        $this->assertSame('Formulir Diperbarui', $version->fresh()->template->name);
        $this->assertSame('Apakah pelayanan sangat mudah dipahami?', $version->fresh()->sections()->first()->questions()->sole()->item_text);
    }
}

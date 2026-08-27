<?php

namespace Tests\Feature\AdminFlexibility;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Filament\Resources\QuestionBankEntries\Pages\CreateQuestionBankEntry;
use App\Filament\Resources\QuestionBankEntries\QuestionBankEntryResource;
use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Models\Survey;
use App\Models\User;
use App\Notifications\GovernedSystemNotification;
use App\Services\SurveyDuplication;
use App\Services\SurveyLifecycle;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase16Test extends TestCase
{
    use RefreshDatabase;

    public function test_published_survey_schedule_can_be_changed_safely_and_revision_keeps_source_intact(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $source = Survey::factory()->create([
            'state' => SurveyState::Active,
            'name' => 'Survei Aktif',
            'action_owner_id' => $admin->id,
            'created_by' => $admin->id,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
            'responses_count' => 3,
            'policy_snapshot' => ['instrument_content_hash' => 'source-hash'],
        ]);

        app(SurveyLifecycle::class)->reschedule($source, $admin, [
            'name' => 'Survei Aktif Diperpanjang',
            'closes_at' => now()->addWeeks(2),
            'action_owner_id' => $admin->id,
        ]);
        $this->assertSame('Survei Aktif Diperpanjang', $source->fresh()->name);
        $this->assertSame(3, $source->fresh()->responses_count);

        $revision = app(SurveyDuplication::class)->revise($source->fresh(), $admin, [
            'change_type' => 'content',
            'reason' => 'Memperbaiki pertanyaan berdasarkan evaluasi.',
            'name' => 'Revisi Survei Aktif',
            'opens_at' => now()->addMonth(),
            'closes_at' => now()->addMonths(2),
        ]);

        $this->assertSame(SurveyState::Draft, $revision->state);
        $this->assertNotSame($source->instrument_version_id, $revision->instrument_version_id);
        $this->assertSame('source-hash', $source->fresh()->policy_snapshot['instrument_content_hash']);
        $this->assertSame(3, $source->fresh()->responses_count);
        $this->assertSame('draft', $revision->instrumentVersion->status->value);
    }

    public function test_active_survey_rejects_a_past_closing_time(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $survey = Survey::factory()->create(['state' => SurveyState::Active, 'action_owner_id' => $admin->id]);

        $this->expectException(DomainRuleViolation::class);
        $this->expectExceptionMessage('masa depan');
        app(SurveyLifecycle::class)->reschedule($survey, $admin, [
            'name' => 'Survei',
            'closes_at' => now()->subMinute(),
            'action_owner_id' => $admin->id,
        ]);
    }

    public function test_activity_history_is_visible_to_staff_and_excludes_respondent_activity(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $leader = User::factory()->create(['name' => 'Pimpinan Penguji']);
        $leader->assignRole('leader');
        $admin = User::factory()->create(['name' => 'Admin Terlihat']);
        $admin->assignRole('admin_lpmpp');
        $respondent = User::factory()->create(['name' => 'Responden Tersembunyi']);
        $respondent->assignRole('respondent');
        activity('test')->causedBy($admin)->event('updated')->log('Admin updated data');
        activity('test')->causedBy($respondent)->event('updated')->log('Respondent updated data');

        $this->actingAs($leader)->get('/admin/activities')
            ->assertOk()
            ->assertSee('Admin Terlihat')
            ->assertDontSee('Responden Tersembunyi');

        $this->actingAs($respondent)->get('/admin/activities')->assertForbidden();
    }

    public function test_notification_read_all_only_updates_the_authenticated_users_notifications(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $other = User::factory()->create();
        $other->assignRole('leader');
        $admin->notify(new GovernedSystemNotification('report_completion', 'Laporan selesai', 'Laporan siap dibaca.', '/app/analytics', []));
        $other->notify(new GovernedSystemNotification('report_completion', 'Laporan lain', 'Laporan lain siap.', '/app/analytics', []));

        $this->assertSame('filament', $admin->notifications()->firstOrFail()->data['format']);
        $this->actingAs($admin)->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('meta.unread', 0);

        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_filament_notification_badge_caps_large_unread_counts(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $html = view('filament-panels::components.topbar.database-notifications-trigger', [
            'unreadNotificationsCount' => 100,
        ])->render();

        $this->assertStringContainsString('99+', $html);
    }

    public function test_simple_question_bank_form_generates_internal_fields_and_option_codes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($admin)
            ->get(QuestionBankEntryResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Tulis pertanyaan')
            ->assertSee('Pengaturan lanjutan');

        Livewire::test(CreateQuestionBankEntry::class)
            ->fillForm([
                'owner_unit_id' => $unit->id,
                'item_text' => 'Apakah informasi mudah dipahami?',
                'category_label' => '',
                'indicator_label' => '',
                'measurement_purpose' => '',
                'response_type' => 'single_choice',
                'default_options' => [
                    ['label' => 'Ya'],
                    ['label' => 'Tidak'],
                ],
                'is_active' => true,
                'is_default' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = QuestionBankEntry::query()->sole();
        $this->assertStringStartsWith('QB-', $entry->code);
        $this->assertSame($entry->code, $entry->family_code);
        $this->assertSame('internal', $entry->method);
        $this->assertSame('Umum', $entry->category_label);
        $this->assertSame('Jawaban responden', $entry->indicator_label);
        $this->assertSame(['O1', 'O2'], collect($entry->default_options)->pluck('code')->all());

    }
}

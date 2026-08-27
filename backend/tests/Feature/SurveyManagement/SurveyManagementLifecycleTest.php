<?php

namespace Tests\Feature\SurveyManagement;

use App\Enums\InstrumentStatus;
use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Services\InstrumentLifecycle;
use App\Services\InstrumentVersioning;
use App\Services\QuestionBank;
use App\Services\SurveyDuplication;
use App\Services\SurveyLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyManagementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_instrument_review_approval_lock_and_version_duplication(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $version = $this->completeInstrument($admin, $unit);
        $lifecycle = app(InstrumentLifecycle::class);

        $lifecycle->submitForReview($version, $admin);
        $this->assertSame(InstrumentStatus::InReview, $version->refresh()->status);
        $this->assertNotNull($version->content_hash);

        $this->expectException(DomainRuleViolation::class);
        $version->sections()->firstOrFail()->questions()->firstOrFail()->update(['item_text' => 'Perubahan tidak aman']);
    }

    public function test_approved_instrument_is_immutable_and_can_be_cloned_as_new_semantic_version(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $source = $this->completeInstrument($admin, $unit);
        $lifecycle = app(InstrumentLifecycle::class);
        $lifecycle->submitForReview($source, $admin);
        $lifecycle->approve($source->refresh(), $reviewer, 'Layak digunakan.');

        $copy = app(InstrumentVersioning::class)->duplicate($source->refresh(), $admin, 'minor', 'Menambah item bermakna.', 'partial');

        $this->assertSame(InstrumentStatus::Approved, $source->refresh()->status);
        $this->assertSame(InstrumentStatus::Draft, $copy->status);
        $this->assertSame('1.1.0', $copy->versionLabel());
        $this->assertCount(1, $copy->sections);
        $this->assertCount(1, $copy->sections->first()->questions);
        $this->assertCount(5, $copy->scales->first()->points);
    }

    public function test_creator_can_approve_own_instrument_and_survey(): void
    {
        [$admin, , $unit] = $this->actors();
        $version = $this->completeInstrument($admin, $unit);
        $instrumentLifecycle = app(InstrumentLifecycle::class);
        $instrumentLifecycle->submitForReview($version, $admin);

        $version = $instrumentLifecycle->approve($version->refresh(), $admin);

        $this->assertSame(InstrumentStatus::Approved, $version->status);
        $this->assertSame($admin->id, $version->approved_by);

        $survey = $this->completeSurvey($admin, $unit, $version, now()->addHour(), now()->addWeek());
        $surveyLifecycle = app(SurveyLifecycle::class);
        $surveyLifecycle->submitForReview($survey, $admin);

        $survey = $surveyLifecycle->approve($survey->refresh(), $admin);

        $this->assertSame(SurveyState::Approved, $survey->state);
        $this->assertSame($admin->id, $survey->approved_by);
    }

    public function test_survey_review_publish_close_archive_and_snapshot(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $version = $this->approvedInstrument($admin, $reviewer, $unit);
        $survey = $this->completeSurvey($admin, $unit, $version, now()->subHour(), now()->addWeek());
        $lifecycle = app(SurveyLifecycle::class);

        $lifecycle->submitForReview($survey, $admin);
        $lifecycle->approve($survey->refresh(), $reviewer);
        $lifecycle->publish($survey->refresh(), $admin);

        $this->assertSame(SurveyState::Active, $survey->refresh()->state);
        $this->assertSame($version->content_hash, $survey->policy_snapshot['instrument_content_hash']);
        $this->assertNotNull($survey->population_snapshot_hash);

        $lifecycle->close($survey, $admin);
        $lifecycle->archive($survey->refresh(), $admin);
        $this->assertSame(SurveyState::Archived, $survey->refresh()->state);
    }

    public function test_configuration_and_targets_are_locked_after_a_response_exists(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $version = $this->approvedInstrument($admin, $reviewer, $unit);
        $survey = $this->completeSurvey($admin, $unit, $version, now()->addDay(), now()->addWeek());
        $survey->update(['responses_count' => 1]);

        try {
            $survey->update(['closes_at' => now()->addMonth()]);
            $this->fail('Unsafe survey edit was not rejected.');
        } catch (DomainRuleViolation $exception) {
            $this->assertSame('survey_configuration_locked', $exception->ruleCode);
        }

        $this->expectException(DomainRuleViolation::class);
        $survey->targets()->create(['target_unit_id' => $unit->id, 'target_type' => 'organizational_unit', 'eligible_count' => 1]);
    }

    public function test_scheduled_survey_advances_and_closes_when_due(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $version = $this->approvedInstrument($admin, $reviewer, $unit);
        $survey = $this->completeSurvey($admin, $unit, $version, now()->addDay(), now()->addWeek());
        $lifecycle = app(SurveyLifecycle::class);
        $lifecycle->submitForReview($survey, $admin);
        $lifecycle->approve($survey->refresh(), $reviewer);
        $lifecycle->publish($survey->refresh(), $admin);
        $this->assertSame(SurveyState::Scheduled, $survey->refresh()->state);

        $this->travel(2)->days();
        $lifecycle->activateScheduled($survey->refresh());
        $this->assertSame(SurveyState::Active, $survey->refresh()->state);

        $this->travel(6)->days();
        $lifecycle->closeDue($survey->refresh());
        $this->assertSame(SurveyState::Closed, $survey->refresh()->state);
    }

    public function test_survey_duplication_resets_lifecycle_and_response_state(): void
    {
        [$admin, $reviewer, $unit] = $this->actors();
        $version = $this->approvedInstrument($admin, $reviewer, $unit);
        $survey = $this->completeSurvey($admin, $unit, $version, now()->addDay(), now()->addWeek());
        $survey->update(['responses_count' => 4]);

        $copy = app(SurveyDuplication::class)->duplicate($survey, $admin, 'SRV-COPY', 'Survey Salinan');

        $this->assertSame(SurveyState::Draft, $copy->state);
        $this->assertSame(0, $copy->responses_count);
        $this->assertNull($copy->policy_snapshot);
        $this->assertCount(1, $copy->targets);
    }

    public function test_question_bank_entry_can_be_copied_into_a_draft_version(): void
    {
        [$admin, , $unit] = $this->actors();
        $version = $this->completeInstrument($admin, $unit);
        $entry = QuestionBankEntry::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $admin->id, 'default_options' => null]);
        $question = app(QuestionBank::class)->addToSection($entry, $version->sections->first(), $version->categories->first()->indicators->first(), $version->scales->first(), 'BANK-01', 2);

        $this->assertSame($entry->item_text, $question->item_text);
        $this->assertSame($entry->id, $question->question_bank_entry_id);

        $entry->update(['item_text' => 'Pertanyaan bank diperbarui']);
        $this->assertNotSame($entry->item_text, $question->fresh()->item_text);
    }

    public function test_inactive_question_bank_entry_cannot_be_copied(): void
    {
        [$admin, , $unit] = $this->actors();
        $version = $this->completeInstrument($admin, $unit);
        $entry = QuestionBankEntry::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $admin->id, 'is_active' => false]);

        $this->expectException(DomainRuleViolation::class);
        $this->expectExceptionMessage('nonaktif');
        app(QuestionBank::class)->addToSection($entry, $version->sections->first(), $version->categories->first()->indicators->first(), $version->scales->first(), 'BANK-OFF', 2);
    }

    /** @return array{User, User, OrganizationalUnit} */
    private function actors(): array
    {
        $unit = OrganizationalUnit::factory()->create();
        $admin = User::factory()->create();
        $reviewer = User::factory()->create();
        $admin->organizationalUnits()->attach($unit, ['scope_mode' => 'subtree', 'is_primary' => true]);
        $reviewer->organizationalUnits()->attach($unit, ['scope_mode' => 'subtree', 'is_primary' => true]);

        return [$admin, $reviewer, $unit];
    }

    private function completeInstrument(User $creator, OrganizationalUnit $unit): InstrumentVersion
    {
        $template = SurveyTemplate::factory()->create(['owner_unit_id' => $unit->id, 'created_by' => $creator->id]);
        $version = InstrumentVersion::factory()->create(['survey_template_id' => $template->id, 'created_by' => $creator->id]);
        $category = $version->categories()->create(['code' => 'CAT', 'name' => 'Kategori', 'position' => 1]);
        $indicator = $category->indicators()->create(['code' => 'IND', 'name' => 'Indikator', 'construct' => 'Konstruk', 'weight' => 1]);
        $scale = $version->scales()->create(['code' => 'LIKERT', 'name' => 'Likert 5', 'scale_type' => 'likert', 'min_value' => 1, 'max_value' => 5, 'missing_policy' => 'exclude_item']);
        foreach (range(1, 5) as $value) {
            $scale->points()->create(['code' => (string) $value, 'numeric_value' => $value, 'label' => "Pilihan {$value}", 'position' => $value]);
        }
        $section = $version->sections()->create(['code' => 'SEC', 'title' => 'Bagian', 'position' => 1]);
        $section->questions()->create(['indicator_id' => $indicator->id, 'scale_id' => $scale->id, 'code' => 'Q1', 'item_text' => 'Layanan diberikan dengan jelas.', 'response_type' => 'scale', 'is_required' => true, 'position' => 1, 'measurement_purpose' => 'Mengukur kejelasan.', 'method' => 'SERVPERF']);

        return $version->refresh();
    }

    private function approvedInstrument(User $creator, User $reviewer, OrganizationalUnit $unit): InstrumentVersion
    {
        $version = $this->completeInstrument($creator, $unit);
        $service = app(InstrumentLifecycle::class);
        $service->submitForReview($version, $creator);

        return $service->approve($version->refresh(), $reviewer);
    }

    private function completeSurvey(User $creator, OrganizationalUnit $unit, InstrumentVersion $version, mixed $opensAt, mixed $closesAt): Survey
    {
        $period = SurveyPeriod::factory()->create();
        $survey = Survey::factory()->create(['instrument_version_id' => $version->id, 'survey_period_id' => $period->id, 'owner_unit_id' => $unit->id, 'action_owner_id' => $creator->id, 'created_by' => $creator->id, 'opens_at' => $opensAt, 'closes_at' => $closesAt]);
        $survey->targets()->create(['target_unit_id' => $unit->id, 'target_type' => 'organizational_unit', 'eligible_count' => 10]);

        return $survey->refresh();
    }
}

<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Models\RespondentGroup;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Services\InstrumentLifecycle;
use Illuminate\Database\Seeder;

class SurveyManagementSeeder extends Seeder
{
    public function run(): void
    {
        if (SurveyTemplate::query()->where('code', 'TPL-LAYANAN-AKADEMIK-DEMO')->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin.lpmpp@example.test')->firstOrFail();
        $reviewer = User::query()->where('email', 'reviewer@example.test')->firstOrFail();
        $lpmpp = OrganizationalUnit::query()->where('code', 'LPMPP-DEMO')->firstOrFail();
        $faculty = OrganizationalUnit::query()->where('code', 'FT-DEMO')->firstOrFail();

        $template = SurveyTemplate::create([
            'owner_unit_id' => $lpmpp->id, 'code' => 'TPL-LAYANAN-AKADEMIK-DEMO', 'family_code' => 'LAYANAN_AKADEMIK',
            'name' => 'Template Layanan Akademik Fiktif', 'status' => 'active',
            'purpose' => 'Fixture untuk memvalidasi pengelolaan instrumen tanpa data mahasiswa asli.', 'created_by' => $admin->id,
        ]);
        $version = $template->versions()->create([
            'major' => 1, 'minor' => 0, 'patch' => 0, 'status' => 'draft', 'comparability_status' => 'not_comparable',
            'change_reason' => 'Versi fixture awal.', 'created_by' => $admin->id,
        ]);
        $category = $version->categories()->create(['code' => 'LAYANAN', 'name' => 'Layanan Akademik', 'description' => 'Fixture.', 'position' => 1]);
        $indicator = $category->indicators()->create(['code' => 'KEJELASAN', 'name' => 'Kejelasan Informasi', 'construct' => 'Kejelasan layanan', 'weight' => 1]);
        $scale = $version->scales()->create(['code' => 'LIKERT-5', 'name' => 'Persetujuan 1–5', 'scale_type' => 'likert', 'min_value' => 1, 'max_value' => 5, 'na_allowed' => false, 'missing_policy' => 'exclude_item']);
        foreach ([1 => 'Sangat tidak setuju', 'Tidak setuju', 'Netral', 'Setuju', 'Sangat setuju'] as $value => $label) {
            $scale->points()->create(['code' => (string) $value, 'numeric_value' => $value, 'label' => $label, 'position' => $value, 'is_neutral' => $value === 3]);
        }
        $section = $version->sections()->create(['code' => 'AKADEMIK', 'title' => 'Layanan Akademik', 'description' => 'Bagian fixture.', 'position' => 1]);
        $section->questions()->create([
            'indicator_id' => $indicator->id, 'scale_id' => $scale->id, 'code' => 'AKD-01',
            'item_text' => 'Informasi layanan akademik disampaikan dengan jelas.', 'response_type' => 'scale',
            'is_required' => true, 'position' => 1, 'measurement_purpose' => 'Mengukur persepsi kejelasan informasi.', 'method' => 'SERVPERF',
        ]);
        app(InstrumentLifecycle::class)->submitForReview($version, $admin);
        app(InstrumentLifecycle::class)->approve($version->refresh(), $reviewer, 'Fixture memenuhi struktur minimum.');

        QuestionBankEntry::create([
            'owner_unit_id' => $lpmpp->id, 'code' => 'QB-AKD-KEJELASAN-01', 'family_code' => 'LAYANAN_AKADEMIK',
            'method' => 'SERVPERF', 'category_label' => 'Layanan Akademik', 'indicator_label' => 'Kejelasan Informasi',
            'item_text' => 'Informasi layanan akademik mudah dipahami.', 'response_type' => 'scale',
            'measurement_purpose' => 'Mengukur kejelasan informasi.', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $period = SurveyPeriod::create(['code' => '2026-GANJIL-DEMO', 'name' => 'Semester Ganjil 2026/2027 (Fiktif)', 'starts_on' => '2026-08-01', 'ends_on' => '2027-01-31', 'timezone' => 'Asia/Jakarta', 'status' => 'active']);
        $group = RespondentGroup::create(['organizational_unit_id' => $faculty->id, 'code' => 'RESPONDEN-FT-DEMO', 'name' => 'Responden Fakultas Teknik Fiktif', 'source_type' => 'manual', 'schema_version' => 'v1', 'is_active' => true]);
        $survey = Survey::create([
            'instrument_version_id' => $version->id, 'survey_period_id' => $period->id, 'owner_unit_id' => $lpmpp->id,
            'code' => 'SRV-AKD-2026-DEMO', 'name' => 'Survei Layanan Akademik 2026 (Fiktif)', 'state' => 'draft',
            'privacy_mode' => 'anonymous', 'opens_at' => '2026-09-01 00:00:00+07', 'closes_at' => '2026-09-30 23:59:59+07',
            'timezone' => 'Asia/Jakarta', 'privacy_notice' => 'Fixture tanpa data mahasiswa asli.', 'reporting_threshold' => 10,
            'action_owner_id' => $admin->id, 'created_by' => $admin->id,
        ]);
        $survey->targets()->create(['respondent_group_id' => $group->id, 'target_type' => 'respondent_group', 'eligible_count' => 0]);
    }
}

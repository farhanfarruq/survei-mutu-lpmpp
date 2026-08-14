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
        if (SurveyTemplate::query()->where('code', 'TPL-MUTU-LAYANAN-ITDA')->exists()) {
            return;
        }

        $admin = User::role('admin_lpmpp')->firstOrFail();
        $approver = User::role('super_admin')->firstOrFail();
        $lpmpp = OrganizationalUnit::query()->where('code', 'LPMPP')->firstOrFail();
        $faculty = OrganizationalUnit::query()->where('code', 'FTK')->firstOrFail();

        $template = SurveyTemplate::create([
            'owner_unit_id' => $lpmpp->id, 'code' => 'TPL-MUTU-LAYANAN-ITDA', 'family_code' => 'MUTU_LAYANAN_ITDA',
            'name' => 'Instrumen Survei Mutu Layanan ITDA', 'status' => 'active',
            'purpose' => 'Mengukur persepsi sivitas akademika terhadap mutu layanan Institut Teknologi Dirgantara Adisutjipto.', 'created_by' => $admin->id,
        ]);
        $version = $template->versions()->create([
            'major' => 1, 'minor' => 0, 'patch' => 0, 'status' => 'draft', 'comparability_status' => 'not_comparable',
            'change_reason' => 'Versi awal instrumen mutu layanan ITDA.', 'created_by' => $admin->id,
        ]);
        $scale = $version->scales()->create(['code' => 'LIKERT-5', 'name' => 'Persetujuan 1–5', 'scale_type' => 'likert', 'min_value' => 1, 'max_value' => 5, 'na_allowed' => false, 'missing_policy' => 'exclude_item']);
        foreach ([1 => 'Sangat tidak setuju', 'Tidak setuju', 'Netral', 'Setuju', 'Sangat setuju'] as $value => $label) {
            $scale->points()->create(['code' => (string) $value, 'numeric_value' => $value, 'label' => $label, 'position' => $value, 'is_neutral' => $value === 3]);
        }

        $section = $version->sections()->create([
            'code' => 'MUTU-LAYANAN',
            'title' => 'Mutu Layanan Kampus',
            'description' => 'Berikan penilaian sesuai pengalaman Anda menggunakan layanan ITDA.',
            'position' => 1,
        ]);
        $items = [
            ['code' => 'KEANDALAN', 'name' => 'Keandalan Layanan', 'question' => 'Layanan kampus diberikan secara konsisten sesuai prosedur yang berlaku.'],
            ['code' => 'KECEPATAN', 'name' => 'Kecepatan Respons', 'question' => 'Petugas merespons kebutuhan layanan saya dengan cepat.'],
            ['code' => 'KEJELASAN', 'name' => 'Kejelasan Informasi', 'question' => 'Informasi layanan akademik disampaikan dengan jelas dan mudah dipahami.'],
            ['code' => 'DIGITAL', 'name' => 'Sarana Digital', 'question' => 'Sistem dan layanan digital ITDA mudah digunakan saat dibutuhkan.'],
        ];

        foreach ($items as $position => $item) {
            $category = $version->categories()->create([
                'code' => $item['code'],
                'name' => $item['name'],
                'description' => 'Aspek '.$item['name'].' di lingkungan ITDA.',
                'position' => $position + 1,
            ]);
            $indicator = $category->indicators()->create([
                'code' => $item['code'].'-01',
                'name' => $item['name'],
                'construct' => $item['name'],
                'weight' => 1,
            ]);
            $section->questions()->create([
                'indicator_id' => $indicator->id,
                'scale_id' => $scale->id,
                'code' => $item['code'].'-01',
                'item_text' => $item['question'],
                'response_type' => 'scale',
                'is_required' => true,
                'position' => $position + 1,
                'measurement_purpose' => 'Mengukur '.$item['name'].' di lingkungan ITDA.',
                'method' => 'SERVPERF',
            ]);
            QuestionBankEntry::create([
                'owner_unit_id' => $lpmpp->id,
                'code' => 'QB-'.$item['code'].'-01',
                'family_code' => 'MUTU_LAYANAN_ITDA',
                'method' => 'SERVPERF',
                'category_label' => $item['name'],
                'indicator_label' => $item['name'],
                'item_text' => $item['question'],
                'response_type' => 'scale',
                'measurement_purpose' => 'Mengukur '.$item['name'].' di lingkungan ITDA.',
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
        }

        app(InstrumentLifecycle::class)->submitForReview($version, $admin);
        app(InstrumentLifecycle::class)->approve($version->refresh(), $approver, 'Instrumen memenuhi struktur minimum untuk data simulasi ITDA.');

        $period = SurveyPeriod::create(['code' => '2026-GANJIL', 'name' => 'Semester Ganjil 2026/2027', 'starts_on' => '2026-08-01', 'ends_on' => '2027-01-31', 'timezone' => 'Asia/Jakarta', 'status' => 'active']);
        $group = RespondentGroup::create(['organizational_unit_id' => $faculty->id, 'code' => 'RESPONDEN-FTK', 'name' => 'Responden Fakultas Teknologi Kedirgantaraan', 'source_type' => 'manual', 'schema_version' => 'v1', 'is_active' => true]);
        $survey = Survey::create([
            'instrument_version_id' => $version->id, 'survey_period_id' => $period->id, 'owner_unit_id' => $lpmpp->id,
            'code' => 'SRV-MUTU-ITDA-2026-GANJIL', 'name' => 'Survei Mutu Layanan ITDA Semester Ganjil 2026/2027', 'state' => 'draft',
            'privacy_mode' => 'anonymous', 'opens_at' => '2026-09-01 00:00:00+07', 'closes_at' => '2026-09-30 23:59:59+07',
            'timezone' => 'Asia/Jakarta', 'privacy_notice' => 'Data ini merupakan simulasi. Jawaban disimpan anonim dan hanya ditampilkan dalam bentuk agregat.', 'reporting_threshold' => 10,
            'action_owner_id' => $admin->id, 'created_by' => $admin->id,
        ]);
        $survey->targets()->create(['respondent_group_id' => $group->id, 'target_type' => 'respondent_group', 'eligible_count' => 0]);
    }
}

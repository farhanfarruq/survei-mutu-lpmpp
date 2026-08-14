<?php

namespace Database\Seeders;

use App\Models\AggregateSnapshot;
use App\Models\AnalysisRun;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DashboardAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Data analitik fiktif tidak boleh dibuat di production.');
        }

        $admin = User::role('admin_lpmpp')->firstOrFail();
        $approver = User::role('super_admin')->firstOrFail();
        $series = [
            'SRV-MUTU-ITDA-2024-GANJIL' => [72.8, [74.0, 68.0, 76.0, 73.2]],
            'SRV-MUTU-ITDA-2025-GENAP' => [75.6, [78.0, 71.4, 79.0, 74.0]],
            'SRV-MUTU-ITDA-2025-GANJIL' => [78.9, [81.0, 75.2, 82.4, 77.0]],
            'SRV-MUTU-ITDA-2026-GENAP' => [82.4, [85.0, 78.0, 86.0, 80.6]],
        ];
        $categories = [
            ['code' => 'KEANDALAN', 'name' => 'Keandalan Layanan'],
            ['code' => 'KECEPATAN', 'name' => 'Kecepatan Respons'],
            ['code' => 'KEJELASAN', 'name' => 'Kejelasan Informasi'],
            ['code' => 'DIGITAL', 'name' => 'Sarana Digital'],
        ];

        foreach ($series as $surveyCode => [$score, $categoryScores]) {
            $survey = Survey::query()->with(['targets', 'period'])->where('code', $surveyCode)->firstOrFail();
            $eligible = (int) $survey->targets->sum('eligible_count');
            $responseCount = (int) $survey->responses_count;
            $generatedAt = $survey->closes_at->addDay()->setTime(9, 0);
            $metricsCategories = collect($categories)->map(function (array $category, int $index) use ($categoryScores, $responseCount): array {
                $value = $categoryScores[$index];

                return $category + ['n' => $responseCount, 'normalized_score' => $value, 'interpretation' => $this->interpretation($value), 'suppressed' => false];
            })->all();
            $metrics = [
                'methodology_version' => 'deterministic-v1',
                'response_rate' => ['submitted' => $responseCount, 'eligible' => $eligible, 'percentage' => round(100 * $responseCount / $eligible, 1)],
                'overall' => ['n' => $responseCount, 'normalized_score' => $score, 'interpretation' => $this->interpretation($score), 'suppressed' => false],
                'categories' => $metricsCategories,
                'indicators' => $metricsCategories,
                'items' => collect($metricsCategories)->map(fn (array $category) => ['code' => $category['code'].'-01', 'text' => $category['name'], 'n' => $responseCount, 'normalized_score' => $category['normalized_score'], 'interpretation' => $category['interpretation'], 'suppressed' => false])->all(),
                'comparison_eligible' => true,
            ];
            $inputHash = hash('sha256', $surveyCode.'-dashboard-aggregate-v1');
            $run = AnalysisRun::query()->updateOrCreate(
                ['survey_id' => $survey->id, 'input_hash' => $inputHash],
                ['requested_by' => $admin->id, 'state' => 'completed', 'formula_version' => 'deterministic-v1', 'parameters' => ['response_state' => 'submitted'], 'started_at' => $generatedAt->subMinutes(2), 'completed_at' => $generatedAt],
            );
            AggregateSnapshot::query()->updateOrCreate(
                ['analysis_run_id' => $run->id],
                [
                    'survey_id' => $survey->id,
                    'owner_unit_id' => $survey->owner_unit_id,
                    'survey_period_id' => $survey->survey_period_id,
                    'respondent_group_id' => $survey->targets->first()?->respondent_group_id,
                    'state' => 'released',
                    'metrics' => $metrics,
                    'filter_provenance' => ['survey_id' => $survey->id, 'response_state' => 'submitted', 'aggregate_only' => true],
                    'limitations' => ['Hasil hanya tersedia dalam bentuk agregat; jawaban individual tidak ditampilkan.'],
                    'response_count' => $responseCount,
                    'eligible_count' => $eligible,
                    'reporting_threshold' => $survey->reporting_threshold,
                    'suppressed' => false,
                    'checksum' => hash('sha256', json_encode($metrics, JSON_THROW_ON_ERROR)),
                    'generated_at' => $generatedAt,
                    'released_at' => $generatedAt->addHour(),
                    'released_by' => $approver->id,
                ],
            );
        }
    }

    private function interpretation(float $score): string
    {
        return $score >= 80 ? 'Sangat Baik' : ($score >= 65 ? 'Baik' : 'Perlu Perbaikan');
    }
}

<?php

namespace Tests\Unit;

use App\Services\DeterministicStatistics;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AnalyticsGoldenDatasetTest extends TestCase
{
    private DeterministicStatistics $statistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statistics = new DeterministicStatistics;
    }

    #[Test]
    public function fictitious_likert_dataset_matches_known_results(): void
    {
        $result = $this->statistics->describe([1, 2, 3, 4, 5], 1, 5, [1, 2, 3, 4, 5]);

        $this->assertSame(5, $result['n']);
        $this->assertSame(3.0, $result['median']);
        $this->assertSame(3.0, $result['mean']);
        $this->assertSame(1.58, $result['sd']);
        $this->assertSame(40.0, $result['top_two_box']);
        $this->assertSame(50.0, $result['normalized_score']);
        $this->assertSame('medium', $result['interpretation']);
        $this->assertSame(20.0, $result['distribution'][0]['percentage']);
    }

    #[Test]
    public function reliability_and_method_specific_golden_results_are_exact(): void
    {
        $rows = [];
        for ($index = 0; $index < 30; $index++) {
            $value = ($index % 5) + 1;
            $rows[] = [$value, $value, $value];
        }

        $alpha = $this->statistics->cronbachAlpha($rows);
        $this->assertSame('calculated', $alpha['status']);
        $this->assertSame(1.0, $alpha['alpha']);
        $this->assertSame('possible_redundancy', $alpha['interpretation']);
        $this->assertSame(['status' => 'calculated', 'n' => 3, 'mean_gap' => 0.0, 'normalized_gap' => 0.0], $this->statistics->servqual([-1, 0, 1], 1, 5));
        $this->assertSame(70.0, $this->statistics->csi([4, 2], [80, 50]));
        $this->assertSame(['status' => 'calculated', 'score' => 75.0, 'grade' => 'C', 'interpretation' => 'less_good'], $this->statistics->ikm(array_fill(0, 9, 3)));
        $this->assertSame('concentrate_here', $this->statistics->ipa(4.2, 3.8));
    }

    #[Test]
    public function reliability_is_not_calculated_below_prerequisite_sample(): void
    {
        $result = $this->statistics->cronbachAlpha(array_fill(0, 29, [1, 2, 3]));
        $this->assertSame('prerequisites_not_met', $result['status']);
        $this->assertNull($result['alpha']);
    }
}

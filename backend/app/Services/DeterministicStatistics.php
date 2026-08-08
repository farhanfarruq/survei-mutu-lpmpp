<?php

namespace App\Services;

final class DeterministicStatistics
{
    public const VERSION = 'methodology-v1';

    public function describe(array $values, float $lower, float $upper, array $orderedValues = []): array
    {
        $values = array_values(array_map('floatval', $values));
        sort($values, SORT_NUMERIC);
        $n = count($values);
        if ($n === 0) {
            return ['n' => 0, 'distribution' => [], 'median' => null, 'mode' => [], 'mean' => null, 'sd' => null, 'top_two_box' => null, 'normalized_score' => null, 'interpretation' => null];
        }

        $counts = [];
        foreach ($values as $value) {
            $key = $this->numberKey($value);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        ksort($counts, SORT_NUMERIC);
        $distribution = [];
        foreach ($counts as $value => $count) {
            $distribution[] = ['value' => (float) $value, 'count' => $count, 'percentage' => $this->display(100 * $count / $n, 1)];
        }

        $middle = intdiv($n, 2);
        $median = $n % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
        $maxCount = max($counts);
        $mode = array_map('floatval', array_keys(array_filter($counts, fn (int $count) => $count === $maxCount)));
        $mean = array_sum($values) / $n;
        $sd = $n > 1 ? sqrt(array_sum(array_map(fn (float $value) => ($value - $mean) ** 2, $values)) / ($n - 1)) : null;

        $orderedValues = array_values(array_unique(array_map('floatval', $orderedValues)));
        sort($orderedValues, SORT_NUMERIC);
        $topValues = array_slice($orderedValues ?: array_map('floatval', array_keys($counts)), -2);
        $topCount = count(array_filter($values, fn (float $value) => in_array($value, $topValues, true)));
        $normalized = $this->normalize($mean, $lower, $upper);

        return [
            'n' => $n,
            'distribution' => $distribution,
            'median' => $this->display($median, 2),
            'mode' => $mode,
            'mean' => $this->display($mean, 2),
            'sd' => $sd === null ? null : $this->display($sd, 2),
            'top_two_box' => $this->display(100 * $topCount / $n, 1),
            'normalized_score' => $this->display($normalized, 2),
            'interpretation' => $this->interpretation($normalized),
        ];
    }

    public function normalize(float $value, float $lower, float $upper): float
    {
        return $upper === $lower ? 0.0 : 100 * ($value - $lower) / ($upper - $lower);
    }

    public function interpretation(float $score): string
    {
        return match (true) {
            $score < 20 => 'very_low',
            $score < 40 => 'low',
            $score < 60 => 'medium',
            $score < 80 => 'high',
            default => 'very_high',
        };
    }

    public function cronbachAlpha(array $rows, int $minimumN = 30): array
    {
        $rows = array_values(array_filter($rows, fn (array $row) => count($row) >= 2 && ! in_array(null, $row, true)));
        $n = count($rows);
        $k = $n ? count($rows[0]) : 0;
        if ($k < 2 || $n < $minimumN || count(array_filter($rows, fn (array $row) => count($row) === $k)) !== $n) {
            return ['status' => 'prerequisites_not_met', 'n' => $n, 'item_count' => $k, 'alpha' => null];
        }

        $itemVariances = 0.0;
        for ($column = 0; $column < $k; $column++) {
            $itemVariances += $this->sampleVariance(array_column($rows, $column));
        }
        $totalVariance = $this->sampleVariance(array_map(fn (array $row) => array_sum($row), $rows));
        if ($totalVariance <= 0) {
            return ['status' => 'zero_variance', 'n' => $n, 'item_count' => $k, 'alpha' => null];
        }

        $alpha = ($k / ($k - 1)) * (1 - $itemVariances / $totalVariance);

        return [
            'status' => 'calculated',
            'n' => $n,
            'item_count' => $k,
            'alpha' => $this->display($alpha, 3),
            'interpretation' => $alpha > .95 ? 'possible_redundancy' : ($alpha >= .70 ? 'acceptable_exploratory' : 'below_exploratory_threshold'),
            'caveat' => 'Reliability is not evidence of validity.',
        ];
    }

    public function servqual(array $pairedGaps, float $lower, float $upper): array
    {
        if ($pairedGaps === []) {
            return ['status' => 'prerequisites_not_met', 'n' => 0];
        }
        $meanGap = array_sum($pairedGaps) / count($pairedGaps);

        return ['status' => 'calculated', 'n' => count($pairedGaps), 'mean_gap' => $this->display($meanGap, 2), 'normalized_gap' => $this->display(100 * $meanGap / ($upper - $lower), 2)];
    }

    public function ipa(float $importance, float $performance): string
    {
        if ($importance >= 4 && $performance < 4) {
            return 'concentrate_here';
        }
        if ($importance >= 4) {
            return 'keep_up_the_good_work';
        }
        if ($performance >= 4) {
            return 'possible_overinvestment';
        }

        return 'low_priority';
    }

    public function csi(array $importance, array $normalizedPerformance): ?float
    {
        $sum = array_sum($importance);
        if ($sum <= 0 || count($importance) !== count($normalizedPerformance) || $importance === []) {
            return null;
        }
        $score = 0.0;
        foreach ($importance as $index => $value) {
            $score += ($value / $sum) * $normalizedPerformance[$index];
        }

        return $this->display($score, 2);
    }

    public function ikm(array $nineElementMeans): array
    {
        if (count($nineElementMeans) !== 9 || in_array(null, $nineElementMeans, true)) {
            return ['status' => 'prerequisites_not_met'];
        }
        $score = $this->display((array_sum($nineElementMeans) / 9) * 25, 2);
        [$grade, $label] = match (true) {
            $score <= 64.99 => ['D', 'not_good'],
            $score <= 76.60 => ['C', 'less_good'],
            $score <= 88.30 => ['B', 'good'],
            default => ['A', 'very_good'],
        };

        return ['status' => 'calculated', 'score' => $score, 'grade' => $grade, 'interpretation' => $label];
    }

    private function sampleVariance(array $values): float
    {
        $values = array_map('floatval', $values);
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / $n;

        return array_sum(array_map(fn (float $value) => ($value - $mean) ** 2, $values)) / ($n - 1);
    }

    private function display(float $value, int $precision): float
    {
        return round($value, $precision, PHP_ROUND_HALF_UP);
    }

    private function numberKey(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}

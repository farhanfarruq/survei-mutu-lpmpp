<?php

namespace App\Services;

use App\Exceptions\DomainRuleViolation;
use App\Models\AggregateSnapshot;

final class AiSafety
{
    private const REQUIRED_KEYS = ['summary', 'topics', 'sentiment', 'trend_explanation', 'recommendations', 'limitations'];

    public function projection(AggregateSnapshot $snapshot): array
    {
        $metrics = $snapshot->metrics;
        $categories = collect($metrics['categories'] ?? [])->map(fn (array $row) => [
            'code' => $this->safeText((string) ($row['code'] ?? '')),
            'name' => $this->safeText((string) ($row['name'] ?? '')),
            'n' => $row['n'] ?? null,
            'normalized_score' => $row['suppressed'] ?? false ? null : ($row['normalized_score'] ?? null),
            'interpretation' => $row['interpretation'] ?? null,
        ])->values()->all();
        $indicators = collect($metrics['indicators'] ?? [])->map(fn (array $row) => [
            'code' => $this->safeText((string) ($row['code'] ?? '')),
            'name' => $this->safeText((string) ($row['name'] ?? '')),
            'n' => $row['n'] ?? null,
            'normalized_score' => $row['suppressed'] ?? false ? null : ($row['normalized_score'] ?? null),
            'interpretation' => $row['interpretation'] ?? null,
        ])->values()->all();

        return [
            'schema' => 'aggregate-ai-projection-v1',
            'survey_id' => $snapshot->survey_id,
            'unit_id' => $snapshot->owner_unit_id,
            'period_id' => $snapshot->survey_period_id,
            'response_count' => $snapshot->response_count,
            'response_rate' => $metrics['response_rate'] ?? null,
            'overall' => array_intersect_key($metrics['overall'] ?? [], array_flip(['n', 'normalized_score', 'interpretation'])),
            'categories' => $categories,
            'indicators' => $indicators,
            'methodology_version' => $metrics['methodology_version'] ?? null,
            'limitations' => $snapshot->limitations ?? [],
        ];
    }

    public function validateOutput(array $content): array
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $content)) {
                throw new DomainRuleViolation('ai_output_quarantined', 'Output AI tidak memenuhi schema wajib.', 422);
            }
        }
        if (! is_string($content['summary']) || mb_strlen($content['summary']) > 4000 || ! is_array($content['topics']) || ! is_array($content['recommendations']) || ! is_array($content['limitations'])) {
            throw new DomainRuleViolation('ai_output_quarantined', 'Tipe atau ukuran output AI tidak valid.', 422);
        }
        if (! is_array($content['sentiment']) || ! in_array($content['sentiment']['label'] ?? null, ['positive', 'neutral', 'negative', 'mixed'], true) || ! is_numeric($content['sentiment']['confidence'] ?? null) || $content['sentiment']['confidence'] < 0 || $content['sentiment']['confidence'] > 1) {
            throw new DomainRuleViolation('ai_output_quarantined', 'Sentimen AI tidak memenuhi schema terukur.', 422);
        }
        if (! is_string($content['trend_explanation']) || mb_strlen($content['trend_explanation']) > 4000) {
            throw new DomainRuleViolation('ai_output_quarantined', 'Penjelasan tren AI tidak valid.', 422);
        }
        foreach (['topics', 'recommendations', 'limitations'] as $key) {
            if (count($content[$key]) > 20 || collect($content[$key])->contains(fn ($value) => ! is_string($value) || mb_strlen($value) > 1000)) {
                throw new DomainRuleViolation('ai_output_quarantined', 'Daftar output AI melewati batas schema.', 422);
            }
        }
        $serialized = json_encode($content, JSON_THROW_ON_ERROR);
        if (preg_match('/(?:[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|bearer\s+[A-Z0-9._-]+|api[_ -]?key|password|secret)/i', $serialized)) {
            throw new DomainRuleViolation('ai_output_quarantined', 'Output AI mengandung data yang tidak boleh diterbitkan.', 422);
        }

        return array_intersect_key($content, array_flip(self::REQUIRED_KEYS));
    }

    private function safeText(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', strip_tags($value)) ?? '';
        if (preg_match('/ignore\s+(all|previous)|system\s+prompt|developer\s+message|jailbreak|<script/i', $value)) {
            return '[REDACTED_UNTRUSTED_TEXT]';
        }

        return mb_substr(trim($value), 0, 240);
    }
}

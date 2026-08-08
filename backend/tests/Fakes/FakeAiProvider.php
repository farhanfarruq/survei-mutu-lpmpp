<?php

namespace Tests\Fakes;

use App\Contracts\AiProvider;
use App\Models\AiPromptTemplate;
use App\Models\AiProviderConfig;
use RuntimeException;

class FakeAiProvider implements AiProvider
{
    public bool $fails = false;

    public array $lastPayload = [];

    public ?array $contentOverride = null;

    public function testConnection(AiProviderConfig $config): array
    {
        if ($this->fails) {
            throw new RuntimeException('fake provider unavailable');
        }

        return ['ok' => true, 'latency_ms' => 4];
    }

    public function generate(AiProviderConfig $config, AiPromptTemplate $template, array $payload): array
    {
        $this->lastPayload = $payload;
        if ($this->fails) {
            throw new RuntimeException('fake provider unavailable');
        }

        return [
            'content' => $this->contentOverride ?? [
                'summary' => 'Skor agregat menunjukkan layanan berada pada kategori baik.',
                'topics' => ['kecepatan layanan'],
                'sentiment' => ['label' => 'positive', 'confidence' => 0.8],
                'trend_explanation' => 'Tren stabil berdasarkan snapshot agregat yang tersedia.',
                'recommendations' => ['Pertahankan waktu layanan dan pantau indikator agregat.'],
                'limitations' => ['Interpretasi terbatas pada scope snapshot released.'],
            ],
            'input_tokens' => 240,
            'output_tokens' => 90,
            'latency_ms' => 8,
        ];
    }
}

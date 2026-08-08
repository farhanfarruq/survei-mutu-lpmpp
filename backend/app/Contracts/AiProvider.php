<?php

namespace App\Contracts;

use App\Models\AiPromptTemplate;
use App\Models\AiProviderConfig;

interface AiProvider
{
    /** @return array{ok: bool, latency_ms: int} */
    public function testConnection(AiProviderConfig $config): array;

    /** @return array{content: array<string, mixed>, input_tokens: int, output_tokens: int, latency_ms: int} */
    public function generate(AiProviderConfig $config, AiPromptTemplate $template, array $payload): array;
}

<?php

namespace Tests\Unit\Services;

use App\Models\AiPromptTemplate;
use App\Models\AiProviderConfig;
use App\Services\HttpAiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpAiProviderTest extends TestCase
{
    public function test_generate_sends_the_required_output_contract(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'summary' => 'Ringkasan',
                    'topics' => ['Layanan'],
                    'sentiment' => ['label' => 'positive', 'confidence' => 0.8],
                    'trend_explanation' => 'Belum ada pembanding.',
                    'recommendations' => ['Pertahankan layanan.'],
                    'limitations' => ['Satu periode.'],
                ], JSON_THROW_ON_ERROR)]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20],
            ]),
        ]);
        $config = new AiProviderConfig([
            'model' => 'openai/gpt-4o-mini',
            'base_url' => 'https://openrouter.ai/api/v1',
            'secret_ciphertext' => 'test-secret',
            'max_output_tokens' => 2000,
            'timeout_seconds' => 30,
        ]);
        $template = new AiPromptTemplate([
            'system_prompt' => 'Buat ringkasan mutu.',
            'output_schema' => ['required' => ['summary', 'topics', 'sentiment', 'trend_explanation', 'recommendations', 'limitations']],
        ]);

        app(HttpAiProvider::class)->generate($config, $template, ['schema' => 'aggregate-ai-projection-v1']);

        Http::assertSent(function (Request $request): bool {
            $instruction = $request['messages'][0]['content'];

            return $request['response_format'] === ['type' => 'json_object']
                && str_contains($instruction, '"summary":"string"')
                && str_contains($instruction, '"topics":["string"]')
                && str_contains($instruction, '"trend_explanation":"string"')
                && str_contains($instruction, '"recommendations":["string"]')
                && str_contains($instruction, '"limitations":["string"]');
        });
    }
}

<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'allowed_base_urls' => [
        'openai' => ['https://api.openai.com/v1'],
        'groq' => ['https://api.groq.com/openai/v1'],
        'gemini' => ['https://generativelanguage.googleapis.com/v1beta/openai'],
        'mock' => ['https://mock-ai.invalid/v1'],
    ],
];

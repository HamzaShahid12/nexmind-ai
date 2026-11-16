<?php

return [
    // Provider used when the admin has not chosen another one (settings table).
    'default' => env('AI_PROVIDER', 'openai'),

    // Providers are replaceable: add an entry here plus a class implementing AIServiceInterface
    // and register its driver name in App\Services\AI\AIManager.
    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),            // server-side only; never sent to the frontend
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        ],
    ],

    'limits' => [
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 2500),   // hard ceiling per request
        'chat_history_messages' => 20,
    ],

    'documents' => [
        'max_kb' => (int) env('DOC_MAX_KB', 5120),
        'max_chars' => (int) env('DOC_MAX_CHARS', 30000),
        'retention_days' => (int) env('DOC_RETENTION_DAYS', 7),
    ],
];

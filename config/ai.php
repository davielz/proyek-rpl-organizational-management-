<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Integration Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LLM API integration used for generating document drafts.
    | API Key MUST be defined via environment variables.
    |
    */

    'provider' => env('AI_PROVIDER', 'gemini'),

    'api_key' => env('AI_API_KEY'),

    'model' => env('AI_MODEL', 'gemini-1.5-flash'),

    'base_url' => env('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 450),

    'retry_times' => (int) env('AI_RETRY_TIMES', 2),

    'retry_sleep_ms' => (int) env('AI_RETRY_SLEEP_MS', 1000),

    'mock' => (bool) env('AI_MOCK', false),
];

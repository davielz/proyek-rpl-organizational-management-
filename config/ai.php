<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Integration Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LLM API integration used for AI Assistant and document drafts.
    | API Key MUST be defined via environment variables.
    |
    */

    'provider' => env('AI_PROVIDER', 'nvidia'),

    'api_key' => env('AI_API_KEY', env('NVIDIA_API_KEY')),

    'model' => env('AI_MODEL', 'meta/llama-3.2-90b-vision-instruct'),

    'base_url' => env('AI_BASE_URL', 'https://integrate.api.nvidia.com/v1'),

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 500),

    'retry_times' => (int) env('AI_RETRY_TIMES', 2),

    'retry_sleep_ms' => (int) env('AI_RETRY_SLEEP_MS', 1000),

    'mock' => (bool) env('AI_MOCK', false),
];

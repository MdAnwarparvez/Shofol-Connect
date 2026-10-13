<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', (env('GEMINI_API_KEY') && !env('AI_API_KEY') && !env('OPENAI_API_KEY') ? 'gemini' : 'openai')),
        'api_key' => env('AI_API_KEY') ?: (env('OPENAI_API_KEY') ?: env('GEMINI_API_KEY')),
        'model' => env('AI_MODEL') ?: ((env('AI_PROVIDER') === 'gemini' || (env('GEMINI_API_KEY') && !env('AI_API_KEY') && !env('OPENAI_API_KEY'))) ? 'gemini-3.8-flash' : 'gpt-4o-mini'),
        'embedding_model' => env('AI_EMBEDDING_MODEL') ?: ((env('AI_PROVIDER') === 'gemini' || (env('GEMINI_API_KEY') && !env('AI_API_KEY') && !env('OPENAI_API_KEY'))) ? 'gemini-embedding-2' : 'text-embedding-3-small'),
        'embedding_dimensions' => env('AI_EMBEDDING_DIMENSIONS') ? (int) env('AI_EMBEDDING_DIMENSIONS') : null,
        'base_url' => rtrim(env('AI_BASE_URL') ?: ((env('AI_PROVIDER') === 'gemini' || (env('GEMINI_API_KEY') && !env('AI_API_KEY') && !env('OPENAI_API_KEY'))) ? 'https://generativelanguage.googleapis.com/v1beta/openai' : 'https://api.openai.com/v1'), '/'),
        'timeout' => (int) env('AI_TIMEOUT', 15),
        'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('AI_RETRY_TIMES', 1),
        'retry_delay' => (int) env('AI_RETRY_DELAY', 500),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 800),
    ],

];

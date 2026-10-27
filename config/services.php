<?php

$aiProvider = strtolower((string) env('AI_PROVIDER', 'gemini'));

$isOllama = $aiProvider === 'ollama';
$isGemini = $aiProvider === 'gemini';

$aiKey = env('AI_API_KEY') ?: match ($aiProvider) {
    'ollama' => env('OLLAMA_API_KEY', 'ollama'),
    'gemini' => env('GEMINI_API_KEY'),
    'openai' => env('OPENAI_API_KEY'),
    default => null,
};

$defaultModel = match ($aiProvider) {
    'ollama' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
    'gemini' => 'gemini-3.8-flash',
    default => 'gpt-4o-mini',
};

$defaultBaseUrl = match ($aiProvider) {
    'ollama' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434/v1'),
    'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai',
    default => 'https://api.openai.com/v1',
};

$defaultEmbeddingModel = match ($aiProvider) {
    'gemini' => 'gemini-embedding-2',
    default => 'text-embedding-3-small',
};

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

    'ollama' => [
        'base_url' => rtrim(env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434/v1'), '/'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
        'api_key' => env('OLLAMA_API_KEY', 'ollama'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 60),
        'connect_timeout' => (int) env('OLLAMA_CONNECT_TIMEOUT', 5),
        'fallback_enabled' => (bool) env('AI_FALLBACK_OLLAMA', true),
    ],

    'ai' => [
        'provider' => $aiProvider,
        'api_key' => $aiKey,
        'model' => env('AI_MODEL') ?: $defaultModel,
        'embedding_model' => env('AI_EMBEDDING_MODEL') ?: $defaultEmbeddingModel,
        'embedding_dimensions' => env('AI_EMBEDDING_DIMENSIONS') ? (int) env('AI_EMBEDDING_DIMENSIONS') : null,
        'base_url' => rtrim(env('AI_BASE_URL') ?: $defaultBaseUrl, '/'),
        'timeout' => (int) env('AI_TIMEOUT', 15),
        'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('AI_RETRY_TIMES', 1),
        'retry_delay' => (int) env('AI_RETRY_DELAY', 500),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 800),
        'fallback_to_ollama' => (bool) env('AI_FALLBACK_OLLAMA', true),
    ],

];

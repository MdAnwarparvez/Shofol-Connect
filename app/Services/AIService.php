<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Standard fallback message returned when the knowledge base has no relevant context.
     */
    public const FALLBACK_MESSAGE = "I couldn't find any relevant information about that in the Shofol Connect knowledge base. Please contact our support team or open a support ticket for further assistance.";

    protected KnowledgeRetrievalService $retrievalService;
    protected string $provider;
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retryTimes;
    protected int $retryDelay;
    protected int $maxTokens;
    protected string $ollamaBaseUrl;
    protected string $ollamaModel;
    protected string $ollamaApiKey;
    protected int $ollamaTimeout;
    protected int $ollamaConnectTimeout;
    protected bool $fallbackEnabled;

    public function __construct(KnowledgeRetrievalService $retrievalService)
    {
        $this->retrievalService = $retrievalService;
        $this->provider = strtolower((string) Config::get('services.ai.provider', 'gemini'));

        $rawKey = Config::get('services.ai.api_key');
        $this->apiKey = !empty($rawKey) ? trim((string) $rawKey) : null;

        $defaultModel = $this->provider === 'ollama'
            ? (string) Config::get('services.ollama.model', 'qwen2.5:7b')
            : 'gemini-3.8-flash';
        $configuredModel = Config::get('services.ai.model');
        $this->model = !empty($configuredModel) ? (string) $configuredModel : $defaultModel;

        $defaultBaseUrl = $this->provider === 'ollama'
            ? (string) Config::get('services.ollama.base_url', 'http://127.0.0.1:11434/v1')
            : 'https://generativelanguage.googleapis.com/v1beta/openai';
        $configuredBaseUrl = Config::get('services.ai.base_url');
        $this->baseUrl = rtrim(!empty($configuredBaseUrl) ? (string) $configuredBaseUrl : $defaultBaseUrl, '/');

        $this->timeout = (int) Config::get('services.ai.timeout', 15);
        $this->connectTimeout = (int) Config::get('services.ai.connect_timeout', 5);
        $this->retryTimes = (int) Config::get('services.ai.retry_times', 1);
        $this->retryDelay = (int) Config::get('services.ai.retry_delay', (function_exists('app') && app()->runningUnitTests()) ? 10 : 500);
        $this->maxTokens = (int) Config::get('services.ai.max_tokens', 800);

        $this->ollamaBaseUrl = rtrim((string) Config::get('services.ollama.base_url', 'http://127.0.0.1:11434/v1'), '/');
        $this->ollamaModel = (string) Config::get('services.ollama.model', 'qwen2.5:7b');
        $this->ollamaApiKey = (string) Config::get('services.ollama.api_key', 'ollama');
        $this->ollamaTimeout = (int) Config::get('services.ollama.timeout', 60);
        $this->ollamaConnectTimeout = (int) Config::get('services.ollama.connect_timeout', 5);
        $this->fallbackEnabled = (bool) Config::get('services.ai.fallback_to_ollama', Config::get('services.ollama.fallback_enabled', true));
    }

    /**
     * Get the configured AI provider name (e.g. gemini, ollama, openai).
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Check if automatic fallback to Ollama is enabled.
     */
    public function isFallbackEnabled(): bool
    {
        return $this->fallbackEnabled;
    }

    /**
     * Get the configured Ollama model name.
     */
    public function getOllamaModel(): string
    {
        return $this->ollamaModel;
    }

    /**
     * Get the configured Ollama base URL.
     */
    public function getOllamaBaseUrl(): string
    {
        return $this->ollamaBaseUrl;
    }

    /**
     * Check if the AI service has an active API key configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the configured model name.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the configured timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the configured connection timeout in seconds.
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * Get the configured retry times.
     */
    public function getRetryTimes(): int
    {
        return $this->retryTimes;
    }

    /**
     * Get the maximum tokens limit per response.
     */
    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    /**
     * Process a user question using RAG and LLM completion.
     *
     * @param string $userQuestion
     * @return array{success: bool, answer: string, sources: array, fallback: bool, error?: string}
     */
    public function ask(string $userQuestion, iterable $conversationHistory = []): array
    {
        if (function_exists('set_time_limit') && (!function_exists('app') || !app()->runningUnitTests())) {
            @set_time_limit(180);
        }

        $cleanQuestion = trim($userQuestion);

        if (empty($cleanQuestion)) {
            return [
                'success' => false,
                'answer' => 'Please provide a valid question.',
                'sources' => [],
                'fallback' => false,
                'error' => 'EMPTY_QUESTION',
            ];
        }

        // Format and bound conversation history (chronological, last 6 messages max, capped per turn)
        $formattedHistory = [];
        $lastUserQuery = null;

        foreach ($conversationHistory as $msg) {
            $role = is_array($msg) ? ($msg['role'] ?? 'user') : ($msg->role ?? 'user');
            $content = is_array($msg) ? ($msg['content'] ?? '') : ($msg->content ?? '');

            $trimmedContent = trim((string) $content);
            if (!empty($trimmedContent)) {
                if ($role === 'user') {
                    $lastUserQuery = $trimmedContent;
                }
                $formattedHistory[] = [
                    'role' => $role === 'assistant' ? 'assistant' : 'user',
                    'content' => \Illuminate\Support\Str::limit($trimmedContent, 1000),
                ];
            }
        }

        if (count($formattedHistory) > 6) {
            $formattedHistory = array_slice($formattedHistory, -6);
        }

        // 1. Retrieve & rank knowledge articles relevant to question
        $retrieval = $this->retrievalService->retrieve($cleanQuestion);

        // If direct follow-up has no matches, but prior conversation has context, try conversational retrieval
        if (!$retrieval['has_relevant_results'] && !empty($lastUserQuery)) {
            $contextualQuery = trim($lastUserQuery . ' ' . $cleanQuestion);
            $contextualRetrieval = $this->retrievalService->retrieve($contextualQuery);
            if ($contextualRetrieval['has_relevant_results']) {
                $retrieval = $contextualRetrieval;
            }
        }

        // 2. Strict Rule: If no relevant articles are found, do not let LLM hallucinate
        if (!$retrieval['has_relevant_results']) {
            return [
                'success' => true,
                'answer' => self::FALLBACK_MESSAGE,
                'sources' => [],
                'fallback' => true,
            ];
        }

        // 3. Check API key configuration
        if (!$this->isConfigured()) {
            Log::warning('AI Service called but no provider API key is configured', [
                'provider' => Config::get('services.ai.provider'),
                'model' => $this->model,
                'base_url' => $this->safeBaseUrl(),
            ]);
            return [
                'success' => false,
                'answer' => 'The AI assistant is temporarily unavailable because the AI service is not configured. Please contact support.',
                'sources' => $retrieval['sources'],
                'fallback' => false,
                'error' => 'API_KEY_MISSING',
            ];
        }

        if (!$this->hasValidBaseUrl()) {
            $this->logInvalidBaseUrl();
            return [
                'success' => false,
                'answer' => $this->buildPolicyFallbackAnswer('INVALID_BASE_URL', $retrieval['articles']),
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => true,
                'error' => 'INVALID_BASE_URL',
            ];
        }

        // 4. Construct System Prompt with Knowledge Base Context
        $systemPrompt = $this->buildSystemPrompt($retrieval['context']);

        // 5. Assemble multi-turn messages payload preserving system bounds
        $llmMessages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($formattedHistory as $turn) {
            $llmMessages[] = $turn;
        }

        $llmMessages[] = [
            'role' => 'user',
            'content' => $cleanQuestion,
        ];

        // 6. Call LLM API via Laravel HTTP Client with timeouts and error handling
        $llmStart = microtime(true);
        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->retry(
                    times: $this->retryTimes,
                    sleepMilliseconds: $this->retryDelay,
                    when: function (\Throwable $exception) {
                        if ($exception instanceof RequestException && $exception->response) {
                            $status = $exception->response->status();
                            return in_array($status, [429, 503], true);
                        }
                        return false;
                    },
                    throw: false
                )
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $llmMessages,
                    'temperature' => 0.2,
                    'max_tokens' => $this->maxTokens,
                ]);

            $llmDurationMs = round((microtime(true) - $llmStart) * 1000, 2);
            if (app()->environment('local', 'testing') || config('app.debug')) {
                Log::info('AI Timing: LLM Generation', [
                    'stage' => 'llm_generation',
                    'duration_ms' => $llmDurationMs,
                    'model' => $this->model,
                    'stream' => false,
                ]);
            }

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!empty($content)) {
                    return [
                        'success' => true,
                        'answer' => $this->sanitizeAnswer(trim($content)),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                    ];
                }
            }

            $status = $response->status();
            $errorMessage = $this->extractProviderErrorMessage($response->json(), $status);
            $errorType = $this->classifyProviderError($status, $errorMessage);
            $errorMessage = $this->sanitizeDiagnostic($errorMessage);
            $this->logProviderError($errorType, $status, $errorMessage);

            // Automatic fallback to local Ollama on recoverable Gemini errors
            if ($this->shouldFallbackToOllama($errorType, $status)) {
                $fallbackResult = $this->executeOllamaFallback($llmMessages, $retrieval);
                if ($fallbackResult !== null) {
                    return $fallbackResult;
                }
            }

            $hasRelevant = $retrieval['has_relevant_results'];
            $fallbackAnswer = $this->buildPolicyFallbackAnswer($errorType, $retrieval['articles']);

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => $hasRelevant,
                'error' => $errorType,
                'error_message' => $errorMessage,
            ];
        } catch (ConnectionException $e) {
            $errorType = str_contains(strtolower($e->getMessage()), 'timed out')
                || str_contains(strtolower($e->getMessage()), 'timeout')
                ? 'TIMEOUT'
                : 'CONNECTION_ERROR';
            $errorMsg = $this->sanitizeDiagnostic($e->getMessage());
            Log::warning('AI provider connection failed', [
                'provider' => Config::get('services.ai.provider'),
                'model' => $this->model,
                'base_url' => $this->safeBaseUrl(),
                'error_type' => $errorType,
                'exception' => $e::class,
                'error_message' => $errorMsg,
            ]);

            // Automatic fallback to local Ollama on connection or timeout failure
            if ($this->shouldFallbackToOllama($errorType)) {
                $fallbackResult = $this->executeOllamaFallback($llmMessages, $retrieval);
                if ($fallbackResult !== null) {
                    return $fallbackResult;
                }
            }

            $hasRelevant = $retrieval['has_relevant_results'];
            $fallbackAnswer = $this->buildPolicyFallbackAnswer($errorType, $retrieval['articles']);

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => $hasRelevant,
                'error' => $errorType,
                'error_message' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            $safeError = $this->sanitizeDiagnostic($e->getMessage());
            Log::error('Unexpected AI provider exception', [
                'provider' => Config::get('services.ai.provider'),
                'model' => $this->model,
                'base_url' => $this->safeBaseUrl(),
                'exception' => $e::class,
                'error_message' => $safeError,
            ]);

            if ($this->shouldFallbackToOllama('API_ERROR')) {
                $fallbackResult = $this->executeOllamaFallback($llmMessages, $retrieval);
                if ($fallbackResult !== null) {
                    return $fallbackResult;
                }
            }

            $hasRelevant = $retrieval['has_relevant_results'];
            $fallbackAnswer = $this->buildPolicyFallbackAnswer('API_ERROR', $retrieval['articles']);

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => $hasRelevant,
                'error' => 'EXCEPTION',
                'error_message' => $safeError,
            ];
        }
    }

    /**
     * Process a user question using RAG and streamed LLM completion.
     * Invokes $onToken callback as text deltas are received from the upstream stream.
     *
     * @param string $userQuestion
     * @param iterable $conversationHistory
     * @param callable(string): void|null $onToken
     * @return array{success: bool, answer: string, sources: array, fallback: bool, is_policy_fallback?: bool, error?: string}
     */
    public function askStream(string $userQuestion, iterable $conversationHistory = [], ?callable $onToken = null): array
    {
        if (function_exists('set_time_limit') && (!function_exists('app') || !app()->runningUnitTests())) {
            @set_time_limit(180);
        }

        $cleanQuestion = trim($userQuestion);

        if (empty($cleanQuestion)) {
            return [
                'success' => false,
                'answer' => 'Please provide a valid question.',
                'sources' => [],
                'fallback' => false,
                'error' => 'EMPTY_QUESTION',
            ];
        }

        // Format and bound conversation history
        $formattedHistory = [];
        $lastUserQuery = null;

        foreach ($conversationHistory as $msg) {
            $role = is_array($msg) ? ($msg['role'] ?? 'user') : ($msg->role ?? 'user');
            $content = is_array($msg) ? ($msg['content'] ?? '') : ($msg->content ?? '');

            $trimmedContent = trim((string) $content);
            if (!empty($trimmedContent)) {
                if ($role === 'user') {
                    $lastUserQuery = $trimmedContent;
                }
                $formattedHistory[] = [
                    'role' => $role === 'assistant' ? 'assistant' : 'user',
                    'content' => \Illuminate\Support\Str::limit($trimmedContent, 1000),
                ];
            }
        }

        if (count($formattedHistory) > 6) {
            $formattedHistory = array_slice($formattedHistory, -6);
        }

        // 1. Retrieve & rank knowledge articles relevant to question
        $retrieval = $this->retrievalService->retrieve($cleanQuestion);

        // If direct follow-up has no matches, but prior conversation has context, try conversational retrieval
        if (!$retrieval['has_relevant_results'] && !empty($lastUserQuery)) {
            $combinedQuery = trim($lastUserQuery . ' ' . $cleanQuestion);
            if (mb_strtolower($combinedQuery) !== mb_strtolower($cleanQuestion)) {
                $contextualRetrieval = $this->retrievalService->retrieve($combinedQuery);
                if ($contextualRetrieval['has_relevant_results']) {
                    $retrieval = $contextualRetrieval;
                }
            }
        }

        // 2. Strict Rule: If no relevant articles are found, do not let LLM hallucinate
        if (!$retrieval['has_relevant_results']) {
            if ($onToken) {
                $onToken(self::FALLBACK_MESSAGE);
            }
            return [
                'success' => true,
                'answer' => self::FALLBACK_MESSAGE,
                'sources' => [],
                'fallback' => true,
            ];
        }

        // 3. Check API key configuration
        if (!$this->isConfigured()) {
            Log::warning('AI Service called but no provider API key is configured', [
                'provider' => Config::get('services.ai.provider'),
                'model' => $this->model,
                'base_url' => $this->safeBaseUrl(),
            ]);
            $msg = 'The AI assistant is temporarily unavailable because the AI service is not configured. Please contact support.';
            if ($onToken) {
                $onToken($msg);
            }
            return [
                'success' => false,
                'answer' => $msg,
                'sources' => $retrieval['sources'],
                'fallback' => false,
                'error' => 'API_KEY_MISSING',
            ];
        }

        if (!$this->hasValidBaseUrl()) {
            $this->logInvalidBaseUrl();
            $message = $this->buildPolicyFallbackAnswer('INVALID_BASE_URL', $retrieval['articles']);
            if ($onToken) {
                $onToken($message);
            }
            return [
                'success' => false,
                'answer' => $message,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => true,
                'error' => 'INVALID_BASE_URL',
            ];
        }

        // 4. Construct System Prompt with Knowledge Base Context
        $systemPrompt = $this->buildSystemPrompt($retrieval['context']);

        $llmMessages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($formattedHistory as $turn) {
            $llmMessages[] = $turn;
        }

        $llmMessages[] = [
            'role' => 'user',
            'content' => $cleanQuestion,
        ];

        $llmStart = microtime(true);
        $ttftMs = null;

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream, application/json',
                ])
                ->withOptions(['stream' => true])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $llmMessages,
                    'temperature' => 0.2,
                    'max_tokens' => $this->maxTokens,
                    'stream' => true,
                ]);

            if ($response->successful()) {
                $fullAnswer = '';
                $body = $response->toPsrResponse()->getBody();

                // If regular JSON (e.g. non-stream response or in mocked test)
                $contentType = $response->header('Content-Type') ?? '';
                if (str_contains($contentType, 'application/json') || !$body->isReadable()) {
                    $jsonContent = $response->json('choices.0.message.content');
                    if (!empty($jsonContent)) {
                        $fullAnswer = trim((string) $jsonContent);
                        $ttftMs = round((microtime(true) - $llmStart) * 1000, 2);
                        if ($onToken) {
                            $onToken($fullAnswer);
                        }
                        $totalDurationMs = round((microtime(true) - $llmStart) * 1000, 2);
                        if (app()->environment('local', 'testing') || config('app.debug')) {
                            Log::info('AI Timing: LLM Stream Generation', [
                                'stage' => 'llm_generation',
                                'ttft_ms' => $ttftMs,
                                'total_duration_ms' => $totalDurationMs,
                                'model' => $this->model,
                                'stream' => true,
                            ]);
                        }
                        return [
                            'success' => true,
                            'answer' => $fullAnswer,
                            'sources' => $retrieval['sources'],
                            'fallback' => false,
                        ];
                    }
                }

                // Read streamed chunks
                while (!$body->eof()) {
                    $line = \GuzzleHttp\Psr7\Utils::readLine($body);
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, 'data: ')) {
                        $payload = substr($trimmed, 6);
                        if ($payload === '[DONE]') {
                            break;
                        }
                        $chunk = json_decode($payload, true);
                        if (is_array($chunk)) {
                            $delta = $chunk['choices'][0]['delta']['content'] ?? '';
                            if ($delta !== '') {
                                if ($ttftMs === null) {
                                    $ttftMs = round((microtime(true) - $llmStart) * 1000, 2);
                                }
                                $fullAnswer .= $delta;
                                if ($onToken) {
                                    $onToken($delta);
                                }
                            }
                        }
                    }
                }

                $totalDurationMs = round((microtime(true) - $llmStart) * 1000, 2);
                if (app()->environment('local', 'testing') || config('app.debug')) {
                    Log::info('AI Timing: LLM Stream Generation', [
                        'stage' => 'llm_generation',
                        'ttft_ms' => $ttftMs,
                        'total_duration_ms' => $totalDurationMs,
                        'model' => $this->model,
                        'stream' => true,
                    ]);
                }

                if (!empty(trim($fullAnswer))) {
                    return [
                        'success' => true,
                        'answer' => $this->sanitizeAnswer(trim($fullAnswer)),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                    ];
                }
            }

            // Upstream returned non-200 status or produced no tokens
            $status = $response->status();
            $errorMessage = $this->extractProviderErrorMessage($response->json(), $status);
            $errorType = $this->classifyProviderError($status, $errorMessage);
            $errorMessage = $this->sanitizeDiagnostic($errorMessage);
            $this->logProviderError($errorType, $status, $errorMessage, true);

            // Attempt Ollama streaming fallback if primary provider failed before yielding tokens
            if ($this->shouldFallbackToOllama($errorType, $status)) {
                $fallbackStreamResult = $this->executeOllamaFallbackStream($llmMessages, $retrieval, $onToken);
                if ($fallbackStreamResult !== null) {
                    return $fallbackStreamResult;
                }
            }

            $totalDurationMs = round((microtime(true) - $llmStart) * 1000, 2);
            if (app()->environment('local', 'testing') || config('app.debug')) {
                Log::info('AI Timing: LLM Stream Generation', [
                    'stage' => 'llm_generation',
                    'ttft_ms' => $ttftMs,
                    'total_duration_ms' => $totalDurationMs,
                    'model' => $this->model,
                    'stream' => true,
                    'error' => $errorType,
                ]);
            }

            // Immediately build policy fallback from already retrieved articles — NO duplicate retrieval, NO duplicate LLM call
            $fallbackAnswer = $this->buildPolicyFallbackAnswer($errorType, $retrieval['articles']);
            if ($onToken) {
                $onToken($fallbackAnswer);
            }

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => true,
                'error' => $errorType,
            ];
        } catch (\Throwable $e) {
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timed out')
                || str_contains(strtolower($e->getMessage()), 'timeout');
            $errorType = $isTimeout ? 'TIMEOUT' : 'CONNECTION_ERROR';
            $safeError = $this->sanitizeDiagnostic($e->getMessage());
            Log::warning('AI provider streaming request failed', [
                'provider' => Config::get('services.ai.provider'),
                'model' => $this->model,
                'base_url' => $this->safeBaseUrl(),
                'error_type' => $errorType,
                'exception' => $e::class,
                'error_message' => $safeError,
            ]);

            // Attempt Ollama streaming fallback on connection / timeout exception
            if ($this->shouldFallbackToOllama($errorType)) {
                $fallbackStreamResult = $this->executeOllamaFallbackStream($llmMessages, $retrieval, $onToken);
                if ($fallbackStreamResult !== null) {
                    return $fallbackStreamResult;
                }
            }

            $totalDurationMs = round((microtime(true) - $llmStart) * 1000, 2);
            if (app()->environment('local', 'testing') || config('app.debug')) {
                Log::info('AI Timing: LLM Stream Generation', [
                    'stage' => 'llm_generation',
                    'ttft_ms' => $ttftMs,
                    'total_duration_ms' => $totalDurationMs,
                    'model' => $this->model,
                    'stream' => true,
                    'error' => $errorType,
                ]);
            }

            // Immediately build policy fallback from already retrieved articles — NO duplicate retrieval, NO duplicate LLM call
            $fallbackAnswer = $this->buildPolicyFallbackAnswer($errorType, $retrieval['articles']);
            if ($onToken) {
                $onToken($fallbackAnswer);
            }

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => true,
                'error' => $errorType,
            ];
        }
    }

    /**
     * Build a friendly fallback answer using retrieved knowledge base articles when the LLM is unavailable.
     *
     * @param string $errorType
     * @param \Illuminate\Support\Collection<\App\Models\KnowledgeArticle> $articles
     * @return string
     */
    protected function buildPolicyFallbackAnswer(string $errorType, Collection $articles): string
    {
        $friendlyReason = match ($errorType) {
            'RATE_LIMIT_OR_QUOTA' => 'The AI assistant encountered a temporary service issue with the upstream provider (request quota limit reached).',
            'SERVICE_UNAVAILABLE' => 'The AI assistant encountered a temporary service issue with the upstream provider (temporary high demand).',
            'TIMEOUT' => 'The AI assistant request timed out. The AI assistant encountered a temporary service issue with the upstream provider.',
            default => 'The AI assistant encountered a temporary service issue.',
        };

        if ($articles->isEmpty()) {
            return "{$friendlyReason} Please try again in a moment or open a support ticket.";
        }

        $primary = $articles->first();
        $title = $primary instanceof \App\Models\KnowledgeArticle ? $primary->title : ($primary->question ?? 'General Policy');
        $module = $primary instanceof \App\Models\KnowledgeArticle ? $primary->module : ($primary->shofolModule?->code ?? $primary->shofolModule?->name ?? 'General');
        $category = $primary instanceof \App\Models\KnowledgeArticle ? $primary->category : ($primary->knowledgeCategory?->name ?? '');
        $content = $primary instanceof \App\Models\KnowledgeArticle ? $primary->content : ($primary->answer ?? '');

        $output = "{$friendlyReason}\n\n";
        $output .= "However, here is the official policy information retrieved directly from the verified Shofol Connect Knowledge Base:\n\n";
        $output .= "📋 Verified Policy: {$title} [Module: {$module}]\n";
        if (!empty($category)) {
            $output .= "Category: {$category}\n";
        }
        $output .= "Policy Details:\n{$content}\n\n";
        $output .= "(Note: This verified policy excerpt is provided directly from official company documentation while AI generation is temporarily unavailable. If you need further assistance, please open a support ticket.)";

        return trim($output);
    }

    /**
     * Extract a provider diagnostic without exposing the response body to users.
     */
    protected function extractProviderErrorMessage(mixed $payload, int $status): string
    {
        if (is_array($payload)) {
            if (isset($payload['error']) && is_string($payload['error']) && $payload['error'] !== '') {
                return $payload['error'];
            }

            $message = $payload['error']['message']
                ?? $payload[0]['error']['message']
                ?? $payload['message']
                ?? null;

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return "HTTP {$status} error";
    }

    /**
     * Normalize provider errors for consistent handling in sync and streaming requests.
     */
    protected function classifyProviderError(int $status, string $message): string
    {
        return match (true) {
            in_array($status, [401, 403], true) => 'AUTH_ERROR',
            $status === 404 => 'INVALID_MODEL',
            in_array($status, [402, 429], true)
                || str_contains(strtolower($message), 'quota')
                || str_contains(strtolower($message), 'billing') => 'RATE_LIMIT_OR_QUOTA',
            in_array($status, [400, 422], true) => 'MALFORMED_REQUEST',
            in_array($status, [500, 502, 503, 504], true) => 'SERVICE_UNAVAILABLE',
            default => 'API_ERROR',
        };
    }

    protected function sanitizeDiagnostic(string $message): string
    {
        if (!empty($this->apiKey)) {
            $message = str_replace($this->apiKey, '[REDACTED]', $message);
        }

        if (!empty($this->ollamaApiKey) && $this->ollamaApiKey !== 'ollama') {
            $message = str_replace($this->ollamaApiKey, '[REDACTED]', $message);
        }

        $message = preg_replace('/(?i)(bearer\s+)[a-z0-9._~+\/=-]+/', '$1[REDACTED]', $message) ?? $message;
        $message = preg_replace('/(?i)(AIza)[a-z0-9_-]{20,}/', '[REDACTED]', $message) ?? $message;
        $message = preg_replace('/(?i)(sk-[a-z0-9_-]{10,})/', '[REDACTED]', $message) ?? $message;

        return $message;
    }

    protected function logProviderError(string $errorType, int $status, string $errorMessage, bool $stream = false): void
    {
        Log::error('AI provider request failed', [
            'provider' => Config::get('services.ai.provider'),
            'status' => $status,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'model' => $this->model,
            'base_url' => $this->safeBaseUrl(),
            'stream' => $stream,
        ]);
    }

    protected function hasValidBaseUrl(): bool
    {
        $parts = parse_url($this->baseUrl);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($parts['host'])
            && filter_var($this->baseUrl, FILTER_VALIDATE_URL) !== false;
    }

    protected function logInvalidBaseUrl(): void
    {
        Log::error('AI provider base URL is invalid', [
            'provider' => Config::get('services.ai.provider'),
            'model' => $this->model,
            'base_url' => $this->safeBaseUrl(),
        ]);
    }

    protected function safeBaseUrl(): string
    {
        $parts = parse_url($this->baseUrl);
        if (!is_array($parts)) {
            return '[invalid URL]';
        }

        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return ($parts['scheme'] ?? '') . '://' . $host . $port . $path;
    }

    protected function safeOllamaBaseUrl(): string
    {
        $parts = parse_url($this->ollamaBaseUrl);
        if (!is_array($parts)) {
            return '[invalid URL]';
        }

        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return ($parts['scheme'] ?? '') . '://' . $host . $port . $path;
    }

    /**
     * Determine whether the service should attempt automatic fallback to Ollama.
     */
    public function shouldFallbackToOllama(string $errorType, int $status = 0): bool
    {
        if (!$this->fallbackEnabled) {
            return false;
        }

        // Do not attempt fallback if already running Ollama as the primary provider
        if ($this->provider === 'ollama') {
            return false;
        }

        if (empty($this->ollamaBaseUrl)) {
            return false;
        }

        $recoverableTypes = [
            'RATE_LIMIT_OR_QUOTA',
            'SERVICE_UNAVAILABLE',
            'TIMEOUT',
            'CONNECTION_ERROR',
        ];

        if (in_array($errorType, $recoverableTypes, true)) {
            return true;
        }

        if ($status === 429 || ($status >= 500 && $status <= 599)) {
            return true;
        }

        return false;
    }

    /**
     * Attempt grounded answer generation using the fallback local Ollama provider.
     *
     * @param array $llmMessages
     * @param array $retrieval
     * @return array{success: bool, answer: string, sources: array, fallback: bool, used_fallback_provider: string}|null
     */
    protected function executeOllamaFallback(array $llmMessages, array $retrieval): ?array
    {
        Log::info('Initiating automatic fallback from Gemini to local Ollama provider', [
            'primary_provider' => $this->provider,
            'fallback_provider' => 'ollama',
            'fallback_model' => $this->ollamaModel,
            'fallback_base_url' => $this->safeOllamaBaseUrl(),
        ]);

        try {
            $response = Http::connectTimeout($this->ollamaConnectTimeout)
                ->timeout($this->ollamaTimeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->ollamaApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->ollamaBaseUrl . '/chat/completions', [
                    'model' => $this->ollamaModel,
                    'messages' => $llmMessages,
                    'temperature' => 0.2,
                    'max_tokens' => $this->maxTokens,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!empty($content)) {
                    Log::info('Automatic Ollama fallback succeeded', [
                        'fallback_model' => $this->ollamaModel,
                    ]);

                    return [
                        'success' => true,
                        'answer' => $this->sanitizeAnswer(trim($content)),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                        'used_fallback_provider' => 'ollama',
                    ];
                }
            }

            $status = $response->status();
            $errorMessage = $this->extractProviderErrorMessage($response->json(), $status);
            $safeError = $this->sanitizeDiagnostic($errorMessage);

            Log::warning('Ollama fallback provider returned non-successful response', [
                'provider' => 'ollama',
                'status' => $status,
                'model' => $this->ollamaModel,
                'base_url' => $this->safeOllamaBaseUrl(),
                'error_message' => $safeError,
            ]);

            return null;
        } catch (\Throwable $e) {
            $safeError = $this->sanitizeDiagnostic($e->getMessage());
            Log::warning('Ollama fallback provider connection or request failed', [
                'provider' => 'ollama',
                'model' => $this->ollamaModel,
                'base_url' => $this->safeOllamaBaseUrl(),
                'exception' => $e::class,
                'error_message' => $safeError,
            ]);

            return null;
        }
    }

    /**
     * Attempt grounded streamed answer generation using the fallback local Ollama provider.
     *
     * @param array $llmMessages
     * @param array $retrieval
     * @param callable(string): void|null $onToken
     * @return array{success: bool, answer: string, sources: array, fallback: bool, used_fallback_provider: string}|null
     */
    protected function executeOllamaFallbackStream(array $llmMessages, array $retrieval, ?callable $onToken = null): ?array
    {
        Log::info('Initiating automatic streaming fallback from Gemini to local Ollama provider', [
            'primary_provider' => $this->provider,
            'fallback_provider' => 'ollama',
            'fallback_model' => $this->ollamaModel,
            'fallback_base_url' => $this->safeOllamaBaseUrl(),
        ]);

        try {
            $response = Http::connectTimeout($this->ollamaConnectTimeout)
                ->timeout($this->ollamaTimeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->ollamaApiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream, application/json',
                ])
                ->withOptions(['stream' => true])
                ->post($this->ollamaBaseUrl . '/chat/completions', [
                    'model' => $this->ollamaModel,
                    'messages' => $llmMessages,
                    'temperature' => 0.2,
                    'max_tokens' => $this->maxTokens,
                    'stream' => true,
                ]);

            if ($response->successful()) {
                $fullAnswer = '';
                $body = $response->toPsrResponse()->getBody();
                $contentType = $response->header('Content-Type') ?? '';

                if (str_contains($contentType, 'application/json') || !$body->isReadable()) {
                    $jsonContent = $response->json('choices.0.message.content');
                    if (!empty($jsonContent)) {
                        $fullAnswer = trim((string) $jsonContent);
                        if ($onToken) {
                            $onToken($fullAnswer);
                        }
                        return [
                            'success' => true,
                            'answer' => $this->sanitizeAnswer($fullAnswer),
                            'sources' => $retrieval['sources'],
                            'fallback' => false,
                            'used_fallback_provider' => 'ollama',
                        ];
                    }
                }

                while (!$body->eof()) {
                    $line = \GuzzleHttp\Psr7\Utils::readLine($body);
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, 'data: ')) {
                        $payload = substr($trimmed, 6);
                        if ($payload === '[DONE]') {
                            break;
                        }
                        $chunk = json_decode($payload, true);
                        if (is_array($chunk)) {
                            $delta = $chunk['choices'][0]['delta']['content'] ?? '';
                            if ($delta !== '') {
                                $fullAnswer .= $delta;
                                if ($onToken) {
                                    $onToken($delta);
                                }
                            }
                        }
                    }
                }

                if (!empty(trim($fullAnswer))) {
                    Log::info('Automatic Ollama streaming fallback succeeded', [
                        'fallback_model' => $this->ollamaModel,
                    ]);

                    return [
                        'success' => true,
                        'answer' => $this->sanitizeAnswer(trim($fullAnswer)),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                        'used_fallback_provider' => 'ollama',
                    ];
                }
            }

            $status = $response->status();
            $errorMessage = $this->extractProviderErrorMessage($response->json(), $status);
            $safeError = $this->sanitizeDiagnostic($errorMessage);

            Log::warning('Ollama fallback streaming provider returned non-successful response', [
                'provider' => 'ollama',
                'status' => $status,
                'model' => $this->ollamaModel,
                'base_url' => $this->safeOllamaBaseUrl(),
                'error_message' => $safeError,
            ]);

            return null;
        } catch (\Throwable $e) {
            $safeError = $this->sanitizeDiagnostic($e->getMessage());
            Log::warning('Ollama fallback streaming provider failed', [
                'provider' => 'ollama',
                'model' => $this->ollamaModel,
                'base_url' => $this->safeOllamaBaseUrl(),
                'exception' => $e::class,
                'error_message' => $safeError,
            ]);

            return null;
        }
    }

    /**
     * Build strict RAG system prompt using retrieved context.
     */
    protected function buildSystemPrompt(string $context): string
    {
        return <<<PROMPT
You are the official AI Support Assistant for Shofol Connect (covering HRM, Payroll, CRM, and Accounting).

CRITICAL CONSTRAINTS:
1. Answer the user's question STRICTLY and ONLY using the provided Shofol Connect Knowledge Base Context below.
2. Answer ONLY the specific user inquiry. Do not answer questions from other topics, and do not append additional questions, next questions, or suggested inquiries from the context.
3. Do NOT include citation tags, source labels (such as '[Source 1]', '[HRM]', or 'Verified Sources:'), or concatenated article titles in your response text. Citations and sources are displayed separately by the system.
4. Do NOT extrapolate, assume, or invent features, policies, numbers, or procedures that are not explicitly stated in the context.
5. For payroll and tax questions: never invent specific income tax rates, percentages, or tax amounts; explain that TDS is itemized on the digital payslip and depends on applicable statutory tax rules.
6. If the provided context does not contain sufficient details to answer the question, state:
   "I couldn't find enough information about that in the Shofol Connect knowledge base. Please contact our support team or open a support ticket for further assistance."
7. Be professional, accurate, and concise.

SHOFOL CONNECT KNOWLEDGE BASE CONTEXT:
---
{$context}
---
PROMPT;
    }

    /**
     * Sanitize LLM-generated answer to eliminate any appended source blocks,
     * next-question repetitions, or echoed citation markers.
     */
    public function sanitizeAnswer(string $answer): string
    {
        // 1. Strip trailing "Verified Sources:", "Sources:", "Citations:" sections if echoed by LLM
        $answer = preg_replace('/(?:\r?\n\s*)+(?:Verified Sources|Sources|Citations|Related Sources)\s*:.*$/is', '', $answer) ?? $answer;

        // 2. Strip trailing "Next Question:", "Next Topics:", "Suggested Questions:" if echoed by LLM
        $answer = preg_replace('/(?:\r?\n\s*)+(?:Next Question|Next Topics?|Suggested Questions?|Related Questions?)\s*:.*$/is', '', $answer) ?? $answer;

        // 3. Strip raw source reference lines like "[Source 1] Module: ... Title: ..."
        $answer = preg_replace('/\[Source\s+\d+\]\s*(?:\(FAQ\)\s*)?(?:Module:[^\n]+)?(?:\s*\|\s*Category:[^\n]+)?(?:\s*\|\s*Title:[^\n]+)?\n?/i', '', $answer) ?? $answer;

        return trim($answer);
    }
}

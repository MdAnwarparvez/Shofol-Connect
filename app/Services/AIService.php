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
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retryTimes;
    protected int $retryDelay;
    protected int $maxTokens;

    public function __construct(KnowledgeRetrievalService $retrievalService)
    {
        $this->retrievalService = $retrievalService;
        $rawKey = Config::get('services.ai.api_key');
        $this->apiKey = !empty($rawKey) ? trim((string) $rawKey) : null;
        $this->model = (string) Config::get('services.ai.model', 'gemini-3.8-flash');
        $this->baseUrl = rtrim((string) Config::get('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai'), '/');
        $this->timeout = (int) Config::get('services.ai.timeout', 15);
        $this->connectTimeout = (int) Config::get('services.ai.connect_timeout', 5);
        $this->retryTimes = (int) Config::get('services.ai.retry_times', 1);
        $this->retryDelay = (int) Config::get('services.ai.retry_delay', (function_exists('app') && app()->runningUnitTests()) ? 10 : 500);
        $this->maxTokens = (int) Config::get('services.ai.max_tokens', 800);
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
            Log::warning('AI Service called but no AI API key is configured (checked AI_API_KEY, OPENAI_API_KEY, GEMINI_API_KEY)');
            return [
                'success' => false,
                'answer' => 'The AI assistant is temporarily unavailable because the AI service is not configured. Please contact support.',
                'sources' => $retrieval['sources'],
                'fallback' => false,
                'error' => 'API_KEY_MISSING',
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
                        'answer' => trim($content),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                    ];
                }
            }

            $status = $response->status();
            $json = $response->json();
            $rawMsg = null;

            if (is_array($json)) {
                if (isset($json[0]['error']['message'])) {
                    $rawMsg = $json[0]['error']['message'];
                } elseif (isset($json['error']['message'])) {
                    $rawMsg = $json['error']['message'];
                } elseif (isset($json['message'])) {
                    $rawMsg = $json['message'];
                }
            }

            $errorMessage = !empty($rawMsg) ? (string) $rawMsg : 'HTTP ' . $status . ' error';

            // Sanitize error message to prevent any potential leakage of credentials
            if (!empty($this->apiKey)) {
                $errorMessage = str_replace($this->apiKey, '[REDACTED]', $errorMessage);
            }

            $errorType = match (true) {
                in_array($status, [401, 403], true) => 'AUTH_ERROR',
                $status === 404 => 'INVALID_MODEL',
                in_array($status, [402, 429], true) || str_contains(strtolower($errorMessage), 'quota') || str_contains(strtolower($errorMessage), 'billing') => 'RATE_LIMIT_OR_QUOTA',
                in_array($status, [400, 422], true) => 'MALFORMED_REQUEST',
                in_array($status, [500, 502, 503, 504], true) => 'SERVICE_UNAVAILABLE',
                default => 'API_ERROR',
            };

            Log::error("AI API Error [{$errorType}]", [
                'status' => $status,
                'error_type' => $errorType,
                'error_message' => $errorMessage,
                'model' => $this->model,
            ]);

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
            $errorMsg = 'Connection timed out after ' . $this->timeout . 's';
            Log::warning('AI Service Timeout/Connection error', ['error' => $errorMsg]);
            $hasRelevant = $retrieval['has_relevant_results'];
            $fallbackAnswer = $this->buildPolicyFallbackAnswer('TIMEOUT', $retrieval['articles']);

            return [
                'success' => false,
                'answer' => $fallbackAnswer,
                'sources' => $retrieval['sources'],
                'fallback' => true,
                'is_policy_fallback' => $hasRelevant,
                'error' => 'TIMEOUT',
                'error_message' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            $rawError = $e->getMessage();
            $safeError = !empty($this->apiKey) ? str_replace($this->apiKey, '[REDACTED]', $rawError) : $rawError;
            Log::error('Unexpected AI Service Exception', ['error' => $safeError]);
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
            Log::warning('AI Service called but no AI API key is configured');
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
                        'answer' => trim($fullAnswer),
                        'sources' => $retrieval['sources'],
                        'fallback' => false,
                    ];
                }
            }

            // Upstream returned non-200 status or produced no tokens
            $status = $response->status();
            $errorMessage = 'HTTP ' . $status . ' error';
            try {
                $json = $response->json();
                if (is_array($json)) {
                    $rawMsg = $json['error']['message'] ?? $json[0]['error']['message'] ?? $json['message'] ?? null;
                    if (!empty($rawMsg)) {
                        $errorMessage = (string) $rawMsg;
                    }
                }
            } catch (\Throwable) {}

            $errorType = match (true) {
                in_array($status, [401, 403], true) => 'AUTH_ERROR',
                $status === 404 => 'INVALID_MODEL',
                in_array($status, [402, 429], true) || str_contains(strtolower($errorMessage), 'quota') || str_contains(strtolower($errorMessage), 'billing') => 'RATE_LIMIT_OR_QUOTA',
                in_array($status, [400, 422], true) => 'MALFORMED_REQUEST',
                in_array($status, [500, 502, 503, 504], true) => 'SERVICE_UNAVAILABLE',
                default => 'API_ERROR',
            };

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
            $isTimeout = ($e instanceof ConnectionException) || str_contains(strtolower($e->getMessage()), 'timed out');
            $errorType = $isTimeout ? 'TIMEOUT' : 'CONNECTION_ERROR';

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

        $output = "{$friendlyReason}\n\n";
        $output .= "However, here is the official policy information retrieved directly from the verified Shofol Connect Knowledge Base:\n\n";
        $output .= "📋 Verified Policy: {$primary->title} [Module: {$primary->module}]\n";
        if (!empty($primary->category)) {
            $output .= "Category: {$primary->category}\n";
        }
        $output .= "Policy Details:\n{$primary->content}\n\n";
        $output .= "(Note: This verified policy excerpt is provided directly from official company documentation while AI generation is temporarily unavailable. If you need further assistance, please open a support ticket.)";

        return trim($output);
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
2. Do NOT extrapolate, assume, or invent features, policies, numbers, or procedures that are not explicitly stated in the context.
3. For payroll and tax questions: never invent specific income tax rates, percentages, or tax amounts; explain that TDS is itemized on the digital payslip and depends on applicable statutory tax rules.
4. If the provided context does not contain sufficient details to answer the question, state:
   "I couldn't find enough information about that in the Shofol Connect knowledge base. Please contact our support team or open a support ticket for further assistance."
5. Be professional, accurate, and concise.

SHOFOL CONNECT KNOWLEDGE BASE CONTEXT:
---
{$context}
---
PROMPT;
    }
}

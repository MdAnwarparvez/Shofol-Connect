<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\KnowledgeArticle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $embeddingModel;
    protected ?int $dimensions;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct()
    {
        $rawKey = Config::get('services.ai.api_key');
        $this->apiKey = !empty($rawKey) ? trim((string) $rawKey) : null;
        $this->baseUrl = rtrim((string) Config::get('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai'), '/');
        $this->embeddingModel = (string) Config::get('services.ai.embedding_model', 'gemini-embedding-2');
        $this->dimensions = Config::get('services.ai.embedding_dimensions') ? (int) Config::get('services.ai.embedding_dimensions') : null;
        $this->timeout = (int) Config::get('services.ai.embedding_timeout', 10);
        $this->connectTimeout = (int) Config::get('services.ai.embedding_connect_timeout', 3);
    }

    /**
     * Check if the embedding service is configured with an API key.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the configured embedding model name.
     */
    public function getModel(): string
    {
        return $this->embeddingModel;
    }

    /**
     * Get the configured dimensions (null if using model default).
     */
    public function getDimensions(): ?int
    {
        return $this->dimensions;
    }

    /**
     * Generate vector embeddings for a given text snippet.
     * Returns a float vector list or null if unconfigured or remote request fails gracefully.
     *
     * @param string $text
     * @return list<float>|null
     */
    public function generateEmbedding(string $text, bool $useCache = true): ?array
    {
        $cleanText = trim($text);

        if (empty($cleanText) || !$this->isConfigured()) {
            return null;
        }

        // Limit input size to prevent API payload limits (approx 8000 characters)
        if (mb_strlen($cleanText) > 8000) {
            $cleanText = mb_substr($cleanText, 0, 8000);
        }

        $cacheKey = 'emb_query_' . md5($this->embeddingModel . ':' . ($this->dimensions ?? 'def') . ':' . mb_strtolower($cleanText));
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                if (app()->environment('local', 'testing') || config('app.debug')) {
                    Log::info('AI Timing: Embedding Generation', [
                        'stage' => 'embedding',
                        'cache_hit' => true,
                        'duration_ms' => 0.0,
                        'model' => $this->embeddingModel,
                    ]);
                }
                return $cached;
            }
        }

        $embStart = microtime(true);

        try {
            $payload = [
                'model' => $this->embeddingModel,
                'input' => $cleanText,
            ];

            if ($this->dimensions !== null && $this->dimensions > 0) {
                $payload['dimensions'] = $this->dimensions;
            }

            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/embeddings", $payload);

            $embDurationMs = round((microtime(true) - $embStart) * 1000, 2);
            if (app()->environment('local', 'testing') || config('app.debug')) {
                Log::info('AI Timing: Embedding Generation', [
                    'stage' => 'embedding',
                    'cache_hit' => false,
                    'duration_ms' => $embDurationMs,
                    'model' => $this->embeddingModel,
                ]);
            }

            if ($response->successful()) {
                $data = $response->json();
                $vector = $data['data'][0]['embedding'] ?? null;

                if (is_array($vector) && !empty($vector)) {
                    $result = array_map('floatval', $vector);
                    if ($useCache) {
                        Cache::put($cacheKey, $result, 3600);
                    }
                    return $result;
                }
            } else {
                $status = $response->status();
                Log::warning("Embedding generation failed with HTTP {$status}.", [
                    'model' => $this->embeddingModel,
                ]);
            }
        } catch (\Throwable $e) {
            $embDurationMs = round((microtime(true) - $embStart) * 1000, 2);
            if (app()->environment('local', 'testing') || config('app.debug')) {
                Log::info('AI Timing: Embedding Generation', [
                    'stage' => 'embedding',
                    'cache_hit' => false,
                    'duration_ms' => $embDurationMs,
                    'model' => $this->embeddingModel,
                ]);
            }
            Log::warning('Embedding generation encountered exception: ' . $this->sanitizeMessage($e->getMessage()));
        }

        return null;
    }

    /**
     * Compute cosine similarity between two vector arrays or embedding envelopes.
     * Guards against model mismatch and dimension differences.
     *
     * @param array<string, mixed>|list<float|int> $vecA
     * @param array<string, mixed>|list<float|int> $vecB
     * @return float Range between -1.0 and 1.0 (or 0.0 for invalid/empty/mismatched vectors)
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        // Model compatibility check: if both specify a model and they differ, vectors are incompatible
        $modelA = $vecA['model'] ?? null;
        $modelB = $vecB['model'] ?? null;
        if (!empty($modelA) && !empty($modelB) && $modelA !== $modelB) {
            return 0.0;
        }

        $vectorA = isset($vecA['vector']) && is_array($vecA['vector']) ? $vecA['vector'] : $vecA;
        $vectorB = isset($vecB['vector']) && is_array($vecB['vector']) ? $vecB['vector'] : $vecB;

        $countA = count($vectorA);
        $countB = count($vectorB);

        if ($countA === 0 || $countA !== $countB) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $countA; $i++) {
            $valA = (float) $vectorA[$i];
            $valB = (float) $vectorB[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        $magnitude = sqrt($normA) * sqrt($normB);

        if ($magnitude == 0.0) {
            return 0.0;
        }

        return round($dotProduct / $magnitude, 6);
    }

    /**
     * Build the text content to be embedded for a knowledge article.
     */
    public function buildArticleEmbeddingText(KnowledgeArticle $article): string
    {
        $parts = [
            "Module: {$article->module}",
            "Category: {$article->category}",
            "Title: {$article->title}",
        ];

        if (!empty($article->question)) {
            $parts[] = "Question: {$article->question}";
        }

        if (!empty($article->keywords)) {
            $parts[] = "Keywords: {$article->keywords}";
        }

        $parts[] = "Content: {$article->content}";

        return implode("\n", $parts);
    }

    /**
     * Build the text content to be embedded for an FAQ.
     */
    public function buildFaqEmbeddingText(Faq $faq): string
    {
        $parts = [
            "Question: {$faq->question}",
        ];

        if (!empty($faq->keywords)) {
            $parts[] = "Keywords: {$faq->keywords}";
        }

        $parts[] = "Answer: {$faq->answer}";

        return implode("\n", $parts);
    }

    /**
     * Generate and persist an embedding for a specific knowledge article.
     * Safe execution: returns false on failure without throwing exceptions.
     */
    public function indexArticle(KnowledgeArticle $article, bool $force = false): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        // Skip if already indexed with the current model unless forced
        if (!$force && !empty($article->embedding)) {
            $storedModel = is_array($article->embedding) ? ($article->embedding['model'] ?? null) : null;
            if ($storedModel === $this->embeddingModel) {
                return true;
            }
        }

        $text = $this->buildArticleEmbeddingText($article);
        $vector = $this->generateEmbedding($text);

        if ($vector !== null && !empty($vector)) {
            $envelope = [
                'model' => $this->embeddingModel,
                'dimensions' => count($vector),
                'vector' => $vector,
                'indexed_at' => now()->toIso8601String(),
            ];

            $article->updateQuietly(['embedding' => $envelope]);
            return true;
        }

        return false;
    }

    /**
     * Generate and persist an embedding for a specific FAQ.
     * Safe execution: returns false on failure without throwing exceptions.
     */
    public function indexFaq(Faq $faq, bool $force = false): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        // Skip if already indexed with current model unless forced
        if (!$force && !empty($faq->embedding)) {
            $storedModel = is_array($faq->embedding) ? ($faq->embedding['model'] ?? null) : null;
            if ($storedModel === $this->embeddingModel) {
                return true;
            }
        }

        $text = $this->buildFaqEmbeddingText($faq);
        $vector = $this->generateEmbedding($text);

        if ($vector !== null && !empty($vector)) {
            $envelope = [
                'model' => $this->embeddingModel,
                'dimensions' => count($vector),
                'vector' => $vector,
                'indexed_at' => now()->toIso8601String(),
            ];

            $faq->updateQuietly(['embedding' => $envelope]);
            return true;
        }

        return false;
    }

    /**
     * Re-index all active knowledge articles and FAQs.
     *
     * @return array{articles: int, faqs: int, skipped: int, failed: int}
     */
    public function reindexAll(bool $force = false): array
    {
        $stats = [
            'articles' => 0,
            'faqs' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if (!$this->isConfigured()) {
            return $stats;
        }

        $articles = KnowledgeArticle::active()->get();
        foreach ($articles as $article) {
            if (!$force && !empty($article->embedding) && ($article->embedding['model'] ?? null) === $this->embeddingModel) {
                $stats['skipped']++;
                continue;
            }

            if ($this->indexArticle($article, $force)) {
                $stats['articles']++;
            } else {
                $stats['failed']++;
            }
        }

        $faqs = Faq::active()->get();
        foreach ($faqs as $faq) {
            if (!$force && !empty($faq->embedding) && ($faq->embedding['model'] ?? null) === $this->embeddingModel) {
                $stats['skipped']++;
                continue;
            }

            if ($this->indexFaq($faq, $force)) {
                $stats['faqs']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Redact API key from log messages.
     */
    protected function sanitizeMessage(string $message): string
    {
        if (!empty($this->apiKey)) {
            return str_replace($this->apiKey, '[REDACTED_API_KEY]', $message);
        }

        return $message;
    }
}

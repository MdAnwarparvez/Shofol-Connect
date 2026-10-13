<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\KnowledgeArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KnowledgeRetrievalService
{
    protected ?EmbeddingService $embeddingService;

    /**
     * Stop words to filter out when tokenizing queries for lexical relevance (English + Bengali).
     */
    protected array $stopWords = [
        'the', 'and', 'for', 'that', 'this', 'with', 'what', 'when', 'where',
        'which', 'who', 'how', 'does', 'can', 'are', 'about', 'from', 'have',
        'has', 'had', 'been', 'will', 'would', 'could', 'should', 'you', 'your',
        'our', 'please', 'tell', 'help', 'give', 'need', 'know', 'some', 'any',
        // Common Bengali query particles & stopwords
        'কি', 'কী', 'কীভাবে', 'কিভাবে', 'এবং', 'বা', 'জন্য', 'থেকে', 'কোন', 'কোনো',
        'এর', 'তে', 'কে', 'এ', 'এই', 'সেই', 'আছে', 'না', 'হয়', 'হবে', 'করতে', 'করা',
        'দিন', 'নাকি', 'দিয়ে', 'একটি', 'কত',
    ];

    /**
     * Minimum relevance score required to consider an item relevant.
     */
    protected int $minimumScoreThreshold = 3;

    public function __construct(?EmbeddingService $embeddingService = null)
    {
        $this->embeddingService = $embeddingService ?? (function_exists('app') ? app(EmbeddingService::class) : new EmbeddingService());
    }

    /**
     * Retrieve and rank knowledge articles and verified FAQs relevant to the user query.
     * Combines lexical keyword matching with vector cosine similarity when embeddings exist.
     *
     * @param string $query
     * @param int $limit
     * @return array{articles: Collection, has_relevant_results: bool, context: string, sources: array}
     */
    public function retrieve(string $query, int $limit = 3): array
    {
        $normalizedQuery = trim(mb_strtolower($query));

        if (empty($normalizedQuery)) {
            return [
                'articles' => collect(),
                'has_relevant_results' => false,
                'context' => '',
                'sources' => [],
            ];
        }

        // Tokenize query into meaningful search words (length >= 2, non-stopwords)
        $tokens = $this->extractTokens($normalizedQuery);

        // Fetch active knowledge articles & FAQs from cache
        $articles = $this->getActiveArticles();
        $faqs = $this->getActiveFaqs();

        if ($articles->isEmpty() && $faqs->isEmpty()) {
            return [
                'articles' => collect(),
                'has_relevant_results' => false,
                'context' => '',
                'sources' => [],
            ];
        }

        // Only call embedding API if at least one article or FAQ in the knowledge base has stored embeddings
        $hasAnyEmbeddings = $articles->contains(fn ($a) => !empty($a->embedding))
            || $faqs->contains(fn ($f) => !empty($f->embedding));

        $queryEmbedding = null;
        if ($hasAnyEmbeddings && $this->embeddingService && $this->embeddingService->isConfigured()) {
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
        }

        $scoredCandidates = collect();

        // 1. Score active knowledge articles
        foreach ($articles as $article) {
            $score = $this->calculateArticleScore($article, $normalizedQuery, $tokens);

            // Hybrid scoring: incorporate vector cosine similarity if embeddings are present
            if (!empty($queryEmbedding) && !empty($article->embedding) && is_array($article->embedding)) {
                $sim = $this->embeddingService->cosineSimilarity($queryEmbedding, $article->embedding);
                if ($sim >= 0.70) {
                    $score += (int) round($sim * 35);
                } elseif ($sim >= 0.45) {
                    $score += (int) round($sim * 15);
                }
            }

            if ($score >= $this->minimumScoreThreshold) {
                $scoredCandidates->push([
                    'type' => 'article',
                    'model' => $article,
                    'module' => $article->module,
                    'category' => $article->category,
                    'score' => $score,
                ]);
            }
        }

        // 2. Score active FAQs
        foreach ($faqs as $faq) {
            $score = $this->calculateFaqScore($faq, $normalizedQuery, $tokens);

            // Hybrid scoring: incorporate vector cosine similarity if embeddings are present
            if (!empty($queryEmbedding) && !empty($faq->embedding) && is_array($faq->embedding)) {
                $sim = $this->embeddingService->cosineSimilarity($queryEmbedding, $faq->embedding);
                if ($sim >= 0.70) {
                    $score += (int) round($sim * 35);
                } elseif ($sim >= 0.45) {
                    $score += (int) round($sim * 15);
                }
            }

            if ($score >= $this->minimumScoreThreshold) {
                $scoredCandidates->push([
                    'type' => 'faq',
                    'model' => $faq,
                    'module' => $faq->shofolModule?->code ?? $faq->shofolModule?->name ?? 'General',
                    'category' => $faq->knowledgeCategory?->name ?? 'General',
                    'score' => $score,
                ]);
            }
        }

        // 3. Sort candidates by relevance score descending
        $sorted = $scoredCandidates->sortByDesc('score');

        // 4. Deduplicate to avoid repetitive responses when the same policy exists in both Articles and FAQs
        $deduplicated = collect();
        $seenSignatures = [];

        foreach ($sorted as $candidate) {
            $mod = mb_strtolower(trim($candidate['module']));
            $cat = mb_strtolower(trim($candidate['category']));
            $sig = "{$mod}|{$cat}";

            // If an authoritative article already covers this exact module & category with higher/equal score,
            // or if an FAQ on the same topic is already included, skip redundant duplicate
            if (isset($seenSignatures[$sig])) {
                // If the query specifically matches a different question or title, allow distinct entries
                $existing = $seenSignatures[$sig];
                if ($this->isRedundantDuplicate($candidate['model'], $existing['model'])) {
                    continue;
                }
            }

            $seenSignatures[$sig] = $candidate;
            $deduplicated->push($candidate['model']);

            if ($deduplicated->count() >= $limit) {
                break;
            }
        }

        $hasRelevant = $deduplicated->isNotEmpty();
        $context = $hasRelevant ? $this->buildContextString($deduplicated) : '';
        $sources = $hasRelevant ? $this->formatSources($deduplicated) : [];

        return [
            'articles' => $deduplicated,
            'has_relevant_results' => $hasRelevant,
            'context' => $context,
            'sources' => $sources,
        ];
    }

    /**
     * Check if two candidates represent redundant duplicate policy text.
     */
    protected function isRedundantDuplicate(mixed $itemA, mixed $itemB): bool
    {
        $textA = mb_strtolower(trim(($itemA instanceof KnowledgeArticle ? $itemA->title . ' ' . $itemA->question : $itemA->question)));
        $textB = mb_strtolower(trim(($itemB instanceof KnowledgeArticle ? $itemB->title . ' ' . $itemB->question : $itemB->question)));

        $tokensA = $this->extractTokens($textA);
        $tokensB = $this->extractTokens($textB);

        if (empty($tokensA) || empty($tokensB)) {
            return false;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $minCount = min(count($tokensA), count($tokensB));

        // High token overlap (>= 60%) in the same category indicates redundant policy duplicate
        return ($minCount > 0) && (count($intersection) / $minCount >= 0.6);
    }

    /**
     * Calculate lexical relevance score for a KnowledgeArticle.
     */
    protected function calculateArticleScore(KnowledgeArticle $article, string $query, array $tokens): int
    {
        $score = 0;

        $title = mb_strtolower($article->title);
        $question = mb_strtolower($article->question ?? '');
        $keywords = mb_strtolower($article->keywords ?? '');
        $category = mb_strtolower($article->category);
        $content = mb_strtolower($article->content);
        $module = mb_strtolower($article->module);

        // 1. Direct phrase matching
        if (!empty($query)) {
            if (str_contains($title, $query)) {
                $score += 20;
            }
            if (!empty($question) && str_contains($question, $query)) {
                $score += 20;
            }
            if (!empty($keywords) && str_contains($keywords, $query)) {
                $score += 15;
            }
            if (str_contains($content, $query)) {
                $score += 8;
            }
        }

        // 2. Token / keyword matches
        foreach ($tokens as $token) {
            if (str_contains($module, $token)) {
                $score += 5;
            }
            if (str_contains($category, $token)) {
                $score += 4;
            }
            if (str_contains($title, $token)) {
                $score += 6;
            }
            if (!empty($question) && str_contains($question, $token)) {
                $score += 6;
            }
            if (!empty($keywords) && str_contains($keywords, $token)) {
                $score += 5;
            }
            if (str_contains($content, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * Calculate lexical relevance score for an Faq model.
     */
    protected function calculateFaqScore(Faq $faq, string $query, array $tokens): int
    {
        $score = 0;

        $question = mb_strtolower($faq->question);
        $answer = mb_strtolower($faq->answer);
        $keywords = mb_strtolower($faq->keywords ?? '');
        $module = mb_strtolower($faq->shofolModule?->name ?? $faq->shofolModule?->code ?? '');
        $category = mb_strtolower($faq->knowledgeCategory?->name ?? '');

        // 1. Direct phrase matching
        if (!empty($query)) {
            if (str_contains($question, $query)) {
                $score += 25; // Exact question match gives strong boost
            }
            if (!empty($keywords) && str_contains($keywords, $query)) {
                $score += 15;
            }
            if (str_contains($answer, $query)) {
                $score += 8;
            }
        }

        // 2. Token / keyword matches
        foreach ($tokens as $token) {
            if (!empty($module) && str_contains($module, $token)) {
                $score += 5;
            }
            if (!empty($category) && str_contains($category, $token)) {
                $score += 4;
            }
            if (str_contains($question, $token)) {
                $score += 7;
            }
            if (!empty($keywords) && str_contains($keywords, $token)) {
                $score += 5;
            }
            if (str_contains($answer, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * Backward-compatible alias for calculateArticleScore.
     */
    protected function calculateRelevanceScore(KnowledgeArticle $article, string $query, array $tokens): int
    {
        return $this->calculateArticleScore($article, $query, $tokens);
    }

    /**
     * Extract normalized tokens from input query.
     */
    protected function extractTokens(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{M}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        $tokens = [];
        foreach ($words as $word) {
            $w = mb_strtolower($word);
            if (mb_strlen($w) >= 2 && !in_array($w, $this->stopWords, true)) {
                $tokens[] = $w;
            }
        }

        return array_unique($tokens);
    }

    /**
     * Construct structured prompt context string from retrieved items.
     */
    protected function buildContextString(Collection $items): string
    {
        $sections = [];

        foreach ($items as $index => $item) {
            $num = $index + 1;
            if ($item instanceof KnowledgeArticle) {
                $entry = "[Source {$num}] Module: {$item->module} | Category: {$item->category} | Title: {$item->title}\n";
                if (!empty($item->question)) {
                    $entry .= "Primary Question: {$item->question}\n";
                }
                $entry .= "Content / Policy: {$item->content}\n";
                if (!empty($item->keywords)) {
                    $entry .= "Keywords: {$item->keywords}\n";
                }
            } elseif ($item instanceof Faq) {
                $modName = $item->shofolModule?->name ?? $item->shofolModule?->code ?? 'General';
                $catName = $item->knowledgeCategory?->name ?? 'General';
                $entry = "[Source {$num}] (FAQ) Module: {$modName} | Category: {$catName} | Question: {$item->question}\n";
                $entry .= "Answer / Verified Policy: {$item->answer}\n";
                if (!empty($item->keywords)) {
                    $entry .= "Keywords: {$item->keywords}\n";
                }
            }
            $sections[] = trim($entry);
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Format source item metadata for citations.
     */
    protected function formatSources(Collection $items): array
    {
        return $items->map(function ($item) {
            if ($item instanceof KnowledgeArticle) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'module' => $item->module,
                    'category' => $item->category,
                    'type' => 'article',
                ];
            }

            return [
                'id' => $item->id,
                'title' => $item->question,
                'module' => $item->shofolModule?->name ?? $item->shofolModule?->code ?? 'General',
                'category' => $item->knowledgeCategory?->name ?? 'General',
                'type' => 'faq',
            ];
        })->values()->toArray();
    }

    /**
     * Retrieve active knowledge articles with caching layer.
     */
    public function getActiveArticles(): Collection
    {
        return Cache::remember('kb_active_articles', 3600, function () {
            return KnowledgeArticle::active()->get();
        });
    }

    /**
     * Retrieve active FAQs with caching layer.
     */
    public function getActiveFaqs(): Collection
    {
        return Cache::remember('kb_active_faqs', 3600, function () {
            return Faq::active()->with(['shofolModule', 'knowledgeCategory'])->get();
        });
    }

    /**
     * Invalidate both knowledge retrieval caches.
     */
    public function clearCache(): void
    {
        Cache::forget('kb_active_articles');
        Cache::forget('kb_active_faqs');
    }
}

<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use Illuminate\Support\Collection;

class KnowledgeRetrievalService
{
    /**
     * Stop words to filter out when tokenizing queries for lexical relevance.
     */
    protected array $stopWords = [
        'the', 'and', 'for', 'that', 'this', 'with', 'what', 'when', 'where',
        'which', 'who', 'how', 'does', 'can', 'are', 'about', 'from', 'have',
        'has', 'had', 'been', 'will', 'would', 'could', 'should', 'you', 'your',
        'our', 'please', 'tell', 'help', 'give', 'need', 'know', 'some', 'any',
    ];

    /**
     * Minimum relevance score required to consider an article relevant.
     */
    protected int $minimumScoreThreshold = 3;

    /**
     * Retrieve and rank knowledge articles relevant to the user query.
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

        // Tokenize query into meaningful search words (length >= 3, non-stopwords)
        $tokens = $this->extractTokens($normalizedQuery);

        // Fetch active articles for candidate scoring
        $candidates = KnowledgeArticle::active()->get();

        $scoredArticles = $candidates->map(function (KnowledgeArticle $article) use ($normalizedQuery, $tokens) {
            $score = $this->calculateRelevanceScore($article, $normalizedQuery, $tokens);
            return [
                'article' => $article,
                'score' => $score,
            ];
        })
        ->filter(fn ($item) => $item['score'] >= $this->minimumScoreThreshold)
        ->sortByDesc('score')
        ->take($limit)
        ->pluck('article');

        $hasRelevant = $scoredArticles->isNotEmpty();
        $context = $hasRelevant ? $this->buildContextString($scoredArticles) : '';
        $sources = $hasRelevant ? $this->formatSources($scoredArticles) : [];

        return [
            'articles' => $scoredArticles,
            'has_relevant_results' => $hasRelevant,
            'context' => $context,
            'sources' => $sources,
        ];
    }

    /**
     * Calculate lexical relevance score for an article against query and tokens.
     */
    protected function calculateRelevanceScore(KnowledgeArticle $article, string $query, array $tokens): int
    {
        $score = 0;

        $title = mb_strtolower($article->title);
        $question = mb_strtolower($article->question ?? '');
        $keywords = mb_strtolower($article->keywords ?? '');
        $category = mb_strtolower($article->category);
        $content = mb_strtolower($article->content);
        $module = mb_strtolower($article->module);

        // 1. Direct phrase or sub-phrase matching (highest relevance)
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
     * Extract normalized tokens from input query.
     */
    protected function extractTokens(string $query): array
    {
        // Split on non-alphanumeric characters
        $words = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        $tokens = [];
        foreach ($words as $word) {
            $w = mb_strtolower($word);
            if (mb_strlen($w) >= 3 && !in_array($w, $this->stopWords, true)) {
                $tokens[] = $w;
            }
        }

        return array_unique($tokens);
    }

    /**
     * Construct structured prompt context string from retrieved articles.
     */
    protected function buildContextString(Collection $articles): string
    {
        $sections = [];

        foreach ($articles as $index => $article) {
            $num = $index + 1;
            $entry = "[Source {$num}] Module: {$article->module} | Category: {$article->category} | Title: {$article->title}\n";
            if (!empty($article->question)) {
                $entry .= "Primary Question: {$article->question}\n";
            }
            $entry .= "Content / Policy: {$article->content}\n";
            if (!empty($article->keywords)) {
                $entry .= "Keywords: {$article->keywords}\n";
            }
            $sections[] = trim($entry);
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Format source article metadata for citations.
     */
    protected function formatSources(Collection $articles): array
    {
        return $articles->map(function (KnowledgeArticle $article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'module' => $article->module,
                'category' => $article->category,
            ];
        })->values()->toArray();
    }
}

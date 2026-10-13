<?php

namespace App\Console\Commands;

use App\Models\Faq;
use App\Models\KnowledgeArticle;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class ReindexKnowledgeEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kb:reindex
                            {--force : Re-index items even if already embedded with current model}
                            {--missing : Only index items that have no embedding at all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and persist vector embeddings for active Knowledge Articles and FAQs';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingService $embeddingService): int
    {
        if (!$embeddingService->isConfigured()) {
            $this->error('AI Embedding Service is not configured. Please set AI_API_KEY or GEMINI_API_KEY in your environment.');
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $onlyMissing = (bool) $this->option('missing');
        $currentModel = $embeddingService->getModel();

        $this->info("Starting vector embedding re-indexing using model: [{$currentModel}]");

        $articles = KnowledgeArticle::active()->get();
        $faqs = Faq::active()->get();

        $stats = [
            'articles_indexed' => 0,
            'faqs_indexed' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $this->info("Processing {$articles->count()} active knowledge articles...");
        foreach ($articles as $article) {
            $hasEmbedding = !empty($article->embedding);
            $hasSameModel = $hasEmbedding && is_array($article->embedding) && ($article->embedding['model'] ?? null) === $currentModel;

            if ($onlyMissing && $hasEmbedding) {
                $stats['skipped']++;
                continue;
            }

            if (!$force && $hasSameModel) {
                $stats['skipped']++;
                continue;
            }

            $success = $embeddingService->indexArticle($article, $force);
            if ($success) {
                $stats['articles_indexed']++;
                $this->line("  ✓ Article #{$article->id}: {$article->title}");
            } else {
                $stats['failed']++;
                $this->warn("  ✗ Article #{$article->id} failed to embed.");
            }
        }

        $this->info("Processing {$faqs->count()} active FAQs...");
        foreach ($faqs as $faq) {
            $hasEmbedding = !empty($faq->embedding);
            $hasSameModel = $hasEmbedding && is_array($faq->embedding) && ($faq->embedding['model'] ?? null) === $currentModel;

            if ($onlyMissing && $hasEmbedding) {
                $stats['skipped']++;
                continue;
            }

            if (!$force && $hasSameModel) {
                $stats['skipped']++;
                continue;
            }

            $success = $embeddingService->indexFaq($faq, $force);
            if ($success) {
                $stats['faqs_indexed']++;
                $this->line("  ✓ FAQ #{$faq->id}: {$faq->question}");
            } else {
                $stats['failed']++;
                $this->warn("  ✗ FAQ #{$faq->id} failed to embed.");
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Embedding Model', $currentModel],
                ['Articles Indexed', $stats['articles_indexed']],
                ['FAQs Indexed', $stats['faqs_indexed']],
                ['Skipped (Up-to-date)', $stats['skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        $this->info('Knowledge base re-indexing process finished.');
        return self::SUCCESS;
    }
}

<?php

use App\Models\Faq;
use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\EmbeddingService;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('EmbeddingService calculates accurate cosine similarity across standard vectors', function () {
    $service = new EmbeddingService();

    // 1. Identical vectors -> 1.0
    $vecA = [1.0, 2.0, 3.0];
    $vecB = [1.0, 2.0, 3.0];
    expect($service->cosineSimilarity($vecA, $vecB))->toEqual(1.0);

    // 2. Orthogonal vectors -> 0.0
    $vecOrthA = [1.0, 0.0];
    $vecOrthB = [0.0, 1.0];
    expect($service->cosineSimilarity($vecOrthA, $vecOrthB))->toEqual(0.0);

    // 3. Opposite vectors -> -1.0
    $vecOppA = [1.0, 0.0];
    $vecOppB = [-1.0, 0.0];
    expect($service->cosineSimilarity($vecOppA, $vecOppB))->toEqual(-1.0);

    // 4. Zero vector handling -> 0.0
    $vecZero = [0.0, 0.0, 0.0];
    expect($service->cosineSimilarity($vecA, $vecZero))->toEqual(0.0);

    // 5. Dimension mismatch handling -> 0.0
    expect($service->cosineSimilarity([1.0, 2.0], [1.0, 2.0, 3.0]))->toEqual(0.0);
});

test('EmbeddingService guards against incompatible models and dimension mismatches', function () {
    $service = new EmbeddingService();

    $envelopeModelA = [
        'model' => 'gemini-embedding-2',
        'dimensions' => 3,
        'vector' => [0.1, 0.2, 0.3],
    ];

    $envelopeModelB = [
        'model' => 'text-embedding-3-small',
        'dimensions' => 3,
        'vector' => [0.1, 0.2, 0.3],
    ];

    // Different models must NOT be compared against each other
    expect($service->cosineSimilarity($envelopeModelA, $envelopeModelB))->toEqual(0.0);

    // Different dimensions on raw vectors must return 0.0
    $envelopeDiffDim = [
        'model' => 'gemini-embedding-2',
        'dimensions' => 4,
        'vector' => [0.1, 0.2, 0.3, 0.4],
    ];
    expect($service->cosineSimilarity($envelopeModelA, $envelopeDiffDim))->toEqual(0.0);
});

test('EmbeddingService generates vector float array via OpenAI-compatible endpoint with gemini-embedding-2', function () {
    Config::set('services.ai.api_key', 'test-gemini-key');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');
    Config::set('services.ai.embedding_dimensions', 768);

    $mockVector = array_fill(0, 768, 0.015);

    $recordedPayload = null;
    Http::fake([
        '*/embeddings' => function ($request) use (&$recordedPayload, $mockVector) {
            $recordedPayload = $request->data();
            return Http::response([
                'object' => 'list',
                'data' => [
                    [
                        'object' => 'embedding',
                        'index' => 0,
                        'embedding' => $mockVector,
                    ],
                ],
            ], 200);
        },
    ]);

    $service = new EmbeddingService();
    expect($service->isConfigured())->toBeTrue();
    expect($service->getModel())->toEqual('gemini-embedding-2');
    expect($service->getDimensions())->toEqual(768);

    $embedding = $service->generateEmbedding('How do I apply for annual leave?');

    expect($embedding)->toBeArray()
        ->and($embedding)->toHaveCount(768);
    expect($recordedPayload['model'])->toEqual('gemini-embedding-2');
    expect($recordedPayload['dimensions'])->toEqual(768);
});

test('EmbeddingService returns null gracefully on remote API failure or unconfigured state', function () {
    Config::set('services.ai.api_key', null);
    $service = new EmbeddingService();
    expect($service->isConfigured())->toBeFalse();
    expect($service->generateEmbedding('Some test text'))->toBeNull();

    // Configured but API returns 500 error
    Config::set('services.ai.api_key', 'test-key');
    Http::fake([
        '*/embeddings' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $serviceConfigured = new EmbeddingService();
    $result = $serviceConfigured->generateEmbedding('Some test text');
    expect($result)->toBeNull();
});

test('KnowledgeRetrievalService falls back gracefully to pure lexical scoring when embeddings are unconfigured', function () {
    Config::set('services.ai.api_key', null);

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Taxation',
        'title' => 'Bangladesh Statutory Income Tax Slabs',
        'question' => 'What are the personal tax slabs for FY 2024-25?',
        'content' => 'First BDT 350,000 is tax free. Next BDT 100,000 is 5%.',
        'keywords' => 'tax, income tax, slabs, payroll',
        'status' => 'active',
        'embedding' => null,
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);
    $results = $retrievalService->retrieve('statutory tax slabs');

    expect($results['has_relevant_results'])->toBeTrue()
        ->and($results['articles'])->toHaveCount(1)
        ->and($results['articles']->first()->title)->toEqual('Bangladesh Statutory Income Tax Slabs');
});

test('KnowledgeRetrievalService hybrid search applies vector boost when embeddings match', function () {
    Config::set('services.ai.api_key', 'test-embedding-key');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');

    $sampleVector = [0.1, 0.2, 0.8, 0.5];

    // Article has stored embedding envelope
    $article = KnowledgeArticle::factory()->create([
        'module' => 'CRM',
        'category' => 'Sales',
        'title' => 'Enterprise Sales Deal Closing Protocols',
        'question' => 'How to finalize enterprise contracts?',
        'content' => 'Requires two director sign-offs and legal compliance review.',
        'keywords' => 'sales, contract, enterprise',
        'status' => 'active',
        'embedding' => [
            'model' => 'gemini-embedding-2',
            'dimensions' => 4,
            'vector' => $sampleVector,
        ],
    ]);

    Http::fake([
        '*/embeddings' => Http::response([
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'index' => 0,
                    'embedding' => $sampleVector, // Same vector -> cosine similarity 1.0
                ],
            ],
        ], 200),
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);
    $results = $retrievalService->retrieve('enterprise contracts approval');

    expect($results['has_relevant_results'])->toBeTrue()
        ->and($results['articles']->first()->id)->toEqual($article->id);
});

test('KnowledgeRetrievalService normalizes Bengali stopwords in query tokenization', function () {
    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'ছুটি নীতিমালা (Leave Policy)',
        'question' => 'ছুটির আবেদন কীভাবে করব?',
        'content' => 'কর্মীরা পোর্টাল থেকে ছুটির আবেদন করতে পারবেন।',
        'keywords' => 'ছুটি, আবেদন, leave',
        'status' => 'active',
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);
    // "কীভাবে" and "করা" are Bengali stopwords, "ছুটি" is the core content token
    $results = $retrievalService->retrieve('ছুটির আবেদন কীভাবে করা যায়');

    expect($results['has_relevant_results'])->toBeTrue()
        ->and($results['articles'])->toHaveCount(1)
        ->and($results['articles']->first()->title)->toEqual('ছুটি নীতিমালা (Leave Policy)');
});

test('Active articles and FAQs are cached and invalidated when models are updated or deleted', function () {
    $retrievalService = app(KnowledgeRetrievalService::class);
    $retrievalService->clearCache();

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Initial Title',
        'status' => 'active',
    ]);

    // First retrieval primes the cache
    $articlesFirst = $retrievalService->getActiveArticles();
    expect($articlesFirst)->toHaveCount(1)
        ->and(Cache::has('kb_active_articles'))->toBeTrue();

    // Updating model clears the cache via booted model event
    $article->update(['title' => 'Updated Title']);
    expect(Cache::has('kb_active_articles'))->toBeFalse();

    // Deleting model also invalidates cache
    $article->delete();
    expect(Cache::has('kb_active_articles'))->toBeFalse();
});

test('Admin article creation triggers auto-indexing when configured without failing on API outage', function () {
    $admin = User::factory()->admin()->create();

    // Scenario 1: API is configured and responds successfully
    Config::set('services.ai.api_key', 'test-key');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');

    $mockVec = [0.12, 0.34, 0.56];
    Http::fake([
        '*/embeddings' => Http::response([
            'object' => 'list',
            'data' => [['object' => 'embedding', 'index' => 0, 'embedding' => $mockVec]],
        ], 200),
    ]);

    $response = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Maternity Leave Policy',
        'question' => 'What is the maternity leave duration?',
        'content' => 'Female employees are entitled to 16 weeks of paid maternity leave.',
        'keywords' => 'maternity, leave, hrm',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('admin.knowledge-articles.index'));
    $article = KnowledgeArticle::where('title', 'Maternity Leave Policy')->first();
    expect($article)->not->toBeNull();
    expect($article->embedding)->toBeArray()
        ->and($article->embedding['model'])->toEqual('gemini-embedding-2')
        ->and($article->embedding['vector'])->toEqual($mockVec);

    // Scenario 2: Remote API fails with 500 error; article creation MUST still succeed without error
    Http::fake([
        '*/embeddings' => Http::response(['error' => 'API Unavailable'], 500),
    ]);

    $responseFail = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Paternity Leave Policy',
        'question' => 'What is the paternity leave duration?',
        'content' => 'Male employees are entitled to 5 days of paternity leave.',
        'keywords' => 'paternity, leave, hrm',
        'status' => 'active',
    ]);

    $responseFail->assertRedirect(route('admin.knowledge-articles.index'));
    $paternityArticle = KnowledgeArticle::where('title', 'Paternity Leave Policy')->first();
    expect($paternityArticle)->not->toBeNull();
});

test('Artisan command kb:reindex processes active knowledge base items and updates embeddings', function () {
    Config::set('services.ai.api_key', 'test-key');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Probation Evaluation Policy',
        'status' => 'active',
        'embedding' => null,
    ]);

    $mockVec = [0.01, 0.02, 0.03];
    Http::fake([
        '*/embeddings' => Http::response([
            'object' => 'list',
            'data' => [['object' => 'embedding', 'index' => 0, 'embedding' => $mockVec]],
        ], 200),
    ]);

    $exitCode = Artisan::call('kb:reindex', ['--force' => true]);
    expect($exitCode)->toEqual(0);

    $article->refresh();
    expect($article->embedding)->toBeArray()
        ->and($article->embedding['model'])->toEqual('gemini-embedding-2')
        ->and($article->embedding['vector'])->toEqual($mockVec);
});

test('Updating article content invalidates stale embedding if re-indexing fails', function () {
    $admin = User::factory()->admin()->create();

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Original Title',
        'question' => 'Original Question',
        'content' => 'Original Content',
        'keywords' => 'original',
        'status' => 'active',
        'embedding' => [
            'model' => 'gemini-embedding-2',
            'dimensions' => 3,
            'vector' => [0.1, 0.2, 0.3],
        ],
    ]);

    // Remote API fails during update
    Config::set('services.ai.api_key', 'test-key');
    Http::fake([
        '*/embeddings' => Http::response(['error' => 'Rate Limit'], 429),
    ]);

    $response = $this->actingAs($admin)->put("/admin/knowledge-articles/{$article->id}", [
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Completely Rewritten Title',
        'question' => 'Completely Rewritten Question',
        'content' => 'Completely Rewritten Content',
        'keywords' => 'rewritten',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('admin.knowledge-articles.show', $article));
    $article->refresh();
    expect($article->title)->toEqual('Completely Rewritten Title');
    // Stale embedding must be cleared to prevent stale vector matches
    expect($article->embedding)->toBeNull();
});

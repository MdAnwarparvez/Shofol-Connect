<?php

use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\AIService;
use App\Services\EmbeddingService;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('AIService applies configured request timeout and connection timeout', function () {
    Config::set('services.ai.timeout', 15);
    Config::set('services.ai.connect_timeout', 5);
    Config::set('services.ai.max_tokens', 800);

    $aiService = app(AIService::class);

    expect($aiService->getTimeout())->toBe(15);
    expect($aiService->getConnectTimeout())->toBe(5);
    expect($aiService->getMaxTokens())->toBe(800);
});

test('EmbeddingService caches query embeddings and avoids duplicate external API calls', function () {
    Config::set('services.ai.api_key', 'test-embedding-key');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    $callCount = 0;
    Http::fake([
        '*/embeddings' => function () use (&$callCount) {
            $callCount++;
            return Http::response([
                'data' => [
                    [
                        'embedding' => array_fill(0, 10, 0.1),
                    ],
                ],
            ], 200);
        },
    ]);

    $embeddingService = app(EmbeddingService::class);

    // First call: should hit remote API
    $emb1 = $embeddingService->generateEmbedding('How to apply for casual leave?');
    expect($emb1)->toBeArray();
    expect($callCount)->toBe(1);

    // Second call with same normalized query: should hit cache without calling API
    $emb2 = $embeddingService->generateEmbedding('How to apply for casual leave?');
    expect($emb2)->toBeArray();
    expect($callCount)->toBe(1); // Call count remains 1, verified cache hit!

    // Third call with different casing: should also hit cache
    $emb3 = $embeddingService->generateEmbedding('HOW TO APPLY FOR CASUAL LEAVE?');
    expect($emb3)->toBeArray();
    expect($callCount)->toBe(1); // Still 1!
});

test('chat send supports Server-Sent Events (SSE) streaming responses', function () {
    Config::set('services.ai.api_key', 'test-valid-key');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual & Casual Leave Policy',
        'question' => 'How many days of annual leave are allowed?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'status' => 'active',
    ]);

    $rawSse = "data: {\"choices\":[{\"delta\":{\"content\":\"Employees receive \"}}]}\n\n" .
              "data: {\"choices\":[{\"delta\":{\"content\":\"14 days annual leave.\"}}]}\n\n" .
              "data: [DONE]\n\n";

    Http::fake([
        '*/chat/completions' => Http::response($rawSse, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/send', [
        'message' => 'How many days of annual leave are allowed in HRM?',
        'stream' => true,
    ], [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    $streamContent = $response->streamedContent();

    expect($streamContent)->toContain('event: user_message');
    expect($streamContent)->toContain('event: token');
    expect($streamContent)->toContain('event: done');
    expect($streamContent)->toContain('14 days annual leave');

    // Verify conversation message was persisted
    $this->assertDatabaseHas('chat_messages', [
        'role' => 'assistant',
        'content' => 'Employees receive 14 days annual leave.',
    ]);
});

test('streamed request gracefully falls back to verified policy on upstream provider timeout', function () {
    Config::set('services.ai.api_key', 'test-valid-key');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Salary Cycle',
        'title' => 'Monthly Salary Disbursement',
        'question' => 'When is salary disbursed?',
        'content' => 'Salary is disbursed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => fn () => throw new ConnectionException('Connection timed out after 15s'),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/send', [
        'message' => 'When is monthly salary disbursed?',
        'stream' => true,
    ], [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    $streamContent = $response->streamedContent();

    expect($streamContent)->toContain('event: done');
    expect($streamContent)->toContain('timed out');
    expect($streamContent)->toContain('Monthly Salary Disbursement');
    expect($streamContent)->toContain('Salary is disbursed on the final working day');

    // Verify fallback message was persisted in conversation
    $this->assertDatabaseHas('chat_messages', [
        'role' => 'assistant',
    ]);
});

test('unsupported question in streaming mode returns immediate fallback without querying LLM', function () {
    Http::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/send', [
        'message' => 'How do I cook Italian pizza while flying a spaceship?',
        'stream' => true,
    ], [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    $streamContent = $response->streamedContent();

    expect($streamContent)->toContain('event: done');
    expect($streamContent)->toContain(AIService::FALLBACK_MESSAGE);

    // Verify LLM was never called
    Http::assertNothingSent();
});

test('KnowledgeRetrievalService bypasses remote query embedding API when lexical matching score is sufficient', function () {
    Config::set('services.ai.api_key', 'test-valid-key');
    Config::set('services.ai.embedding_model', 'gemini-embedding-2');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Accounting',
        'category' => 'Billing',
        'title' => 'Customer Invoicing Guidelines',
        'question' => 'How do I generate and issue client invoices?',
        'content' => 'Invoices must be created via the Sales module with NET 30 terms.',
        'status' => 'active',
        'embedding' => [
            'model' => 'gemini-embedding-2',
            'dimensions' => 4,
            'vector' => [0.1, 0.2, 0.3, 0.4],
        ],
    ]);

    $embeddingCallCount = 0;
    Http::fake([
        '*/embeddings' => function () use (&$embeddingCallCount) {
            $embeddingCallCount++;
            return Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3, 0.4]]]], 200);
        },
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);

    // Query directly matches title & question -> lexical score >= 30
    $result = $retrievalService->retrieve('How do I generate and issue client invoices?');

    expect($result['has_relevant_results'])->toBeTrue()
        ->and($result['articles']->first()->id)->toBe($article->id);

    // Verify remote embedding API was NOT called, saving network latency!
    expect($embeddingCallCount)->toBe(0);
});

test('stream failure on upstream 429 does not execute duplicate LLM calls or duplicate retrieval', function () {
    Config::set('services.ai.api_key', 'test-valid-key');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Emergency Leave Policy',
        'question' => 'What is the procedure for emergency leave?',
        'content' => 'Employees must notify HR within 24 hours of emergency leave.',
        'status' => 'active',
    ]);

    $llmCallCount = 0;
    Http::fake([
        '*/chat/completions' => function () use (&$llmCallCount) {
            $llmCallCount++;
            return Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Resource has been exhausted (e.g. check quota).',
                ],
            ], 429);
        },
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/send', [
        'message' => 'What is the procedure for emergency leave in HRM?',
        'stream' => true,
    ], [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    $streamContent = $response->streamedContent();

    expect($streamContent)->toContain('event: done');
    expect($streamContent)->toContain('quota limit reached');
    expect($streamContent)->toContain('Emergency Leave Policy');
    expect($streamContent)->toContain('Employees must notify HR within 24 hours');

    // Crucial: exactly ONE call was made, NO duplicate second attempt!
    expect($llmCallCount)->toBe(1);
});

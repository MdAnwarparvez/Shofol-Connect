<?php

use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Configure Gemini as primary provider and Ollama as local fallback
    Config::set('services.ai.provider', 'gemini');
    Config::set('services.ai.api_key', 'test-gemini-key');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.fallback_to_ollama', true);

    Config::set('services.ollama.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ollama.model', 'qwen2.5:7b');
    Config::set('services.ollama.api_key', 'ollama');
    Config::set('services.ollama.fallback_enabled', true);
});

test('gemini is primary provider and ollama is configured independently as fallback', function () {
    $aiService = app(AIService::class);

    expect($aiService->getProvider())->toBe('gemini');
    expect($aiService->getModel())->toBe('gemini-3.8-flash');
    expect($aiService->getBaseUrl())->toBe('https://generativelanguage.googleapis.com/v1beta/openai');
    expect($aiService->isFallbackEnabled())->toBeTrue();
    expect($aiService->getOllamaModel())->toBe('qwen2.5:7b');
    expect($aiService->getOllamaBaseUrl())->toBe('http://127.0.0.1:11434/v1');
});

test('gemini succeeds: ollama is not called', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual Leave Guidelines',
        'question' => 'How many days of annual leave do employees receive?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'keywords' => 'annual leave, pto, vacation',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'chatcmpl-gemini-success',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Employees receive 14 days of annual leave per year according to the Shofol Connect HRM policy.',
                    ],
                ],
            ],
        ], 200),
        'http://127.0.0.1:11434/*' => Http::response([], 500),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days of annual leave do employees receive?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['answer'])->toContain('14 days of annual leave');
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['title'])->toBe($article->title);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://generativelanguage.googleapis.com'));
    Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'http://127.0.0.1:11434'));
});

test('gemini returns HTTP 429: ollama fallback is attempted and succeeds', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual Leave Guidelines',
        'question' => 'How many days of annual leave do employees receive?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'keywords' => 'annual leave, pto, vacation',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'Resource has been exhausted (e.g. check quota).',
                'status' => 'RESOURCE_EXHAUSTED',
            ],
        ], 429),
        'http://127.0.0.1:11434/*' => Http::response([
            'id' => 'chatcmpl-ollama-fallback',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Full-time employees receive 14 days of annual leave annually based on the verified HRM leave guidelines.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days of annual leave do employees receive?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['used_fallback_provider'] ?? null)->toBe('ollama');
    expect($result['answer'])->toContain('14 days of annual leave');
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['title'])->toBe($article->title);

    // Verify Gemini was attempted first
    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://generativelanguage.googleapis.com'));

    // Verify Ollama was called as fallback with correct headers and payload
    Http::assertSent(function ($request) use ($article) {
        if (!str_starts_with($request->url(), 'http://127.0.0.1:11434')) {
            return false;
        }

        $authHeader = $request->header('Authorization')[0] ?? '';
        $data = $request->data();

        return $authHeader === 'Bearer ollama'
            && ($data['model'] ?? '') === 'qwen2.5:7b'
            && str_contains(json_encode($data['messages']), $article->title);
    });
});

test('gemini returns HTTP 503: ollama fallback is attempted and succeeds', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'Accounting',
        'category' => 'Invoicing',
        'title' => 'Invoice Payment Terms',
        'question' => 'What are the default payment terms for invoices?',
        'content' => 'Standard payment terms are Net 30 days from invoice issuance.',
        'keywords' => 'invoice, payment terms, net 30, billing',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 503,
                'message' => 'The model is overloaded. Please try again later.',
                'status' => 'UNAVAILABLE',
            ],
        ], 503),
        'http://127.0.0.1:11434/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Under Shofol Connect Accounting, standard payment terms are Net 30 days from invoice issuance.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('What are the default payment terms for invoices?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['used_fallback_provider'] ?? null)->toBe('ollama');
    expect($result['answer'])->toContain('Net 30 days');
    expect($result['sources'][0]['title'])->toBe($article->title);
});

test('gemini times out or encounters connection failure: fallback to ollama succeeds', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Disbursement',
        'title' => 'Salary Disbursement Schedule',
        'question' => 'When are salaries disbursed each month?',
        'content' => 'Monthly salaries are disbursed by direct bank deposit on the 28th of each month.',
        'keywords' => 'salary disbursement, payroll date, bank deposit',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => function () {
            throw new ConnectionException('cURL error 28: Connection timed out after 5000 milliseconds');
        },
        'http://127.0.0.1:11434/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Salaries are disbursed via direct bank deposit on the 28th of each month.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When are salaries disbursed each month?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['used_fallback_provider'] ?? null)->toBe('ollama');
    expect($result['answer'])->toContain('28th of each month');
});

test('both gemini and ollama fail: safe policy fallback is returned without leaking secrets', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual Leave Guidelines',
        'question' => 'How many days of annual leave do employees receive?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'keywords' => 'annual leave, pto, vacation',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 503,
                'message' => 'The model is overloaded. Please try again later.',
            ],
        ], 503),
        'http://127.0.0.1:11434/*' => Http::response([
            'error' => 'Ollama model daemon is not running',
        ], 500),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days of annual leave do employees receive?');

    expect($result['success'])->toBeFalse();
    expect($result['fallback'])->toBeTrue();
    expect($result['is_policy_fallback'] ?? false)->toBeTrue();
    expect($result['answer'])->toContain('Verified Policy: Annual Leave Guidelines');
    expect($result['answer'])->toContain('14 days annual leave');
    expect($result['answer'])->not->toContain('test-gemini-key');
    expect($result['answer'])->not->toContain('Ollama model daemon is not running');
    expect($result['sources'])->toHaveCount(1);
});

test('no relevant knowledge base content: neither gemini nor ollama is called', function () {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('What is the secret recipe for Martian space soup?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeTrue();
    expect($result['answer'])->toBe(AIService::FALLBACK_MESSAGE);
    expect($result['sources'])->toBeEmpty();

    Http::assertNothingSent();
});

test('source citations and answer sanitization remain correct during ollama fallback', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual & Casual Leave Guidelines',
        'question' => 'How do full-time employees request annual or casual leave?',
        'content' => 'Full-time employees receive 14 annual leave days and 10 casual leave days annually. Leave requests must be submitted at least 3 business days in advance.',
        'keywords' => 'annual leave, casual leave, request leave, pto',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([], 429),
        'http://127.0.0.1:11434/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => "Full-time employees receive 14 annual leave days and 10 casual leave days annually.\n\nVerified Sources:\n[HRM] Annual & Casual Leave Guidelines\n\nNext Question: How do employees request sick leave?",
                    ],
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How do full-time employees request annual or casual leave?');

    expect($result['success'])->toBeTrue();
    expect($result['answer'])->toContain('14 annual leave days and 10 casual leave days');
    // Ensure echoed sources and next questions are stripped by sanitization
    expect($result['answer'])->not->toContain('Verified Sources:');
    expect($result['answer'])->not->toContain('Next Question:');
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['id'])->toBe($article->id);
    expect($result['sources'][0]['title'])->toBe('Annual & Casual Leave Guidelines');
});

test('streaming completion automatically falls back to ollama when gemini encounters 429', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'CRM',
        'category' => 'Pipelines',
        'title' => 'Lead Qualification Standards',
        'question' => 'What criteria must be confirmed before a lead is qualified in the sales pipeline?',
        'content' => 'A lead is qualified once budget, authority, need, and timeline (BANT) are confirmed.',
        'keywords' => 'lead qualification, sales pipeline, BANT, CRM',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'Quota exceeded'],
        ], 429),
        'http://127.0.0.1:11434/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'A lead is qualified once BANT criteria are verified in Shofol Connect CRM.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $tokens = [];
    $onToken = function (string $token) use (&$tokens) {
        $tokens[] = $token;
    };

    $aiService = app(AIService::class);
    $result = $aiService->askStream('What criteria must be confirmed before a lead is qualified in the sales pipeline?', [], $onToken);

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['used_fallback_provider'] ?? null)->toBe('ollama');
    expect($result['answer'])->toContain('BANT criteria are verified');
    expect($tokens)->not->toBeEmpty();
});

test('when fallback_to_ollama is disabled, gemini failure returns policy fallback without calling ollama', function () {
    Config::set('services.ai.fallback_to_ollama', false);

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual Leave Guidelines',
        'question' => 'How many days of annual leave do employees receive?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'keywords' => 'annual leave, pto, vacation',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([], 429),
        'http://127.0.0.1:11434/*' => Http::response([], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days of annual leave do employees receive?');

    expect($result['success'])->toBeFalse();
    expect($result['fallback'])->toBeTrue();
    expect($result['is_policy_fallback'] ?? false)->toBeTrue();
    expect($result['answer'])->toContain('Verified Policy: Annual Leave Guidelines');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://generativelanguage.googleapis.com'));
    Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'http://127.0.0.1:11434'));
});

test('chat send HTTP endpoint triggers automatic ollama fallback transparently', function () {
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual & Casual Leave Guidelines',
        'question' => 'How do full-time employees request annual or casual leave?',
        'content' => 'Full-time employees receive 14 annual leave days and 10 casual leave days annually.',
        'keywords' => 'annual leave, casual leave, request leave, pto',
        'status' => 'active',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'Rate limit exceeded'],
        ], 429),
        'http://127.0.0.1:11434/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Employees receive 14 annual leave days and 10 casual leave days annually per the HRM guidelines.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'How do full-time employees request annual or casual leave?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'fallback' => false,
            'sources' => [
                [
                    'id' => $article->id,
                    'title' => 'Annual & Casual Leave Guidelines',
                    'module' => 'HRM',
                ],
            ],
        ],
    ]);

    $content = $response->json('assistant_message.content');
    expect($content)->toContain('14 annual leave days and 10 casual leave days');
});

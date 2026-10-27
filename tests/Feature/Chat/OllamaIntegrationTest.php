<?php

use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('config services defines ollama configuration defaults', function () {
    expect(config('services.ollama.base_url'))->toBe('http://127.0.0.1:11434/v1');
    expect(config('services.ollama.model'))->toBe('qwen2.5:7b');
    expect(config('services.ollama.api_key'))->toBe('ollama');
});

test('AIService defaults to ollama settings when AI_PROVIDER is set to ollama', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.api_key', 'ollama');
    Config::set('services.ai.model', null);
    Config::set('services.ai.base_url', null);

    $aiService = app(AIService::class);

    expect($aiService->getProvider())->toBe('ollama');
    expect($aiService->getModel())->toBe('qwen2.5:7b');
    expect($aiService->getBaseUrl())->toBe('http://127.0.0.1:11434/v1');
    expect($aiService->isConfigured())->toBeTrue();
});

test('AIService defaults to gemini settings when AI_PROVIDER is set to gemini', function () {
    Config::set('services.ai.provider', 'gemini');
    Config::set('services.ai.api_key', 'test-gemini-key');
    Config::set('services.ai.model', null);
    Config::set('services.ai.base_url', null);

    $aiService = app(AIService::class);

    expect($aiService->getProvider())->toBe('gemini');
    expect($aiService->getModel())->toBe('gemini-3.8-flash');
    expect($aiService->getBaseUrl())->toBe('https://generativelanguage.googleapis.com/v1beta/openai');
    expect($aiService->isConfigured())->toBeTrue();
});

test('relevant question with Ollama provider retrieves context and sends request to local Ollama base URL', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Leave Policy',
        'title' => 'Annual & Casual Leave Policy',
        'question' => 'How do employees apply for annual leave?',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'keywords' => 'annual leave, casual leave, pto, vacation',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-test-ollama',
            'object' => 'chat.completion',
            'created' => time(),
            'model' => 'qwen2.5:7b',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Full-time employees receive 14 days of annual leave per year according to the HRM policy.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days of annual leave do employees receive in HRM?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['answer'])->toContain('14 days of annual leave');
    expect($result['sources'])->not->toBeEmpty();
    expect($result['sources'][0]['title'])->toBe($article->title);

    Http::assertSent(function ($request) use ($article) {
        $data = $request->data();
        $authHeader = $request->header('Authorization')[0] ?? '';

        return $request->url() === 'http://127.0.0.1:11434/v1/chat/completions' &&
               $authHeader === 'Bearer ollama' &&
               $data['model'] === 'qwen2.5:7b' &&
               str_contains(json_encode($data['messages']), $article->title) &&
               str_contains(json_encode($data['messages']), '14 days annual leave');
    });
});

test('chat send endpoint works end-to-end with Ollama provider', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Accounting',
        'category' => 'Invoicing & Receivables',
        'title' => 'Customer Invoice Generation and Payment Terms',
        'question' => 'How do I generate a customer invoice and what are the payment terms?',
        'content' => 'Standard payment terms are Net 30 days. Invoices have sequential serial numbers.',
        'keywords' => 'accounting, invoice, payment terms, net 30',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-test-ollama',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Standard invoice payment terms are Net 30 days with sequential serial numbers.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'What are the standard customer invoice payment terms in Accounting?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => 'Standard invoice payment terms are Net 30 days with sequential serial numbers.',
            'fallback' => false,
        ],
    ]);
});

test('Ollama streaming completion works via askStream', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    KnowledgeArticle::factory()->create([
        'module' => 'CRM',
        'title' => 'Sales Pipeline Stages and Lead Qualification',
        'content' => 'The Shofol CRM pipeline consists of five stages.',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'The Shofol CRM pipeline consists of five stages.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $aiService = app(AIService::class);
    $streamedTokens = [];

    $result = $aiService->askStream(
        'What are the pipeline stages in CRM?',
        [],
        function ($token) use (&$streamedTokens) {
            $streamedTokens[] = $token;
        }
    );

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['answer'])->toContain('five stages');
    expect($streamedTokens)->not->toBeEmpty();
});

test('when Ollama server is offline or connection fails, returns safe policy fallback without crashing', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month by direct bank transfer.',
        'keywords' => 'payroll, salary, disbursement, direct bank transfer',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => function () {
            throw new ConnectionException('cURL error 7: Failed to connect to 127.0.0.1 port 11434: Connection refused');
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed in Payroll?');

    // Must not crash, must return safe fallback with verified knowledge base excerpt
    expect($result['success'])->toBeFalse();
    expect($result['fallback'])->toBeTrue();
    expect($result['is_policy_fallback'])->toBeTrue();
    expect($result['error'])->toBe('CONNECTION_ERROR');
    expect($result['answer'])->toContain('temporary service issue');
    expect($result['answer'])->toContain('Verified Policy: Monthly Payroll Cycle and Tax Deductions');
    expect($result['answer'])->toContain('final working day of each month by direct bank transfer');
    expect($result['sources'])->not->toBeEmpty();
});

test('when Ollama returns 404 model not found, returns safe policy fallback', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'error' => 'model "qwen2.5:7b" not found, try pulling it first',
        ], 404),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed in Payroll?');

    expect($result['success'])->toBeFalse();
    expect($result['fallback'])->toBeTrue();
    expect($result['error'])->toBe('INVALID_MODEL');
    expect($result['error_message'])->toContain('model "qwen2.5:7b" not found');
    expect($result['answer'])->toContain('temporary service issue');
    expect($result['answer'])->toContain('Monthly Payroll Cycle');
});

test('when Ollama returns 500 server error, returns safe policy fallback without crashing', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Annual Leave Policy',
        'content' => 'Full-time employees receive 14 days annual leave.',
        'status' => 'active',
    ]);

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'error' => ['message' => 'Internal server error during inference'],
        ], 500),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How many days annual leave?');

    expect($result['success'])->toBeFalse();
    expect($result['fallback'])->toBeTrue();
    expect($result['error'])->toBe('SERVICE_UNAVAILABLE');
    expect($result['answer'])->toContain('temporary service issue');
});

test('unsupported question without knowledge base match returns standard fallback without querying Ollama', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    Http::fake();

    $aiService = app(AIService::class);
    $result = $aiService->ask('What is the recipe for chocolate lava cake with strawberry icing?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeTrue();
    expect($result['answer'])->toBe(AIService::FALLBACK_MESSAGE);

    // Verify Ollama was never contacted
    Http::assertNothingSent();
});

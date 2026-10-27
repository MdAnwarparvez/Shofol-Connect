<?php

use App\Models\Faq;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use App\Models\User;
use App\Services\AIService;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Clear KB cache before each test
    app(KnowledgeRetrievalService::class)->clearCache();

    // Create CRM Module and Category
    $crmModule = ShofolModule::firstOrCreate(
        ['code' => 'CRM'],
        ['name' => 'Customer Relationship Management', 'description' => 'Sales and CRM', 'is_active' => true]
    );

    $crmCategory = KnowledgeCategory::firstOrCreate(
        ['name' => 'Lead Management'],
        ['shofol_module_id' => $crmModule->id, 'description' => 'Lead pipeline', 'is_active' => true]
    );

    // Create HRM Article (Leave Policy)
    KnowledgeArticle::updateOrCreate(
        ['title' => 'Annual & Casual Leave Guidelines'],
        [
            'module' => 'HRM',
            'category' => 'Leave Policy',
            'question' => 'How do full-time employees request annual or casual leave?',
            'content' => 'Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. Submit requests through the HRM portal at least 3 business days in advance. Line managers must review requests within 48 hours. Unused casual leave expires at year-end; up to 5 annual leave days may be carried forward.',
            'keywords' => 'hrm, leave, annual leave, casual leave, full-time employees, request leave, time off, vacation, pto, line manager, carry forward',
            'status' => 'active',
        ]
    );

    // Create CRM FAQ (Lead Qualification containing "timeline" keyword)
    Faq::updateOrCreate(
        ['slug' => 'lead-qualification-and-bant-criteria'],
        [
            'shofol_module_id' => $crmModule->id,
            'knowledge_category_id' => $crmCategory->id,
            'question' => 'What criteria must be confirmed before a lead is qualified in the sales pipeline?',
            'answer' => 'In the Shofol CRM sales pipeline, a lead is qualified when the official BANT criteria are confirmed: Budget, Authority, Need, and Timeline. Qualified leads then progress from Contacted to Proposal Sent. Deals inactive for more than 14 days trigger an automated reminder to the assigned sales representative.',
            'keywords' => 'crm, lead qualification, bant, budget, authority, need, timeline, proposal sent, deals, reminder',
            'is_active' => true,
            'sort_order' => 1,
        ]
    );
});

test('retrieval for leave inquiry does not contaminate context or sources with unrelated CRM lead qualification FAQ', function () {
    $retrievalService = app(KnowledgeRetrievalService::class);
    $query = 'How do full-time employees request annual or casual leave?';

    $result = $retrievalService->retrieve($query);

    expect($result['has_relevant_results'])->toBeTrue();
    expect($result['articles'])->toHaveCount(1);
    expect($result['sources'])->toHaveCount(1);

    // Verified source must be the HRM leave article
    expect($result['sources'][0]['module'])->toBe('HRM');
    expect($result['sources'][0]['title'])->toBe('Annual & Casual Leave Guidelines');

    // CRM Lead FAQ must NOT be in context or sources
    expect($result['context'])->not->toContain('sales pipeline');
    expect($result['context'])->not->toContain('BANT');
    expect($result['context'])->not->toContain('Lead Management');
    expect($result['context'])->not->toContain('What criteria must be confirmed before a lead is qualified');

    foreach ($result['sources'] as $src) {
        expect($src['module'])->not->toBe('CRM');
        expect($src['module'])->not->toBe('Customer Relationship Management');
        expect($src['title'])->not->toContain('lead is qualified');
    }
});

test('AIService ask with Ollama provider returns clean leave answer without CRM source contamination', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => function ($request) {
            $payload = $request->data();
            $systemMsg = $payload['messages'][0]['content'] ?? '';

            // Verify the system prompt sent to Ollama does NOT contain CRM contamination
            expect($systemMsg)->not->toContain('What criteria must be confirmed before a lead is qualified');
            expect($systemMsg)->not->toContain('BANT criteria');
            expect($systemMsg)->toContain('Annual & Casual Leave Guidelines');

            return Http::response([
                'id' => 'chatcmpl-mock-leave',
                'object' => 'chat.completion',
                'model' => 'qwen2.5:7b',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. Requests must be submitted via the HRM portal at least 3 business days in advance.',
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How do full-time employees request annual or casual leave?');

    expect($result['success'])->toBeTrue();
    expect($result['fallback'])->toBeFalse();
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['module'])->toBe('HRM');
    expect($result['sources'][0]['title'])->toBe('Annual & Casual Leave Guidelines');
    expect($result['answer'])->toContain('Full-time employees receive 14 annual leave days');
    expect($result['answer'])->not->toContain('sales pipeline');
    expect($result['answer'])->not->toContain('lead is qualified');
});

test('AIService askStream with Ollama provider streams clean leave answer without CRM source contamination', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    $sseBody = "data: " . json_encode([
        'choices' => [['delta' => ['content' => 'Full-time employees request leave via the HRM portal.']]],
    ]) . "\n\ndata: [DONE]\n\n";

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response($sseBody, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $tokens = [];
    $aiService = app(AIService::class);
    $result = $aiService->askStream('How do full-time employees request annual or casual leave?', [], function (string $token) use (&$tokens) {
        $tokens[] = $token;
    });

    expect($result['success'])->toBeTrue();
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['module'])->toBe('HRM');
    expect($result['answer'])->toBe('Full-time employees request leave via the HRM portal.');
    expect($tokens)->toContain('Full-time employees request leave via the HRM portal.');
});

test('chat send endpoint returns clean sources without unrelated CRM citations', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Full-time employees must submit leave requests through the HRM portal at least 3 business days in advance.',
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
            'sources' => [
                [
                    'module' => 'HRM',
                    'title' => 'Annual & Casual Leave Guidelines',
                ],
            ],
        ],
    ]);

    $response->assertJsonMissing([
        'module' => 'CRM',
        'title' => 'What criteria must be confirmed before a lead is qualified in the sales pipeline?',
    ]);
});

test('chat send streaming endpoint returns verified sources containing only HRM leave citations', function () {
    Config::set('services.ai.provider', 'ollama');
    Config::set('services.ai.base_url', 'http://127.0.0.1:11434/v1');
    Config::set('services.ai.model', 'qwen2.5:7b');
    Config::set('services.ai.api_key', 'ollama');

    $sseBody = "data: " . json_encode([
        'choices' => [['delta' => ['content' => 'Submit leave requests 3 days in advance.']]],
    ]) . "\n\ndata: [DONE]\n\n";

    Http::fake([
        'http://127.0.0.1:11434/v1/chat/completions' => Http::response($sseBody, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/send', [
        'message' => 'How do full-time employees request annual or casual leave?',
        'stream' => true,
    ], [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    $content = $response->streamedContent();

    expect($content)->toContain('Annual & Casual Leave Guidelines');
    expect($content)->toContain('"module":"HRM"');
    expect($content)->not->toContain('sales pipeline');
    expect($content)->not->toContain('What criteria must be confirmed before a lead is qualified');
});

test('AIService sanitizeAnswer strips trailing model-generated source headers and next questions', function () {
    $aiService = app(AIService::class);

    $rawOutput = "Full-time employees receive 14 days annual leave.\n\nVerified Sources:\n[HRM] Annual & Casual Leave Guidelines[Customer Relationship Management] What criteria must be confirmed before a lead is qualified in the sales pipeline?\n\nNext Question: What criteria must be confirmed before a lead is qualified?";

    $sanitized = $aiService->sanitizeAnswer($rawOutput);

    expect($sanitized)->toBe('Full-time employees receive 14 days annual leave.');
    expect($sanitized)->not->toContain('Verified Sources:');
    expect($sanitized)->not->toContain('Next Question:');
    expect($sanitized)->not->toContain('sales pipeline');
});

test('source isolation works identically when switching AI_PROVIDER to gemini', function () {
    Config::set('services.ai.provider', 'gemini');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.api_key', 'test-gemini-key');

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions' => function ($request) {
            $payload = $request->data();
            $systemMsg = $payload['messages'][0]['content'] ?? '';

            // Verify Gemini also receives strictly isolated context without CRM FAQ
            expect($systemMsg)->not->toContain('lead is qualified');
            expect($systemMsg)->toContain('Annual & Casual Leave Guidelines');

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Employees can apply for leave via the HRM portal.',
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How do full-time employees request annual or casual leave?');

    expect($result['success'])->toBeTrue();
    expect($result['sources'])->toHaveCount(1);
    expect($result['sources'][0]['module'])->toBe('HRM');
    expect($result['sources'][0]['title'])->toBe('Annual & Casual Leave Guidelines');
});

<?php

use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('guest cannot access chat page or send messages', function () {
    $this->get('/chat')->assertRedirect('/login');
    $this->post('/chat/send', ['message' => 'Hello'])->assertRedirect('/login');
    $this->post('/chat/new')->assertRedirect('/login');
});

test('authenticated user can view chat interface', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/chat');

    $response->assertStatus(200);
    $response->assertSee('Shofol Connect AI Assistant');
    $response->assertSee('RAG Grounded');
});

test('chat message validation rejects empty input', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/chat/send', ['message' => '']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['message']);
});

test('unsupported question without knowledge base match returns fallback without calling LLM', function () {
    Http::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'What is the recipe for baking chocolate brownies and fixing an airplane engine?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => AIService::FALLBACK_MESSAGE,
            'fallback' => true,
            'sources' => [],
        ],
    ]);

    // Critical constraint verification: LLM must not be called when no relevant KB context exists
    Http::assertNothingSent();
});

test('relevant question retrieves knowledge context and queries LLM', function () {
    Config::set('services.ai.api_key', 'test-fake-key');

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
        '*/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'According to the Shofol Connect HRM policy, full-time employees are entitled to 14 days of annual leave per year.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'How many days of annual leave do employees receive in HRM?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => 'According to the Shofol Connect HRM policy, full-time employees are entitled to 14 days of annual leave per year.',
            'fallback' => false,
        ],
    ]);

    // Verify context was sent to LLM
    Http::assertSent(function ($request) use ($article) {
        $body = $request->body();
        return str_contains($body, 'HRM') &&
               str_contains($body, 'Annual & Casual Leave Policy') &&
               str_contains($body, 'Full-time employees receive 14 days');
    });
});

test('handles LLM API errors gracefully', function () {
    Config::set('services.ai.api_key', 'test-fake-key');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Tax Deduction Computation',
        'content' => 'Tax deduction is computed according to monthly income tiers.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'error' => ['message' => 'Rate limit exceeded'],
        ], 429),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'How is tax deduction computed in Payroll?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
    ]);
    $this->assertStringContainsString('temporary service issue', $response->json('assistant_message.content'));
});

test('handles LLM connection timeout gracefully', function () {
    Config::set('services.ai.api_key', 'test-fake-key');

    KnowledgeArticle::factory()->create([
        'module' => 'Accounting',
        'title' => 'Invoice Payment Terms',
        'content' => 'Payment terms are Net 30 days.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => fn () => throw new ConnectionException('Request timeout after 15s'),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'What are invoice payment terms in Accounting?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
    ]);
    $this->assertStringContainsString('timed out', $response->json('assistant_message.content'));
});

test('authenticated user can start a new conversation session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat/new');

    $response->assertRedirect('/chat');
    $this->assertDatabaseHas('conversations', [
        'user_id' => $user->id,
        'title' => 'New Support Conversation',
    ]);
});

test('relevant question with unconfigured AI service returns service unavailable message with retrieved sources', function () {
    Config::set('services.ai.api_key', null);

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Salary & Tax',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
        'content' => 'Payroll cutoff is the 25th day of every calendar month. Salary disbursement is executed on the final working day of each month via direct bank transfer. Statutory deductions such as income tax withholding (TDS) and provident fund contributions (8% employer and employee match) are itemized on the digital pay slip accessible under the Payroll portal.',
        'keywords' => 'payroll, salary, tax, deduction, payslip, disbursement, bank transfer, provident fund',
        'status' => 'active',
    ]);

    Http::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'When is monthly salary disbursed and how is tax deduction computed?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'assistant_message' => [
            'content' => 'The AI assistant is temporarily unavailable because the AI service is not configured. Please contact support.',
            'fallback' => false,
        ],
    ]);

    // Verify sources were retrieved even though LLM was not called
    $sources = $response->json('assistant_message.sources');
    $this->assertNotEmpty($sources);
    $this->assertSame('Monthly Payroll Cycle and Tax Deductions', $sources[0]['title']);

    // Critical: LLM must not be contacted when unconfigured
    Http::assertNothingSent();
});

test('relevant question with configured AI service successfully responds with knowledge-grounded answer', function () {
    Config::set('services.ai.api_key', 'test-valid-key');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Salary & Tax',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
        'content' => 'Payroll cutoff is the 25th day of every calendar month. Salary disbursement is executed on the final working day of each month via direct bank transfer. Statutory deductions such as income tax withholding (TDS) and provident fund contributions (8% employer and employee match) are itemized on the digital pay slip accessible under the Payroll portal.',
        'keywords' => 'payroll, salary, tax, deduction, payslip, disbursement, bank transfer, provident fund',
        'status' => 'active',
    ]);

    $groundedAnswer = 'According to Shofol Connect Payroll policy, salary disbursement occurs on the final working day of each calendar month via direct bank transfer, with a cutoff on the 25th. Statutory tax deductions (TDS) and provident fund contributions (8%) are itemized on your digital pay slip.';

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => $groundedAnswer,
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'When is monthly salary disbursed and how is tax deduction computed?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => $groundedAnswer,
            'fallback' => false,
        ],
    ]);

    $sources = $response->json('assistant_message.sources');
    $this->assertNotEmpty($sources);
    $this->assertSame($article->title, $sources[0]['title']);

    // Verify prompt payload contains exact verified knowledge content
    Http::assertSent(function ($request) {
        $body = $request->body();
        return str_contains($body, 'Payroll cutoff is the 25th day') &&
               str_contains($body, 'direct bank transfer') &&
               str_contains($body, 'income tax withholding');
    });
});

test('AIService helper methods report configuration status correctly', function () {
    Config::set('services.ai.api_key', null);
    $aiServiceUnconfigured = app(AIService::class);
    $this->assertFalse($aiServiceUnconfigured->isConfigured());

    Config::set('services.ai.api_key', 'test-valid-key');
    Config::set('services.ai.model', 'gpt-4o-mini');
    Config::set('services.ai.base_url', 'https://api.openai.com/v1');
    $aiServiceConfigured = app(AIService::class);
    $this->assertTrue($aiServiceConfigured->isConfigured());
    $this->assertSame('gpt-4o-mini', $aiServiceConfigured->getModel());
    $this->assertSame('https://api.openai.com/v1', $aiServiceConfigured->getBaseUrl());
});

test('handles invalid model 404 response from Gemini gracefully with INVALID_MODEL classification', function () {
    Config::set('services.ai.api_key', 'test-fake-key');
    Config::set('services.ai.model', 'gemini-2.5-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            [
                'error' => [
                    'code' => 404,
                    'message' => 'This model models/gemini-2.5-flash is no longer available to new users. Please update your code to use models/gemini-3.8-flash for the latest features and improvements.',
                    'status' => 'NOT_FOUND',
                ],
            ],
        ], 404),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('INVALID_MODEL', $result['error']);
    $this->assertStringContainsString('no longer available to new users', $result['error_message']);
    $this->assertStringContainsString('temporary service issue', $result['answer']);
});

test('handles Gemini authentication failure 401 gracefully with AUTH_ERROR classification', function () {
    Config::set('services.ai.api_key', 'test-invalid-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'error' => [
                'code' => 401,
                'message' => 'API key not valid. Please pass a valid API key.',
                'status' => 'UNAUTHENTICATED',
            ],
        ], 401),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('AUTH_ERROR', $result['error']);
    $this->assertStringContainsString('API key not valid', $result['error_message']);
});

test('successfully completes request with gemini-3.8-flash via OpenAI-compatible endpoint', function () {
    Config::set('services.ai.api_key', 'test-valid-gemini-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    $article = KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Salary & Tax',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
        'content' => 'Payroll cutoff is the 25th day of every calendar month. Salary disbursement is executed on the final working day of each month via direct bank transfer. Statutory deductions such as income tax withholding (TDS) and provident fund contributions (8% employer and employee match) are itemized on the digital pay slip accessible under the Payroll portal.',
        'keywords' => 'payroll, salary, tax, deduction, payslip, disbursement, bank transfer, provident fund',
        'status' => 'active',
    ]);

    $geminiAnswer = 'Based on the Shofol Connect Payroll policy, monthly salary is disbursed on the final working day of each calendar month via direct bank transfer (cutoff: 25th). Tax withholding (TDS) and provident fund contributions (8%) are itemized on your pay slip.';

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([
            'id' => 'chatcmpl-test-gemini',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $geminiAnswer,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'When is monthly salary disbursed and how is tax deduction computed?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => $geminiAnswer,
            'fallback' => false,
        ],
    ]);

    $this->assertNotEmpty($response->json('assistant_message.sources'));
    $this->assertSame($article->title, $response->json('assistant_message.sources.0.title'));

    Http::assertSent(function ($request) {
        $body = $request->body();
        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions' &&
               str_contains($body, 'gemini-3.8-flash') &&
               str_contains($body, 'Payroll cutoff is the 25th day') &&
               $request->hasHeader('Authorization');
    });
});

test('handles Gemini rate limit and quota exhaustion 429 gracefully with RATE_LIMIT_OR_QUOTA classification', function () {
    Config::set('services.ai.api_key', 'test-gemini-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            [
                'error' => [
                    'code' => 429,
                    'message' => 'Resource has been exhausted (e.g. check quota).',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ],
        ], 429),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('RATE_LIMIT_OR_QUOTA', $result['error']);
    $this->assertStringContainsString('Resource has been exhausted', $result['error_message']);
    $this->assertStringContainsString('temporary service issue', $result['answer']);
});

test('handles malformed request 400 gracefully with MALFORMED_REQUEST classification', function () {
    Config::set('services.ai.api_key', 'test-gemini-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            [
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid JSON payload received. Unknown parameter: invalid_arg',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ],
        ], 400),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('MALFORMED_REQUEST', $result['error']);
    $this->assertStringContainsString('Invalid JSON payload', $result['error_message']);
    $this->assertStringContainsString('temporary service issue', $result['answer']);
});

test('sanitizes API key from error messages and logs if returned in upstream error', function () {
    $secretKey = 'AIzaSySecretTestKey12345';
    Config::set('services.ai.api_key', $secretKey);
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            [
                'error' => [
                    'code' => 401,
                    'message' => "Invalid API key: {$secretKey}. Please verify credentials.",
                    'status' => 'UNAUTHENTICATED',
                ],
            ],
        ], 401),
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('AUTH_ERROR', $result['error']);
    $this->assertStringNotContainsString($secretKey, $result['error_message']);
    $this->assertStringContainsString('[REDACTED]', $result['error_message']);
});

test('handles transient HTTP 503 by retrying and succeeding on recovery', function () {
    Config::set('services.ai.api_key', 'test-valid-gemini-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.retry_times', 2);
    Config::set('services.ai.retry_delay', 5);

    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Annual & Casual Leave Guidelines',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'status' => 'active',
    ]);

    $callCount = 0;
    Http::fake([
        '*/chat/completions' => function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    [
                        'error' => [
                            'code' => 503,
                            'message' => 'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.',
                            'status' => 'UNAVAILABLE',
                        ],
                    ],
                ], 503);
            }

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Full-time employees are entitled to 14 days of annual leave per year according to HRM guidelines.',
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How do full-time employees request annual or casual leave?');

    $this->assertTrue($result['success']);
    $this->assertSame(2, $callCount);
    $this->assertStringContainsString('14 days of annual leave', $result['answer']);
});

test('handles persistent HTTP 503 gracefully after retry exhaustion with SERVICE_UNAVAILABLE classification', function () {
    Config::set('services.ai.api_key', 'test-valid-gemini-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.retry_times', 2);
    Config::set('services.ai.retry_delay', 5);

    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Annual & Casual Leave Guidelines',
        'content' => 'Full-time employees receive 14 days annual leave per year.',
        'status' => 'active',
    ]);

    $callCount = 0;
    Http::fake([
        '*/chat/completions' => function () use (&$callCount) {
            $callCount++;
            return Http::response([
                [
                    'error' => [
                        'code' => 503,
                        'message' => 'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.',
                        'status' => 'UNAVAILABLE',
                    ],
                ],
            ], 503);
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('How do full-time employees request annual or casual leave?');

    $this->assertFalse($result['success']);
    $this->assertSame('SERVICE_UNAVAILABLE', $result['error']);
    $this->assertStringContainsString('high demand', $result['error_message']);
    $this->assertStringContainsString('temporary service issue', $result['answer']);
    $this->assertSame(2, $callCount);
});

test('does not retry authentication errors or invalid models', function () {
    Config::set('services.ai.api_key', 'test-invalid-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.retry_times', 3);
    Config::set('services.ai.retry_delay', 5);

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'title' => 'Monthly Payroll Cycle and Tax Deductions',
        'content' => 'Salary disbursement is executed on the final working day of each month.',
        'status' => 'active',
    ]);

    $callCount = 0;
    Http::fake([
        '*/chat/completions' => function () use (&$callCount) {
            $callCount++;
            return Http::response([
                'error' => [
                    'code' => 401,
                    'message' => 'API key not valid.',
                    'status' => 'UNAUTHENTICATED',
                ],
            ], 401);
        },
    ]);

    $aiService = app(AIService::class);
    $result = $aiService->ask('When is monthly salary disbursed and how is tax deduction computed?');

    $this->assertFalse($result['success']);
    $this->assertSame('AUTH_ERROR', $result['error']);
    $this->assertSame(1, $callCount);
});

test('historical assistant fallback message renders support ticket CTA button for owner but not for user messages or non-fallback assistant messages', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $conversation = $userA->conversations()->create([
        'title' => 'Custom Payroll Exception',
    ]);

    // Message 1: User message (ineligible)
    $msg1 = $conversation->messages()->create([
        'role' => 'user',
        'content' => 'Can we apply an undocumented tax deduction rate?',
    ]);

    // Message 2: Eligible assistant fallback message
    $msg2 = $conversation->messages()->create([
        'role' => 'assistant',
        'content' => AIService::FALLBACK_MESSAGE,
        'sources' => [],
    ]);

    // Message 3: Normal grounded assistant message (ineligible)
    $msg3 = $conversation->messages()->create([
        'role' => 'assistant',
        'content' => 'Full-time employees receive 14 annual leave days per year according to HRM guidelines.',
        'sources' => [],
    ]);

    // Assert helper method behavior
    expect($msg1->isSupportTicketEligible())->toBeFalse();
    expect($msg2->isSupportTicketEligible())->toBeTrue();
    expect($msg3->isSupportTicketEligible())->toBeFalse();

    // Acting as conversation owner User A
    $responseA = $this->actingAs($userA)->get("/chat/{$conversation->id}");
    $responseA->assertStatus(200);

    // Verify historical CTA button is rendered for the eligible assistant message
    $responseA->assertSee('Create Support Ticket from this Inquiry');
    $responseA->assertSee(route('tickets.create', ['conversation_id' => $conversation->id]));

    // Verify that the CTA button appears in the message bubble (1 in bubble, 1 in JS template)
    $content = $responseA->getContent();
    $messagesHtml = strstr($content, '<script>', true) ?: $content;
    $this->assertSame(1, substr_count($messagesHtml, 'Create Support Ticket from this Inquiry'));

    // Authorization & ownership guard: User B cannot access User A conversation
    $responseB = $this->actingAs($userB)->get("/chat/{$conversation->id}");
    $responseB->assertStatus(403);
});

test('chat view renders safe markdown for assistant messages, strips raw HTML, and preserves Bengali text', function () {
    $user = User::factory()->create();
    $conversation = $user->conversations()->create([
        'title' => 'Markdown Policy Display',
    ]);

    $markdownContent = "# HRM Leave Overview\n\n**Annual Leave**: 14 days per calendar year.\n\n- Casual Leave: 10 days\n- Sick Leave: 14 days\n\nস্বাগতম Shofol Connect\n\n```php\n\$status = 'approved';\n```\n\n[Safe Portal](https://shofolconnect.com) and [Dangerous](javascript:alert(1))\n\n<script>alert('xss')</script>";

    $conversation->messages()->create([
        'role' => 'assistant',
        'content' => $markdownContent,
        'sources' => [],
    ]);

    $response = $this->actingAs($user)->get("/chat/{$conversation->id}");
    $response->assertStatus(200);

    // Verify safe structural Markdown elements are rendered into HTML
    $response->assertSee('<h1>HRM Leave Overview</h1>', false);
    $response->assertSee('<strong>Annual Leave</strong>', false);
    $response->assertSee('<li>Casual Leave: 10 days</li>', false);
    $response->assertSee('<li>Sick Leave: 14 days</li>', false);
    $response->assertSee('স্বাগতম Shofol Connect');
    $response->assertSee('<pre><code', false);
    $response->assertSee('<a href="https://shofolconnect.com">Safe Portal</a>', false);

    // Verify raw unsafe HTML is sanitized and executable script tags are stripped
    $response->assertDontSee('<script>alert(\'xss\')</script>', false);
    $response->assertDontSee('javascript:alert(1)', false);
});

test('chat view renders auto-resizing textarea input with keyboard shortcut instructions', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/chat');

    $response->assertStatus(200);
    $response->assertSee('<textarea', false);
    $response->assertSee('id="messageInput"', false);
    $response->assertSee('name="message"', false);
    $response->assertSee('Press Enter to send, Shift + Enter for new line');
});

test('chat send AJAX response returns pre-rendered safe content_html for assistant message', function () {
    Config::set('services.ai.api_key', 'test-valid-key');

    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'category' => 'Salary & Tax',
        'title' => 'Payroll Disbursement Schedule',
        'content' => 'Salary disbursement occurs on final working day.',
        'status' => 'active',
    ]);

    $assistantMarkdown = "### Payroll Guidelines\n\n**Disbursement Date**: Final working day of each month.\n\n- Direct bank transfer\n- Digital payslip access\n\n<script>malicious()</script>";

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => $assistantMarkdown,
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'What is the payroll disbursement schedule in Payroll?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'content' => $assistantMarkdown,
        ],
    ]);

    $contentHtml = $response->json('assistant_message.content_html');
    expect($contentHtml)->not->toBeEmpty();
    expect($contentHtml)->toContain('<h3>Payroll Guidelines</h3>');
    expect($contentHtml)->toContain('<strong>Disbursement Date</strong>');
    expect($contentHtml)->toContain('<li>Direct bank transfer</li>');
    expect($contentHtml)->not->toContain('<script>malicious()</script>');
});

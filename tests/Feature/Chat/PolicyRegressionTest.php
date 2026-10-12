<?php

use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Services\AIService;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.ai.api_key', 'test-gemini-mock-key');
    Config::set('services.ai.model', 'gemini-3.8-flash');
    Config::set('services.ai.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
    Config::set('services.ai.timeout', 60);
    Config::set('services.ai.retry_times', 2);
    Config::set('services.ai.retry_delay', 10);
});

test('RAG retrieves exact Accounting article and AI answers invoice generation and payment terms', function () {
    $accountingArticle = KnowledgeArticle::updateOrCreate(
        ['title' => 'Customer Invoice Generation and Payment Terms'],
        [
            'module' => 'Accounting',
            'category' => 'Invoicing & Receivables',
            'question' => 'How do I generate a customer invoice and what are the payment terms?',
            'content' => 'Generate invoices through the Accounting module with sequential serial numbers. Standard payment terms are Net 30 days unless an approved contract specifies otherwise. A 7-day grace period applies; after it, a 1.5% monthly service fee applies to unpaid invoices. Marking an invoice Paid automatically reconciles it with connected bank statements.',
            'keywords' => 'accounting, invoice, customer invoice, billing, payment terms, net 30, sequential serial numbers, grace period, service fee, reconciliation, bank statements',
            'status' => 'active',
        ]
    );

    // 1. Verify RAG retrieval directly
    $retrievalService = app(KnowledgeRetrievalService::class);
    $question = 'How do I generate a customer invoice and what are the payment terms?';
    $retrieval = $retrievalService->retrieve($question);

    expect($retrieval['has_relevant_results'])->toBeTrue();
    expect($retrieval['sources'])->toHaveCount(1);
    expect($retrieval['sources'][0]['id'])->toBe($accountingArticle->id);
    expect($retrieval['sources'][0]['module'])->toBe('Accounting');
    expect($retrieval['context'])->toContain('sequential serial numbers');
    expect($retrieval['context'])->toContain('Net 30 days');
    expect($retrieval['context'])->toContain('1.5% monthly service fee');

    // 2. Mock Gemini API endpoint
    $expectedAnswer = "Customer invoices must be generated through the Accounting module with sequential serial numbers. The standard payment terms are Net 30 days, unless an approved contract specifies different terms. A 7-day grace period applies, after which a 1.5% monthly service fee is assessed on unpaid invoices. When marked as Paid, invoices are automatically reconciled with connected bank statements.";

    Http::fake([
        '*/chat/completions' => function ($request) {
            $payload = $request->data();
            $messages = $payload['messages'] ?? [];
            $systemMsg = $messages[0]['content'] ?? '';
            $userMsg = $messages[1]['content'] ?? '';

            // Verify payload has strict RAG context and model
            if (!str_contains($systemMsg, 'sequential serial numbers') ||
                !str_contains($systemMsg, 'Net 30 days') ||
                !str_contains($systemMsg, '1.5% monthly service fee') ||
                !str_contains($userMsg, 'customer invoice')) {
                return Http::response(['error' => ['message' => 'Malformed context']], 400);
            }

            return Http::response([
                'id' => 'chatcmpl-mock-accounting',
                'object' => 'chat.completion',
                'model' => 'gemini-3.8-flash',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => "Customer invoices must be generated through the Accounting module with sequential serial numbers. Standard payment terms are Net 30 days unless an approved contract specifies otherwise. A 7-day grace period applies, after which a 1.5% monthly service fee is charged on unpaid invoices. Marking an invoice as Paid automatically reconciles it with connected bank statements.",
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ], 200);
        },
    ]);

    // 3. Test through endpoint
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => $question,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'sources' => [
                [
                    'id' => $accountingArticle->id,
                    'title' => 'Customer Invoice Generation and Payment Terms',
                    'module' => 'Accounting',
                ],
            ],
            'fallback' => false,
        ],
    ]);

    $content = $response->json('assistant_message.content');
    expect($content)->toContain('sequential serial numbers');
    expect($content)->toContain('Net 30 days');
    expect($content)->toContain('1.5% monthly service fee');
    expect($content)->toContain('reconciles');
});

test('RAG retrieves exact HRM article and AI answers leave request guidelines', function () {
    $hrmArticle = KnowledgeArticle::updateOrCreate(
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

    // 1. Verify RAG retrieval directly
    $retrievalService = app(KnowledgeRetrievalService::class);
    $question = 'How do full-time employees request annual or casual leave?';
    $retrieval = $retrievalService->retrieve($question);

    expect($retrieval['has_relevant_results'])->toBeTrue();
    expect($retrieval['sources'])->toHaveCount(1);
    expect($retrieval['sources'][0]['id'])->toBe($hrmArticle->id);
    expect($retrieval['sources'][0]['module'])->toBe('HRM');
    expect($retrieval['context'])->toContain('14 annual leave days');
    expect($retrieval['context'])->toContain('10 casual leave days');
    expect($retrieval['context'])->toContain('3 business days in advance');
    expect($retrieval['context'])->toContain('48 hours');
    expect($retrieval['context'])->toContain('up to 5 annual leave days may be carried forward');

    // 2. Mock Gemini API endpoint
    Http::fake([
        '*/chat/completions' => function ($request) {
            $payload = $request->data();
            $systemMsg = $payload['messages'][0]['content'] ?? '';

            if (!str_contains($systemMsg, '14 annual leave days') ||
                !str_contains($systemMsg, '10 casual leave days') ||
                !str_contains($systemMsg, '3 business days in advance')) {
                return Http::response(['error' => ['message' => 'Malformed HRM context']], 400);
            }

            return Http::response([
                'id' => 'chatcmpl-mock-hrm',
                'object' => 'chat.completion',
                'model' => 'gemini-3.8-flash',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => "Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. To request leave, submit a request via the HRM portal at least 3 business days in advance. Line managers are required to review requests within 48 hours. Unused casual leave expires at year-end, while up to 5 annual leave days can be carried forward.",
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ], 200);
        },
    ]);

    // 3. Test through endpoint
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => $question,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'sources' => [
                [
                    'id' => $hrmArticle->id,
                    'title' => 'Annual & Casual Leave Guidelines',
                    'module' => 'HRM',
                ],
            ],
            'fallback' => false,
        ],
    ]);

    $content = $response->json('assistant_message.content');
    expect($content)->toContain('14 annual leave days');
    expect($content)->toContain('10 casual leave days');
    expect($content)->toContain('3 business days');
    expect($content)->toContain('48 hours');
    expect($content)->toContain('5 annual leave days');
});

test('RAG retrieves exact Payroll article and AI explains salary disbursement and tax itemization without inventing arbitrary tax rates', function () {
    $payrollArticle = KnowledgeArticle::updateOrCreate(
        ['title' => 'Monthly Payroll Cycle and Tax Deductions'],
        [
            'module' => 'Payroll',
            'category' => 'Salary & Tax',
            'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
            'content' => 'Payroll cutoff is the 25th of each month. Salary disbursement is executed on the final working day of each month by direct bank transfer. Tax deduction (TDS) and provident fund contributions are itemized on the digital payslip accessible under the Payroll portal. Employee and employer provident fund contributions are each 8%. Tax deduction (TDS) is computed based on applicable statutory tax rules rather than a fixed company rate, and is itemized on the digital payslip.',
            'keywords' => 'payroll, salary, disbursement, bank transfer, direct bank transfer, tax deduction, tds, taxes, provident fund, cutoff, digital payslip, pay slip',
            'status' => 'active',
        ]
    );

    // 1. Verify RAG retrieval directly
    $retrievalService = app(KnowledgeRetrievalService::class);
    $question = 'When is monthly salary disbursed and how is tax deduction computed?';
    $retrieval = $retrievalService->retrieve($question);

    expect($retrieval['has_relevant_results'])->toBeTrue();
    expect($retrieval['sources'])->toHaveCount(1);
    expect($retrieval['sources'][0]['id'])->toBe($payrollArticle->id);
    expect($retrieval['sources'][0]['module'])->toBe('Payroll');
    expect($retrieval['context'])->toContain('25th of each month');
    expect($retrieval['context'])->toContain('final working day of each month by direct bank transfer');
    expect($retrieval['context'])->toContain('itemized on the digital payslip');
    expect($retrieval['context'])->toContain('8%');

    // 2. Mock Gemini API endpoint and verify system prompt constraints
    Http::fake([
        '*/chat/completions' => function ($request) {
            $payload = $request->data();
            $systemMsg = $payload['messages'][0]['content'] ?? '';

            // Strict constraint verification: never invent arbitrary tax rates, TDS itemized on payslip
            if (!str_contains($systemMsg, 'never invent specific income tax rates') ||
                !str_contains($systemMsg, '25th of each month') ||
                !str_contains($systemMsg, 'direct bank transfer')) {
                return Http::response(['error' => ['message' => 'Malformed Payroll prompt constraints']], 400);
            }

            return Http::response([
                'id' => 'chatcmpl-mock-payroll',
                'object' => 'chat.completion',
                'model' => 'gemini-3.8-flash',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => "Monthly payroll has a cutoff on the 25th of each month, with salary disbursed on the final working day of each month via direct bank transfer. Tax deductions (TDS) and provident fund contributions (8% employee and 8% employer) are itemized on your digital payslip in the Payroll portal. Income tax deduction (TDS) is computed based on applicable statutory tax rules and individual tax slabs, rather than a fixed flat company rate.",
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ], 200);
        },
    ]);

    // 3. Test through endpoint
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => $question,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'assistant_message' => [
            'sources' => [
                [
                    'id' => $payrollArticle->id,
                    'title' => 'Monthly Payroll Cycle and Tax Deductions',
                    'module' => 'Payroll',
                ],
            ],
            'fallback' => false,
        ],
    ]);

    $content = $response->json('assistant_message.content');
    expect($content)->toContain('25th of each month');
    expect($content)->toContain('final working day');
    expect($content)->toContain('direct bank transfer');
    expect($content)->toContain('itemized');
    expect($content)->toContain('8%');
    expect($content)->toContain('applicable statutory tax rules');
});

test('handles quota-exhausted 429 by returning friendly explanation and verified policy fallback without leaking secrets', function () {
    $article = KnowledgeArticle::updateOrCreate(
        ['title' => 'Customer Invoice Generation and Payment Terms'],
        [
            'module' => 'Accounting',
            'category' => 'Invoicing & Receivables',
            'question' => 'How do I generate a customer invoice and what are the payment terms?',
            'content' => 'Generate invoices through the Accounting module with sequential serial numbers. Standard payment terms are Net 30 days unless an approved contract specifies otherwise. A 7-day grace period applies; after it, a 1.5% monthly service fee applies to unpaid invoices. Marking an invoice Paid automatically reconciles it with connected bank statements.',
            'keywords' => 'accounting, invoice, customer invoice, billing, payment terms, net 30',
            'status' => 'active',
        ]
    );

    Http::fake([
        '*/chat/completions' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'Quota exceeded for metric: generativelanguage.googleapis.com/generate_content_free_tier_requests, limit: 20',
                'status' => 'RESOURCE_EXHAUSTED',
            ],
        ], 429),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'How do I generate a customer invoice and what are the payment terms?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'assistant_message' => [
            'fallback' => true,
            'is_policy_fallback' => true,
            'error' => 'RATE_LIMIT_OR_QUOTA',
            'sources' => [
                [
                    'id' => $article->id,
                    'title' => 'Customer Invoice Generation and Payment Terms',
                    'module' => 'Accounting',
                ],
            ],
        ],
    ]);

    $content = $response->json('assistant_message.content');
    // Verify friendly explanation is present
    expect($content)->toContain('temporary service issue with the upstream provider (request quota limit reached)');
    // Verify verified policy excerpt is present
    expect($content)->toContain('Verified Policy: Customer Invoice Generation and Payment Terms');
    expect($content)->toContain('sequential serial numbers');
    expect($content)->toContain('Net 30 days');
    expect($content)->toContain('1.5% monthly service fee');
    expect($content)->toContain('reconciles it with connected bank statements');
    // Verify disclaimer clearly distinguishes verified documentation from AI generation
    expect($content)->toContain('This verified policy excerpt is provided directly from official company documentation while AI generation is temporarily unavailable');
    // Verify zero leakage of credentials or raw errors
    expect($content)->not->toContain('test-gemini-mock-key');
    expect($content)->not->toContain('RESOURCE_EXHAUSTED');
});

test('handles service-unavailable 503 by returning friendly explanation and verified policy fallback without leaking secrets', function () {
    $article = KnowledgeArticle::updateOrCreate(
        ['title' => 'Annual & Casual Leave Guidelines'],
        [
            'module' => 'HRM',
            'category' => 'Leave Policy',
            'question' => 'How do full-time employees request annual or casual leave?',
            'content' => 'Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. Submit requests through the HRM portal at least 3 business days in advance. Line managers must review requests within 48 hours. Unused casual leave expires at year-end; up to 5 annual leave days may be carried forward.',
            'keywords' => 'hrm, leave, annual leave, casual leave, full-time employees',
            'status' => 'active',
        ]
    );

    Http::fake([
        '*/chat/completions' => Http::response([
            'error' => [
                'code' => 503,
                'message' => 'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.',
                'status' => 'UNAVAILABLE',
            ],
        ], 503),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'message' => 'How do full-time employees request annual or casual leave?',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'assistant_message' => [
            'fallback' => true,
            'is_policy_fallback' => true,
            'error' => 'SERVICE_UNAVAILABLE',
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
    expect($content)->toContain('temporary service issue with the upstream provider (temporary high demand)');
    expect($content)->toContain('Verified Policy: Annual & Casual Leave Guidelines');
    expect($content)->toContain('14 annual leave days');
    expect($content)->toContain('10 casual leave days');
    expect($content)->toContain('3 business days in advance');
    expect($content)->toContain('48 hours');
    expect($content)->not->toContain('UNAVAILABLE');
    expect($content)->not->toContain('test-gemini-mock-key');
});

test('chat interface renders official knowledge base policy badge and verified sources', function () {
    $user = User::factory()->create();
    $conversation = $user->conversations()->create([
        'title' => 'Leave Policy Inquiry',
    ]);

    $conversation->messages()->create([
        'role' => 'user',
        'content' => 'How do full-time employees request annual or casual leave?',
    ]);

    $conversation->messages()->create([
        'role' => 'assistant',
        'content' => "The AI assistant encountered a temporary service issue with the upstream provider (request quota limit reached).\n\nHowever, here is the official policy information retrieved directly from the verified Shofol Connect Knowledge Base:\n\n📋 Verified Policy: Annual & Casual Leave Guidelines [Module: HRM]\nPolicy Details:\nFull-time employees receive 14 annual leave days.",
        'sources' => [
            [
                'id' => 1,
                'title' => 'Annual & Casual Leave Guidelines',
                'module' => 'HRM',
                'category' => 'Leave Policy',
            ],
        ],
    ]);

    $response = $this->actingAs($user)->get('/chat/' . $conversation->id);

    $response->assertStatus(200);
    // Verifies official policy badge is rendered in the HTML view
    $response->assertSee('Official Knowledge Base Policy');
    // Verifies verified sources chip is rendered in the HTML view
    $response->assertSee('Verified Sources:');
    $response->assertSee('[HRM] Annual & Casual Leave Guidelines');
    $response->assertSee('Full-time employees receive 14 annual leave days');
});

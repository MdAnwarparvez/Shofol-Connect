<?php

use App\Models\Faq;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use App\Models\User;
use App\Services\AIService;
use App\Services\KnowledgeRetrievalService;
use Database\Seeders\FaqSeeder;
use Database\Seeders\KnowledgeCategorySeeder;
use Database\Seeders\ShofolModuleSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('guest cannot access FAQ management routes and is redirected to login', function () {
    $faq = Faq::factory()->create();

    $this->get('/admin/faqs')->assertRedirect('/login');
    $this->get('/admin/faqs/create')->assertRedirect('/login');
    $this->post('/admin/faqs', [])->assertRedirect('/login');
    $this->get("/admin/faqs/{$faq->id}")->assertRedirect('/login');
    $this->get("/admin/faqs/{$faq->id}/edit")->assertRedirect('/login');
    $this->put("/admin/faqs/{$faq->id}", [])->assertRedirect('/login');
    $this->patch("/admin/faqs/{$faq->id}/toggle")->assertRedirect('/login');
    $this->delete("/admin/faqs/{$faq->id}")->assertRedirect('/login');
});

test('regular user cannot access FAQ management routes and receives 403', function () {
    $user = User::factory()->create(['role' => 'user']);
    $faq = Faq::factory()->create();

    $this->actingAs($user)->get('/admin/faqs')->assertStatus(403);
    $this->actingAs($user)->get('/admin/faqs/create')->assertStatus(403);
    $this->actingAs($user)->post('/admin/faqs', [
        'shofol_module_id' => $faq->shofol_module_id,
        'question' => 'Unauthorized Question?',
        'answer' => 'Unauthorized Answer.',
    ])->assertStatus(403);
    $this->actingAs($user)->get("/admin/faqs/{$faq->id}/edit")->assertStatus(403);
    $this->actingAs($user)->put("/admin/faqs/{$faq->id}", [
        'question' => 'Hacked Question?',
    ])->assertStatus(403);
    $this->actingAs($user)->patch("/admin/faqs/{$faq->id}/toggle")->assertStatus(403);
    $this->actingAs($user)->delete("/admin/faqs/{$faq->id}")->assertStatus(403);
});

test('admin can view FAQs index list with module and category hierarchy', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'HR Management', 'code' => 'HRM_TEST']);
    $category = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Leave Guidelines',
    ]);

    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $category->id,
        'question' => 'How many casual leave days do we get?',
        'slug' => 'how-many-casual-leave-days',
        'answer' => 'Employees receive 10 casual leave days annually.',
    ]);

    $response = $this->actingAs($admin)->get('/admin/faqs');

    $response->assertStatus(200);
    $response->assertSee('Dedicated FAQ Management');
    $response->assertSee('How many casual leave days do we get?');
    $response->assertSee('how-many-casual-leave-days');
    $response->assertSee('HRM_TEST');
    $response->assertSee('Leave Guidelines');
});

test('admin can view FAQ create form with active modules and categories', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Finance Operations', 'code' => 'FIN_TEST']);
    $category = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Tax Exemption',
    ]);

    $response = $this->actingAs($admin)->get('/admin/faqs/create');

    $response->assertStatus(200);
    $response->assertSee('Create New FAQ');
    $response->assertSee('Parent Shofol Module');
    $response->assertSee('Finance Operations');
    $response->assertSee('Tax Exemption');
});

test('admin can store a new FAQ with valid data and auto-generated slug', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Payroll System', 'code' => 'PAY_TEST']);
    $category = KnowledgeCategory::factory()->create(['shofol_module_id' => $module->id, 'name' => 'Tax Cutoffs']);

    $response = $this->actingAs($admin)->post('/admin/faqs', [
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $category->id,
        'question' => 'When is monthly tax deduction calculated?',
        'answer' => 'Tax deduction is computed on the 25th of each month based on statutory slabs.',
        'keywords' => 'tax, payroll, deduction, 25th',
        'is_active' => '1',
        'sort_order' => '2',
    ]);

    $response->assertRedirect('/admin/faqs');
    $this->assertDatabaseHas('faqs', [
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $category->id,
        'question' => 'When is monthly tax deduction calculated?',
        'slug' => 'when-is-monthly-tax-deduction-calculated',
        'sort_order' => 2,
        'is_active' => true,
        'created_by' => $admin->id,
    ]);
});

test('FAQ validation rejects missing required fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/faqs', [
        'shofol_module_id' => '',
        'question' => '',
        'answer' => '',
    ]);

    $response->assertSessionHasErrors(['shofol_module_id', 'question', 'answer']);
});

test('FAQ validation rejects duplicate manual slug and handles updates cleanly', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create();

    $faq1 = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'slug' => 'unique-policy-slug',
    ]);

    // Attempt to create second FAQ with duplicate slug
    $response = $this->actingAs($admin)->post('/admin/faqs', [
        'shofol_module_id' => $module->id,
        'question' => 'Another question',
        'slug' => 'unique-policy-slug',
        'answer' => 'Some answer content',
    ]);

    $response->assertSessionHasErrors('slug');

    // Updating faq1 keeping its own slug must succeed
    $updateResponse = $this->actingAs($admin)->put("/admin/faqs/{$faq1->id}", [
        'shofol_module_id' => $module->id,
        'question' => 'Updated question text',
        'slug' => 'unique-policy-slug',
        'answer' => 'Updated answer content',
    ]);

    $updateResponse->assertRedirect('/admin/faqs');
    $this->assertDatabaseHas('faqs', [
        'id' => $faq1->id,
        'question' => 'Updated question text',
    ]);
});

test('FAQ validation enforces category belongs to selected module', function () {
    $admin = User::factory()->admin()->create();
    $moduleA = ShofolModule::factory()->create(['name' => 'Module A', 'code' => 'MOD_A']);
    $moduleB = ShofolModule::factory()->create(['name' => 'Module B', 'code' => 'MOD_B']);

    // Category belongs to Module B
    $categoryB = KnowledgeCategory::factory()->create(['shofol_module_id' => $moduleB->id, 'name' => 'Module B Category']);

    // Attempt to assign Module B category while selecting Module A
    $response = $this->actingAs($admin)->post('/admin/faqs', [
        'shofol_module_id' => $moduleA->id,
        'knowledge_category_id' => $categoryB->id,
        'question' => 'Mismatched Module Question?',
        'answer' => 'Content text.',
    ]);

    $response->assertSessionHasErrors('knowledge_category_id');
});

test('existing FAQ with inactive category remains editable without validation error', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create();
    $inactiveCat = KnowledgeCategory::factory()->inactive()->create(['shofol_module_id' => $module->id, 'name' => 'Old Category']);

    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $inactiveCat->id,
        'question' => 'Old Category Question?',
        'answer' => 'Original answer text.',
    ]);

    // Updating this FAQ keeping its inactive category must pass
    $response = $this->actingAs($admin)->put("/admin/faqs/{$faq->id}", [
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $inactiveCat->id,
        'question' => 'Updated Old Category Question?',
        'answer' => 'Updated answer text.',
    ]);

    $response->assertRedirect('/admin/faqs');
    $this->assertDatabaseHas('faqs', [
        'id' => $faq->id,
        'question' => 'Updated Old Category Question?',
        'knowledge_category_id' => $inactiveCat->id,
    ]);
});

test('admin can search FAQs and filter by module, category, and status', function () {
    $admin = User::factory()->admin()->create();
    $moduleA = ShofolModule::factory()->create(['name' => 'Module Alpha', 'code' => 'ALPHA']);
    $moduleB = ShofolModule::factory()->create(['name' => 'Module Beta', 'code' => 'BETA']);

    $catA = KnowledgeCategory::factory()->create(['shofol_module_id' => $moduleA->id, 'name' => 'Alpha Policy']);
    $catB = KnowledgeCategory::factory()->create(['shofol_module_id' => $moduleB->id, 'name' => 'Beta Protocol']);

    $faq1 = Faq::factory()->create([
        'shofol_module_id' => $moduleA->id,
        'knowledge_category_id' => $catA->id,
        'question' => 'Alpha Question Onboarding Procedure?',
        'answer' => 'Answer for Alpha.',
        'keywords' => 'alpha, onboarding',
        'is_active' => true,
    ]);

    $faq2 = Faq::factory()->inactive()->create([
        'shofol_module_id' => $moduleB->id,
        'knowledge_category_id' => $catB->id,
        'question' => 'Beta Inactive Protocol Details?',
        'answer' => 'Answer for Beta.',
        'keywords' => 'beta, protocol',
        'is_active' => false,
    ]);

    // 1. Search by keyword
    $this->actingAs($admin)->get('/admin/faqs?q=Onboarding')
        ->assertStatus(200)
        ->assertSee('Alpha Question Onboarding Procedure?')
        ->assertDontSee('Beta Inactive Protocol Details?');

    // 2. Filter by module ID
    $this->actingAs($admin)->get("/admin/faqs?module_id={$moduleB->id}")
        ->assertStatus(200)
        ->assertSee('Beta Inactive Protocol Details?')
        ->assertDontSee('Alpha Question Onboarding Procedure?');

    // 3. Filter by category ID
    $this->actingAs($admin)->get("/admin/faqs?category_id={$catA->id}")
        ->assertStatus(200)
        ->assertSee('Alpha Question Onboarding Procedure?')
        ->assertDontSee('Beta Inactive Protocol Details?');

    // 4. Filter by status: active
    $this->actingAs($admin)->get('/admin/faqs?status=active')
        ->assertStatus(200)
        ->assertSee('Alpha Question Onboarding Procedure?')
        ->assertDontSee('Beta Inactive Protocol Details?');

    // 5. Filter by status: inactive
    $this->actingAs($admin)->get('/admin/faqs?status=inactive')
        ->assertStatus(200)
        ->assertSee('Beta Inactive Protocol Details?')
        ->assertDontSee('Alpha Question Onboarding Procedure?');

    // 6. Empty filters returns both
    $this->actingAs($admin)->get('/admin/faqs?q=&module_id=&category_id=&status=')
        ->assertStatus(200)
        ->assertSee('Alpha Question Onboarding Procedure?')
        ->assertSee('Beta Inactive Protocol Details?');
});

test('admin can toggle FAQ active status', function () {
    $admin = User::factory()->admin()->create();
    $faq = Faq::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->patch("/admin/faqs/{$faq->id}/toggle");

    $response->assertRedirect();
    $this->assertDatabaseHas('faqs', [
        'id' => $faq->id,
        'is_active' => false,
    ]);

    // Toggle back to active
    $this->actingAs($admin)->patch("/admin/faqs/{$faq->id}/toggle");
    $this->assertDatabaseHas('faqs', [
        'id' => $faq->id,
        'is_active' => true,
    ]);
});

test('admin can delete an FAQ', function () {
    $admin = User::factory()->admin()->create();
    $faq = Faq::factory()->create(['question' => 'Ephemeral Question to Delete?']);

    $response = $this->actingAs($admin)->delete("/admin/faqs/{$faq->id}");

    $response->assertRedirect('/admin/faqs');
    $this->assertDatabaseMissing('faqs', [
        'id' => $faq->id,
    ]);
});

test('FAQs listing paginates correctly when exceeding 15 items', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['code' => 'PAGE_MOD']);

    for ($i = 1; $i <= 18; $i++) {
        Faq::factory()->create([
            'shofol_module_id' => $module->id,
            'question' => sprintf('FAQ Pagination Item %02d?', $i),
            'sort_order' => $i,
        ]);
    }

    $page1 = $this->actingAs($admin)->get("/admin/faqs?module_id={$module->id}&page=1");
    $page1->assertStatus(200);
    $page1->assertSee('Configured FAQs (18)');
    $page1->assertSee('FAQ Pagination Item 01?');
    $page1->assertSee('FAQ Pagination Item 15?');
    $page1->assertDontSee('FAQ Pagination Item 16?');

    $page2 = $this->actingAs($admin)->get("/admin/faqs?module_id={$module->id}&page=2");
    $page2->assertStatus(200);
    $page2->assertSee('Configured FAQs (18)');
    $page2->assertSee('FAQ Pagination Item 16?');
    $page2->assertSee('FAQ Pagination Item 18?');
    $page2->assertDontSee('FAQ Pagination Item 01?');
});

test('FaqSeeder is idempotent and creates verified FAQs without duplicates', function () {
    // Ensure core modules and categories exist
    (new ShofolModuleSeeder())->run();
    (new KnowledgeCategorySeeder())->run();

    $seeder = new FaqSeeder();
    $seeder->run();

    $initialCount = Faq::count();
    expect($initialCount)->toBeGreaterThanOrEqual(4);

    // Run again
    $seeder->run();
    expect(Faq::count())->toBe($initialCount);

    // Verify verified seed contents exist
    $this->assertDatabaseHas('faqs', [
        'slug' => 'annual-and-casual-leave-entitlement',
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('faqs', [
        'slug' => 'customer-invoice-payment-terms-and-late-fees',
        'is_active' => true,
    ]);
});

test('RAG retrieval retrieves active FAQ when user asks relevant question', function () {
    $module = ShofolModule::factory()->create(['name' => 'Human Resources', 'code' => 'HRM']);
    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'question' => 'How many days of bereavement leave are granted to employees?',
        'slug' => 'bereavement-leave-days',
        'answer' => 'Employees are entitled to 5 consecutive paid business days of bereavement leave upon notifying HR.',
        'keywords' => 'bereavement leave, compassionate leave, funeral, 5 days',
        'is_active' => true,
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);
    $result = $retrievalService->retrieve('How many days of bereavement leave are granted?');

    expect($result['has_relevant_results'])->toBeTrue();
    expect($result['articles']->pluck('id')->all())->toContain($faq->id);
    expect($result['context'])->toContain('bereavement leave');
    expect($result['context'])->toContain('5 consecutive paid business days');
    expect($result['sources'][0]['title'])->toBe($faq->question);
});

test('inactive FAQs are excluded from normal RAG retrieval', function () {
    $module = ShofolModule::factory()->create(['name' => 'Operations', 'code' => 'OPS']);
    $faq = Faq::factory()->inactive()->create([
        'shofol_module_id' => $module->id,
        'question' => 'What is the discontinued remote workstation stipend policy?',
        'slug' => 'discontinued-workstation-stipend',
        'answer' => 'This stipend was discontinued in December 2025.',
        'keywords' => 'workstation stipend, remote allowance',
        'is_active' => false,
    ]);

    $retrievalService = app(KnowledgeRetrievalService::class);
    $result = $retrievalService->retrieve('What is the discontinued remote workstation stipend policy?');

    expect($result['articles']->pluck('id')->all())->not->toContain($faq->id);
});

test('AI chat integrates FAQ answers into LLM prompt context and cites sources', function () {
    Config::set('services.ai.api_key', 'mock-key');

    $module = ShofolModule::factory()->create(['name' => 'Accounting Core', 'code' => 'ACC']);
    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'question' => 'What is the corporate petty cash reimbursement ceiling?',
        'slug' => 'petty-cash-reimbursement-ceiling',
        'answer' => 'Petty cash reimbursement is capped at 5,000 BDT per expense with original receipt submission.',
        'keywords' => 'petty cash, reimbursement, 5000 bdt, receipts',
        'is_active' => true,
    ]);

    Http::fake([
        '*/chat/completions' => function ($request) {
            $payload = $request->data();
            $systemMsg = $payload['messages'][0]['content'] ?? '';

            if (str_contains($systemMsg, '5,000 BDT') && str_contains($systemMsg, 'Petty cash')) {
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'The petty cash reimbursement ceiling is 5,000 BDT per expense with receipt submission.']],
                    ],
                ], 200);
            }

            return Http::response(['error' => ['message' => 'Missing context']], 400);
        },
    ]);

    $aiService = app(AIService::class);
    $response = $aiService->ask('What is the corporate petty cash reimbursement ceiling?');

    expect($response['success'])->toBeTrue();
    expect($response['answer'])->toContain('5,000 BDT');
    expect($response['sources'])->not->toBeEmpty();
    expect($response['sources'][0]['id'])->toBe($faq->id);
});

test('AI chat provides verified policy fallback using FAQ when AI provider returns 429 quota exhausted', function () {
    Config::set('services.ai.api_key', 'mock-key');

    $module = ShofolModule::factory()->create(['name' => 'Accounting Core', 'code' => 'ACC']);
    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'question' => 'What is the corporate petty cash reimbursement ceiling?',
        'slug' => 'petty-cash-reimbursement-ceiling-fallback',
        'answer' => 'Petty cash reimbursement is capped at 5,000 BDT per expense with original receipt submission.',
        'keywords' => 'petty cash, reimbursement, 5000 bdt, receipts',
        'is_active' => true,
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'Resource has been exhausted (e.g. check quota)',
                'status' => 'RESOURCE_EXHAUSTED',
            ],
        ], 429),
    ]);

    $aiService = app(AIService::class);
    $fallbackResponse = $aiService->ask('What is the corporate petty cash reimbursement ceiling?');

    expect($fallbackResponse['success'])->toBeFalse();
    expect($fallbackResponse['fallback'])->toBeTrue();
    expect($fallbackResponse['answer'])->toContain('Verified Policy');
    expect($fallbackResponse['answer'])->toContain('5,000 BDT');
});

test('deleting a category nullifies category_id on associated FAQs safely without deleting the FAQ', function () {
    $module = ShofolModule::factory()->create();
    $category = KnowledgeCategory::factory()->create(['shofol_module_id' => $module->id]);

    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
        'knowledge_category_id' => $category->id,
    ]);

    // Delete the category
    $category->delete();

    $faq->refresh();
    expect($faq->knowledge_category_id)->toBeNull();
    expect($faq->exists)->toBeTrue();
    expect($faq->shofol_module_id)->toBe($module->id);
});

test('module cannot be deleted while FAQs reference it', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Locked Module', 'code' => 'LOCK']);

    $faq = Faq::factory()->create([
        'shofol_module_id' => $module->id,
    ]);

    expect($module->canBeDeleted())->toBeFalse();

    $response = $this->actingAs($admin)->delete("/admin/modules/{$module->id}");
    $response->assertRedirect('/admin/modules');
    $this->assertDatabaseHas('shofol_modules', ['id' => $module->id]);
});

<?php

use App\Models\KnowledgeArticle;
use App\Models\User;

test('guest cannot access knowledge articles routes', function () {
    $article = KnowledgeArticle::factory()->create();

    $this->get('/admin/knowledge-articles')->assertRedirect('/login');
    $this->get('/admin/knowledge-articles/create')->assertRedirect('/login');
    $this->post('/admin/knowledge-articles', [])->assertRedirect('/login');
    $this->get("/admin/knowledge-articles/{$article->id}")->assertRedirect('/login');
    $this->get("/admin/knowledge-articles/{$article->id}/edit")->assertRedirect('/login');
    $this->put("/admin/knowledge-articles/{$article->id}", [])->assertRedirect('/login');
    $this->delete("/admin/knowledge-articles/{$article->id}")->assertRedirect('/login');
});

test('regular user cannot access knowledge articles routes', function () {
    $user = User::factory()->create(['role' => 'user']);
    $article = KnowledgeArticle::factory()->create();

    $this->actingAs($user)->get('/admin/knowledge-articles')->assertStatus(403);
    $this->actingAs($user)->get('/admin/knowledge-articles/create')->assertStatus(403);
    $this->actingAs($user)->post('/admin/knowledge-articles', [
        'module' => 'HRM',
        'category' => 'Policies',
        'title' => 'Forbidden Post',
        'content' => 'Restricted content',
        'status' => 'active',
    ])->assertStatus(403);

    $this->actingAs($user)->get("/admin/knowledge-articles/{$article->id}")->assertStatus(403);
    $this->actingAs($user)->get("/admin/knowledge-articles/{$article->id}/edit")->assertStatus(403);
    $this->actingAs($user)->put("/admin/knowledge-articles/{$article->id}", [
        'title' => 'Hacked Title',
    ])->assertStatus(403);
    $this->actingAs($user)->delete("/admin/knowledge-articles/{$article->id}")->assertStatus(403);
});

test('admin can view knowledge articles list', function () {
    $admin = User::factory()->admin()->create();
    $article = KnowledgeArticle::factory()->create([
        'title' => 'HR Onboarding Protocol',
        'module' => 'HRM',
    ]);

    $response = $this->actingAs($admin)->get('/admin/knowledge-articles');

    $response->assertStatus(200);
    $response->assertSee('Knowledge Base Management');
    $response->assertSee('HR Onboarding Protocol');
});

test('admin can filter knowledge articles by module and status', function () {
    $admin = User::factory()->admin()->create();
    $hrmArticle = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'HR Benefit Plan',
        'status' => 'active',
    ]);
    $crmArticle = KnowledgeArticle::factory()->create([
        'module' => 'CRM',
        'title' => 'CRM Lead Conversion',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get('/admin/knowledge-articles?module=HRM');

    $response->assertStatus(200);
    $response->assertSee('HR Benefit Plan');
    $response->assertDontSee('CRM Lead Conversion');
});

test('admin can search knowledge articles by keyword', function () {
    $admin = User::factory()->admin()->create();
    $articleA = KnowledgeArticle::factory()->create([
        'title' => 'Tax Withholding Calculation',
        'keywords' => 'tax, withholding, deduction',
    ]);
    $articleB = KnowledgeArticle::factory()->create([
        'title' => 'Customer Feedback Loop',
        'keywords' => 'nps, feedback',
    ]);

    $response = $this->actingAs($admin)->get('/admin/knowledge-articles?q=Withholding');

    $response->assertStatus(200);
    $response->assertSee('Tax Withholding Calculation');
    $response->assertDontSee('Customer Feedback Loop');
});

test('admin can view create article form', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/knowledge-articles/create');

    $response->assertStatus(200);
    $response->assertSee('Create New Knowledge Article');
});

test('admin can store a new knowledge article with valid data', function () {
    $admin = User::factory()->admin()->create();

    $payload = [
        'module' => 'Payroll',
        'category' => 'Overtime',
        'title' => 'Weekend Overtime Rate Policy',
        'question' => 'What is the hourly overtime multiplier for weekend shifts?',
        'content' => 'Weekend overtime is reimbursed at 2.0x standard hourly rate.',
        'keywords' => 'overtime, weekend, payroll multiplier',
        'status' => 'active',
    ];

    $response = $this->actingAs($admin)->post('/admin/knowledge-articles', $payload);

    $response->assertRedirect(route('admin.knowledge-articles.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('knowledge_articles', [
        'title' => 'Weekend Overtime Rate Policy',
        'module' => 'Payroll',
        'category' => 'Overtime',
        'created_by' => $admin->id,
    ]);
});

test('article validation fails with invalid or missing fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'InvalidModule',
        'title' => '',
        'category' => '',
        'content' => '',
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['module', 'title', 'category', 'content', 'status']);
});

test('admin can view knowledge article details', function () {
    $admin = User::factory()->admin()->create();
    $article = KnowledgeArticle::factory()->create([
        'title' => 'Accounting Ledger Reconciliation',
        'question' => 'How often should general ledgers be reconciled?',
        'content' => 'General ledgers must be reconciled weekly.',
    ]);

    $response = $this->actingAs($admin)->get("/admin/knowledge-articles/{$article->id}");

    $response->assertStatus(200);
    $response->assertSee('Accounting Ledger Reconciliation');
    $response->assertSee('How often should general ledgers be reconciled?');
    $response->assertSee('General ledgers must be reconciled weekly.');
});

test('admin can view edit form', function () {
    $admin = User::factory()->admin()->create();
    $article = KnowledgeArticle::factory()->create([
        'title' => 'CRM Deals Management',
    ]);

    $response = $this->actingAs($admin)->get("/admin/knowledge-articles/{$article->id}/edit");

    $response->assertStatus(200);
    $response->assertSee('Edit Knowledge Article');
    $response->assertSee('CRM Deals Management');
});

test('admin can update knowledge article', function () {
    $admin = User::factory()->admin()->create();
    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Recruitment',
        'title' => 'Old Title',
        'content' => 'Old content',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->put("/admin/knowledge-articles/{$article->id}", [
        'module' => 'HRM',
        'category' => 'Hiring',
        'title' => 'Updated Interview Process',
        'question' => 'What is the interview procedure?',
        'content' => 'There are 3 rounds of technical interviews.',
        'keywords' => 'hiring, interview',
        'status' => 'inactive',
    ]);

    $response->assertRedirect(route('admin.knowledge-articles.show', $article));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('knowledge_articles', [
        'id' => $article->id,
        'title' => 'Updated Interview Process',
        'category' => 'Hiring',
        'status' => 'inactive',
    ]);
});

test('admin can delete knowledge article', function () {
    $admin = User::factory()->admin()->create();
    $article = KnowledgeArticle::factory()->create([
        'title' => 'Article to Delete',
    ]);

    $response = $this->actingAs($admin)->delete("/admin/knowledge-articles/{$article->id}");

    $response->assertRedirect(route('admin.knowledge-articles.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('knowledge_articles', [
        'id' => $article->id,
    ]);
});

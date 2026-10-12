<?php

use App\Models\KnowledgeArticle;
use App\Models\ShofolModule;
use App\Models\User;
use Database\Seeders\ShofolModuleSeeder;

test('guest cannot access module management routes and is redirected to login', function () {
    $module = ShofolModule::factory()->create();

    $this->get('/admin/modules')->assertRedirect('/login');
    $this->get('/admin/modules/create')->assertRedirect('/login');
    $this->post('/admin/modules', [])->assertRedirect('/login');
    $this->get("/admin/modules/{$module->id}")->assertRedirect('/login');
    $this->get("/admin/modules/{$module->id}/edit")->assertRedirect('/login');
    $this->put("/admin/modules/{$module->id}", [])->assertRedirect('/login');
    $this->patch("/admin/modules/{$module->id}/toggle")->assertRedirect('/login');
    $this->delete("/admin/modules/{$module->id}")->assertRedirect('/login');
});

test('regular user cannot access module management routes and receives 403', function () {
    $user = User::factory()->create(['role' => 'user']);
    $module = ShofolModule::factory()->create();

    $this->actingAs($user)->get('/admin/modules')->assertStatus(403);
    $this->actingAs($user)->get('/admin/modules/create')->assertStatus(403);
    $this->actingAs($user)->post('/admin/modules', [
        'name' => 'Unauthorized Module',
        'code' => 'UNAUTH',
    ])->assertStatus(403);
    $this->actingAs($user)->get("/admin/modules/{$module->id}/edit")->assertStatus(403);
    $this->actingAs($user)->put("/admin/modules/{$module->id}", [
        'name' => 'Hacked Name',
    ])->assertStatus(403);
    $this->actingAs($user)->patch("/admin/modules/{$module->id}/toggle")->assertStatus(403);
    $this->actingAs($user)->delete("/admin/modules/{$module->id}")->assertStatus(403);
});

test('admin can view modules index list with accurate counts', function () {
    $admin = User::factory()->admin()->create();

    $moduleA = ShofolModule::factory()->create([
        'name' => 'Supply Chain Logistics',
        'code' => 'SCM',
        'is_active' => true,
    ]);

    KnowledgeArticle::factory()->create([
        'module' => 'SCM',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/modules');

    $response->assertStatus(200);
    $response->assertSee('Shofol Module Management');
    $response->assertSee('Supply Chain Logistics');
    $response->assertSee('SCM');
    $response->assertSee('Active in KB');
});

test('admin can search and filter modules by status', function () {
    $admin = User::factory()->admin()->create();

    $activeMod = ShofolModule::factory()->create([
        'name' => 'Procurement Operations',
        'code' => 'PROC',
        'is_active' => true,
    ]);

    $inactiveMod = ShofolModule::factory()->inactive()->create([
        'name' => 'Legacy Telephony',
        'code' => 'TEL',
    ]);

    // Search by name
    $searchResponse = $this->actingAs($admin)->get('/admin/modules?q=Procurement');
    $searchResponse->assertStatus(200);
    $searchResponse->assertSee('Procurement Operations');
    $searchResponse->assertDontSee('Legacy Telephony');

    // Filter by inactive status
    $filterResponse = $this->actingAs($admin)->get('/admin/modules?status=inactive');
    $filterResponse->assertStatus(200);
    $filterResponse->assertSee('Legacy Telephony');
    $filterResponse->assertDontSee('Procurement Operations');
});

test('admin can view module create form', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/modules/create');

    $response->assertStatus(200);
    $response->assertSee('Create New Shofol Module');
    $response->assertSee('Module Code / Key');
});

test('admin can store a new module with valid data', function () {
    $admin = User::factory()->admin()->create();

    $payload = [
        'name' => 'Warehouse Management',
        'code' => 'WMS',
        'icon' => 'bi-boxes',
        'description' => 'Stock auditing, storage bin tracking, and dispatching SOPs.',
        'sort_order' => 5,
        'is_active' => '1',
    ];

    $response = $this->actingAs($admin)->post('/admin/modules', $payload);

    $response->assertRedirect('/admin/modules');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('shofol_modules', [
        'name' => 'Warehouse Management',
        'code' => 'WMS',
        'icon' => 'bi-boxes',
        'sort_order' => 5,
        'is_active' => true,
    ]);
});

test('module validation rejects missing required fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/modules', [
        'name' => '',
        'code' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'code']);
});

test('module validation rejects duplicate name or code', function () {
    $admin = User::factory()->admin()->create();

    ShofolModule::factory()->create([
        'name' => 'Unique Module',
        'code' => 'UNIQ',
    ]);

    $response = $this->actingAs($admin)->post('/admin/modules', [
        'name' => 'Unique Module',
        'code' => 'UNIQ2',
    ]);
    $response->assertSessionHasErrors(['name']);

    $responseCode = $this->actingAs($admin)->post('/admin/modules', [
        'name' => 'Another Module',
        'code' => 'UNIQ',
    ]);
    $responseCode->assertSessionHasErrors(['code']);
});

test('module code validation rejects invalid characters', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/modules', [
        'name' => 'Invalid Code Module',
        'code' => 'INVALID CODE WITH SPACES!',
    ]);

    $response->assertSessionHasErrors(['code']);
});

test('admin can view edit form for existing module', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'name' => 'Asset Tracking',
        'code' => 'ASSET',
    ]);

    $response = $this->actingAs($admin)->get("/admin/modules/{$module->id}/edit");

    $response->assertStatus(200);
    $response->assertSee('Edit Shofol Module');
    $response->assertSee('Asset Tracking');
    $response->assertSee('ASSET');
});

test('admin can update an existing module', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'name' => 'Initial Module',
        'code' => 'INIT',
    ]);

    $response = $this->actingAs($admin)->put("/admin/modules/{$module->id}", [
        'name' => 'Updated Module Name',
        'code' => 'INIT',
        'icon' => 'bi-check',
        'description' => 'Updated description text.',
        'sort_order' => 20,
        'is_active' => '1',
    ]);

    $response->assertRedirect('/admin/modules');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('shofol_modules', [
        'id' => $module->id,
        'name' => 'Updated Module Name',
        'sort_order' => 20,
    ]);
});

test('updating module code cascades to existing knowledge articles', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'name' => 'Helpdesk',
        'code' => 'HLP',
    ]);

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HLP',
        'title' => 'Helpdesk SLA Policy',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->put("/admin/modules/{$module->id}", [
        'name' => 'Service Desk',
        'code' => 'SDESK',
        'sort_order' => 1,
        'is_active' => '1',
    ]);

    $response->assertRedirect('/admin/modules');
    $this->assertDatabaseHas('shofol_modules', ['id' => $module->id, 'code' => 'SDESK']);
    $this->assertDatabaseHas('knowledge_articles', ['id' => $article->id, 'module' => 'SDESK']);
});

test('admin can toggle module active status', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'is_active' => true,
    ]);

    // Toggle to inactive
    $response1 = $this->actingAs($admin)->patch("/admin/modules/{$module->id}/toggle");
    $response1->assertSessionHas('success');
    expect($module->fresh()->is_active)->toBeFalse();

    // Toggle back to active
    $response2 = $this->actingAs($admin)->patch("/admin/modules/{$module->id}/toggle");
    $response2->assertSessionHas('success');
    expect($module->fresh()->is_active)->toBeTrue();
});

test('inactive module is not offered in new article creation form', function () {
    $admin = User::factory()->admin()->create();

    $activeMod = ShofolModule::factory()->create([
        'name' => 'Active Operations',
        'code' => 'ACTMOD',
        'is_active' => true,
    ]);

    $inactiveMod = ShofolModule::factory()->inactive()->create([
        'name' => 'Deprecated Project',
        'code' => 'INACTMOD',
    ]);

    $response = $this->actingAs($admin)->get('/admin/knowledge-articles/create');

    $response->assertStatus(200);
    $response->assertSee('ACTMOD');
    $response->assertDontSee('INACTMOD');

    // Attempting to store article with inactive module should fail validation
    $postResponse = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'INACTMOD',
        'category' => 'General',
        'title' => 'Article with Inactive Module',
        'content' => 'Sample content',
        'status' => 'active',
    ]);

    $postResponse->assertSessionHasErrors(['module']);
});

test('existing article with inactive module remains viewable and editable without validation error', function () {
    $admin = User::factory()->admin()->create();

    $module = ShofolModule::factory()->inactive()->create([
        'code' => 'ARCHIVED',
        'name' => 'Archived Dept',
    ]);

    $article = KnowledgeArticle::factory()->create([
        'module' => 'ARCHIVED',
        'title' => 'Old Department Protocol',
        'created_by' => $admin->id,
    ]);

    // View edit form
    $editResponse = $this->actingAs($admin)->get("/admin/knowledge-articles/{$article->id}/edit");
    $editResponse->assertStatus(200);
    $editResponse->assertSee('ARCHIVED');

    // Update article while keeping inactive module
    $updateResponse = $this->actingAs($admin)->put("/admin/knowledge-articles/{$article->id}", [
        'module' => 'ARCHIVED',
        'category' => $article->category,
        'title' => 'Updated Old Protocol Title',
        'content' => $article->content,
        'status' => 'active',
    ]);

    $updateResponse->assertRedirect("/admin/knowledge-articles/{$article->id}");
    $this->assertDatabaseHas('knowledge_articles', [
        'id' => $article->id,
        'title' => 'Updated Old Protocol Title',
        'module' => 'ARCHIVED',
    ]);
});

test('admin cannot delete module when knowledge articles reference its code', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'name' => 'Protected Module',
        'code' => 'PROT',
    ]);

    KnowledgeArticle::factory()->create([
        'module' => 'PROT',
        'title' => 'Referencing Article',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/modules/{$module->id}");

    $response->assertRedirect('/admin/modules');
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('shofol_modules', ['id' => $module->id]);
});

test('admin can delete module when no knowledge articles reference it', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create([
        'name' => 'Empty Module',
        'code' => 'EMPTY',
    ]);

    $response = $this->actingAs($admin)->delete("/admin/modules/{$module->id}");

    $response->assertRedirect('/admin/modules');
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('shofol_modules', ['id' => $module->id]);
});

test('ShofolModuleSeeder is idempotent and creates 4 core modules without duplicates', function () {
    // Run seeder first time
    $seeder = new ShofolModuleSeeder();
    $seeder->run();

    expect(ShofolModule::whereIn('code', ['HRM', 'Payroll', 'CRM', 'Accounting'])->count())->toBe(4);

    // Run seeder second time
    $seeder->run();

    expect(ShofolModule::whereIn('code', ['HRM', 'Payroll', 'CRM', 'Accounting'])->count())->toBe(4);
});

test('dynamic module articles are fully compatible with RAG retrieval', function () {
    $admin = User::factory()->admin()->create();

    $module = ShofolModule::factory()->create([
        'name' => 'Logistics & Fleet',
        'code' => 'LOGISTICS',
        'is_active' => true,
    ]);

    KnowledgeArticle::factory()->create([
        'module' => 'LOGISTICS',
        'category' => 'Vehicle Maintenance',
        'title' => 'Commercial Fleet Inspection Checklist',
        'question' => 'How often must fleet delivery vehicles undergo mechanical inspection?',
        'content' => 'All commercial fleet vehicles must undergo a multi-point safety inspection every 90 days. Maintenance records must be logged in the Logistics portal.',
        'keywords' => 'fleet, vehicle, mechanical inspection, delivery, maintenance, logistics',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    /** @var \App\Services\KnowledgeRetrievalService $retrievalService */
    $retrievalService = app(\App\Services\KnowledgeRetrievalService::class);
    $result = $retrievalService->retrieve('How often must fleet vehicles undergo inspection?');

    expect($result['has_relevant_results'])->toBeTrue();
    expect($result['articles']->first()->module)->toBe('LOGISTICS');
    expect($result['sources'][0]['module'])->toBe('LOGISTICS');
    expect($result['context'])->toContain('LOGISTICS');
});

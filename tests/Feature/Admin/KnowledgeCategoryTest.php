<?php

use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use App\Models\User;
use Database\Seeders\KnowledgeCategorySeeder;
use Database\Seeders\ShofolModuleSeeder;

test('guest cannot access knowledge category management routes and is redirected to login', function () {
    $category = KnowledgeCategory::factory()->create();

    $this->get('/admin/categories')->assertRedirect('/login');
    $this->get('/admin/categories/create')->assertRedirect('/login');
    $this->post('/admin/categories', [])->assertRedirect('/login');
    $this->get("/admin/categories/{$category->id}")->assertRedirect('/login');
    $this->get("/admin/categories/{$category->id}/edit")->assertRedirect('/login');
    $this->put("/admin/categories/{$category->id}", [])->assertRedirect('/login');
    $this->patch("/admin/categories/{$category->id}/toggle")->assertRedirect('/login');
    $this->delete("/admin/categories/{$category->id}")->assertRedirect('/login');
});

test('regular user cannot access category management routes and receives 403', function () {
    $user = User::factory()->create(['role' => 'user']);
    $category = KnowledgeCategory::factory()->create();

    $this->actingAs($user)->get('/admin/categories')->assertStatus(403);
    $this->actingAs($user)->get('/admin/categories/create')->assertStatus(403);
    $this->actingAs($user)->post('/admin/categories', [
        'name' => 'Unauthorized Category',
        'shofol_module_id' => $category->shofol_module_id,
    ])->assertStatus(403);
    $this->actingAs($user)->get("/admin/categories/{$category->id}/edit")->assertStatus(403);
    $this->actingAs($user)->put("/admin/categories/{$category->id}", [
        'name' => 'Hacked Name',
    ])->assertStatus(403);
    $this->actingAs($user)->patch("/admin/categories/{$category->id}/toggle")->assertStatus(403);
    $this->actingAs($user)->delete("/admin/categories/{$category->id}")->assertStatus(403);
});

test('admin can view categories index list with parent module details', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Operations', 'code' => 'OPS']);

    $category = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Dispatch Guidelines',
        'slug' => 'dispatch-guidelines',
    ]);

    $response = $this->actingAs($admin)->get('/admin/categories');

    $response->assertStatus(200);
    $response->assertSee('Knowledge Category Management');
    $response->assertSee('Dispatch Guidelines');
    $response->assertSee('OPS');
});

test('admin can search categories and filter by module and status', function () {
    $admin = User::factory()->admin()->create();
    $moduleA = ShofolModule::factory()->create(['name' => 'Module Alpha', 'code' => 'ALPHA']);
    $moduleB = ShofolModule::factory()->create(['name' => 'Module Beta', 'code' => 'BETA']);

    $cat1 = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleA->id,
        'name' => 'Alpha Policy',
        'is_active' => true,
    ]);

    $cat2 = KnowledgeCategory::factory()->inactive()->create([
        'shofol_module_id' => $moduleB->id,
        'name' => 'Beta Protocol',
    ]);

    // Search query
    $searchResponse = $this->actingAs($admin)->get('/admin/categories?q=Alpha');
    $searchResponse->assertStatus(200);
    $searchResponse->assertSee('Alpha Policy');
    $searchResponse->assertDontSee('Beta Protocol');

    // Filter by module
    $moduleResponse = $this->actingAs($admin)->get("/admin/categories?module_id={$moduleB->id}");
    $moduleResponse->assertStatus(200);
    $moduleResponse->assertSee('Beta Protocol');
    $moduleResponse->assertDontSee('Alpha Policy');

    // Filter by inactive status
    $statusResponse = $this->actingAs($admin)->get('/admin/categories?status=inactive');
    $statusResponse->assertStatus(200);
    $statusResponse->assertSee('Beta Protocol');
    $statusResponse->assertDontSee('Alpha Policy');
});

test('admin can view category create form', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/categories/create');

    $response->assertStatus(200);
    $response->assertSee('Create New Knowledge Category');
    $response->assertSee('Parent Shofol Module');
});

test('admin can store a new category with valid data', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Finance', 'code' => 'FIN']);

    $payload = [
        'shofol_module_id' => $module->id,
        'name' => 'Tax Exemption Guidelines',
        'slug' => 'tax-exemption-guidelines',
        'description' => 'Documentation covering VAT and tax exemption protocols.',
        'sort_order' => 5,
        'is_active' => '1',
    ];

    $response = $this->actingAs($admin)->post('/admin/categories', $payload);

    $response->assertRedirect('/admin/categories');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('knowledge_categories', [
        'shofol_module_id' => $module->id,
        'name' => 'Tax Exemption Guidelines',
        'slug' => 'tax-exemption-guidelines',
        'sort_order' => 5,
        'is_active' => true,
    ]);
});

test('category validation rejects missing required fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/categories', [
        'shofol_module_id' => '',
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['shofol_module_id', 'name']);
});

test('category uniqueness is scoped to parent module', function () {
    $admin = User::factory()->admin()->create();
    $moduleA = ShofolModule::factory()->create();
    $moduleB = ShofolModule::factory()->create();

    KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleA->id,
        'name' => 'General Policy',
        'slug' => 'general-policy',
    ]);

    // Duplicate name within same module must fail
    $dupResponse = $this->actingAs($admin)->post('/admin/categories', [
        'shofol_module_id' => $moduleA->id,
        'name' => 'General Policy',
        'slug' => 'general-policy-2',
    ]);
    $dupResponse->assertSessionHasErrors(['name']);

    // Same category name in a DIFFERENT module is allowed
    $diffResponse = $this->actingAs($admin)->post('/admin/categories', [
        'shofol_module_id' => $moduleB->id,
        'name' => 'General Policy',
        'slug' => 'general-policy',
    ]);
    $diffResponse->assertSessionHasNoErrors();
    $this->assertDatabaseHas('knowledge_categories', [
        'shofol_module_id' => $moduleB->id,
        'name' => 'General Policy',
    ]);
});

test('admin can view edit form for existing category', function () {
    $admin = User::factory()->admin()->create();
    $category = KnowledgeCategory::factory()->create(['name' => 'Leave Policies']);

    $response = $this->actingAs($admin)->get("/admin/categories/{$category->id}/edit");

    $response->assertStatus(200);
    $response->assertSee('Edit Knowledge Category');
    $response->assertSee('Leave Policies');
});

test('admin can update an existing category and cascade name changes to articles', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['code' => 'HRM']);
    $category = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Initial Category Name',
    ]);

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Initial Category Name',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->put("/admin/categories/{$category->id}", [
        'shofol_module_id' => $module->id,
        'name' => 'Updated Category Name',
        'slug' => 'updated-category-name',
        'sort_order' => 15,
        'is_active' => '1',
    ]);

    $response->assertRedirect('/admin/categories');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('knowledge_categories', [
        'id' => $category->id,
        'name' => 'Updated Category Name',
    ]);

    // Verify referencing article was automatically synchronized
    $this->assertDatabaseHas('knowledge_articles', [
        'id' => $article->id,
        'category' => 'Updated Category Name',
    ]);
});

test('admin can toggle category active status', function () {
    $admin = User::factory()->admin()->create();
    $category = KnowledgeCategory::factory()->create(['is_active' => true]);

    $response1 = $this->actingAs($admin)->patch("/admin/categories/{$category->id}/toggle");
    $response1->assertSessionHas('success');
    expect($category->fresh()->is_active)->toBeFalse();

    $response2 = $this->actingAs($admin)->patch("/admin/categories/{$category->id}/toggle");
    $response2->assertSessionHas('success');
    expect($category->fresh()->is_active)->toBeTrue();
});

test('admin cannot delete category when knowledge articles reference its name', function () {
    $admin = User::factory()->admin()->create();
    $category = KnowledgeCategory::factory()->create(['name' => 'Referenced Category']);

    KnowledgeArticle::factory()->create([
        'category' => 'Referenced Category',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");

    $response->assertRedirect('/admin/categories');
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('knowledge_categories', ['id' => $category->id]);
});

test('admin can delete category when no knowledge articles reference it', function () {
    $admin = User::factory()->admin()->create();
    $category = KnowledgeCategory::factory()->create(['name' => 'Orphan Category']);

    $response = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");

    $response->assertRedirect('/admin/categories');
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('knowledge_categories', ['id' => $category->id]);
});

test('knowledge article validation enforces category belongs to selected module', function () {
    $admin = User::factory()->admin()->create();
    $moduleHRM = ShofolModule::factory()->create(['code' => 'HRM']);
    $moduleCRM = ShofolModule::factory()->create(['code' => 'CRM']);

    KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleHRM->id,
        'name' => 'HR Attendance Guidelines',
        'is_active' => true,
    ]);

    KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleCRM->id,
        'name' => 'CRM Pipeline Stages',
        'is_active' => true,
    ]);

    // Submitting an HRM article with a CRM category should fail
    $mismatchResponse = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'HRM',
        'category' => 'CRM Pipeline Stages',
        'title' => 'Mismatched Article',
        'content' => 'Some content',
        'status' => 'active',
    ]);

    $mismatchResponse->assertSessionHasErrors(['category']);

    // Submitting with correct matching category succeeds
    $validResponse = $this->actingAs($admin)->post('/admin/knowledge-articles', [
        'module' => 'HRM',
        'category' => 'HR Attendance Guidelines',
        'title' => 'Valid HRM Article',
        'content' => 'Correct matching content',
        'status' => 'active',
    ]);

    $validResponse->assertRedirect('/admin/knowledge-articles');
    $this->assertDatabaseHas('knowledge_articles', [
        'module' => 'HRM',
        'category' => 'HR Attendance Guidelines',
    ]);
});

test('existing article with legacy or inactive category remains editable without validation errors', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['code' => 'HRM']);

    $inactiveCat = KnowledgeCategory::factory()->inactive()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Legacy Discontinued Benefit',
    ]);

    $article = KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'category' => 'Legacy Discontinued Benefit',
        'title' => 'Old Benefit Documentation',
        'created_by' => $admin->id,
    ]);

    // Editing this article keeping its legacy category must succeed
    $updateResponse = $this->actingAs($admin)->put("/admin/knowledge-articles/{$article->id}", [
        'module' => 'HRM',
        'category' => 'Legacy Discontinued Benefit',
        'title' => 'Updated Old Benefit Documentation',
        'content' => $article->content,
        'status' => 'active',
    ]);

    $updateResponse->assertRedirect("/admin/knowledge-articles/{$article->id}");
    $this->assertDatabaseHas('knowledge_articles', [
        'id' => $article->id,
        'title' => 'Updated Old Benefit Documentation',
        'category' => 'Legacy Discontinued Benefit',
    ]);
});

test('KnowledgeCategorySeeder is idempotent and creates categories without duplicates', function () {
    // Ensure core modules exist
    $moduleSeeder = new ShofolModuleSeeder();
    $moduleSeeder->run();

    $catSeeder = new KnowledgeCategorySeeder();
    $catSeeder->run();

    $initialCount = KnowledgeCategory::count();
    expect($initialCount)->toBeGreaterThanOrEqual(12);

    // Run again
    $catSeeder->run();
    expect(KnowledgeCategory::count())->toBe($initialCount);
});

test('default categories listing displays all seeded categories when no filters are selected', function () {
    $admin = User::factory()->admin()->create();

    // Ensure seeded categories exist
    (new ShofolModuleSeeder())->run();
    (new KnowledgeCategorySeeder())->run();
    $totalCount = KnowledgeCategory::count();

    $response = $this->actingAs($admin)->get('/admin/categories');

    $response->assertStatus(200);
    $response->assertSee('Knowledge Category Management');
    $response->assertSee("Configured Categories ({$totalCount})");
    $response->assertDontSee('No Categories Found');

    // Verify seeded category names appear
    $response->assertSee('Leave Policy');
    $response->assertSee('Onboarding & Training');
    $response->assertSee('Employee Management');
    $response->assertSee('Salary & Tax');
    $response->assertSee('Lead Management');
});

test('categories listing displays all categories when filter form is submitted with empty parameters', function () {
    $admin = User::factory()->admin()->create();

    (new ShofolModuleSeeder())->run();
    (new KnowledgeCategorySeeder())->run();
    $totalCount = KnowledgeCategory::count();

    // 1. Submit with empty form fields (q=&module_id=&status=)
    $response = $this->actingAs($admin)->get('/admin/categories?q=&module_id=&status=');
    $response->assertStatus(200);
    $response->assertSee("Configured Categories ({$totalCount})");
    $response->assertDontSee('No Categories Found');
    $response->assertSee('Leave Policy');

    // 2. Submit with empty status param (?status=)
    $responseStatusEmpty = $this->actingAs($admin)->get('/admin/categories?status=');
    $responseStatusEmpty->assertStatus(200);
    $responseStatusEmpty->assertSee("Configured Categories ({$totalCount})");
    $responseStatusEmpty->assertDontSee('No Categories Found');

    // 3. Submit with status=all
    $responseStatusAll = $this->actingAs($admin)->get('/admin/categories?status=all');
    $responseStatusAll->assertStatus(200);
    $responseStatusAll->assertSee("Configured Categories ({$totalCount})");
    $responseStatusAll->assertDontSee('No Categories Found');
});

test('categories listing filters accurately by search text across name, slug, and description', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Support', 'code' => 'SUPP']);

    $cat1 = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Escalation Protocol',
        'slug' => 'escalation-protocol',
        'description' => 'Urgent ticket dispatch rules',
    ]);

    $cat2 = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Ticket SLA Management',
        'slug' => 'ticket-sla-management',
        'description' => 'Response time guarantees',
    ]);

    // Search by name
    $this->actingAs($admin)->get('/admin/categories?q=Escalation')
        ->assertStatus(200)
        ->assertSee('Escalation Protocol')
        ->assertDontSee('Ticket SLA Management');

    // Search by slug
    $this->actingAs($admin)->get('/admin/categories?q=ticket-sla')
        ->assertStatus(200)
        ->assertSee('Ticket SLA Management')
        ->assertDontSee('Escalation Protocol');

    // Search by description
    $this->actingAs($admin)->get('/admin/categories?q=guarantees')
        ->assertStatus(200)
        ->assertSee('Ticket SLA Management')
        ->assertDontSee('Escalation Protocol');
});

test('categories listing filters accurately by module ID and module code', function () {
    $admin = User::factory()->admin()->create();
    $moduleHR = ShofolModule::factory()->create(['name' => 'Human Resources', 'code' => 'HRM_CUSTOM']);
    $moduleFin = ShofolModule::factory()->create(['name' => 'Finance Core', 'code' => 'FIN_CUSTOM']);

    $catHR = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleHR->id,
        'name' => 'HR Work Hours Policy',
    ]);

    $catFin = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $moduleFin->id,
        'name' => 'Finance Audit Checklist',
    ]);

    // Filter by module ID
    $this->actingAs($admin)->get("/admin/categories?module_id={$moduleHR->id}")
        ->assertStatus(200)
        ->assertSee('HR Work Hours Policy')
        ->assertDontSee('Finance Audit Checklist');

    // Filter by module code
    $this->actingAs($admin)->get("/admin/categories?module=FIN_CUSTOM")
        ->assertStatus(200)
        ->assertSee('Finance Audit Checklist')
        ->assertDontSee('HR Work Hours Policy');
});

test('categories listing filters accurately by status including active, inactive, and numeric flags', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Tech Ops', 'code' => 'TECH']);

    $activeCat = KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Active Server Standards',
        'is_active' => true,
    ]);

    $inactiveCat = KnowledgeCategory::factory()->inactive()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Deprecated Server Standards',
        'is_active' => false,
    ]);

    // Status: active
    $this->actingAs($admin)->get('/admin/categories?status=active')
        ->assertStatus(200)
        ->assertSee('Active Server Standards')
        ->assertDontSee('Deprecated Server Standards');

    // Status: 1 (active)
    $this->actingAs($admin)->get('/admin/categories?status=1')
        ->assertStatus(200)
        ->assertSee('Active Server Standards')
        ->assertDontSee('Deprecated Server Standards');

    // Status: inactive
    $this->actingAs($admin)->get('/admin/categories?status=inactive')
        ->assertStatus(200)
        ->assertSee('Deprecated Server Standards')
        ->assertDontSee('Active Server Standards');

    // Status: 0 (inactive)
    $this->actingAs($admin)->get('/admin/categories?status=0')
        ->assertStatus(200)
        ->assertSee('Deprecated Server Standards')
        ->assertDontSee('Active Server Standards');
});

test('categories listing paginates correctly when exceeding 15 items', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Large Module', 'code' => 'LRG']);

    // Create 18 categories
    for ($i = 1; $i <= 18; $i++) {
        KnowledgeCategory::factory()->create([
            'shofol_module_id' => $module->id,
            'name' => sprintf('Category Item %02d', $i),
            'sort_order' => $i,
        ]);
    }

    $page1 = $this->actingAs($admin)->get("/admin/categories?module_id={$module->id}&page=1");
    $page1->assertStatus(200);
    $page1->assertSee('Configured Categories (18)');
    $page1->assertSee('Category Item 01');
    $page1->assertSee('Category Item 15');
    $page1->assertDontSee('Category Item 16');

    $page2 = $this->actingAs($admin)->get("/admin/categories?module_id={$module->id}&page=2");
    $page2->assertStatus(200);
    $page2->assertSee('Configured Categories (18)');
    $page2->assertSee('Category Item 16');
    $page2->assertSee('Category Item 18');
    $page2->assertDontSee('Category Item 01');
});

test('summary cards and configured category count remain consistent with query results', function () {
    $admin = User::factory()->admin()->create();
    $module = ShofolModule::factory()->create(['name' => 'Summary Module', 'code' => 'SUMM']);

    KnowledgeCategory::factory()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Active Policy Alpha',
        'is_active' => true,
    ]);
    KnowledgeCategory::factory()->inactive()->create([
        'shofol_module_id' => $module->id,
        'name' => 'Archived Policy Beta',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get('/admin/categories');
    $response->assertStatus(200);
    $response->assertSee('Total Categories');
    $response->assertSee('Active in KB');
    $response->assertSee('Inactive / Archived');

    // Filtering by active only changes Configured Categories count, leaving overall stats accurate
    $filterResponse = $this->actingAs($admin)->get('/admin/categories?status=active');
    $filterResponse->assertStatus(200);
    $filterResponse->assertSee('Active Policy Alpha');
    $filterResponse->assertDontSee('Archived Policy Beta');
});

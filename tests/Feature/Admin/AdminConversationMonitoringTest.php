<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SupportTicket;
use App\Models\User;

test('guest cannot access admin conversation routes and is redirected to login', function () {
    $conversation = Conversation::factory()->create();

    $this->get('/admin/conversations')->assertRedirect('/login');
    $this->get("/admin/conversations/{$conversation->id}")->assertRedirect('/login');
});

test('regular user cannot access admin conversation routes and receives 403', function () {
    $user = User::factory()->create(['role' => 'user']);
    $conversation = Conversation::factory()->create();

    $this->actingAs($user)->get('/admin/conversations')->assertStatus(403);
    $this->actingAs($user)->get("/admin/conversations/{$conversation->id}")->assertStatus(403);
});

test('admin can access conversation monitoring index and view session metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Rahim Ahmed', 'email' => 'rahim@shofol.com']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Leave Policy Question',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'How many casual leave days do I have?',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'You receive 10 casual leave days per calendar year.',
    ]);

    $response = $this->actingAs($admin)->get('/admin/conversations');

    $response->assertStatus(200);
    $response->assertSee('AI Conversation Monitoring');
    $response->assertSee('Rahim Ahmed');
    $response->assertSee('Leave Policy Question');
    $response->assertSee('Total Conversations');
});

test('admin can search conversations by title, user name, or email', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $userA = User::factory()->create(['name' => 'Fatima Begum', 'email' => 'fatima@shofol.com']);
    $userB = User::factory()->create(['name' => 'Karim Ullah', 'email' => 'karim@shofol.com']);

    $convA = Conversation::factory()->create(['user_id' => $userA->id, 'title' => 'Tax Deduction Inquiry']);
    $convB = Conversation::factory()->create(['user_id' => $userB->id, 'title' => 'CRM Lead Pipeline']);

    // Search by title
    $response = $this->actingAs($admin)->get('/admin/conversations?q=Tax');
    $response->assertStatus(200);
    $response->assertSee('Tax Deduction Inquiry');
    $response->assertDontSee('CRM Lead Pipeline');

    // Search by user name
    $responseUser = $this->actingAs($admin)->get('/admin/conversations?q=Fatima');
    $responseUser->assertStatus(200);
    $responseUser->assertSee('Tax Deduction Inquiry');
    $responseUser->assertDontSee('CRM Lead Pipeline');
});

test('admin can filter conversations by escalation status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $escalatedConv = Conversation::factory()->create(['user_id' => $user->id, 'title' => 'Escalated Session']);
    $normalConv = Conversation::factory()->create(['user_id' => $user->id, 'title' => 'Self-Resolved Session']);

    SupportTicket::factory()->create([
        'user_id' => $user->id,
        'conversation_id' => $escalatedConv->id,
        'subject' => 'Need Help with Escalated Session',
    ]);

    $responseEscalated = $this->actingAs($admin)->get('/admin/conversations?escalated=yes');
    $responseEscalated->assertStatus(200);
    $responseEscalated->assertSee('Escalated Session');
    $responseEscalated->assertDontSee('Self-Resolved Session');

    $responseNonEscalated = $this->actingAs($admin)->get('/admin/conversations?escalated=no');
    $responseNonEscalated->assertStatus(200);
    $responseNonEscalated->assertSee('Self-Resolved Session');
    $responseNonEscalated->assertDontSee('Escalated Session');
});

test('admin can inspect conversation transcript in read-only audit mode without 403', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Tariq Islam']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Overtime Payment Rate',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What is the overtime payment rate?',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Overtime is compensated at 1.5x standard hourly rate.',
        'sources' => [['module' => 'Payroll', 'title' => 'Overtime Calculation Policy']],
    ]);

    $response = $this->actingAs($admin)->get("/admin/conversations/{$conversation->id}");

    $response->assertStatus(200);
    $response->assertSee('Administrative Read-Only Audit Mode');
    $response->assertSee('Tariq Islam');
    $response->assertSee('Overtime Payment Rate');
    $response->assertSee('What is the overtime payment rate?');
    $response->assertSee('Overtime Calculation Policy');
});

test('inspect chat session button on escalated ticket links to admin conversation viewer and loads successfully', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Payroll Deduction Discrepancy',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'My payslip deduction is wrong.',
    ]);

    $ticket = SupportTicket::factory()->create([
        'user_id' => $user->id,
        'conversation_id' => $conversation->id,
        'subject' => 'Incorrect Payslip Deduction',
    ]);

    // Admin views the support ticket
    $responseTicket = $this->actingAs($admin)->get("/admin/tickets/{$ticket->id}");
    $responseTicket->assertStatus(200);
    $responseTicket->assertSee(route('admin.conversations.show', $conversation));

    // Admin clicks Inspect Chat Session
    $responseChat = $this->actingAs($admin)->get(route('admin.conversations.show', $conversation));
    $responseChat->assertStatus(200);
    $responseChat->assertSee('My payslip deduction is wrong.');
});

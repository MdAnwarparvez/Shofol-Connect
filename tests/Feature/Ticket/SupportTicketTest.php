<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SupportTicket;
use App\Models\User;

test('guest cannot access ticket routes and is redirected to login', function () {
    $ticket = SupportTicket::factory()->create();

    $this->get('/tickets')->assertRedirect('/login');
    $this->get('/tickets/create')->assertRedirect('/login');
    $this->post('/tickets', [])->assertRedirect('/login');
    $this->get("/tickets/{$ticket->id}")->assertRedirect('/login');
    $this->get('/admin/tickets')->assertRedirect('/login');
    $this->get("/admin/tickets/{$ticket->id}")->assertRedirect('/login');
    $this->put("/admin/tickets/{$ticket->id}", [])->assertRedirect('/login');
});

test('regular user cannot access admin ticket management routes', function () {
    $user = User::factory()->create(['role' => 'user']);
    $ticket = SupportTicket::factory()->create();

    $this->actingAs($user)->get('/admin/tickets')->assertStatus(403);
    $this->actingAs($user)->get("/admin/tickets/{$ticket->id}")->assertStatus(403);
    $this->actingAs($user)->put("/admin/tickets/{$ticket->id}", [
        'status' => 'Resolved',
        'priority' => 'High',
    ])->assertStatus(403);
});

test('authenticated user can view ticket submission form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/tickets/create');

    $response->assertStatus(200);
    $response->assertSee('Submit Support Ticket');
    $response->assertSee('HRM');
    $response->assertSee('Payroll');
});

test('authenticated user can create a support ticket with valid data', function () {
    $user = User::factory()->create();

    $payload = [
        'subject' => 'Incorrect Tax Deduction in Payslip',
        'category' => 'Payroll',
        'priority' => 'High',
        'description' => 'The monthly tax deduction on my August payslip does not match the standard formula.',
    ];

    $response = $this->actingAs($user)->post('/tickets', $payload);

    $ticket = SupportTicket::where('subject', 'Incorrect Tax Deduction in Payslip')->first();
    $this->assertNotNull($ticket);

    $response->assertRedirect(route('tickets.show', $ticket));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'user_id' => $user->id,
        'subject' => 'Incorrect Tax Deduction in Payslip',
        'category' => 'Payroll',
        'priority' => 'High',
        'status' => 'Open',
    ]);
});

test('ticket creation validation rejects missing or invalid inputs', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/tickets', [
        'subject' => '',
        'category' => 'InvalidCategory',
        'priority' => 'InvalidPriority',
        'description' => '',
    ]);

    $response->assertSessionHasErrors(['subject', 'category', 'priority', 'description']);
});

test('user can view their own tickets list and details', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $ticketA = SupportTicket::factory()->create([
        'user_id' => $userA->id,
        'subject' => 'User A Vacation Calculation',
    ]);

    $ticketB = SupportTicket::factory()->create([
        'user_id' => $userB->id,
        'subject' => 'User B Private Query',
    ]);

    // List view
    $responseList = $this->actingAs($userA)->get('/tickets');
    $responseList->assertStatus(200);
    $responseList->assertSee('User A Vacation Calculation');
    $responseList->assertDontSee('User B Private Query');

    // Details view
    $responseDetail = $this->actingAs($userA)->get("/tickets/{$ticketA->id}");
    $responseDetail->assertStatus(200);
    $responseDetail->assertSee('User A Vacation Calculation');
});

test('user is blocked from viewing another users ticket', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $ticket = SupportTicket::factory()->create([
        'user_id' => $owner->id,
        'subject' => 'Owner Confidential Payroll Issue',
    ]);

    $response = $this->actingAs($attacker)->get("/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('ticket creation pre-fills context from an escalated conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Leave Policy Query',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'How do I submit annual leave?',
    ]);

    $response = $this->actingAs($user)->get("/tickets/create?conversation_id={$conversation->id}");

    $response->assertStatus(200);
    $response->assertSee('Escalated from AI Support Chat');
    $response->assertSee('Need Support: Leave Policy Query');
    $response->assertSee('How do I submit annual leave?');

    // Submit ticket linked to conversation
    $postResponse = $this->actingAs($user)->post('/tickets', [
        'subject' => 'Need Support: Leave Policy Query',
        'category' => 'HRM',
        'priority' => 'Medium',
        'description' => 'User question could not be answered by AI.',
        'conversation_id' => $conversation->id,
    ]);

    $ticket = SupportTicket::where('conversation_id', $conversation->id)->first();
    $this->assertNotNull($ticket);
    $this->assertEquals($conversation->id, $ticket->conversation_id);
});

test('user cannot link a ticket to another users conversation', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($attacker)->get("/tickets/create?conversation_id={$conversation->id}");
    $response->assertStatus(404);

    $postResponse = $this->actingAs($attacker)->post('/tickets', [
        'subject' => 'Illegal Escalation Attempt',
        'category' => 'Technical',
        'priority' => 'Low',
        'description' => 'Trying to attach someone elses conversation.',
        'conversation_id' => $conversation->id,
    ]);
    $postResponse->assertStatus(404);
});

test('admin can view all tickets with search and filters', function () {
    $admin = User::factory()->admin()->create();

    $ticketHRM = SupportTicket::factory()->create([
        'category' => 'HRM',
        'subject' => 'HR Benefits Query',
        'status' => 'Open',
    ]);

    $ticketCRM = SupportTicket::factory()->create([
        'category' => 'CRM',
        'subject' => 'CRM Pipeline Discrepancy',
        'status' => 'Resolved',
    ]);

    // Admin list
    $response = $this->actingAs($admin)->get('/admin/tickets');
    $response->assertStatus(200);
    $response->assertSee('Support Ticket Management');
    $response->assertSee('HR Benefits Query');
    $response->assertSee('CRM Pipeline Discrepancy');

    // Filter by status
    $responseFilter = $this->actingAs($admin)->get('/admin/tickets?status=Open');
    $responseFilter->assertStatus(200);
    $responseFilter->assertSee('HR Benefits Query');
    $responseFilter->assertDontSee('CRM Pipeline Discrepancy');
});

test('admin can manage ticket details and update status priority and response', function () {
    $admin = User::factory()->admin()->create();

    $ticket = SupportTicket::factory()->create([
        'subject' => 'Invoice Reversal Request',
        'category' => 'Accounting',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'admin_response' => null,
    ]);

    // View admin details
    $responseShow = $this->actingAs($admin)->get("/admin/tickets/{$ticket->id}");
    $responseShow->assertStatus(200);
    $responseShow->assertSee('Invoice Reversal Request');
    $responseShow->assertSee('Staff Response', false);

    // Update ticket
    $responseUpdate = $this->actingAs($admin)->put("/admin/tickets/{$ticket->id}", [
        'status' => 'Resolved',
        'priority' => 'High',
        'admin_response' => 'The invoice has been credited and reversed in the accounting ledger.',
    ]);

    $responseUpdate->assertRedirect(route('admin.tickets.show', $ticket));
    $responseUpdate->assertSessionHas('success');

    $ticket->refresh();
    $this->assertEquals('Resolved', $ticket->status);
    $this->assertEquals('High', $ticket->priority);
    $this->assertEquals('The invoice has been credited and reversed in the accounting ledger.', $ticket->admin_response);
    $this->assertNotNull($ticket->resolved_at);
});

test('user and admin ticket lists are paginated', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->count(15)->create([
        'user_id' => $user->id,
    ]);

    // User pagination
    $userResp = $this->actingAs($user)->get('/tickets');
    $userResp->assertStatus(200);
    $this->assertCount(10, $userResp->viewData('tickets'));

    // Admin pagination
    $adminResp = $this->actingAs($admin)->get('/admin/tickets');
    $adminResp->assertStatus(200);
    $this->assertCount(10, $adminResp->viewData('tickets'));
});

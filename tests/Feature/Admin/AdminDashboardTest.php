<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\KnowledgeArticle;
use App\Models\SupportTicket;
use App\Models\User;

test('guest cannot access admin dashboard and is redirected to login', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect('/login');
});

test('regular user cannot access admin dashboard and receives 403', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertStatus(403);
});

test('admin dashboard renders successfully with accurate database statistics', function () {
    $admin = User::factory()->admin()->create();

    // Create additional users
    $user1 = User::factory()->create(['role' => 'user']);
    $user2 = User::factory()->create(['role' => 'user']);

    // Create Knowledge Base articles across modules
    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    KnowledgeArticle::factory()->create([
        'module' => 'Payroll',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    KnowledgeArticle::factory()->create([
        'module' => 'CRM',
        'status' => 'inactive',
        'created_by' => $admin->id,
    ]);
    KnowledgeArticle::factory()->create([
        'module' => 'Accounting',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    // Create Conversations and Chat Messages
    $convo1 = Conversation::factory()->create(['user_id' => $user1->id, 'title' => 'Leave Policy Query']);
    ChatMessage::factory()->create(['conversation_id' => $convo1->id, 'role' => 'user']);
    ChatMessage::factory()->create(['conversation_id' => $convo1->id, 'role' => 'assistant']);

    $convo2 = Conversation::factory()->create(['user_id' => $user2->id, 'title' => 'Salary Deduction Help']);
    ChatMessage::factory()->create(['conversation_id' => $convo2->id, 'role' => 'user']);

    // Create Support Tickets with various statuses and priorities
    $ticket1 = SupportTicket::factory()->create([
        'user_id' => $user1->id,
        'subject' => 'Urgent Server Outage',
        'priority' => 'High',
        'status' => 'Open',
    ]);
    $ticket2 = SupportTicket::factory()->create([
        'user_id' => $user1->id,
        'subject' => 'Payroll tax inquiry',
        'priority' => 'Medium',
        'status' => 'In Progress',
    ]);
    $ticket3 = SupportTicket::factory()->create([
        'user_id' => $user2->id,
        'subject' => 'CRM contact export',
        'priority' => 'Low',
        'status' => 'Resolved',
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertStatus(200);

    // Verify view data
    $response->assertViewHas('totalUsers', 3); // 1 admin + 2 users
    $response->assertViewHas('adminUsersCount', 1);
    $response->assertViewHas('regularUsersCount', 2);

    $response->assertViewHas('kbCount', 5);
    $response->assertViewHas('kbActiveCount', 4);
    $response->assertViewHas('kbInactiveCount', 1);

    $response->assertViewHas('articlesByModule', [
        'HRM' => 2,
        'Payroll' => 1,
        'CRM' => 1,
        'Accounting' => 1,
    ]);

    $response->assertViewHas('totalConversations', 2);
    $response->assertViewHas('totalMessages', 3);
    $response->assertViewHas('userMessagesCount', 2);
    $response->assertViewHas('assistantMessagesCount', 1);

    $response->assertViewHas('ticketCount', 3);
    $response->assertViewHas('highPriorityTicketsCount', 1);

    // Assert rendered text in the HTML
    $response->assertSee('Admin Dashboard');
    $response->assertSee('Urgent Attention Required');
    $response->assertSee('Urgent Server Outage');
    $response->assertSee('Leave Policy Query');
});

test('admin dashboard renders gracefully with empty database', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('totalUsers', 1);
    $response->assertViewHas('kbCount', 0);
    $response->assertViewHas('totalConversations', 0);
    $response->assertViewHas('ticketCount', 0);
    $response->assertViewHas('highPriorityTicketsCount', 0);

    $response->assertSee('No support tickets recorded yet');
    $response->assertSee('No conversations recorded yet');
    $response->assertDontSee('Urgent Attention Required');
});

test('user dashboard displays only authenticated users own statistics', function () {
    $userA = User::factory()->create(['role' => 'user']);
    $userB = User::factory()->create(['role' => 'user']);

    // User A activity: 1 conversation, 1 open ticket
    $convoA = Conversation::factory()->create([
        'user_id' => $userA->id,
        'title' => 'User A Conversation Session',
    ]);
    ChatMessage::factory()->create(['conversation_id' => $convoA->id, 'role' => 'user']);

    $ticketA = SupportTicket::factory()->create([
        'user_id' => $userA->id,
        'subject' => 'User A Ticket Issue',
        'status' => 'Open',
    ]);

    // User B activity: 3 conversations, 2 tickets
    Conversation::factory()->count(3)->create(['user_id' => $userB->id]);
    SupportTicket::factory()->count(2)->create([
        'user_id' => $userB->id,
        'subject' => 'User B Secret Ticket Issue',
    ]);

    $response = $this->actingAs($userA)->get('/dashboard');

    $response->assertStatus(200);

    // Assert User A sees only their own metrics
    $response->assertViewHas('conversationCount', 1);
    $response->assertViewHas('ticketCount', 1);
    $response->assertViewHas('openTicketCount', 1);
    $response->assertViewHas('resolvedTicketCount', 0);

    // Assert User A sees their own items and not User B's
    $response->assertSee('User A Conversation Session');
    $response->assertSee('User A Ticket Issue');
    $response->assertDontSee('User B Secret Ticket Issue');
});

test('user dashboard renders gracefully when user has no activity', function () {
    $freshUser = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($freshUser)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('conversationCount', 0);
    $response->assertViewHas('ticketCount', 0);
    $response->assertViewHas('openTicketCount', 0);
    $response->assertViewHas('resolvedTicketCount', 0);

    $response->assertSee('No conversations recorded yet');
    $response->assertSee('No support tickets submitted yet');
});

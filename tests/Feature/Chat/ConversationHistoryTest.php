<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('guest cannot access conversation history or delete conversations', function () {
    $conversation = Conversation::factory()->create();

    $this->get('/conversations')->assertRedirect('/login');
    $this->delete("/conversations/{$conversation->id}")->assertRedirect('/login');
});

test('user can view their own conversation history and message count', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $convA = Conversation::factory()->create([
        'user_id' => $userA->id,
        'title' => 'HRM Leave Inquiries',
    ]);
    ChatMessage::factory()->count(3)->create([
        'conversation_id' => $convA->id,
    ]);

    $convB = Conversation::factory()->create([
        'user_id' => $userB->id,
        'title' => 'Secret User B Conversation',
    ]);

    $response = $this->actingAs($userA)->get('/conversations');

    $response->assertStatus(200);
    $response->assertSee('HRM Leave Inquiries');
    $response->assertSee('3 messages');
    $response->assertDontSee('Secret User B Conversation');
});

test('user cannot access or open another users conversation', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Private Payroll Session',
    ]);

    // Test direct route URL parameter
    $responseRoute = $this->actingAs($attacker)->get("/chat/{$conversation->id}");
    $responseRoute->assertStatus(403);

    // Test query parameter
    $responseQuery = $this->actingAs($attacker)->get("/chat?conversation_id={$conversation->id}");
    $responseQuery->assertStatus(403);
});

test('user cannot send messages to another users conversation', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($attacker)->post('/chat/send', [
        'conversation_id' => $conversation->id,
        'message' => 'Unauthorized injection attempt',
    ]);

    $response->assertStatus(403);
});

test('user can open and resume their own previous conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'CRM Pipeline Help',
    ]);

    ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What are the official sales pipeline stages?',
    ]);

    ChatMessage::factory()->assistant()->create([
        'conversation_id' => $conversation->id,
        'content' => 'The sales pipeline consists of 5 stages.',
    ]);

    $response = $this->actingAs($user)->get("/chat/{$conversation->id}");

    $response->assertStatus(200);
    $response->assertSee('CRM Pipeline Help');
    $response->assertSee('What are the official sales pipeline stages?');
    $response->assertSee('The sales pipeline consists of 5 stages.');
});

test('user can delete their own conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Outdated Query to Remove',
    ]);

    $message = ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
    ]);

    $response = $this->actingAs($user)->delete("/conversations/{$conversation->id}");

    $response->assertRedirect(route('conversations.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('conversations', [
        'id' => $conversation->id,
    ]);
    // Cascade delete verification
    $this->assertDatabaseMissing('chat_messages', [
        'id' => $message->id,
    ]);
});

test('user cannot delete another users conversation', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Protected User Conversation',
    ]);

    $response = $this->actingAs($attacker)->delete("/conversations/{$conversation->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
    ]);
});

test('conversations are paginated correctly', function () {
    $user = User::factory()->create();

    Conversation::factory()->count(15)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/conversations');

    $response->assertStatus(200);
    $this->assertCount(10, $response->viewData('conversations'));
});

test('first question automatically sets a descriptive conversation title capped at 50 chars', function () {
    Config::set('services.ai.api_key', 'test-fake-key');

    KnowledgeArticle::factory()->create([
        'module' => 'HRM',
        'title' => 'Casual Leave Policy',
        'content' => 'Casual leave policy details.',
        'status' => 'active',
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => ['content' => 'Casual leave is 10 days per year.'],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Support Assistant',
    ]);

    $question = 'How many days of casual leave are allowed per year for employees in Shofol Connect?';

    $response = $this->actingAs($user)->postJson('/chat/send', [
        'conversation_id' => $conversation->id,
        'message' => $question,
    ]);

    $response->assertStatus(200);

    $conversation->refresh();
    $this->assertEquals(\Illuminate\Support\Str::limit($question, 47, '...'), $conversation->title);
    $this->assertLessThanOrEqual(50, mb_strlen($conversation->title));
});

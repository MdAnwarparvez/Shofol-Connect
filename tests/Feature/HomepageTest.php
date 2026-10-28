<?php

use App\Models\Conversation;
use App\Models\User;

test('guest can visit homepage and sees ChatGPT-style landing interface', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $html = $response->getContent();

    // 1. Welcoming heading and subtitle
    expect($html)->toContain('How can I help you today?');
    expect($html)->toContain('Ask anything about company HRM policies, payroll calculations, CRM sales pipelines, or accounting and invoicing.');

    // 2. Center logo badge reusing public/images/shofol-connect-logo.png
    expect($html)->toContain('class="landing-logo-badge');
    expect($html)->toContain('images/shofol-connect-logo.png');

    // 3. Four example prompt suggestions
    expect($html)->toContain('Leave policy');
    expect($html)->toContain('Payroll and salary');
    expect($html)->toContain('CRM and sales');
    expect($html)->toContain('Accounting and invoicing');

    // 4. Large rounded message composer
    expect($html)->toContain('id="homepageComposerForm"');
    expect($html)->toContain('id="homepageComposerInput"');
    expect($html)->toContain('placeholder="Message Shofol Connect AI Assistant..."');
    expect($html)->toContain('id="homepageSendBtn"');
    expect($html)->toContain('action="' . route('chat.send') . '"');

    // 5. RAG Grounded and Dual AI chips
    expect($html)->toContain('RAG Grounded');
    expect($html)->toContain('Dual AI (Gemini / Ollama)');

    // 6. Verification disclaimer note
    expect($html)->toContain('Shofol Connect AI Assistant can make mistakes. Please verify important policies and financial details with HR or Finance.');

    // 7. Single sidebar branding and search input
    expect($html)->toContain('class="chatgpt-sidebar"');
    expect($html)->toContain('id="sidebarChatSearch"');
    expect($html)->toContain('placeholder="Search chats"');
    expect($html)->toContain('class="sidebar-new-chat-btn');

    // 8. Topbar theme toggle (icon only)
    expect($html)->toContain('id="topbarThemeToggleBtn"');
    expect($html)->toContain('theme-icon-moon');
    expect($html)->toContain('theme-icon-sun');

    // 9. Guest auth path: Log In and Register links
    expect($html)->toContain('href="' . route('login') . '"');
    expect($html)->toContain('href="' . route('register') . '"');
    expect($html)->toContain('id="guestAuthModal"');
    expect($html)->toContain('Sign in to Shofol Connect');
});

test('authenticated user visits homepage and sees user profile and recent conversations', function () {
    $user = User::factory()->create([
        'name' => 'Md. Anwar Parvez',
    ]);

    $conversation = Conversation::create([
        'user_id' => $user->id,
        'title' => 'Leave Policy Query',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);

    $html = $response->getContent();

    // Authenticated homepage view
    expect($html)->toContain('How can I help you today?');
    expect($html)->toContain('id="homepageComposerForm"');
    expect($html)->toContain('data-authenticated="1"');

    // Sidebar displays user name and conversation history
    expect($html)->toContain('Md. Anwar Parvez');
    expect($html)->toContain('Leave Policy Query');
    expect($html)->toContain(route('chat.show', $conversation));

    // Sidebar has New Support Chat and user navigation
    expect($html)->toContain(route('chat.index'));
    expect($html)->toContain('User Portal');
    expect($html)->toContain('AI Assistant');

    // Topbar has theme toggle and navigation
    expect($html)->toContain('id="topbarThemeToggleBtn"');
});

test('guest cannot send messages directly to chat.send and is redirected to login', function () {
    $response = $this->post(route('chat.send'), [
        'message' => 'What is the leave policy?',
    ]);

    $response->assertRedirect(route('login'));
});

test('sidebar has exactly one brand logo and no duplicate navigation controls on homepage', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $html = $response->getContent();

    // Exactly one sidebar brand logo
    preg_match_all('/<a class="navbar-brand[^>]*>.*?<\/a>/s', $html, $brandMatches);
    expect(count($brandMatches[0]))->toBe(1);

    // One search chats input
    preg_match_all('/id="sidebarChatSearch"/', $html, $searchMatches);
    expect(count($searchMatches[0]))->toBe(1);

    // One sidebar toggle control in topbar
    preg_match_all('/id="sidebarToggleBtn"/', $html, $toggleMatches);
    expect(count($toggleMatches[0]))->toBe(1);

    // One collapse button in sidebar header
    preg_match_all('/id="sidebarCollapseBtn"/', $html, $collapseMatches);
    expect(count($collapseMatches[0]))->toBe(1);
});


<?php

use App\Models\User;

test('shofol-connect logo file exists locally and is a valid png image', function () {
    $logoPath = public_path('images/shofol-connect-logo.png');

    expect(file_exists($logoPath))->toBeTrue();

    $imageInfo = getimagesize($logoPath);
    expect($imageInfo)->not->toBeFalse();
    expect($imageInfo['mime'])->toBe('image/png');
    expect($imageInfo[0])->toBe(640);
    expect($imageInfo[1])->toBe(533);
});

test('sidebar renders Shofol Connect logo image followed by Shofol Connect text as a single link to appiflybd.com', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);

    $content = $response->getContent();

    // Extract sidebar navbar-brand block
    preg_match('/<aside[^>]*>.*?<a class="navbar-brand[^>]*>(.*?)<\/a>/s', $content, $matches);
    expect($matches)->not->toBeEmpty();
    $navbarBrand = $matches[0];

    // Single link pointing to https://appiflybd.com/
    expect($navbarBrand)->toContain('href="https://appiflybd.com/"');

    // Shofol Connect logo image with alt="Shofol Connect"
    expect($navbarBrand)->toContain('images/shofol-connect-logo.png');
    expect($navbarBrand)->toContain('alt="Shofol Connect"');
    expect($navbarBrand)->toContain('class="shofol-navbar-logo"');

    // Followed by text Shofol Connect
    expect($navbarBrand)->toContain('Shofol Connect');

    // No visible APPIFLY text inside the sidebar brand element
    expect($navbarBrand)->not->toContain('>APPIFLY');
    expect($navbarBrand)->not->toContain('APPIFLY BD LTD');

    // No MVP badge and no robot icon in the navbar brand
    expect($navbarBrand)->not->toContain('MVP');
    expect($navbarBrand)->not->toContain('bi-robot');

    // Verify topbar has collapsed-topbar-brand showing logo
    expect($content)->toContain('class="collapsed-topbar-brand');
    expect($content)->toContain('class="shofol-topbar-logo"');
});

test('shared layout renders Developed by APPIFLY BD LTD credit centered in footer with copyright on left', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $html = $response->getContent();

    // Verify footer layout container and CSS grid classes
    expect($html)->toContain('class="footer-layout"');
    expect($html)->toContain('class="footer-left"');
    expect($html)->toContain('class="footer-center"');

    // Verify copyright text on left
    expect($html)->toContain('Shofol Connect</strong> — AI Support Assistant MVP. All rights reserved.');

    // Verify developer credit centered with clickable bold link
    expect($html)->toContain('Developed by <a href="https://appiflybd.com/"');
    expect($html)->toContain('class="footer-credit-link fw-bold"');
    expect($html)->toContain('>APPIFLY BD LTD</a>');

    // Extract footer block and ensure clean alignment without extra icons/entities
    preg_match('/<div class="footer-center">(.*?)<\/div>/s', $html, $matches);
    expect($matches)->not->toBeEmpty();
    $centerContent = trim(strip_tags($matches[1]));
    expect($centerContent)->toBe('Developed by APPIFLY BD LTD');
});

test('branding is consistent across chat, tickets, and admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    foreach (['/chat', '/tickets', '/admin/dashboard'] as $url) {
        $pageResponse = $this->actingAs($admin)->get($url);
        $pageResponse->assertStatus(200);

        $html = $pageResponse->getContent();
        preg_match('/<aside[^>]*>.*?<a class="navbar-brand[^>]*>(.*?)<\/a>/s', $html, $matches);
        expect($matches)->not->toBeEmpty();
        $brandHtml = $matches[0];

        expect($brandHtml)->toContain('href="https://appiflybd.com/"');
        expect($brandHtml)->toContain('images/shofol-connect-logo.png');
        expect($brandHtml)->toContain('alt="Shofol Connect"');
        expect($brandHtml)->toContain('Shofol Connect');
        expect($brandHtml)->not->toContain('>APPIFLY');
        expect($brandHtml)->not->toContain('APPIFLY BD LTD');
        expect($brandHtml)->not->toContain('MVP');
        expect($brandHtml)->not->toContain('bi-robot');
    }
});

test('sidebar renders user navigation links for regular users and hides admin navigation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    // User navigation links
    expect($html)->toContain('User Portal');
    expect($html)->toContain(route('dashboard'));
    expect($html)->toContain('AI Assistant');
    expect($html)->toContain(route('chat.index'));
    expect($html)->toContain('History');
    expect($html)->toContain(route('conversations.index'));
    expect($html)->toContain('Support Tickets');
    expect($html)->toContain(route('tickets.index'));

    // Admin-only navigation links must NOT be visible
    expect($html)->not->toContain('Admin Panel');
    expect($html)->not->toContain(route('admin.dashboard'));
    expect($html)->not->toContain(route('admin.knowledge-articles.index'));
    expect($html)->not->toContain(route('admin.modules.index'));
    expect($html)->not->toContain(route('admin.categories.index'));
    expect($html)->not->toContain(route('admin.faqs.index'));
    expect($html)->not->toContain(route('admin.tickets.index'));
    expect($html)->not->toContain(route('admin.conversations.index'));
});

test('sidebar renders admin navigation links for admin users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    // User navigation links are present
    expect($html)->toContain('User Portal');
    expect($html)->toContain('AI Assistant');
    expect($html)->toContain('History');
    expect($html)->toContain('Support Tickets');

    // Admin navigation links are present
    expect($html)->toContain('Admin Panel');
    expect($html)->toContain(route('admin.dashboard'));
    expect($html)->toContain('Knowledge Base');
    expect($html)->toContain(route('admin.knowledge-articles.index'));
    expect($html)->toContain('Modules');
    expect($html)->toContain(route('admin.modules.index'));
    expect($html)->toContain('Categories');
    expect($html)->toContain(route('admin.categories.index'));
    expect($html)->toContain('FAQs');
    expect($html)->toContain(route('admin.faqs.index'));
    expect($html)->toContain('Manage Tickets');
    expect($html)->toContain(route('admin.tickets.index'));
    expect($html)->toContain('Conversations');
    expect($html)->toContain(route('admin.conversations.index'));
});

test('sidebar renders account section with user name, role, and functional logout form', function () {
    $user = User::factory()->create([
        'name' => 'Md.Anwar parvez',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    // Account details
    expect($html)->toContain('Signed in as:');
    expect($html)->toContain('Md.Anwar parvez');
    expect($html)->toContain('<span class="badge bg-primary">User</span>');

    // Logout form
    expect($html)->toContain('action="' . route('logout') . '"');
    expect($html)->toContain('Log Out');

    // Test with admin user
    $admin = User::factory()->admin()->create();
    $adminResponse = $this->actingAs($admin)->get('/admin/dashboard');
    $adminHtml = $adminResponse->getContent();
    expect($adminHtml)->toContain('<span class="badge bg-danger">Admin</span>');
});

test('sidebar controls and toggle buttons exist in layout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    expect($html)->toContain('id="sidebarToggleBtn"');
    expect($html)->toContain('id="sidebarCollapseBtn"');
    expect($html)->toContain('id="sidebarCloseBtn"');
    expect($html)->toContain('id="sidebarBackdrop"');
    expect($html)->toContain('class="chatgpt-sidebar"');
    expect($html)->toContain('class="main-workspace"');

    // Verify buttons are explicit type="button" and have accessibility attributes
    expect($html)->toContain('type="button" id="sidebarToggleBtn"');
    expect($html)->toContain('type="button" id="sidebarCollapseBtn"');
    expect($html)->toContain('type="button" id="sidebarCloseBtn"');
    expect($html)->toContain('aria-expanded="true"');

    // Verify desktop collapsed and mobile open CSS rules are defined
    expect($html)->toContain('.app-shell.sidebar-collapsed .chatgpt-sidebar');
    expect($html)->toContain('.app-shell.sidebar-collapsed .main-workspace');
    expect($html)->toContain('.app-shell.sidebar-mobile-open .chatgpt-sidebar');
    expect($html)->toContain('.app-shell.sidebar-mobile-open .sidebar-backdrop');

    // Verify topbar has collapsed logo button to reopen sidebar without extra text
    expect($html)->toContain('id="collapsedLogoBtn"');
    expect($html)->toContain('type="button" id="collapsedLogoBtn"');
    expect($html)->toContain('class="collapsed-topbar-brand');
    expect($html)->toContain('class="shofol-topbar-logo"');
    preg_match('/<button[^>]*id="collapsedLogoBtn"[^>]*>(.*?)<\/button>/s', $html, $logoBtnMatches);
    expect($logoBtnMatches)->not->toBeEmpty();
    expect(trim(strip_tags($logoBtnMatches[1])))->toBeEmpty();
    expect($logoBtnMatches[0])->toContain('title="Open sidebar"');
});

test('sidebar highlights active navigation item based on current route', function () {
    $user = User::factory()->create();

    // On /dashboard, User Portal link is active
    $dashboardResponse = $this->actingAs($user)->get('/dashboard');
    $dashboardResponse->assertStatus(200);
    $dashboardHtml = $dashboardResponse->getContent();

    expect($dashboardHtml)->toContain('class="sidebar-nav-link active" href="' . route('dashboard') . '"');

    // On /chat, AI Assistant link is active
    $chatResponse = $this->actingAs($user)->get('/chat');
    $chatResponse->assertStatus(200);
    $chatHtml = $chatResponse->getContent();

    expect($chatHtml)->toContain('class="sidebar-nav-link active" href="' . route('chat.index') . '"');
});

test('sidebar resizer handle, styling, and drag constraints are configured correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    // Check resizer element inside sidebar
    expect($html)->toContain('<div class="sidebar-resizer" id="sidebarResizer" title="Drag to resize sidebar" aria-hidden="true"></div>');
    expect($html)->toContain('id="sidebarResizer"');

    // Check CSS custom properties for default, min, and max width
    expect($html)->toContain('--sidebar-width: 260px;');
    expect($html)->toContain('--sidebar-min-width: 200px;');
    expect($html)->toContain('--sidebar-max-width: 400px;');

    // Check resizer handle styling
    expect($html)->toContain('.sidebar-resizer {');
    expect($html)->toContain('cursor: col-resize;');
    expect($html)->toContain('position: absolute;');
    expect($html)->toContain('right: 0;');

    // Check smooth drag interaction styles: text selection disabled and transition disabled during resizing
    expect($html)->toContain('.app-shell.is-resizing');
    expect($html)->toContain('user-select: none !important;');
    expect($html)->toContain('.app-shell.is-resizing .chatgpt-sidebar,');
    expect($html)->toContain('transition: none !important;');

    // Check responsive behavior: resizer hidden on mobile and when collapsed
    expect($html)->toContain('.app-shell.sidebar-collapsed .sidebar-resizer');
    expect($html)->toContain('display: none !important;');

    // Check JS controller handles MIN, MAX, and DEFAULT widths
    expect($html)->toContain('const MIN_WIDTH = 200;');
    expect($html)->toContain('const MAX_WIDTH = 400;');
    expect($html)->toContain('const DEFAULT_WIDTH = 260;');
    expect($html)->toContain("document.documentElement.style.setProperty('--sidebar-width',");
    expect($html)->toContain('localStorage.setItem(\'shofol_sidebar_width\',');
});

test('dark and light mode theme toggle is located exclusively in the top navbar with icon only and tooltip', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $html = $response->getContent();

    // 1. Anti-FOUC Script in head
    expect($html)->toContain('<!-- Anti-FOUC Theme Script (executes before paint) -->');
    expect($html)->toContain("localStorage.getItem('shofol_theme') || 'light'");
    expect($html)->toContain("document.documentElement.setAttribute('data-bs-theme', theme)");

    // 2. Light and Dark mode CSS tokens
    expect($html)->toContain('[data-bs-theme="light"]');
    expect($html)->toContain('[data-bs-theme="dark"]');
    expect($html)->toContain('--theme-body-bg:');
    expect($html)->toContain('--theme-card-bg:');
    expect($html)->toContain('--theme-workspace-bg:');
    expect($html)->toContain('--theme-topbar-bg:');

    // 3. Theme Toggle Button ONLY in Header Topbar
    expect($html)->toContain('id="topbarThemeToggleBtn"');
    expect($html)->toContain('class="bi bi-moon-stars-fill theme-icon-moon');
    expect($html)->toContain('class="bi bi-sun-fill theme-icon-sun');

    // 4. Icon only - no text, label, or theme name inside the topbar button
    preg_match('/<button[^>]*id="topbarThemeToggleBtn"[^>]*>(.*?)<\/button>/s', $html, $btnMatches);
    expect($btnMatches)->not->toBeEmpty();
    $buttonInnerHtml = $btnMatches[1];
    $strippedText = trim(strip_tags($buttonInnerHtml));
    expect($strippedText)->toBeEmpty(); // Pure icon only, zero visible text
    expect($btnMatches[0])->toContain('data-bs-toggle="tooltip"');
    expect($btnMatches[0])->toContain('title="Switch to Dark Mode"');

    // 5. Sidebar must NOT contain theme toggle or theme text
    expect($html)->not->toContain('id="sidebarThemeToggleBtn"');
    expect($html)->not->toContain('id="sidebarThemeStatus"');

    // Extract sidebar HTML and verify no theme controls inside
    preg_match('/<aside class="chatgpt-sidebar"[^>]*>.*?<\/aside>/s', $html, $sidebarMatches);
    expect($sidebarMatches)->not->toBeEmpty();
    expect($sidebarMatches[0])->not->toContain('theme-toggle-btn');
    expect($sidebarMatches[0])->not->toContain('theme-icon-moon');
    expect($sidebarMatches[0])->not->toContain('theme-icon-sun');

    // 6. Dark theme component rules
    expect($html)->toContain('[data-bs-theme="dark"] .bg-white');
    expect($html)->toContain('[data-bs-theme="dark"] .card');
    expect($html)->toContain('[data-bs-theme="dark"] .table');
    expect($html)->toContain('[data-bs-theme="dark"] .form-control');

    // 7. Theme controller JS
    expect($html)->toContain("const THEME_KEY = 'shofol_theme';");
    expect($html)->toContain("localStorage.setItem(THEME_KEY, targetTheme);");
    expect($html)->toContain("function toggleTheme(");
});

test('guest layout provides theme toggle button and anti-FOUC protection', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);

    $html = $response->getContent();

    expect($html)->toContain('id="guestThemeToggleBtn"');
    expect($html)->toContain('theme-icon-moon');
    expect($html)->toContain('theme-icon-sun');
    expect($html)->toContain("localStorage.getItem('shofol_theme') || 'light'");
});




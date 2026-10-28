<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen renders modern ChatGPT-style UI with single logo and all form controls', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);

    $html = $response->getContent();

    // 1. Heading and subtitle
    expect($html)->toContain('Create your account');
    expect($html)->toContain('Get started with Shofol Connect AI Support');

    // 2. Exactly one Shofol Connect brand logo on the page
    preg_match_all('/images\/shofol-connect-logo\.png/', $html, $logoMatches);
    expect(count($logoMatches[0]))->toBe(1);

    // 3. Form elements
    expect($html)->toContain('action="' . route('register') . '"');
    expect($html)->toContain('method="POST"');
    expect($html)->toContain('name="name"');
    expect($html)->toContain('name="email"');
    expect($html)->toContain('name="password"');
    expect($html)->toContain('name="password_confirmation"');

    // 4. Password visibility toggles
    expect($html)->toContain('id="togglePasswordBtn"');
    expect($html)->toContain('id="togglePasswordConfirmBtn"');

    // 5. Submit button and loading state elements
    expect($html)->toContain('id="registerSubmitBtn"');
    expect($html)->toContain('Create account');
    expect($html)->toContain('id="registerBtnSpinner"');

    // 6. Login link
    expect($html)->toContain('Already have an account?');
    expect($html)->toContain('href="' . route('login') . '"');

    // 7. Small footer note
    expect($html)->toContain('AI responses may require verification.');

    // 8. Theme toggle support
    expect($html)->toContain('id="guestThemeToggleBtn"');
});

test('registration validation errors are displayed and old input is preserved for name and email only', function () {
    $response = $this->post('/register', [
        'name' => 'Valid Name',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertSessionHasErrors(['email', 'password']);

    // Check old values preserved in session for name and email
    $response->assertSessionHas('_old_input.name', 'Valid Name');
    $response->assertSessionHas('_old_input.email', 'invalid-email');

    // Passwords must NEVER be stored in old input session
    $response->assertSessionMissing('_old_input.password');
    $response->assertSessionMissing('_old_input.password_confirmation');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

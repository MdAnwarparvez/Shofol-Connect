<?php

use App\Models\User;

test('guest cannot access admin dashboard and is redirected to login', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect('/login');
});

test('regular user cannot access admin dashboard', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertStatus(403);
});

test('admin can access admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Admin Dashboard');
    $response->assertSee('System Status', false);
});

test('regular user can access user dashboard', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Welcome back');
    $response->assertSee('AI Support Assistant');
});

test('admin is redirected to admin dashboard upon login', function () {
    $admin = User::factory()->admin()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('regular user is redirected to user dashboard upon login', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
});

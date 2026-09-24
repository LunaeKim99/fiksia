<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page to guests', function () {
    $this->get('/login')->assertOk();
});

it('logs in a user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $response->assertSessionHas('success', 'Selamat datang kembali!');
});

it('rejects login with wrong credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('throttles repeated login attempts', function () {
    User::factory()->create([
        'email' => 'limited@example.com',
        'password' => 'password123',
    ]);

    $response = null;

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $response = $this->postJson('/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ]);
    }

    expect($response)->not->toBeNull()
        ->and($response->status())->toBe(429);
});

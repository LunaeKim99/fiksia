<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the registration page to guests', function () {
    $this->get('/register')->assertOk();
});

it('registers a new user with valid data', function () {
    $response = $this->post('/register', [
        'name' => 'Tester',
        'email' => 'tester@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
    $response->assertSessionHas('success', 'Akun berhasil dibuat.');
});

it('rejects registration without required fields', function () {
    $response = $this->postJson('/register', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/register', [
        'name' => 'Second',
        'email' => 'dup@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('rejects registration when password confirmation does not match', function () {
    $response = $this->postJson('/register', [
        'name' => 'Mismatch',
        'email' => 'mismatch@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password456',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

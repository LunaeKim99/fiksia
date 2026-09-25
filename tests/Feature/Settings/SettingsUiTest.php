<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from settings', function () {
    $this->get(route('settings.show'))->assertRedirect(route('login'));
});

it('exposes settings defaults and safe account provider state', function () {
    $user = User::factory()->create([
        'name' => 'Pembaca Fiksia',
        'google_id' => 'google-safe-id',
        'password' => null,
    ]);

    $this->actingAs($user)
        ->get(route('settings.show'))
        ->assertOk()
        ->assertInertia(function ($page) use ($user): void {
            $props = $page->toArray()['props'];

            expect($props['settings'])->toMatchArray([
                'appearance' => [
                    'mode' => 'system',
                    'accentStyle' => 'single',
                    'preset' => 'violet',
                    'primaryColor' => '#7C3AED',
                    'secondaryColor' => null,
                ],
                'reading' => [
                    'fontSize' => 'normal',
                    'fontFamily' => 'sans',
                    'lineHeight' => '1.7',
                    'columnWidth' => 'normal',
                ],
                'privacy' => [
                    'publicReadingActivity' => null,
                    'publicBookmarks' => null,
                ],
                'notifications' => [
                    'email' => null,
                    'push' => null,
                ],
            ]);
            expect($props['account'])->toMatchArray([
                'name' => $user->name,
                'email' => $user->email,
                'googleConnected' => true,
                'hasPassword' => false,
            ]);
            expect($props['account'])->not->toHaveKey('google_id');
            expect($props['account'])->not->toHaveKey('google_avatar_url');
            expect($props['account'])->not->toHaveKey('token');
            expect($props['account'])->not->toHaveKey('access_token');
            expect($props['account'])->not->toHaveKey('refresh_token');
        });
});

it('updates only the authenticated account name', function () {
    $user = User::factory()->create(['name' => 'Nama Lama']);
    $other = User::factory()->create(['name' => 'Nama Pengguna Lain']);

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.account.profile.update'), [
            'name' => 'Nama Baru',
        ])
        ->assertRedirect(route('settings.show'))
        ->assertSessionHas('success');

    expect($user->fresh()->name)->toBe('Nama Baru')
        ->and($other->fresh()->name)->toBe('Nama Pengguna Lain');
});

it('trims account names before saving', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.account.profile.update'), [
            'name' => '  Nama Bersih  ',
        ])
        ->assertRedirect(route('settings.show'));

    expect($user->fresh()->name)->toBe('Nama Bersih');
});

it('rejects a client supplied account user id', function () {
    $user = User::factory()->create(['name' => 'Nama Tetap']);
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('settings.account.profile.update'), [
            'name' => 'Nama Baru',
            'user_id' => $other->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    expect($user->fresh()->name)->toBe('Nama Tetap');
});

<?php

use App\Application\Settings\SettingsNormalizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from settings show', function () {
    $this->get(route('settings.show'))->assertRedirect(route('login'));
});

it('loads first time settings for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index', false)
            ->where('settings.appearance.mode', 'system')
            ->where('settings.appearance.primaryColor', '#7C3AED')
            ->where('settings.reading.fontSize', 'normal')
            ->where('account.email', $user->email)
        );

    $this->assertDatabaseMissing('user_settings', ['user_id' => $user->id]);
});

it('accepts a complete single appearance payload with null secondary color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('account'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'light',
            'accentStyle' => 'single',
            'preset' => 'violet',
            'primaryColor' => '#7C3AED',
            'secondaryColor' => null,
        ])
        ->assertRedirect(route('account'))
        ->assertSessionHasNoErrors();

    expect($user->settings()->first()->fresh()->appearance)->toMatchArray([
        'mode' => 'light',
        'accentStyle' => 'single',
        'preset' => 'violet',
        'primaryColor' => '#7C3AED',
        'secondaryColor' => null,
    ]);
});

it('redirects a valid appearance update to the safe current internal page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('account'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'dark',
            'accentStyle' => 'single',
            'preset' => 'violet',
            'primaryColor' => '#7C3AED',
            'secondaryColor' => null,
        ])
        ->assertRedirect(route('account'))
        ->assertSessionHas('success', 'Pengaturan berhasil diperbarui.');
});

it('falls back to an internal route for an external referer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('Referer', 'https://external.example/account')
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'dark',
            'accentStyle' => 'single',
            'preset' => 'violet',
            'primaryColor' => '#7C3AED',
            'secondaryColor' => null,
        ])
        ->assertRedirect(route('account'));
});

it('keeps one settings row per user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), ['mode' => 'dark'])
        ->assertRedirect(route('settings.show'));

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), ['mode' => 'light'])
        ->assertRedirect(route('settings.show'));

    expect($user->settings()->count())->toBe(1);
    $this->assertDatabaseHas('user_settings', ['user_id' => $user->id]);
    expect($user->settings()->first()->fresh()->appearance['mode'])->toBe('light');
});

it('preserves reading when patching appearance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'reading']), ['fontSize' => 'large'])
        ->assertRedirect(route('settings.show'));

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), ['mode' => 'dark'])
        ->assertRedirect(route('settings.show'));

    $settings = $user->settings()->first()->fresh();
    expect($settings->appearance['mode'])->toBe('dark')
        ->and($settings->reading['fontSize'])->toBe('large');
});

it('preserves appearance when patching reading', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), ['mode' => 'dark'])
        ->assertRedirect(route('settings.show'));

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'reading']), ['fontSize' => 'large'])
        ->assertRedirect(route('settings.show'));

    $settings = $user->settings()->first()->fresh();
    expect($settings->reading['fontSize'])->toBe('large')
        ->and($settings->appearance['mode'])->toBe('dark');
});

it('rejects invalid colors without changing rows', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), ['mode' => 'dark'])
        ->assertRedirect(route('settings.show'));

    $before = $user->settings()->first()->fresh()->appearance;

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patchJson(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => 'single',
            'primaryColor' => '#fff',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['primaryColor']);

    expect($user->settings()->first()->fresh()->appearance)->toBe($before);
});

it('rejects custom dual appearance without a secondary color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => 'dual',
            'primaryColor' => '#123456',
        ])
        ->assertSessionHasErrors(['secondaryColor'])
        ->assertSessionHas('error', 'Pengaturan gagal diperbarui. Periksa kembali isian Anda.');

    $this->assertDatabaseMissing('user_settings', ['user_id' => $user->id]);
});

it('rejects custom appearance with null or invalid primary color', function () {
    $user = User::factory()->create();

    foreach ([null, 'red', '#fff'] as $primaryColor) {
        $this->actingAs($user)
            ->from(route('settings.show'))
            ->patch(route('settings.update', ['section' => 'appearance']), [
                'preset' => 'custom',
                'accentStyle' => 'single',
                'primaryColor' => $primaryColor,
            ])
            ->assertSessionHasErrors(['primaryColor'])
            ->assertSessionHas('error', 'Pengaturan gagal diperbarui. Periksa kembali isian Anda.');
    }

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => 'single',
        ])
        ->assertSessionHasErrors(['primaryColor'])
        ->assertSessionHas('error', 'Pengaturan gagal diperbarui. Periksa kembali isian Anda.');

    $this->assertDatabaseMissing('user_settings', ['user_id' => $user->id]);
});

it('rejects unknown keys', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patchJson(route('settings.update', ['section' => 'appearance']), ['unknownKey' => 'x'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['unknownKey']);

    $this->assertDatabaseMissing('user_settings', ['user_id' => $user->id]);
});

it('rejects client supplied user id', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'dark',
            'user_id' => $other->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    $this->assertDatabaseMissing('user_settings', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('user_settings', ['user_id' => $other->id]);
});

it('rejects privacy and notification writes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('settings.update', ['section' => 'privacy']), [
            'publicReadingActivity' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['publicReadingActivity']);

    $this->actingAs($user)
        ->patchJson(route('settings.update', ['section' => 'notifications']), [
            'email' => ['enabled' => true],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('repairs malformed persisted sections on read without changing unrelated sections', function () {
    $user = User::factory()->create();
    $user->settings()->create([
        'appearance' => ['mode' => 'neon', 'accentStyle' => 'wide', 'preset' => 'nope', 'primaryColor' => 'red', 'secondaryColor' => 'red'],
        'reading' => ['fontSize' => 'large', 'fontFamily' => 'serif', 'lineHeight' => '1.7', 'columnWidth' => 'normal'],
        'privacy' => ['publicReadingActivity' => true],
        'notifications' => ['email' => ['enabled' => true]],
    ]);

    $defaults = (new SettingsNormalizer)->defaults();

    $this->actingAs($user)
        ->get(route('settings.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index', false)
            ->where('settings.appearance', $defaults['appearance'])
            ->where('settings.privacy', $defaults['privacy'])
            ->where('settings.notifications', $defaults['notifications'])
        );

    expect($user->settings()->first()->fresh()->reading['fontSize'])->toBe('large')
        ->and($user->profile)->toBeNull();
});

it('needs no profile dependency', function () {
    $user = User::factory()->create();

    expect($user->profile)->toBeNull();

    $this->actingAs($user)
        ->get(route('settings.show'))
        ->assertOk();
});

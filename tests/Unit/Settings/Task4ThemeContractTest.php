<?php

namespace Tests\Unit\Settings;

use App\Application\Settings\SettingsNormalizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

it('rejects null secondary color for dual and gradient appearance', function (string $style) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('account'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => $style,
            'primaryColor' => '#123456',
            'secondaryColor' => null,
        ])
        ->assertSessionHasErrors(['secondaryColor']);
})->with(['dual', 'gradient']);

it('falls back for a same-host scheme-relative referer', function () {
    $user = User::factory()->create();
    $origin = rtrim(url('/'), '/');

    $this->actingAs($user)
        ->withHeader('Referer', $origin.'//attacker.example')
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'dark',
        ])
        ->assertRedirect(route('account'));
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

it('redirects validation failures to a safe internal referer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('account'))
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => 'single',
            'primaryColor' => '#fff',
        ])
        ->assertRedirect(route('account'))
        ->assertSessionHasErrors(['primaryColor'])
        ->assertSessionHas(
            'error',
            'Pengaturan gagal diperbarui. Periksa kembali isian Anda.',
        );
});

it('falls back to an internal route for an external referer on validation failure', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('Referer', 'https://external.example/account')
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'preset' => 'custom',
            'accentStyle' => 'single',
            'primaryColor' => '#fff',
        ])
        ->assertRedirect(route('account'))
        ->assertSessionHasErrors(['primaryColor'])
        ->assertSessionHas(
            'error',
            'Pengaturan gagal diperbarui. Periksa kembali isian Anda.',
        );
});

it('falls back to an internal route for an external referer on success', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('Referer', 'https://external.example/account')
        ->patch(route('settings.update', ['section' => 'appearance']), [
            'mode' => 'dark',
        ])
        ->assertRedirect(route('account'));
});

it('resolves canonical preset colors', function () {
    $normalizer = new SettingsNormalizer;
    $current = $normalizer->defaults()['appearance'];

    $ocean = $normalizer->appearance($current, [
        'preset' => 'ocean',
        'accentStyle' => 'gradient',
        'primaryColor' => '#ff0000',
        'secondaryColor' => '#00ff00',
    ]);

    expect($ocean['primaryColor'])->toBe('#0E7490')
        ->and($ocean['secondaryColor'])->toBe('#2563EB');
});

<?php

use App\Domain\Shared\Enums\UserStatus;
use App\Models\User;

it('defaults factory users to the active status', function () {
    expect(User::factory()->make()->status)->toBe(UserStatus::Active);
});

it('identifies active users only', function () {
    $active = User::factory()->make(['status' => UserStatus::Active->value]);
    $disabled = User::factory()->make(['status' => UserStatus::Disabled->value]);
    $banned = User::factory()->make(['status' => UserStatus::Banned->value]);

    expect($active->isActive())->toBeTrue()
        ->and($disabled->isActive())->toBeFalse()
        ->and($banned->isActive())->toBeFalse();
});

it('allows google disconnect only when a password exists', function () {
    $withPassword = User::factory()->make(['password' => 'secret-password']);
    $googleOnly = User::factory()->make(['password' => null]);

    expect($withPassword->canDisconnectGoogle())->toBeTrue()
        ->and($googleOnly->canDisconnectGoogle())->toBeFalse();
});

it('exposes the expected status values', function () {
    expect(UserStatus::Active->value)->toBe('active')
        ->and(UserStatus::Disabled->value)->toBe('disabled')
        ->and(UserStatus::Banned->value)->toBe('banned');
});

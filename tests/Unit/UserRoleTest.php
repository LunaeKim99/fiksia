<?php

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;

it('defaults users to the member role', function () {
    expect(User::factory()->make()->role)->toBe(UserRole::Member);
});

it('identifies moderator and admin roles', function () {
    $member = User::factory()->make(['role' => UserRole::Member->value]);
    $moderator = User::factory()->make(['role' => UserRole::Moderator->value]);
    $admin = User::factory()->make(['role' => UserRole::Admin->value]);

    expect($member->isModerator())->toBeFalse()
        ->and($member->isAdmin())->toBeFalse()
        ->and($moderator->isModerator())->toBeTrue()
        ->and($moderator->isAdmin())->toBeFalse()
        ->and($admin->isModerator())->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue();
});

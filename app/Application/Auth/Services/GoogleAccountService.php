<?php

namespace App\Application\Auth\Services;

use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\Enums\UserStatus;
use App\Domain\Shared\ValueObjects\GoogleIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GoogleAccountService
{
    public function loginWithGoogle(GoogleIdentity $identity): User
    {
        $this->assertValidIdentity($identity);

        if (! $identity->emailVerified) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        $user = User::query()
            ->where('google_id', $identity->id)
            ->first();

        if ($user !== null) {
            if (! $user->isActive()) {
                throw new \RuntimeException('Akun tidak dapat diautentikasi.');
            }

            return $user;
        }

        $email = Str::lower($identity->email);

        $emailCollision = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if ($emailCollision !== null) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        return DB::transaction(function () use ($identity, $email) {
            return User::query()->forceCreate([
                'name' => $identity->name,
                'email' => $email,
                'google_id' => $identity->id,
                'google_avatar_url' => $identity->avatarUrl,
                'password' => null,
                'status' => UserStatus::Active->value,
                'role' => UserRole::Member->value,
                'email_verified_at' => now(),
            ]);
        });
    }

    public function connectGoogle(User $user, GoogleIdentity $identity): User
    {
        $this->assertValidIdentity($identity);

        if (! $identity->emailVerified) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        if (! $user->isActive()) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        $incomingEmail = Str::lower($identity->email);

        if (Str::lower($user->email) !== $incomingEmail) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        $claimed = User::query()
            ->where('google_id', $identity->id)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($claimed) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        return DB::transaction(function () use ($user, $identity) {
            $user->forceFill([
                'google_id' => $identity->id,
                'google_avatar_url' => $identity->avatarUrl,
            ])->save();

            return $user;
        });
    }

    public function disconnectGoogle(User $user): User
    {
        if ($user->password === null) {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }

        return DB::transaction(function () use ($user) {
            $user->forceFill([
                'google_id' => null,
                'google_avatar_url' => null,
            ])->save();

            return $user;
        });
    }

    private function assertValidIdentity(GoogleIdentity $identity): void
    {
        if (trim($identity->id) === '' || trim($identity->email) === '' || trim($identity->name) === '') {
            throw new \RuntimeException('Akun tidak dapat diautentikasi.');
        }
    }
}

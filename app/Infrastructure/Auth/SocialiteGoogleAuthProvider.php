<?php

namespace App\Infrastructure\Auth;

use App\Application\Auth\Contracts\GoogleAuthProvider;
use App\Domain\Shared\ValueObjects\GoogleIdentity;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

final class SocialiteGoogleAuthProvider implements GoogleAuthProvider
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect();
    }

    public function user(): GoogleIdentity
    {
        $socialiteUser = Socialite::driver('google')->user();
        $raw = $socialiteUser->user;

        $id = trim((string) ($socialiteUser->getId() ?? ''));
        if ($id === '') {
            $id = trim((string) ($raw['sub'] ?? ''));
        }

        $email = strtolower(trim((string) ($socialiteUser->getEmail() ?? '')));

        $name = trim((string) ($socialiteUser->getName() ?? ''));
        if ($name === '') {
            $name = trim((string) ($raw['name'] ?? ''));
        }

        $avatar = $socialiteUser->getAvatar();
        if (! is_string($avatar) || $avatar === '') {
            $avatar = is_string($raw['picture'] ?? null) ? $raw['picture'] : null;
        }

        $avatarUrl = $this->normalizeAvatarUrl($avatar);

        $rawVerified = $raw['verified_email'] ?? $raw['email_verified'] ?? null;
        if (is_bool($rawVerified)) {
            $emailVerified = $rawVerified;
        } elseif (is_string($rawVerified)) {
            $emailVerified = in_array(strtolower(trim($rawVerified)), ['1', 'true', 'yes'], true);
        } elseif (is_int($rawVerified)) {
            $emailVerified = $rawVerified === 1;
        } else {
            $emailVerified = false;
        }

        if ($id === '' || $email === '' || $name === '') {
            throw new \RuntimeException('Identitas Google tidak valid.');
        }

        return new GoogleIdentity(
            id: $id,
            email: $email,
            name: $name,
            avatarUrl: $avatarUrl,
            emailVerified: $emailVerified,
        );
    }

    private function normalizeAvatarUrl(mixed $avatar): ?string
    {
        if (! is_string($avatar) || trim($avatar) === '') {
            return null;
        }

        $url = trim($avatar);

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        if (! str_starts_with($url, 'https://')) {
            return null;
        }

        if (strlen($url) > 2048) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }
}

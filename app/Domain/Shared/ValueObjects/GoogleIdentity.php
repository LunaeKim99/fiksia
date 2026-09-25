<?php

namespace App\Domain\Shared\ValueObjects;

final readonly class GoogleIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public ?string $avatarUrl,
        public bool $emailVerified,
    ) {}
}

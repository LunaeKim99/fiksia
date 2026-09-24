<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Shared\Contracts\UserRepository;
use App\Models\User;

class EloquentUserRepository implements UserRepository
{
    public function exists(int $id): bool
    {
        return User::query()->whereKey($id)->exists();
    }
}

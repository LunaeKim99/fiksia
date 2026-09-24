<?php

namespace App\Domain\Shared\Contracts;

interface UserRepository
{
    public function exists(int $id): bool;
}

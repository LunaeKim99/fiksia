<?php

namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
}

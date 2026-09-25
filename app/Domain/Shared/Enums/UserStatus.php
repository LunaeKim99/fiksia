<?php

namespace App\Domain\Shared\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Banned = 'banned';
}

<?php

namespace App\Domain\Novel\Enums;

enum StoryStatus: string
{
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Hiatus = 'hiatus';
}

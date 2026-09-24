<?php

namespace App\Domain\Moderation\Enums;

enum SubmissionItemType: string
{
    case Cover = 'cover';
    case Synopsis = 'synopsis';
    case Metadata = 'metadata';
    case Chapter = 'chapter';
}

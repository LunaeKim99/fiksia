<?php

namespace App\Domain\Chapter\Enums;

enum ChapterStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';

    /**
     * @var array<string, list<self>>
     */
    private const TRANSITIONS = [
        'draft' => [self::Published, self::Archived],
        'published' => [self::Hidden, self::Archived],
        'hidden' => [self::Published, self::Archived],
        'archived' => [],
    ];

    public static function canTransitionTo(self $from, self $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value], true);
    }
}

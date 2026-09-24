<?php

namespace App\Domain\Novel\Enums;

enum NovelStatus: string
{
    case Draft = 'draft';
    case InitialReview = 'initial_review';
    case Published = 'published';
    case RevisionRequired = 'revision_required';
    case Suspended = 'suspended';
    case Archived = 'archived';
    case Rejected = 'rejected';

    /**
     * @var array<string, list<self>>
     */
    private const TRANSITIONS = [
        'draft' => [self::InitialReview],
        'initial_review' => [self::Published, self::RevisionRequired, self::Rejected],
        'revision_required' => [self::InitialReview, self::Suspended, self::Archived],
        'published' => [self::Suspended, self::Archived],
        'suspended' => [self::Published, self::Archived],
        'archived' => [],
        'rejected' => [],
    ];

    public static function canTransitionTo(self $from, self $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value], true);
    }
}

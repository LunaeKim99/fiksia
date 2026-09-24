<?php

namespace App\Domain\Moderation\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';

    // ponytail: Reviewing reserved for future claim-for-review step; transitions allow direct approve for MVP
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case RevisionRequired = 'revision_required';
    case Rejected = 'rejected';

    /**
     * @var array<string, list<self>>
     */
    private const TRANSITIONS = [
        'submitted' => [self::Reviewing, self::Approved, self::RevisionRequired, self::Rejected],
        'reviewing' => [self::Approved, self::RevisionRequired, self::Rejected],
        'approved' => [],
        'revision_required' => [],
        'rejected' => [],
    ];

    public static function canTransitionTo(self $from, self $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value], true);
    }
}

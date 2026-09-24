<?php

namespace App\Domain\Moderation\Enums;

enum AuditStatus: string
{
    case Planned = 'planned';
    case InReview = 'in_review';
    case Passed = 'passed';
    case RevisionRequired = 'revision_required';
    case Restricted = 'restricted';
    case Removed = 'removed';
}

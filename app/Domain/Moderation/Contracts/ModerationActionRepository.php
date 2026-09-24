<?php

namespace App\Domain\Moderation\Contracts;

use App\Domain\Moderation\Enums\SubmissionStatus;

interface ModerationActionRepository
{
    public function recordInitialSubmissionDecision(
        int $moderatorId,
        int $submissionId,
        int $novelId,
        SubmissionStatus $decision,
        ?string $reason,
    ): void;
}

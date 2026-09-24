<?php

namespace App\Application\Moderation\Commands\ModerateInitialSubmission;

use App\Domain\Moderation\Enums\SubmissionStatus;

readonly class ModerateInitialSubmissionCommand
{
    public function __construct(
        public int $submissionId,
        public int $moderatorId,
        public SubmissionStatus $decision,
        public ?string $notes = null,
    ) {}
}

<?php

namespace App\Application\Moderation\Commands\RecordModerationAction;

use App\Domain\Moderation\Enums\SubmissionStatus;

readonly class RecordModerationActionCommand
{
    public function __construct(
        public int $moderatorId,
        public int $submissionId,
        public int $novelId,
        public SubmissionStatus $decision,
        public ?string $reason = null,
    ) {}
}

<?php

namespace App\Application\Moderation\Commands\RecordModerationAction;

use App\Domain\Moderation\Contracts\ModerationActionRepository;

class RecordModerationActionHandler
{
    public function __construct(
        private ModerationActionRepository $repository,
    ) {}

    public function handle(RecordModerationActionCommand $command): void
    {
        $this->repository->recordInitialSubmissionDecision(
            moderatorId: $command->moderatorId,
            submissionId: $command->submissionId,
            novelId: $command->novelId,
            decision: $command->decision,
            reason: $command->reason,
        );
    }
}

<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Moderation\Contracts\ModerationActionRepository;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Models\InitialSubmission;
use App\Models\ModerationAction;

class EloquentModerationActionRepository implements ModerationActionRepository
{
    public function recordInitialSubmissionDecision(
        int $moderatorId,
        int $submissionId,
        int $novelId,
        SubmissionStatus $decision,
        ?string $reason,
    ): void {
        ModerationAction::query()->create([
            'moderator_id' => $moderatorId,
            'target_type' => (new InitialSubmission)->getMorphClass(),
            'target_id' => $submissionId,
            'action' => $decision->value,
            'reason' => $reason,
            'metadata' => [
                'novel_id' => $novelId,
                'submission_id' => $submissionId,
                'decision' => $decision->value,
            ],
        ]);
    }
}

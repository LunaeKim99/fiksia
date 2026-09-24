<?php

namespace App\Application\Moderation\Commands\ModerateInitialSubmission;

use App\Application\Moderation\Commands\RecordModerationAction\RecordModerationActionCommand;
use App\Application\Moderation\Commands\RecordModerationAction\RecordModerationActionHandler;
use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Moderation\Contracts\InitialSubmissionRepository;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Exceptions\InvalidReviewDecisionException;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;
use Illuminate\Support\Facades\DB;

class ModerateInitialSubmissionHandler
{
    public function __construct(
        private InitialSubmissionRepository $submissionRepository,
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
        private RecordModerationActionHandler $moderationActionHandler,
    ) {}

    public function handle(ModerateInitialSubmissionCommand $command): void
    {
        // ponytail: application guards self-approval only; Task 6 Policy owns full moderator/admin role check

        DB::transaction(function () use ($command): void {
            $submission = $this->submissionRepository->findOrFailForUpdate($command->submissionId);

            if ($submission->submittedBy === $command->moderatorId) {
                throw new UnauthorizedActionException('Moderator tidak boleh memoderasi karya sendiri.');
            }

            $this->validateDecision($command, $submission->status);

            $now = time();

            $this->submissionRepository->updateDecision(
                $command->submissionId,
                $command->moderatorId,
                $command->decision,
                $command->notes,
                $now,
            );

            $this->moderationActionHandler->handle(new RecordModerationActionCommand(
                moderatorId: $command->moderatorId,
                submissionId: $command->submissionId,
                novelId: $submission->novelId,
                decision: $command->decision,
                reason: $command->notes,
            ));

            $novelStatus = match ($command->decision) {
                SubmissionStatus::Approved => NovelStatus::Published,
                SubmissionStatus::RevisionRequired => NovelStatus::RevisionRequired,
                SubmissionStatus::Rejected => NovelStatus::Rejected,
                default => throw new InvalidReviewDecisionException('Keputusan tidak valid.'),
            };

            $novelUpdate = ['status' => $novelStatus];

            if ($command->decision === SubmissionStatus::Approved) {
                $novelUpdate['published_at'] = $now;
            }

            $this->novelRepository->update($submission->novelId, $novelUpdate);

            if ($command->decision === SubmissionStatus::Approved) {
                $this->publishSubmissionChapters($command->submissionId, $now);
            }
        });
    }

    private function validateDecision(ModerateInitialSubmissionCommand $command, SubmissionStatus $currentStatus): void
    {
        $allowed = [SubmissionStatus::Approved, SubmissionStatus::RevisionRequired, SubmissionStatus::Rejected];

        if (! in_array($command->decision, $allowed, true)) {
            $allowedValues = implode(', ', array_map(
                fn (SubmissionStatus $status): string => $status->value,
                $allowed,
            ));

            throw new InvalidReviewDecisionException('Keputusan tidak valid. Hanya '.$allowedValues.'.');
        }

        if (! SubmissionStatus::canTransitionTo($currentStatus, $command->decision)) {
            throw new InvalidReviewDecisionException('Submission ini sudah memiliki keputusan.');
        }

        if ($command->decision !== SubmissionStatus::Approved && ($command->notes === null || $command->notes === '')) {
            throw new InvalidReviewDecisionException('Catatan review wajib diisi untuk revisi atau penolakan.');
        }
    }

    private function publishSubmissionChapters(int $submissionId, int $publishedAt): void
    {
        $chapterIds = $this->submissionRepository->findChapterIds($submissionId);

        foreach ($chapterIds as $chapterId) {
            $this->chapterRepository->setPublished($chapterId, $publishedAt);
        }
    }
}

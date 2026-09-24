<?php

namespace App\Domain\Moderation\Contracts;

use App\Domain\Moderation\Entities\InitialSubmissionEntity;
use App\Domain\Moderation\Enums\SubmissionStatus;

interface InitialSubmissionRepository
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createForNovel(int $novelId, int $submittedBy, array $items): int;

    public function findOrFail(int $id): InitialSubmissionEntity;

    public function findOrFailForUpdate(int $id): InitialSubmissionEntity;

    public function updateDecision(int $id, int $moderatorId, SubmissionStatus $status, ?string $notes, int $decidedAtTimestamp): void;

    /**
     * @return array<int>
     */
    public function findChapterIds(int $submissionId): array;
}

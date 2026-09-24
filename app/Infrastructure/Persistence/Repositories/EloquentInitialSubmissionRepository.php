<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Moderation\Contracts\InitialSubmissionRepository;
use App\Domain\Moderation\Entities\InitialSubmissionEntity;
use App\Domain\Moderation\Enums\SubmissionItemType;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Models\InitialSubmission;
use App\Models\SubmissionItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class EloquentInitialSubmissionRepository implements InitialSubmissionRepository
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createForNovel(int $novelId, int $submittedBy, array $items): int
    {
        /** @var InitialSubmission $submission */
        $submission = InitialSubmission::query()->create([
            'novel_id' => $novelId,
            'submitted_by' => $submittedBy,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
        ]);

        foreach ($items as $index => $item) {
            SubmissionItem::query()->create([
                'submission_id' => $submission->id,
                'item_type' => $item['item_type'] instanceof SubmissionItemType ? $item['item_type']->value : $item['item_type'],
                'chapter_id' => $item['chapter_id'] ?? null,
                'snapshot' => $item['snapshot'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }

        return (int) $submission->id;
    }

    public function findOrFail(int $id): InitialSubmissionEntity
    {
        return $this->map($this->loadModelOrFail($id));
    }

    public function findOrFailForUpdate(int $id): InitialSubmissionEntity
    {
        // ponytail: SQLite dev does not exercise row locks; MySQL production does

        return $this->map($this->loadModelOrFail($id, true));
    }

    public function updateDecision(int $id, int $moderatorId, SubmissionStatus $status, ?string $notes, int $decidedAtTimestamp): void
    {
        $model = $this->loadModelOrFail($id);

        $model->status = $status;
        $model->reviewer_id = $moderatorId;
        $model->review_notes = $notes;
        $model->decided_at = Carbon::createFromTimestamp($decidedAtTimestamp);
        $model->saveQuietly();
    }

    /**
     * @return array<int>
     */
    public function findChapterIds(int $submissionId): array
    {
        /** @var array<int> $ids */
        $ids = SubmissionItem::query()
            ->where('submission_id', $submissionId)
            ->where('item_type', SubmissionItemType::Chapter->value)
            ->whereNotNull('chapter_id')
            ->pluck('chapter_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $ids;
    }

    private function loadModelOrFail(int $id, bool $forUpdate = false): InitialSubmission
    {
        try {
            $query = InitialSubmission::query();

            if ($forUpdate) {
                $query->lockForUpdate();
            }

            /** @var InitialSubmission $model */
            $model = $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundException('Submission tidak ditemukan.', previous: $e);
        }

        return $model;
    }

    private function map(InitialSubmission $submission): InitialSubmissionEntity
    {
        return new InitialSubmissionEntity(
            id: (int) $submission->id,
            novelId: (int) $submission->novel_id,
            submittedBy: (int) $submission->submitted_by,
            status: $submission->status,
            reviewerId: $submission->reviewer_id !== null ? (int) $submission->reviewer_id : null,
            reviewNotes: $submission->review_notes,
            submittedAt: $submission->submitted_at?->toDateTimeImmutable(),
            decidedAt: $submission->decided_at?->toDateTimeImmutable(),
        );
    }
}

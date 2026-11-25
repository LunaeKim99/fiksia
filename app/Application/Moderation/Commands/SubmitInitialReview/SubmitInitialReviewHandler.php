<?php

namespace App\Application\Moderation\Commands\SubmitInitialReview;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Moderation\Contracts\InitialSubmissionRepository;
use App\Domain\Moderation\Entities\InitialSubmissionEntity;
use App\Domain\Moderation\Enums\SubmissionItemType;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Entities\NovelEntity;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\Exceptions\InvalidStatusTransitionException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;
use Illuminate\Support\Facades\DB;

class SubmitInitialReviewHandler
{
    public function __construct(
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
        private InitialSubmissionRepository $submissionRepository,
    ) {}

    public function handle(SubmitInitialReviewCommand $command): InitialSubmissionEntity
    {
        return DB::transaction(function () use ($command): InitialSubmissionEntity {
            $novel = $this->novelRepository->findOrFailForUpdate($command->novelId);

            if ($novel->userId !== $command->authorId) {
                throw new UnauthorizedActionException('Anda tidak memiliki novel ini.');
            }

            if (! NovelStatus::canTransitionTo($novel->status, NovelStatus::InitialReview)) {
                throw new InvalidStatusTransitionException('Novel tidak dapat diajukan untuk review pada status saat ini.');
            }

            if ($command->chapterIds === []) {
                throw new DomainException('Pengajuan awal wajib menyertakan minimal satu bab.');
            }

            $items = $this->buildSubmissionItems($novel, $command->chapterIds);

            $submissionId = $this->submissionRepository->createForNovel(
                $novel->id,
                $command->authorId,
                $items,
            );

            $this->novelRepository->update($novel->id, [
                'status' => NovelStatus::InitialReview,
            ]);

            return $this->submissionRepository->findOrFail($submissionId);
        });
    }

    /**
     * @param  array<int>  $chapterIds
     * @return array<int, array<string, mixed>>
     */
    private function buildSubmissionItems(NovelEntity $novel, array $chapterIds): array
    {
        $items = [];

        $items[] = [
            'item_type' => SubmissionItemType::Cover,
            'chapter_id' => null,
            'snapshot' => ['cover_path' => $novel->coverPath],
            'sort_order' => 0,
        ];

        $items[] = [
            'item_type' => SubmissionItemType::Synopsis,
            'chapter_id' => null,
            'snapshot' => ['synopsis' => $novel->synopsis],
            'sort_order' => 1,
        ];

        $items[] = [
            'item_type' => SubmissionItemType::Metadata,
            'chapter_id' => null,
            'snapshot' => [
                'title' => $novel->title,
                'story_status' => $novel->storyStatus->value,
                'slug' => $novel->slug,
            ],
            'sort_order' => 2,
        ];

        $sortOrder = 3;

        foreach ($chapterIds as $chapterId) {
            $chapter = $this->chapterRepository->findOrFail((int) $chapterId);

            if ($chapter->novelId !== $novel->id) {
                throw new NotFoundException('Bab tidak termasuk dalam novel ini.');
            }

            if ($chapter->status !== ChapterStatus::Draft) {
                throw new InvalidStatusTransitionException('Hanya draft bab yang dapat diajukan.');
            }

            if (trim((string) $chapter->content) === '') {
                throw new DomainException('Semua bab yang diajukan harus memiliki isi.');
            }

            $items[] = [
                'item_type' => SubmissionItemType::Chapter,
                'chapter_id' => $chapterId,
                'snapshot' => [
                    'chapter_number' => $chapter->chapterNumber,
                    'title' => $chapter->title,
                    'content' => $chapter->content,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        return $items;
    }
}

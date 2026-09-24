<?php

namespace App\Application\Novel\Commands\PublishChapter;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Entities\ChapterEntity;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Exceptions\InvalidStatusTransitionException;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;

class PublishChapterHandler
{
    public function __construct(
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
    ) {}

    public function handle(PublishChapterCommand $command): ChapterEntity
    {
        $chapter = $this->chapterRepository->findOrFail($command->chapterId);

        $novel = $this->novelRepository->findOrFail($chapter->novelId);

        if ($novel->userId !== $command->authorId) {
            throw new UnauthorizedActionException('Anda tidak memiliki novel ini.');
        }

        if ($novel->status !== NovelStatus::Published) {
            throw new InvalidStatusTransitionException('Bab hanya dapat diterbitkan setelah novel disetujui.');
        }

        if (! ChapterStatus::canTransitionTo($chapter->status, ChapterStatus::Published)) {
            throw new InvalidStatusTransitionException('Bab ini tidak dapat diterbitkan dari status saat ini.');
        }

        $this->chapterRepository->setPublished($command->chapterId, time());

        return $this->chapterRepository->findOrFail($command->chapterId);
    }
}

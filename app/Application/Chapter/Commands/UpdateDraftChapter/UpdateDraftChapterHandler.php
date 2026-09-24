<?php

namespace App\Application\Chapter\Commands\UpdateDraftChapter;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Entities\ChapterEntity;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Shared\Exceptions\InvalidStatusTransitionException;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;

class UpdateDraftChapterHandler
{
    public function __construct(
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
    ) {}

    public function handle(UpdateDraftChapterCommand $command): ChapterEntity
    {
        $chapter = $this->chapterRepository->findOrFail($command->chapterId);
        $novel = $this->novelRepository->findOrFail($chapter->novelId);

        if ($novel->userId !== $command->authorId) {
            throw new UnauthorizedActionException('Anda tidak memiliki novel ini.');
        }

        if ($chapter->status !== ChapterStatus::Draft) {
            throw new InvalidStatusTransitionException('Draft yang sudah terbit tidak dapat diedit.');
        }

        return $this->chapterRepository->update($command->chapterId, [
            'title' => $command->title,
            'content' => $command->content,
        ]);
    }
}

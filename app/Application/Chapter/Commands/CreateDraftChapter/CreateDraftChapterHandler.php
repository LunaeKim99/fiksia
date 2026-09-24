<?php

namespace App\Application\Chapter\Commands\CreateDraftChapter;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Entities\ChapterEntity;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;
use Illuminate\Support\Facades\DB;

class CreateDraftChapterHandler
{
    public function __construct(
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
    ) {}

    public function handle(CreateDraftChapterCommand $command): ChapterEntity
    {
        $novel = $this->novelRepository->findOrFail($command->novelId);

        if ($novel->userId !== $command->authorId) {
            throw new UnauthorizedActionException('Anda tidak memiliki novel ini.');
        }

        return DB::transaction(function () use ($command) {
            $chapterNumber = $this->chapterRepository->nextChapterNumber($command->novelId);

            return $this->chapterRepository->create($command->novelId, [
                'chapter_number' => $chapterNumber,
                'title' => $command->title,
                'content' => $command->content,
                'status' => ChapterStatus::Draft,
            ]);
        });
    }
}

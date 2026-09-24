<?php

namespace App\Application\Reader\Commands\AddBookmark;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class AddBookmarkHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
        private ChapterRepository $chapterRepository,
        private NovelRepository $novelRepository,
    ) {}

    public function handle(AddBookmarkCommand $command): void
    {
        if ($command->userId < 1 || $command->chapterId < 1) {
            throw new DomainException('Data bookmark tidak valid.');
        }

        if ($command->position !== null && $command->position < 0) {
            throw new DomainException('Posisi bookmark tidak valid.');
        }

        $chapter = $this->chapterRepository->findOrFail($command->chapterId);

        if ($chapter->status !== ChapterStatus::Published) {
            throw new DomainException('Bab harus terbit sebelum dapat di-bookmark.');
        }

        $novel = $this->novelRepository->findOrFail($chapter->novelId);

        if ($novel->status !== NovelStatus::Published) {
            throw new DomainException('Novel harus terbit sebelum dapat di-bookmark.');
        }

        $this->libraryRepository->addBookmark(
            $command->userId,
            $command->chapterId,
            $command->position,
            $command->note,
        );
    }
}

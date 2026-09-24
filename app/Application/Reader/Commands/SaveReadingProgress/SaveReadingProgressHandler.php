<?php

namespace App\Application\Reader\Commands\SaveReadingProgress;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Reader\Entities\ReadingProgressEntity;
use App\Domain\Shared\Exceptions\DomainException;

class SaveReadingProgressHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
        private NovelRepository $novelRepository,
        private ChapterRepository $chapterRepository,
    ) {}

    public function handle(SaveReadingProgressCommand $command): ReadingProgressEntity
    {
        if ($command->userId < 1 || $command->novelId < 1 || $command->chapterId < 1) {
            throw new DomainException('Data progres baca tidak valid.');
        }

        if ($command->position !== null && $command->position < 0) {
            throw new DomainException('Posisi baca tidak valid.');
        }

        $novel = $this->novelRepository->findOrFail($command->novelId);

        if ($novel->status !== NovelStatus::Published) {
            throw new DomainException('Novel harus terbit sebelum progres dapat disimpan.');
        }

        $chapter = $this->chapterRepository->findOrFail($command->chapterId);

        if ($chapter->novelId !== $command->novelId) {
            throw new DomainException('Bab tidak terkait dengan novel ini.');
        }

        if ($chapter->status !== ChapterStatus::Published) {
            throw new DomainException('Bab harus terbit sebelum progres dapat disimpan.');
        }

        $this->libraryRepository->upsertProgress(
            $command->userId,
            $command->novelId,
            $command->chapterId,
            $command->position,
            time(),
        );

        $progress = $this->libraryRepository->findProgress($command->userId, $command->novelId);

        if ($progress === null) {
            throw new DomainException('Progres baca tidak dapat disimpan.');
        }

        return $progress;
    }
}

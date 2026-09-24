<?php

namespace App\Application\Reader\Commands\SaveNovel;

use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class SaveNovelHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
        private NovelRepository $novelRepository,
    ) {}

    public function handle(SaveNovelCommand $command): void
    {
        if ($command->userId < 1 || $command->novelId < 1) {
            throw new DomainException('Data library tidak valid.');
        }

        $novel = $this->novelRepository->findOrFail($command->novelId);

        if ($novel->status !== NovelStatus::Published) {
            throw new DomainException('Novel harus terbit sebelum dapat disimpan.');
        }

        $this->libraryRepository->saveNovel($command->userId, $command->novelId);
    }
}

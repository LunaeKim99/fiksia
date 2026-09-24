<?php

namespace App\Application\Reader\Commands\UnsaveNovel;

use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class UnsaveNovelHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
    ) {}

    public function handle(UnsaveNovelCommand $command): void
    {
        if ($command->userId < 1 || $command->novelId < 1) {
            throw new DomainException('Data library tidak valid.');
        }

        $this->libraryRepository->unsaveNovel($command->userId, $command->novelId);
    }
}

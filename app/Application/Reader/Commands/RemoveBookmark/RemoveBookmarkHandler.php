<?php

namespace App\Application\Reader\Commands\RemoveBookmark;

use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class RemoveBookmarkHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
    ) {}

    public function handle(RemoveBookmarkCommand $command): void
    {
        if ($command->userId < 1 || $command->chapterId < 1) {
            throw new DomainException('Data bookmark tidak valid.');
        }

        $this->libraryRepository->removeBookmark($command->userId, $command->chapterId);
    }
}

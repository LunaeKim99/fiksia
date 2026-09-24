<?php

namespace App\Application\Reader\Commands\UnfollowNovel;

use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class UnfollowNovelHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
    ) {}

    public function handle(UnfollowNovelCommand $command): void
    {
        if ($command->userId < 1 || $command->novelId < 1) {
            throw new DomainException('Data library tidak valid.');
        }

        $this->libraryRepository->unfollow($command->userId, $command->novelId);
    }
}

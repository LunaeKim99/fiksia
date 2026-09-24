<?php

namespace App\Application\Reader\Commands\FollowNovel;

use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;

class FollowNovelHandler
{
    public function __construct(
        private LibraryRepository $libraryRepository,
        private NovelRepository $novelRepository,
    ) {}

    public function handle(FollowNovelCommand $command): void
    {
        if ($command->userId < 1 || $command->novelId < 1) {
            throw new DomainException('Data library tidak valid.');
        }

        $novel = $this->novelRepository->findOrFail($command->novelId);

        if ($novel->status !== NovelStatus::Published) {
            throw new DomainException('Novel harus terbit sebelum dapat diikuti.');
        }

        $this->libraryRepository->follow($command->userId, $command->novelId);
    }
}

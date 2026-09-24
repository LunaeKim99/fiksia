<?php

namespace App\Application\Reader\Commands\UnfollowNovel;

readonly class UnfollowNovelCommand
{
    public function __construct(
        public int $userId,
        public int $novelId,
    ) {}
}

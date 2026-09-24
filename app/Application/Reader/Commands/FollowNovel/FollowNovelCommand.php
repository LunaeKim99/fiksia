<?php

namespace App\Application\Reader\Commands\FollowNovel;

readonly class FollowNovelCommand
{
    public function __construct(
        public int $userId,
        public int $novelId,
    ) {}
}

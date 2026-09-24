<?php

namespace App\Application\Reader\Commands\UnsaveNovel;

readonly class UnsaveNovelCommand
{
    public function __construct(
        public int $userId,
        public int $novelId,
    ) {}
}

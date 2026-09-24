<?php

namespace App\Application\Reader\Commands\SaveNovel;

readonly class SaveNovelCommand
{
    public function __construct(
        public int $userId,
        public int $novelId,
    ) {}
}

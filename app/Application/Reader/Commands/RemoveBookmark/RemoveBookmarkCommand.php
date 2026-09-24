<?php

namespace App\Application\Reader\Commands\RemoveBookmark;

readonly class RemoveBookmarkCommand
{
    public function __construct(
        public int $userId,
        public int $chapterId,
    ) {}
}

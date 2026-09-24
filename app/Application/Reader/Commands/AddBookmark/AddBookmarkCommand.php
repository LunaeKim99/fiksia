<?php

namespace App\Application\Reader\Commands\AddBookmark;

readonly class AddBookmarkCommand
{
    public function __construct(
        public int $userId,
        public int $chapterId,
        public ?int $position = null,
        public ?string $note = null,
    ) {}
}

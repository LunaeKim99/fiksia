<?php

namespace App\Application\Reader\Commands\SaveReadingProgress;

readonly class SaveReadingProgressCommand
{
    public function __construct(
        public int $userId,
        public int $novelId,
        public int $chapterId,
        public ?int $position = null,
    ) {}
}

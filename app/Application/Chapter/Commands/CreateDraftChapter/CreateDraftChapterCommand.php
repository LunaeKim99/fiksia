<?php

namespace App\Application\Chapter\Commands\CreateDraftChapter;

readonly class CreateDraftChapterCommand
{
    public function __construct(
        public int $novelId,
        public int $authorId,
        public string $title,
        public ?string $content = null,
    ) {}
}

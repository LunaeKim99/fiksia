<?php

namespace App\Application\Chapter\Commands\UpdateDraftChapter;

readonly class UpdateDraftChapterCommand
{
    public function __construct(
        public int $chapterId,
        public int $authorId,
        public string $title,
        public ?string $content = null,
    ) {}
}

<?php

namespace App\Application\Novel\Commands\PublishChapter;

readonly class PublishChapterCommand
{
    public function __construct(
        public int $chapterId,
        public int $authorId,
    ) {}
}

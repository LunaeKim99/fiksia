<?php

namespace App\Domain\Chapter\Entities;

use App\Domain\Chapter\Enums\ChapterStatus;
use DateTimeImmutable;

readonly class ChapterEntity
{
    public function __construct(
        public int $id,
        public int $novelId,
        public int $chapterNumber,
        public string $title,
        public ?string $content,
        public ChapterStatus $status,
        public bool $isFree,
        public ?DateTimeImmutable $publishedAt,
    ) {}
}

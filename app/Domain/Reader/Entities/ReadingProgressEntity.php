<?php

namespace App\Domain\Reader\Entities;

use DateTimeImmutable;

readonly class ReadingProgressEntity
{
    public function __construct(
        public int $userId,
        public int $novelId,
        public int $chapterId,
        public ?int $position,
        public ?DateTimeImmutable $lastReadAt,
    ) {}
}

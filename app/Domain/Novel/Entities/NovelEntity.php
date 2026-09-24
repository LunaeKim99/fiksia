<?php

namespace App\Domain\Novel\Entities;

use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Novel\Enums\StoryStatus;
use DateTimeImmutable;

readonly class NovelEntity
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $title,
        public string $slug,
        public ?string $coverPath,
        public ?string $synopsis,
        public NovelStatus $status,
        public StoryStatus $storyStatus,
        public ?DateTimeImmutable $publishedAt,
        public ?DateTimeImmutable $createdAt,
    ) {}
}

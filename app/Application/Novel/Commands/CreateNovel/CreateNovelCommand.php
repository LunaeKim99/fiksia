<?php

namespace App\Application\Novel\Commands\CreateNovel;

use App\Domain\Novel\Enums\StoryStatus;

readonly class CreateNovelCommand
{
    /**
     * @param  array<int>  $genreIds
     * @param  array<int>  $tagIds
     */
    public function __construct(
        public int $userId,
        public string $title,
        public ?string $synopsis = null,
        public ?string $coverPath = null,
        public StoryStatus $storyStatus = StoryStatus::Ongoing,
        public array $genreIds = [],
        public array $tagIds = [],
    ) {}
}

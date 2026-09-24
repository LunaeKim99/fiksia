<?php

namespace App\Application\Moderation\Commands\SubmitInitialReview;

readonly class SubmitInitialReviewCommand
{
    /**
     * @param  array<int>  $chapterIds
     */
    public function __construct(
        public int $novelId,
        public int $authorId,
        public array $chapterIds,
    ) {}
}

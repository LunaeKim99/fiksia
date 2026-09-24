<?php

namespace App\Domain\Moderation\Entities;

use App\Domain\Moderation\Enums\SubmissionStatus;
use DateTimeImmutable;

readonly class InitialSubmissionEntity
{
    public function __construct(
        public int $id,
        public int $novelId,
        public int $submittedBy,
        public SubmissionStatus $status,
        public ?int $reviewerId,
        public ?string $reviewNotes,
        public ?DateTimeImmutable $submittedAt,
        public ?DateTimeImmutable $decidedAt,
    ) {}
}

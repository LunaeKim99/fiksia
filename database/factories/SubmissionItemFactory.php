<?php

namespace Database\Factories;

use App\Domain\Moderation\Enums\SubmissionItemType;
use App\Models\InitialSubmission;
use App\Models\SubmissionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionItem>
 */
class SubmissionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => InitialSubmission::factory(),
            'item_type' => SubmissionItemType::Metadata->value,
            'chapter_id' => null,
            'snapshot' => [],
            'sort_order' => 0,
        ];
    }
}

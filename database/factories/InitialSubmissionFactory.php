<?php

namespace Database\Factories;

use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Models\InitialSubmission;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InitialSubmission>
 */
class InitialSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'novel_id' => Novel::factory(),
            'submitted_by' => User::factory(),
            'status' => SubmissionStatus::Submitted->value,
            'reviewer_id' => null,
            'review_notes' => null,
            'submitted_at' => now(),
            'decided_at' => null,
        ];
    }
}

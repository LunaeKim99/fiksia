<?php

namespace Database\Factories;

use App\Models\ModerationAction;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationAction>
 */
class ModerationActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'moderator_id' => User::factory(),
            'target_type' => (new Novel)->getMorphClass(),
            'target_id' => Novel::factory(),
            'action' => fake()->randomElement(['hide', 'restrict', 'remove']),
            'reason' => fake()->sentence(),
            'metadata' => null,
        ];
    }
}

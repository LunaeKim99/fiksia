<?php

namespace Database\Factories;

use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Novel\Enums\StoryStatus;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Novel>
 */
class NovelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'cover_path' => null,
            'synopsis' => fake()->paragraph(),
            'status' => NovelStatus::Draft->value,
            'story_status' => StoryStatus::Ongoing->value,
            'initial_review_status' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NovelStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}

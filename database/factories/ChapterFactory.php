<?php

namespace Database\Factories;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'novel_id' => Novel::factory(),
            'chapter_number' => fake()->unique()->numberBetween(1, 100000),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'status' => ChapterStatus::Draft->value,
            'is_free' => true,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChapterStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}

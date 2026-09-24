<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Novel;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingProgress>
 */
class ReadingProgressFactory extends Factory
{
    public function definition(): array
    {
        $novel = Novel::factory()->published()->create();
        $chapter = Chapter::factory()->published()->create(['novel_id' => $novel->id]);

        return [
            'user_id' => User::factory(),
            'novel_id' => $novel->id,
            'chapter_id' => $chapter->id,
            'position' => 0,
            'last_read_at' => now(),
        ];
    }
}

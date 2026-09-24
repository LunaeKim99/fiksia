<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'chapter_id' => Chapter::factory()->published(),
            'position' => 0,
            'note' => null,
        ];
    }
}

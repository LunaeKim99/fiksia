<?php

namespace Database\Factories;

use App\Models\Novel;
use App\Models\SavedNovel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedNovel>
 */
class SavedNovelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'novel_id' => Novel::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'novel_id' => Novel::factory(),
        ];
    }
}

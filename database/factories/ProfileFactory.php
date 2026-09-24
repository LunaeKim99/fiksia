<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->userName(),
            'display_name' => fake()->name(),
            'bio' => fake()->optional()->paragraph(),
            'avatar_path' => null,
            'theme_preference' => 'system',
            'reading_preferences' => null,
        ];
    }
}

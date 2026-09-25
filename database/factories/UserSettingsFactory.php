<?php

namespace Database\Factories;

use App\Application\Settings\SettingsNormalizer;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSettings>
 */
class UserSettingsFactory extends Factory
{
    public function definition(): array
    {
        $defaults = (new SettingsNormalizer)->defaults();

        return [
            'user_id' => User::factory(),
            'appearance' => $defaults['appearance'],
            'reading' => $defaults['reading'],
            'privacy' => $defaults['privacy'],
            'notifications' => $defaults['notifications'],
        ];
    }
}

<?php

namespace App\Application\Settings;

use App\Domain\Settings\Enums\SettingsSection;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Support\Facades\DB;

final class SettingsService
{
    public function __construct(private readonly SettingsNormalizer $normalizer) {}

    /** @return array<string, array<string, mixed>> */
    public function forUser(User $user): array
    {
        $settings = $user->settings()->first();

        if ($settings === null) {
            return $this->normalizer->defaults();
        }

        return [
            'appearance' => $this->normalizer->appearance($settings->appearance ?? [], []),
            'reading' => $this->normalizer->reading($settings->reading ?? [], []),
            'privacy' => $this->normalizer->privacy($settings->privacy ?? [], []),
            'notifications' => $this->normalizer->notifications($settings->notifications ?? [], []),
        ];
    }

    /** @param array<string, mixed> $validatedPatch @return array<string, array<string, mixed>> */
    public function update(User $user, SettingsSection $section, array $validatedPatch): array
    {
        DB::transaction(function () use ($user, $section, $validatedPatch): void {
            /** @var UserSettings $settings */
            $settings = $user->settings()->firstOrCreate([], ['user_id' => $user->id]);
            $current = $settings->{$section->value} ?? [];

            $settings->{$section->value} = match ($section) {
                SettingsSection::Appearance => $this->normalizer->appearance($current, $validatedPatch),
                SettingsSection::Reading => $this->normalizer->reading($current, $validatedPatch),
                SettingsSection::Privacy => $this->normalizer->privacy($current, $validatedPatch),
                SettingsSection::Notifications => $this->normalizer->notifications($current, $validatedPatch),
            };

            $settings->save();
        });

        return $this->forUser($user);
    }
}

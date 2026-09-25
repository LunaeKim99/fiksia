<?php

namespace App\Models;

use Database\Factories\UserSettingsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property array<string, mixed>|null $appearance
 * @property array<string, mixed>|null $reading
 * @property array<string, mixed>|null $privacy
 * @property array<string, mixed>|null $notifications
 */
#[Fillable(['user_id', 'appearance', 'reading', 'privacy', 'notifications'])]
final class UserSettings extends Model
{
    /** @use HasFactory<UserSettingsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'appearance' => 'array',
            'reading' => 'array',
            'privacy' => 'array',
            'notifications' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

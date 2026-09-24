<?php

namespace App\Models;

use Database\Factories\ReadingProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $novel_id
 * @property int $chapter_id
 * @property int|null $position
 * @property Carbon|null $last_read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'novel_id', 'chapter_id', 'position', 'last_read_at'])]
class ReadingProgress extends Model
{
    /** @use HasFactory<ReadingProgressFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'last_read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Novel, $this> */
    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    /** @return BelongsTo<Chapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

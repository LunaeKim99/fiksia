<?php

namespace App\Models;

use App\Domain\Chapter\Enums\ChapterStatus;
use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $novel_id
 * @property int $chapter_number
 * @property string $title
 * @property string|null $content
 * @property ChapterStatus $status
 * @property bool $is_free
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['novel_id', 'chapter_number', 'title', 'content', 'status', 'is_free', 'published_at'])]
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ChapterStatus::class,
            'is_free' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Novel, $this> */
    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    /** @return HasMany<ContentAudit, $this> */
    public function contentAudits(): HasMany
    {
        return $this->hasMany(ContentAudit::class);
    }

    /** @return HasMany<Bookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
}

<?php

namespace App\Models;

use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Novel\Enums\StoryStatus;
use Database\Factories\NovelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $cover_path
 * @property string|null $synopsis
 * @property NovelStatus $status
 * @property StoryStatus $story_status
 * @property string|null $initial_review_status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['user_id', 'title', 'slug', 'cover_path', 'synopsis', 'status', 'story_status', 'initial_review_status', 'published_at'])]
class Novel extends Model
{
    /** @use HasFactory<NovelFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => NovelStatus::class,
            'story_status' => StoryStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug) && ! empty($model->title)) {
                $model->slug = static::uniqueSlug($model->title);
            }
        });
    }

    protected static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        // ponytail: slug loop races under concurrent writes; add DB unique+retry or advisory lock later

        return $slug;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Chapter, $this> */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /** @return BelongsToMany<Genre, $this> */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'novel_genres');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'novel_tags');
    }

    /** @return HasMany<InitialSubmission, $this> */
    public function initialSubmissions(): HasMany
    {
        return $this->hasMany(InitialSubmission::class);
    }

    /** @return HasMany<ContentAudit, $this> */
    public function contentAudits(): HasMany
    {
        return $this->hasMany(ContentAudit::class);
    }

    /** @return HasMany<Follow, $this> */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /** @return HasMany<SavedNovel, $this> */
    public function savedNovels(): HasMany
    {
        return $this->hasMany(SavedNovel::class);
    }

    /** @return HasMany<ReadingProgress, $this> */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }
}

<?php

namespace App\Models;

use App\Domain\Moderation\Enums\SubmissionItemType;
use Database\Factories\SubmissionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $submission_id
 * @property SubmissionItemType $item_type
 * @property int|null $chapter_id
 * @property array<string, mixed>|null $snapshot
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['submission_id', 'item_type', 'chapter_id', 'snapshot', 'sort_order'])]
class SubmissionItem extends Model
{
    /** @use HasFactory<SubmissionItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'item_type' => SubmissionItemType::class,
            'snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<InitialSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(InitialSubmission::class, 'submission_id');
    }

    /** @return BelongsTo<Chapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

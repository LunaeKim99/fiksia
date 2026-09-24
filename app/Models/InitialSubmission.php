<?php

namespace App\Models;

use App\Domain\Moderation\Enums\SubmissionStatus;
use Database\Factories\InitialSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $novel_id
 * @property int $submitted_by
 * @property SubmissionStatus $status
 * @property int|null $reviewer_id
 * @property string|null $review_notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['novel_id', 'submitted_by', 'status', 'reviewer_id', 'review_notes', 'submitted_at', 'decided_at'])]
class InitialSubmission extends Model
{
    /** @use HasFactory<InitialSubmissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Novel, $this> */
    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return HasMany<SubmissionItem, $this> */
    public function submissionItems(): HasMany
    {
        return $this->hasMany(SubmissionItem::class, 'submission_id');
    }
}

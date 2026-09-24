<?php

namespace App\Models;

use App\Domain\Moderation\Enums\AuditStatus;
use Database\Factories\ContentAuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $novel_id
 * @property int|null $chapter_id
 * @property int|null $assigned_to
 * @property AuditStatus $status
 * @property string|null $notes
 * @property Carbon|null $audited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['novel_id', 'chapter_id', 'assigned_to', 'status', 'notes', 'audited_at'])]
class ContentAudit extends Model
{
    /** @use HasFactory<ContentAuditFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'audited_at' => 'datetime',
        ];
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

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

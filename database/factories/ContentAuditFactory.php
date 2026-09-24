<?php

namespace Database\Factories;

use App\Domain\Moderation\Enums\AuditStatus;
use App\Models\ContentAudit;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentAudit>
 */
class ContentAuditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'novel_id' => Novel::factory(),
            'chapter_id' => null,
            'assigned_to' => null,
            'status' => AuditStatus::Planned->value,
            'notes' => null,
            'audited_at' => null,
        ];
    }
}

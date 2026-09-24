<?php

namespace App\Policies;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Models\Chapter;
use App\Models\Novel;
use App\Models\User;

class ChapterPolicy
{
    public function create(User $user, Novel $novel): bool
    {
        return $novel->user_id === $user->id;
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $chapter->novel->user_id === $user->id
            && $chapter->status === ChapterStatus::Draft;
    }

    public function publish(User $user, Chapter $chapter): bool
    {
        return $chapter->novel->user_id === $user->id
            && $chapter->novel->status === NovelStatus::Published
            && $chapter->status === ChapterStatus::Draft;
    }

    public function view(?User $user, Chapter $chapter): bool
    {
        return $chapter->status === ChapterStatus::Published
            && $chapter->novel->status === NovelStatus::Published;
    }
}

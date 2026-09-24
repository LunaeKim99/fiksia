<?php

namespace App\Policies;

use App\Domain\Novel\Enums\NovelStatus;
use App\Models\Novel;
use App\Models\User;

class NovelPolicy
{
    public function view(?User $user, Novel $novel): bool
    {
        return $novel->status === NovelStatus::Published;
    }

    public function viewForAuthor(User $user, Novel $novel): bool
    {
        return $novel->user_id === $user->id || $user->isModerator();
    }

    public function viewCover(?User $user, Novel $novel): bool
    {
        return $novel->status === NovelStatus::Published
            || ($user !== null && ($novel->user_id === $user->id || $user->isModerator()));
    }

    public function update(User $user, Novel $novel): bool
    {
        return $novel->user_id === $user->id;
    }

    public function delete(User $user, Novel $novel): bool
    {
        return $novel->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function submitForReview(User $user, Novel $novel): bool
    {
        return $novel->user_id === $user->id
            && in_array($novel->status, [NovelStatus::Draft, NovelStatus::RevisionRequired], true);
    }
}

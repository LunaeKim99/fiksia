<?php

namespace App\Policies;

use App\Models\InitialSubmission;
use App\Models\User;

class InitialSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, InitialSubmission $submission): bool
    {
        return $user->isModerator() || $submission->novel->user_id === $user->id;
    }

    public function review(User $user, InitialSubmission $submission): bool
    {
        return $user->isModerator() && $submission->submitted_by !== $user->id;
    }
}

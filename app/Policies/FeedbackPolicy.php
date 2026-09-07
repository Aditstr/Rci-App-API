<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->is_active;
    }

    public function view(User $user, Feedback $feedback): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

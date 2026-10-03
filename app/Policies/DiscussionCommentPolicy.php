<?php

namespace App\Policies;

use App\Models\DiscussionComment;
use App\Models\User;

class DiscussionCommentPolicy
{
    /**
     * Determine whether the user can create discussion comments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the discussion comment.
     */
    public function update(User $user, DiscussionComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the discussion comment.
     */
    public function delete(User $user, DiscussionComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }
}

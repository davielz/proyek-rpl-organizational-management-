<?php

namespace App\Policies;

use App\Models\DiscussionPost;
use App\Models\User;

class DiscussionPostPolicy
{
    /**
     * Determine whether the user can view any discussion posts.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the discussion post.
     */
    public function view(User $user, DiscussionPost $post): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create discussion posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the discussion post.
     */
    public function update(User $user, DiscussionPost $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the discussion post.
     */
    public function delete(User $user, DiscussionPost $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }
}

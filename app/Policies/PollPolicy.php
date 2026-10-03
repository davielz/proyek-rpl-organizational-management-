<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    /**
     * Determine whether the user can view any polls.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the poll.
     */
    public function view(User $user, Poll $poll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create polls.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurus();
    }

    /**
     * Determine whether the user can update the poll.
     */
    public function update(User $user, Poll $poll): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPengurus() && $poll->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the poll.
     */
    public function delete(User $user, Poll $poll): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPengurus() && $poll->created_by === $user->id;
    }

    /**
     * Determine whether the user can close the poll.
     */
    public function close(User $user, Poll $poll): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPengurus() && $poll->created_by === $user->id;
    }

    /**
     * Determine whether the user can cast a vote on the poll.
     */
    public function vote(User $user, Poll $poll): bool
    {
        if ($poll->isClosed()) {
            return false;
        }

        return ! $poll->hasUserVoted($user);
    }
}

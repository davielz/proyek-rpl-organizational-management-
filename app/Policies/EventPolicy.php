<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->status === 'active' || $event->status === 'completed') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($event->organization_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurus();
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($event->organization_id);
        }

        return false;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageStatus(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function register(User $user, Event $event): bool
    {
        if ($event->status !== 'active') {
            return false;
        }

        if ($event->end_time < now()) {
            return false;
        }

        // Already registered?
        if ($event->registrations()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Quota full?
        if ($event->quota > 0 && $event->registrations()->where('status', '!=', 'cancelled')->count() >= $event->quota) {
            return false;
        }

        return true;
    }

    public function cancelRegistration(User $user, Event $event): bool
    {
        if ($event->start_time <= now()) {
            return false;
        }

        return $event->registrations()
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->exists();
    }
}

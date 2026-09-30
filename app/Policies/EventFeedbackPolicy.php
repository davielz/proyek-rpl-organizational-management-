<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\User;

class EventFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurus();
    }

    public function view(User $user, EventFeedback $feedback): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($feedback->event->organization_id);
        }

        return $user->id === $feedback->user_id;
    }

    public function create(User $user, Event $event): bool
    {
        // 1. Check if user already submitted feedback for this event (Duplicate prevention)
        $alreadySubmitted = EventFeedback::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadySubmitted) {
            return false;
        }

        // 2. Check if user is registered for this event or has permission
        $isRegistered = $event->registrations()->where('user_id', $user->id)->exists();

        return $isRegistered || $user->isAnggota() || $user->isPengurus() || $user->isAdmin();
    }
}

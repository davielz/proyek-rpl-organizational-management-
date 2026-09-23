<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurus();
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($document->event->organization_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurus();
    }

    public function update(User $user, Document $document): bool
    {
        if (! in_array($document->status, ['draft', 'revision_requested'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($document->event->organization_id);
        }

        return false;
    }

    public function submit(User $user, Document $document): bool
    {
        if (! in_array($document->status, ['draft', 'revision_requested'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($document->event->organization_id);
        }

        return false;
    }

    public function review(User $user, Document $document): bool
    {
        return $user->isAdmin() && $document->status === 'submitted';
    }

    public function delete(User $user, Document $document): bool
    {
        if (! in_array($document->status, ['draft', 'revision_requested'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPengurus()) {
            return $user->organizations->pluck('id')->contains($document->event->organization_id);
        }

        return false;
    }
}

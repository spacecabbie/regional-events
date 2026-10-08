<?php

namespace App\Events;

use App\Models\User;

/**
 * Any account that can open the panel is an admin. A visitor account later
 * needs a narrower check here. The public pages do not use this policy.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Event $event): bool
    {
        return true;
    }

    public function delete(User $user, Event $event): bool
    {
        return true;
    }
}

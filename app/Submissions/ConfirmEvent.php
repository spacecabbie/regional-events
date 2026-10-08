<?php

namespace App\Submissions;

use App\Events\Event;
use App\Events\EventStatus;

class ConfirmEvent
{
    public function __invoke(Event $event): void
    {
        if ($event->status === EventStatus::Confirmed) {
            return;
        }

        $event->status = EventStatus::Confirmed;
        $event->confirmed_at = now();
        $event->save();
    }
}

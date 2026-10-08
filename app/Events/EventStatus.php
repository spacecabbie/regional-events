<?php

namespace App\Events;

enum EventStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
}

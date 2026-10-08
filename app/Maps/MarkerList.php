<?php

namespace App\Maps;

use App\Events\Event;
use Illuminate\Support\Collection;

class MarkerList
{
    /**
     * One marker list for every map. Popup HTML is escaped in the Blade view.
     *
     * @param  Collection<int, Event>  $events
     * @return array<int, array{lat: float, lng: float, popup: string}>
     */
    public function for(Collection $events): array
    {
        return $events->map(fn (Event $event): array => [
            'lat' => (float) $event->lat,
            'lng' => (float) $event->lng,
            'popup' => view('events.popup', ['event' => $event])->render(),
        ])->values()->all();
    }
}

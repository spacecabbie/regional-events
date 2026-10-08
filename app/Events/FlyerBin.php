<?php

namespace App\Events;

/**
 * Holds the flyer stored while a Filament form is saving, so the thumbnail
 * path can be written with the event.
 */
class FlyerBin
{
    private ?StoredFlyer $latest = null;

    public function put(StoredFlyer $flyer): void
    {
        $this->latest = $flyer;
    }

    public function pull(): ?StoredFlyer
    {
        $flyer = $this->latest;
        $this->latest = null;

        return $flyer;
    }
}

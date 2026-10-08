<?php

namespace App\Locations;

interface LocationLookup
{
    /**
     * Return a coordinate, or null when this lookup does not recognise the input.
     * Throw InvalidLocation when the input is this format but the coordinates are unusable.
     */
    public function find(string $input): ?Coordinate;
}

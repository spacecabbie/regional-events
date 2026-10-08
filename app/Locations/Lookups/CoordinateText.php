<?php

namespace App\Locations\Lookups;

use App\Locations\Coordinate;
use App\Locations\LocationLookup;
use InvalidArgumentException;
use League\Geotools\Coordinate\Coordinate as LeagueCoordinate;
use ReflectionClass;
use ReflectionMethod;

/**
 * Decimal, decimal-minutes, and DMS text via laravie/geotools.
 * Geotools clamps out-of-range numbers, so the raw degrees are read and rejected here.
 */
class CoordinateText implements LocationLookup
{
    public function find(string $input): ?Coordinate
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $input)) {
            return null;
        }

        $method = new ReflectionMethod(LeagueCoordinate::class, 'toDecimalDegrees');
        $instance = (new ReflectionClass(LeagueCoordinate::class))->newInstanceWithoutConstructor();

        try {
            $pair = $method->invoke($instance, $input);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! is_array($pair) || count($pair) < 2) {
            return null;
        }

        return Coordinate::fromNumbers($pair[0], $pair[1]);
    }
}

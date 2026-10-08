<?php

namespace App\Locations\Lookups;

use App\Locations\Coordinate;
use App\Locations\InvalidLocation;
use App\Locations\LocationLookup;

/**
 * RFC 5870 geo URI. Latitude then longitude. Only the WGS84 CRS is accepted.
 */
class GeoUri implements LocationLookup
{
    public function find(string $input): ?Coordinate
    {
        if (! str_starts_with(strtolower($input), 'geo:')) {
            return null;
        }

        if (preg_match('/(?:^|[;])crs=([^;]+)/i', $input, $crs) && strtolower($crs[1]) !== 'wgs84') {
            throw new InvalidLocation('Only WGS84 geo: links are accepted.');
        }

        if (! preg_match('#^geo:(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)(?:$|[,;])#i', $input, $match)) {
            throw new InvalidLocation('This geo: link does not contain coordinates.');
        }

        return Coordinate::fromNumbers($match[1], $match[2]);
    }
}

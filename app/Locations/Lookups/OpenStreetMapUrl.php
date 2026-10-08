<?php

namespace App\Locations\Lookups;

use App\Locations\Coordinate;
use App\Locations\LocationLookup;

class OpenStreetMapUrl implements LocationLookup
{
    public function find(string $input): ?Coordinate
    {
        if (! str_contains($input, 'openstreetmap.org')) {
            return null;
        }

        $parts = parse_url($input);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, ['openstreetmap.org', 'www.openstreetmap.org'], true)) {
            return null;
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        if (isset($query['mlat'], $query['mlon']) && is_numeric($query['mlat']) && is_numeric($query['mlon'])) {
            return Coordinate::fromNumbers((string) $query['mlat'], (string) $query['mlon']);
        }

        $fragment = $parts['fragment'] ?? '';

        if (preg_match('#map=\d+/(-?\d+(?:\.\d+)?)/(-?\d+(?:\.\d+)?)#', $fragment, $match)) {
            return Coordinate::fromNumbers($match[1], $match[2]);
        }

        return null;
    }
}

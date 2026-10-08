<?php

namespace App\Locations\Lookups;

use App\Locations\Coordinate;
use App\Locations\InvalidLocation;
use App\Locations\LocationLookup;

/**
 * Reads a pin from a Google Maps URL. A short maps.app.goo.gl link is handled
 * separately, because the coordinates are only on the page it redirects to.
 */
class GoogleMapsUrl implements LocationLookup
{
    public function find(string $input): ?Coordinate
    {
        $hasPin = str_contains($input, '!3d') && str_contains($input, '!4d');
        $looksLikeUrl = str_contains($input, '://') || str_starts_with($input, 'www.');

        if (! $hasPin && ! $looksLikeUrl) {
            return null;
        }

        if ($hasPin) {
            if (! preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $input, $match)) {
                throw new InvalidLocation('This Google Maps link has a pin, but the coordinates could not be read.');
            }

            return Coordinate::fromNumbers($match[1], $match[2]);
        }

        if (! $this->isGoogleMaps($input)) {
            return null;
        }

        if (preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', $input, $match)) {
            return Coordinate::fromNumbers($match[1], $match[2]);
        }

        $query = [];
        $parts = parse_url(str_contains($input, '://') ? $input : 'https://'.$input);
        parse_str($parts['query'] ?? '', $query);

        foreach (['q', 'query', 'll'] as $key) {
            if (! isset($query[$key]) || ! is_string($query[$key])) {
                continue;
            }

            if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $query[$key], $match)) {
                return Coordinate::fromNumbers($match[1], $match[2]);
            }
        }

        return null;
    }

    private function isGoogleMaps(string $input): bool
    {
        $host = strtolower((string) parse_url(str_contains($input, '://') ? $input : 'https://'.$input, PHP_URL_HOST));

        return $host === 'maps.google.com'
            || $host === 'www.google.com'
            || $host === 'google.com'
            || str_ends_with($host, '.google.com')
            || str_contains($input, 'google.com/maps')
            || str_contains($input, 'maps.google.');
    }
}

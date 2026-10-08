<?php

namespace App\Locations;

/**
 * WGS84 latitude and longitude stored at 7 decimal places.
 * The range includes -90, 90, -180, and 180. Out-of-range values are rejected.
 */
final class Coordinate
{
    private function __construct(
        public readonly string $latitude,
        public readonly string $longitude,
    ) {}

    public static function fromNumbers(string|int|float $latitude, string|int|float $longitude): self
    {
        $lat = self::plain($latitude);
        $lng = self::plain($longitude);

        if (
            bccomp($lat, '-90', 8) < 0
            || bccomp($lat, '90', 8) > 0
            || bccomp($lng, '-180', 8) < 0
            || bccomp($lng, '180', 8) > 0
        ) {
            throw new InvalidLocation('Coordinates must be between -90 and 90 latitude and -180 and 180 longitude.');
        }

        return new self(bcadd($lat, '0', 7), bcadd($lng, '0', 7));
    }

    private static function plain(string|int|float $number): string
    {
        if (is_float($number)) {
            $number = rtrim(rtrim(sprintf('%.8F', $number), '0'), '.');
        } else {
            $number = trim((string) $number);
        }

        if (! preg_match('/^-?\d+(\.\d+)?$/', $number)) {
            throw new InvalidLocation('Those coordinates are not numbers.');
        }

        return $number;
    }
}

<?php

namespace App\Locations;

use App\Locations\Lookups\ShortLink;

class LocationParser
{
    /** @var array<string, Coordinate> */
    private array $cache = [];

    /**
     * @param  array<int, LocationLookup>  $lookups
     */
    public function __construct(
        private ShortLink $shortLinks,
        private array $lookups,
    ) {}

    public function parse(string $input, bool $followShortLinks = true): Coordinate
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidLocation('Enter a map link or coordinates.');
        }

        if (isset($this->cache[$input])) {
            return $this->cache[$input];
        }

        if ($followShortLinks && $this->shortLinks->matches($input)) {
            $target = $this->shortLinks->resolve($input);

            return $this->cache[$input] = $this->parse($target, false);
        }

        foreach ($this->lookups as $lookup) {
            $found = $lookup->find($input);

            if ($found instanceof Coordinate) {
                return $this->cache[$input] = $found;
            }
        }

        throw new InvalidLocation('No coordinates found. Paste a map link or coordinates. Place names are not looked up.');
    }
}

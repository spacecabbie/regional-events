<?php

namespace App\Locations\Lookups;

use App\Locations\InvalidLocation;
use Illuminate\Support\Facades\Http;

/**
 * Follows one redirect from maps.app.goo.gl or goo.gl. Other hosts are never requested.
 */
class ShortLink
{
    public function matches(string $input): bool
    {
        $parts = parse_url(trim($input));

        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');

        return in_array($host, ['maps.app.goo.gl', 'goo.gl'], true);
    }

    public function resolve(string $url): string
    {
        $response = Http::withoutRedirecting()
            ->timeout(8)
            ->withHeaders(['User-Agent' => 'RegionalEvents/1.0'])
            ->get($url);

        if (! in_array($response->status(), [301, 302, 303, 307, 308], true)) {
            throw new InvalidLocation('The short link did not redirect to a map URL.');
        }

        $location = $response->header('Location');

        if (! is_string($location) || $location === '' || strlen($location) > 4000 || ! preg_match('#^https?://#i', $location)) {
            throw new InvalidLocation('The short link did not redirect to a map URL.');
        }

        return $location;
    }
}

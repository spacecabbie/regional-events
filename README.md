# Regional events

Public list and map of upcoming events around Castelo Branco, plus one Filament admin.

The public page shows confirmed events from the start of today in Europe/Lisbon through the configured number of days (30 by default). Times are stored in UTC. `config/app.php` keeps the application timezone at UTC so MySQL `DATETIME` values stay UTC.

Visitors submit an event with an email address. The event stays hidden until they open the signed link in that email and press confirm. The same address can later request a short-lived link to edit or delete. There are no visitor accounts.

## Maps

Both maps use one marker list built in PHP.

- Open map: MapLibre GL JS, OpenFreeMap Liberty streets, and Esri World Imagery satellite. The satellite tile URL keeps `{z}/{y}/{x}`.
- Google Maps: the Maps JavaScript API, loaded only after Google is chosen. The API key and Map ID are saved from the admin Map page and fall back to `GOOGLE_MAPS_API_KEY` and `GOOGLE_MAPS_MAP_ID`. `DEMO_MAP_ID` works until a Cloud Map ID exists. Hide Google by leaving the key empty.

`cheesegrits/filament-google-maps` is not installed. Current releases require Guzzle 7, and this app is on Laravel 13's Guzzle 8. The map is the official JavaScript API instead. Do not put Google tiles inside MapLibre.

The browser key must be restricted to `https://regional-events.hhaufe.eu/*`. A billing account is required. Geocoding stays off.

The public "Open in Google Maps" link is `https://www.google.com/maps/search/?api=1&query=LAT,LNG`. `api=1` is not an API key. Short `maps.app.goo.gl` links are accepted only as input.

## Location input

`App\Locations` tries lookups in order: one redirect from `maps.app.goo.gl` or `goo.gl`, a Google Maps URL (`!3d`/`!4d`, then `@lat,lng`, then `q`), an OpenStreetMap URL, an RFC 5870 `geo:` URI, then decimal or DMS text via `laravie/geotools`. A new format is a new lookup class. Place names are rejected. Out-of-range coordinates are rejected rather than clamped.

## Flyers

JPEG, PNG, GIF, WebP, BMP, TIFF, or PDF. The stored files are lossy WebP, at most 2 MB, without the original camera data. The click view is an ISO 216 A4 page at the CSS reference pixel, 794×1123 (or swapped). A second WebP is kept only when the picture is larger, at most twice that size, and `srcset` offers it to a high-density screen. A smaller upload is not enlarged. A PDF contributes its first page, rendered by Ghostscript. The list thumbnail fits in 160×224 pixels. The thumbnail is a button. It fills one page-level HTML dialog and does not leave the page. The same button is used in the map pin. Escape, the Close button, and a click on the dimmed backdrop dismiss it.

## Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan storage:link
```

Create the admin with `php artisan make:filament-user` or tinker. Any user can open `/admin`. Registration is off.

The web PHP must be 8.4.1 or newer. Composer’s platform check otherwise returns HTTP 500 before Laravel boots.

On this server the document root is a symlink, `public_html` → `laravel-app/public`, and the domain’s DirectAdmin PHP selector is `php1_select=4` (CustomBuild slot 4 is PHP 8.4). The database account is `spacecabbie_events@localhost`, which is the MariaDB socket. `127.0.0.1` is a different host and is refused. `public/build` is not in git; run `npm run build` after install.

Tests use sqlite in memory: `php artisan test`.

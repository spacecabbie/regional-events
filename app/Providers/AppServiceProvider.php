<?php

namespace App\Providers;

use App\Events\Event;
use App\Events\EventPolicy;
use App\Events\FlyerBin;
use App\Locations\LocationParser;
use App\Locations\Lookups\CoordinateText;
use App\Locations\Lookups\GeoUri;
use App\Locations\Lookups\GoogleMapsUrl;
use App\Locations\Lookups\OpenStreetMapUrl;
use App\Locations\Lookups\ShortLink;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocationParser::class, function (): LocationParser {
            return new LocationParser(new ShortLink, [
                new GoogleMapsUrl,
                new OpenStreetMapUrl,
                new GeoUri,
                new CoordinateText,
            ]);
        });

        $this->app->singleton(FlyerBin::class);
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);

        RateLimiter::for('submissions', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(30)->by($request->ip()),
            ];
        });
    }
}

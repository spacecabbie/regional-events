<?php

namespace App\Http\Controllers;

use App\Events\Event;
use App\Maps\MapConfig;
use App\Maps\MarkerList;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, MarkerList $markers, MapConfig $maps): View
    {
        $view = $request->query('view', 'both');
        $provider = $request->query('provider', 'open');

        if (! in_array($view, ['list', 'map', 'both'], true)) {
            $view = 'both';
        }

        if (! in_array($provider, ['open', 'google'], true) || ($provider === 'google' && ! $maps->googleEnabled())) {
            $provider = 'open';
        }

        $events = Event::query()->upcoming()->get();

        return view('events.index', [
            'events' => $events,
            'view' => $view,
            'provider' => $provider,
            'googleEnabled' => $maps->googleEnabled(),
            'markers' => $view === 'list' ? [] : $markers->for($events),
            'days' => (int) config('events.window_days'),
            'center' => config('events.center'),
            'zoom' => (int) config('events.zoom'),
            'singleZoom' => (int) config('events.single_pin_zoom'),
            'streetStyle' => config('events.open.style'),
            'satellite' => config('events.open.satellite'),
            'satelliteRoads' => config('events.open.satellite_roads'),
            'satellitePlaces' => config('events.open.satellite_places'),
            'satelliteAttribution' => config('events.open.satellite_attribution'),
            'googleKey' => $provider === 'google' ? $maps->googleKey() : null,
            'googleMapId' => $maps->googleMapId(),
        ]);
    }
}

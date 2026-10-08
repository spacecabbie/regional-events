<x-public-layout :title="'Events in the next '.$days.' days'">
    <h1 class="page-title">Events in the next {{ $days }} days</h1>
    <p class="lede">Confirmed events around Castelo Branco. Choose a list, a map, or both.</p>

    <x-form-errors />

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <nav aria-label="View" class="segmented">
            @foreach (['both' => 'Both', 'list' => 'List', 'map' => 'Map'] as $value => $label)
                <a
                    href="{{ route('events.index', ['view' => $value, 'provider' => $provider]) }}"
                    @if ($view === $value) aria-current="page" @endif
                >{{ $label }}</a>
            @endforeach
        </nav>

        @if ($googleEnabled)
            <nav aria-label="Map provider" class="segmented">
                @foreach (['open' => 'Open map', 'google' => 'Google Maps'] as $value => $label)
                    <a
                        href="{{ route('events.index', ['view' => $view, 'provider' => $value]) }}"
                        @if ($provider === $value) aria-current="page" @endif
                    >{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </div>

    <div class="mt-6 grid gap-6 {{ $view === 'both' ? 'lg:grid-cols-2' : '' }}">
        @if ($view !== 'map')
            <section aria-label="Event list" class="{{ $view === 'both' ? 'order-2 lg:order-1' : '' }}">
                @if ($events->isEmpty())
                    <p class="card p-4 text-slate-700">No events in the next {{ $days }} days.</p>
                @else
                    <ul class="grid gap-3">
                        @foreach ($events as $event)
                            <li class="card p-3 sm:p-4">
                                <article class="flex items-center gap-3 flex-wrap">
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-lg font-semibold text-slate-900">{{ $event->name }}</h2>
                                        <div class="mt-1 text-sm text-slate-600">
                                            <x-event-when :event="$event" />
                                        </div>
                                    </div>
                                    <div class="ms-auto flex items-center gap-2">
                                        <x-maps-link :event="$event" />
                                        <x-flyer-thumb :event="$event" />
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        @if ($view !== 'list')
            <section aria-label="Event map" class="{{ $view === 'both' ? 'order-1 lg:order-2' : '' }}">
                @if ($events->isEmpty())
                    <p class="card mb-3 p-4 text-slate-700">No events in the next {{ $days }} days.</p>
                @endif
                <div class="card overflow-hidden">
                    <div
                        id="events-map"
                        class="h-80 w-full bg-slate-200 sm:h-96 lg:h-[32rem]"
                        data-provider="{{ $provider }}"
                        data-markers='@json($markers)'
                        data-center='@json($center)'
                        data-zoom="{{ $zoom }}"
                        data-single-zoom="{{ $singleZoom }}"
                        data-style="{{ $streetStyle }}"
                        data-satellite="{{ $satellite }}"
                        data-satellite-roads="{{ $satelliteRoads }}"
                        data-satellite-places="{{ $satellitePlaces }}"
                        data-satellite-attribution="{{ $satelliteAttribution }}"
                        @if ($googleKey) data-google-key="{{ $googleKey }}" @endif
                        data-google-map-id="{{ $googleMapId }}"
                    ></div>
                    @if ($provider === 'open')
                        <div class="grid gap-3 border-t border-slate-200 p-3">
                            <div class="segmented max-w-xs">
                                <button type="button" id="map-street" aria-pressed="true">Streets</button>
                                <button type="button" id="map-satellite" aria-pressed="false">Satellite</button>
                            </div>
                            <p id="map-attribution" class="text-sm leading-5 text-slate-600">
                                <span data-layer="street">
                                    <a href="https://openfreemap.org/" class="underline">OpenFreeMap</a>
                                    · © <a href="https://www.openmaptiles.org/" class="underline">OpenMapTiles</a>
                                    · Data from <a href="https://www.openstreetmap.org/copyright" class="underline">OpenStreetMap</a>
                                </span>
                                <span data-layer="satellite" hidden>
                                    {{ $satelliteAttribution }}
                                    Roads and places © Esri, HERE, Garmin, © <a href="https://www.openstreetmap.org/copyright" class="underline">OpenStreetMap</a> contributors, and the GIS user community.
                                </span>
                            </p>
                        </div>
                    @endif
                </div>
                <noscript>
                    <p class="mt-3 text-sm text-slate-700">The map needs JavaScript. The event list is on the List view.</p>
                </noscript>
            </section>
        @endif
    </div>
</x-public-layout>

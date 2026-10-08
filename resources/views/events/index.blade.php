<x-public-layout :title="'Events in the next '.$days.' days'">
    <h1 class="text-2xl font-semibold">Events in the next {{ $days }} days</h1>
    <p class="mt-2 text-stone-700">Castelo Branco and the surrounding area. Times are Portugal time.</p>

    <x-form-errors />

    <nav aria-label="View" class="mt-6 flex flex-wrap gap-2">
        @foreach (['both' => 'Both', 'list' => 'List', 'map' => 'Map'] as $value => $label)
            <a
                href="{{ route('events.index', ['view' => $value, 'provider' => $provider]) }}"
                @if ($view === $value) aria-current="page" @endif
                class="inline-flex min-h-12 items-center border border-stone-400 px-4 underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900 {{ $view === $value ? 'bg-stone-900 text-white' : 'bg-white hover:underline' }}"
            >{{ $label }}</a>
        @endforeach
    </nav>

    @if ($googleEnabled)
        <nav aria-label="Map provider" class="mt-3 flex flex-wrap gap-2">
            @foreach (['open' => 'Open map', 'google' => 'Google Maps'] as $value => $label)
                <a
                    href="{{ route('events.index', ['view' => $view, 'provider' => $value]) }}"
                    @if ($provider === $value) aria-current="page" @endif
                    class="inline-flex min-h-12 items-center border border-stone-400 px-4 underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900 {{ $provider === $value ? 'bg-stone-900 text-white' : 'bg-white hover:underline' }}"
                >{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <div class="mt-6 grid gap-6 {{ $view === 'both' ? 'lg:grid-cols-2' : '' }}">
        @if ($view !== 'map')
            <section aria-label="Event list" class="{{ $view === 'both' ? 'order-2 lg:order-1' : '' }}">
                @if ($events->isEmpty())
                    <p>No events in the next {{ $days }} days.</p>
                @else
                    <ul class="divide-y divide-stone-200 border-y border-stone-200 bg-white">
                        @foreach ($events as $event)
                            <li class="p-4">
                                <article>
                                    <h2 class="text-lg font-semibold">{{ $event->name }}</h2>
                                    <p class="mt-1">{{ $event->localStart() }}</p>
                                    @if ($event->thumbUrl())
                                        <img
                                            src="{{ $event->thumbUrl() }}"
                                            alt="Flyer for {{ $event->name }}"
                                            class="mt-3 max-h-40 w-auto object-contain"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @endif
                                    <p class="mt-3">
                                        <a href="{{ $event->mapsUrl() }}" class="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Open in Google Maps</a>
                                    </p>
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
                    <p class="mb-3">No events in the next {{ $days }} days.</p>
                @endif
                <div
                    id="events-map"
                    class="h-80 w-full overflow-hidden rounded-lg border border-stone-400 bg-stone-200 sm:h-96 lg:h-[32rem]"
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
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" id="map-street" aria-pressed="true" class="inline-flex min-h-12 items-center border border-stone-400 bg-stone-900 px-4 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Streets</button>
                        <button type="button" id="map-satellite" aria-pressed="false" class="inline-flex min-h-12 items-center border border-stone-400 bg-white px-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Satellite</button>
                    </div>
                    <p id="map-attribution" class="mt-2 text-sm leading-5 text-stone-700">
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
                @endif
                <noscript>
                    <p class="mt-3">The map needs JavaScript. The event list is on the List view.</p>
                </noscript>
            </section>
        @endif
    </div>
</x-public-layout>

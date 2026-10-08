<div class="space-y-2 text-sm">
    <p class="font-semibold">{{ $event->name }}</p>
    <p>{{ $event->localStart() }}</p>
    @if ($event->flyerUrl())
        <img src="{{ $event->flyerUrl() }}" alt="Flyer for {{ $event->name }}" class="max-h-48 w-full object-contain">
    @endif
    <p><a href="{{ $event->mapsUrl() }}">Open in Google Maps</a></p>
</div>

<div class="space-y-2 text-sm">
    <p class="font-semibold">{{ $event->name }}</p>
    <p>{{ $event->localStart() }}</p>
    <x-maps-link :event="$event" />
    @if ($event->flyerUrl())
        <img src="{{ $event->flyerUrl() }}" alt="Flyer for {{ $event->name }}" class="max-h-48 w-full object-contain">
    @endif
</div>

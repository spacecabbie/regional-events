<div class="space-y-2 text-sm">
    <p class="font-semibold">{{ $event->name }}</p>
    <x-event-when :event="$event" />
    <x-maps-link :event="$event" />
    @if ($event->flyerUrl())
        <a href="{{ $event->flyerUrl() }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ $event->thumbUrl() ?? $event->flyerUrl() }}" alt="Flyer for {{ $event->name }}" class="max-h-48 w-full object-contain">
        </a>
    @endif
</div>

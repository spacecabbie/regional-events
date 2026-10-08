<div class="grid gap-2 text-sm text-slate-700">
    <p class="text-base font-semibold text-slate-900">{{ $event->name }}</p>
    <x-event-when :event="$event" />
    <div class="flex items-end justify-between gap-2">
        <x-maps-link :event="$event" />
        <x-flyer-thumb :event="$event" image-class="max-h-36 w-auto rounded-md bg-slate-100 object-contain" />
    </div>
</div>

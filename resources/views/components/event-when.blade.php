@props(['event', 'compact' => false])

@if ($compact)
    <p class="truncate text-sm leading-5 text-slate-600">
        {{ $event->localDateLabel() }}
        ·
        @if ($event->all_day)
            All day
        @else
            {{ $event->localTimeLabel() }}
        @endif
    </p>
@else
<p>{{ $event->localDateLabel() }}</p>
@if ($event->all_day)
    <p class="mt-1"><span class="badge">All day</span></p>
@else
    <p class="mt-1">{{ $event->localTimeLabel() }}</p>
@endif
@endif

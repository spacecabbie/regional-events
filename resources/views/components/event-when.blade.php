@props(['event'])

<p>{{ $event->localDateLabel() }}</p>
@if ($event->all_day)
    <p class="mt-1"><span class="badge">All day</span></p>
@else
    <p class="mt-1">{{ $event->localTimeLabel() }}</p>
@endif

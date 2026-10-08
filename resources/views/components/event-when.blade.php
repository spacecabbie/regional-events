@props(['event'])

<p>{{ $event->localDateLabel() }}</p>
@if ($event->all_day)
    <p class="mt-1"><span class="inline-flex items-center border border-stone-400 px-2 text-sm leading-8">All day</span></p>
@else
    <p class="mt-1">{{ $event->localTimeLabel() }}</p>
@endif

@props(['event' => null])

@php
    $record = $event instanceof \App\Events\Event ? $event : null;
    $schedule = old('schedule', ($record?->all_day ?? false) ? 'all_day' : 'timed');
    $startsOn = old('starts_on', $record?->localStartDate());
    $endsOn = old('ends_on', $record?->localEndDate());
    $startsTime = old('starts_time', ($record && ! $record->all_day) ? $record->localStartClock() : null);
    $endsTime = old('ends_time', ($record && ! $record->all_day && $record->ends_at) ? $record->localEndClock() : null);
    $field = 'mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900';
@endphp

<fieldset>
    <legend class="font-medium">Time</legend>
    <div class="mt-2 flex flex-wrap gap-4">
        <label class="inline-flex min-h-12 items-center gap-2">
            <input type="radio" name="schedule" value="timed" @checked($schedule !== 'all_day') class="size-5">
            Start and end
        </label>
        <label class="inline-flex min-h-12 items-center gap-2">
            <input type="radio" name="schedule" value="all_day" @checked($schedule === 'all_day') class="size-5">
            All day
        </label>
    </div>
</fieldset>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="starts_on" class="block font-medium">Date</label>
        <input id="starts_on" name="starts_on" type="date" required value="{{ $startsOn }}" class="{{ $field }}" aria-describedby="date-help">
        <p id="date-help" class="mt-1 text-sm text-stone-700">Portugal time (Europe/Lisbon). Today or later.</p>
    </div>
    <div>
        <label for="ends_on" class="block font-medium">End date</label>
        <input id="ends_on" name="ends_on" type="date" required value="{{ $endsOn }}" class="{{ $field }}" aria-describedby="end-date-help">
        <p id="end-date-help" class="mt-1 text-sm text-stone-700">Use the same date when the event is on one day.</p>
    </div>
</div>

<div data-timed-fields @if ($schedule === 'all_day') hidden @endif class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="starts_time" class="block font-medium">Starts</label>
        <input id="starts_time" name="starts_time" type="text" inputmode="numeric" maxlength="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" placeholder="14:30" autocomplete="off" spellcheck="false" value="{{ $startsTime }}" @disabled($schedule === 'all_day') class="{{ $field }}" aria-describedby="time-help">
        <p id="time-help" class="mt-1 text-sm text-stone-700">24-hour time, for example 14:30. Not used for an all-day event.</p>
    </div>
    <div>
        <label for="ends_time" class="block font-medium">Ends</label>
        <input id="ends_time" name="ends_time" type="text" inputmode="numeric" maxlength="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" placeholder="18:00" autocomplete="off" spellcheck="false" value="{{ $endsTime }}" @disabled($schedule === 'all_day') class="{{ $field }}">
    </div>
</div>

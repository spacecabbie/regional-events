@props(['event' => null])

@php
    $record = $event instanceof \App\Events\Event ? $event : null;
    $schedule = old('schedule', ($record?->all_day ?? false) ? 'all_day' : 'timed');
    $startsOn = old('starts_on', $record?->localStartDate());
    $startsTime = old('starts_time', ($record && ! $record->all_day) ? $record->localStartClock() : null);
    $endsTime = old('ends_time', ($record && ! $record->all_day && $record->ends_at) ? $record->localEndClock() : null);
    $field = 'field';
    $earliest = now()->timezone(config('events.timezone'))->toDateString();
@endphp

<fieldset class="space-y-2">
    <legend class="font-medium">When</legend>
    <div>
        <label for="starts_on" class="block font-medium">Date</label>
        <input id="starts_on" name="starts_on" type="date" required min="{{ $earliest }}" value="{{ $startsOn }}" class="{{ $field }}" aria-describedby="date-help">
        <p id="date-help" class="help">One day, Portugal time. Today or later.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <label class="inline-flex min-h-9 items-center gap-2">
            <input type="radio" name="schedule" value="timed" @checked($schedule !== 'all_day') class="size-4 accent-blue-800">
            Start and end
        </label>
        <label class="inline-flex min-h-9 items-center gap-2">
            <input type="radio" name="schedule" value="all_day" @checked($schedule === 'all_day') class="size-4 accent-blue-800">
            All day
        </label>
    </div>
    <div data-timed-fields @if ($schedule === 'all_day') hidden @endif class="grid grid-cols-2 gap-2">
        <div>
            <label for="starts_time" class="block font-medium">Starts</label>
            <input id="starts_time" name="starts_time" type="text" inputmode="numeric" maxlength="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" placeholder="14:30" autocomplete="off" spellcheck="false" value="{{ $startsTime }}" @disabled($schedule === 'all_day') class="{{ $field }}" aria-describedby="time-help">
        </div>
        <div>
            <label for="ends_time" class="block font-medium">Ends</label>
            <input id="ends_time" name="ends_time" type="text" inputmode="numeric" maxlength="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" placeholder="18:00" autocomplete="off" spellcheck="false" value="{{ $endsTime }}" @disabled($schedule === 'all_day') class="{{ $field }}">
        </div>
        <p id="time-help" class="help col-span-2">24-hour time on this day, for example 14:30.</p>
    </div>
</fieldset>

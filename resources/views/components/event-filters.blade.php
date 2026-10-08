@props(['filters', 'view', 'provider'])

<details class="filters">
    <summary>
        Filters
        @if ($filters->isActive())
            <span class="filters-current">· {{ $filters->label() }}</span>
        @endif
    </summary>
    <div class="card filters-panel">
        <form method="get" action="{{ route('events.index') }}">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="hidden" name="provider" value="{{ $provider }}">
            <fieldset>
                <legend>Dates</legend>
                <label class="filter-choice">
                    <input type="radio" name="range" value="day" @checked($filters->range === 'day')>
                    One day
                </label>
                <label class="filter-choice">
                    <input type="radio" name="range" value="week" @checked($filters->range === 'week')>
                    Week
                </label>
                <label class="filter-choice">
                    <input type="radio" name="range" value="month" @checked($filters->range === 'month')>
                    Month
                </label>

                <div class="filter-day">
                    <label for="filter-on">Date</label>
                    <input class="field" type="date" id="filter-on" name="on" min="{{ $filters->earliestDay() }}" max="{{ $filters->latestDayValue() }}" value="{{ $filters->onValue() }}">
                    <p class="help">Shows events on this day.</p>
                </div>

                <div class="filter-week">
                    <label for="filter-monday">Monday</label>
                    <select class="field" id="filter-monday" name="monday">
                        @foreach ($filters->mondays() as $monday)
                            <option value="{{ $monday }}" @selected($filters->mondayValue() === $monday)>
                                {{ $filters->mondayLabel($monday) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="help">Shows that Monday through Sunday.</p>
                </div>

                <div class="filter-month">
                    <label for="filter-month">Month</label>
                    <select class="field" id="filter-month" name="month">
                        @foreach ($filters->months() as $month)
                            <option value="{{ $month['value'] }}" @selected($filters->monthToken() === $month['value'])>
                                {{ $month['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="help">Shows every day of this month.</p>
                </div>
            </fieldset>
            <div class="mt-2 flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Show events</button>
                <a class="btn btn-secondary" href="{{ route('events.index', ['view' => $view, 'provider' => $provider]) }}">All dates</a>
            </div>
        </form>
    </div>
</details>

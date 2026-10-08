<?php

namespace App\Events\Filters;

use App\Events\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The public event query. With no period, the 30-day window stands.
 * A chosen day, week, or month replaces that window.
 */
final class PublicQuery
{
    public function __construct(
        public readonly ?string $range,
        public readonly ?string $on,
        public readonly ?int $month,
        public readonly ?int $year,
        private readonly ?Period $period,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $range = $request->query('range');

        if (! in_array($range, ['day', 'week', 'month'], true)) {
            return self::open();
        }

        $timezone = (string) config('events.timezone');

        if ($range === 'month') {
            $choice = self::monthChoice($request);

            if ($choice !== null) {
                [$month, $year] = $choice;
                $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfDay();
            } else {
                $on = self::date($request->query('on'), $timezone);

                if ($on === null) {
                    return self::open();
                }

                $start = $on->copy()->startOfMonth();
                $month = (int) $start->month;
                $year = (int) $start->year;
            }

            $end = $start->copy()->endOfMonth()->startOfSecond();

            if ($end->lt(self::today($timezone)) || $start->gt(self::latestMonth($timezone))) {
                return self::open();
            }

            return new self(
                'month',
                $start->format('Y-m-d'),
                $month,
                $year,
                new Period('month', $start->copy()->utc(), $end->copy()->utc()),
            );
        }

        if ($range === 'week') {
            $raw = $request->query('monday');

            if (! is_string($raw) || $raw === '') {
                $raw = $request->query('on');
            }

            $monday = self::date(is_string($raw) ? $raw : null, $timezone);

            if ($monday === null || ! $monday->isMonday()) {
                return self::open();
            }

            $earliestMonday = self::today($timezone)->startOfWeek(Carbon::MONDAY);
            $latestMonday = self::latestDay($timezone)->startOfDay();

            if (! $latestMonday->isMonday()) {
                $latestMonday = $latestMonday->previous(Carbon::MONDAY);
            }

            if ($monday->lt($earliestMonday) || $monday->gt($latestMonday)) {
                return self::open();
            }

            $start = $monday->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay()->startOfSecond();

            return new self(
                'week',
                $start->format('Y-m-d'),
                (int) $start->month,
                (int) $start->year,
                new Period('week', $start->copy()->utc(), $end->copy()->utc()),
            );
        }

        $on = self::date($request->query('on'), $timezone);

        if ($on === null) {
            return self::open();
        }

        $start = $on->copy()->startOfDay();
        $end = $on->copy()->endOfDay()->startOfSecond();

        if ($start->lt(self::today($timezone)) || $end->gt(self::latestDay($timezone))) {
            return self::open();
        }

        return new self(
            'day',
            $on->format('Y-m-d'),
            (int) $start->month,
            (int) $start->year,
            new Period('day', $start->copy()->utc(), $end->copy()->utc()),
        );
    }

    public function isActive(): bool
    {
        return $this->period !== null;
    }

    /**
     * @return Collection<int, Event>
     */
    public function events(): Collection
    {
        $query = Event::query();
        $criteria = $this->criteria();

        if ($criteria === []) {
            return $query->upcoming()->get();
        }

        foreach ($criteria as $criterion) {
            $criterion->apply($query);
        }

        return $query->orderBy('starts_at')->get();
    }

    /**
     * @return array<string, int|string>
     */
    public function parameters(): array
    {
        if ($this->range === 'month' && $this->year !== null && $this->month !== null) {
            return [
                'range' => 'month',
                'year' => $this->year,
                'month' => $this->month,
            ];
        }

        if ($this->range !== null && $this->on !== null) {
            return [
                'range' => $this->range,
                'on' => $this->on,
            ];
        }

        return [];
    }

    public function heading(): string
    {
        $label = $this->label();

        return match ($this->range) {
            'day' => 'Events on '.$label,
            'week' => 'Events in the week of '.$label,
            'month' => 'Events in '.$label,
            default => 'Events in the next '.(int) config('events.window_days').' days',
        };
    }

    public function emptyMessage(): string
    {
        $label = $this->label();

        return match ($this->range) {
            'day' => 'No events on '.$label.'.',
            'week' => 'No events in the week of '.$label.'.',
            'month' => 'No events in '.$label.'.',
            default => 'No events in the next '.(int) config('events.window_days').' days.',
        };
    }

    public function label(): ?string
    {
        if ($this->period === null || $this->range === null) {
            return null;
        }

        $timezone = (string) config('events.timezone');
        $start = $this->period->start->copy()->timezone($timezone);
        $end = $this->period->end->copy()->timezone($timezone);

        if ($this->range === 'day') {
            return $start->format('j M Y');
        }

        if ($this->range === 'month') {
            return $start->format('F Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->format('j').'–'.$end->format('j M Y');
        }

        if ($start->year === $end->year) {
            return $start->format('j M').'–'.$end->format('j M Y');
        }

        return $start->format('j M Y').'–'.$end->format('j M Y');
    }

    public function onValue(): string
    {
        if ($this->range === 'day' && $this->on !== null) {
            return $this->on;
        }

        return now()->timezone((string) config('events.timezone'))->format('Y-m-d');
    }

    public function mondayValue(): string
    {
        if ($this->range === 'week' && $this->on !== null) {
            return $this->on;
        }

        return now()->timezone((string) config('events.timezone'))->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function earliestDay(): string
    {
        return self::today((string) config('events.timezone'))->format('Y-m-d');
    }

    public function latestDayValue(): string
    {
        return self::latestDay((string) config('events.timezone'))->startOfDay()->format('Y-m-d');
    }

    /**
     * Mondays from the current week through next year, in Europe/Lisbon.
     * The Monday of the current week stays available after Monday has passed.
     *
     * @return list<string>
     */
    public function mondays(): array
    {
        $timezone = (string) config('events.timezone');
        $start = self::today($timezone)->startOfWeek(Carbon::MONDAY);
        $end = self::latestDay($timezone)->startOfDay();

        if (! $end->isMonday()) {
            $end = $end->previous(Carbon::MONDAY);
        }

        $dates = [];

        for ($day = $start->copy(); $day->lte($end); $day->addWeek()) {
            $dates[] = $day->format('Y-m-d');
        }

        return $dates;
    }

    public function mondayLabel(string $date): string
    {
        return Carbon::createFromFormat('!Y-m-d', $date, (string) config('events.timezone'))->format('D j M Y');
    }

    public function monthToken(): string
    {
        if ($this->range === 'month' && $this->year !== null && $this->month !== null) {
            return sprintf('%04d-%02d', $this->year, $this->month);
        }

        return self::today((string) config('events.timezone'))->format('Y-m');
    }

    /**
     * The current month through December of next year.
     *
     * @return list<array{value: string, label: string}>
     */
    public function months(): array
    {
        $timezone = (string) config('events.timezone');
        $cursor = self::today($timezone)->startOfMonth();
        $end = self::latestMonth($timezone);
        $months = [];

        for ($month = $cursor->copy(); $month->lte($end); $month->addMonth()) {
            $months[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
            ];
        }

        return $months;
    }

    private static function open(): self
    {
        return new self(null, null, null, null, null);
    }

    /**
     * @return list<FiltersEvents>
     */
    private function criteria(): array
    {
        if ($this->period === null) {
            return [];
        }

        return [$this->period];
    }

    private static function date(mixed $value, string $timezone): ?Carbon
    {
        if (! is_string($value)) {
            return null;
        }

        $parsed = Carbon::createFromFormat('!Y-m-d', $value, $timezone);

        if (! $parsed instanceof Carbon || $parsed->format('Y-m-d') !== $value) {
            return null;
        }

        if ($parsed->year < 2000 || $parsed->year > 2100) {
            return null;
        }

        return $parsed->startOfDay();
    }

    private static function today(string $timezone): Carbon
    {
        return now()->timezone($timezone)->startOfDay();
    }

    private static function latestDay(string $timezone): Carbon
    {
        $today = self::today($timezone);

        return Carbon::create($today->year + 1, 12, 31, 0, 0, 0, $timezone)->endOfDay()->startOfSecond();
    }

    private static function latestMonth(string $timezone): Carbon
    {
        $today = self::today($timezone);

        return Carbon::create($today->year + 1, 12, 1, 0, 0, 0, $timezone)->startOfMonth();
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private static function monthChoice(Request $request): ?array
    {
        $month = $request->query('month');

        if (is_string($month) && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month, $matches) === 1) {
            $year = (int) $matches[1];

            if ($year < 2000 || $year > 2100) {
                return null;
            }

            return [(int) $matches[2], $year];
        }

        $monthNumber = self::monthNumber($month);
        $yearNumber = self::yearNumber($request->query('year'));

        if ($monthNumber === null || $yearNumber === null) {
            return null;
        }

        return [$monthNumber, $yearNumber];
    }

    private static function monthNumber(mixed $value): ?int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^(?:[1-9]|1[0-2])$/', $value) === 1)) {
            return null;
        }

        return (int) $value;
    }

    private static function yearNumber(mixed $value): ?int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^\d{4}$/', $value) === 1)) {
            return null;
        }

        $year = (int) $value;

        if ($year < 2000 || $year > 2100) {
            return null;
        }

        return $year;
    }
}

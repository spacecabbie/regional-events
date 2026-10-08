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
            $month = self::monthNumber($request->query('month'));
            $year = self::yearNumber($request->query('year'));

            if ($month !== null && $year !== null) {
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

            return new self(
                'month',
                $start->format('Y-m-d'),
                $month,
                $year,
                new Period('month', $start->copy()->utc(), $end->copy()->utc()),
            );
        }

        $on = self::date($request->query('on'), $timezone);

        if ($on === null) {
            return self::open();
        }

        if ($range === 'week') {
            $start = $on->copy()->startOfWeek(Carbon::MONDAY);
            $end = $on->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()->startOfSecond();
        } else {
            $start = $on->copy()->startOfDay();
            $end = $on->copy()->endOfDay()->startOfSecond();
        }

        return new self(
            $range,
            $on->format('Y-m-d'),
            (int) $start->month,
            (int) $start->year,
            new Period($range, $start->copy()->utc(), $end->copy()->utc()),
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
        if ($this->on !== null) {
            return $this->on;
        }

        return now()->timezone((string) config('events.timezone'))->format('Y-m-d');
    }

    public function monthValue(): int
    {
        return $this->month ?? (int) now()->timezone((string) config('events.timezone'))->month;
    }

    public function yearValue(): int
    {
        return $this->year ?? (int) now()->timezone((string) config('events.timezone'))->year;
    }

    /**
     * @return list<int>
     */
    public function years(): array
    {
        $today = now()->timezone((string) config('events.timezone'));
        $years = range($today->year - 1, $today->year + 1);

        if (! in_array($this->yearValue(), $years, true)) {
            $years[] = $this->yearValue();
            sort($years);
        }

        return $years;
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

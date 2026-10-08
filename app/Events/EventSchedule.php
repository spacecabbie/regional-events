<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EventSchedule
{
    /**
     * Public form. One Europe/Lisbon day. Times are 24-hour HH:MM.
     *
     * @param  array<string, mixed>  $data
     * @return array{starts_at: Carbon, ends_at: Carbon, all_day: bool}
     */
    public static function fromForm(array $data): array
    {
        $timezone = config('events.timezone');
        $allDay = ($data['schedule'] ?? '') === 'all_day';
        $start = Carbon::createFromFormat('!Y-m-d', (string) $data['starts_on'], $timezone)->startOfDay();

        if ($allDay) {
            return [
                'all_day' => true,
                'starts_at' => $start->copy()->utc(),
                'ends_at' => $start->copy()->endOfDay()->startOfSecond()->utc(),
            ];
        }

        [$startHour, $startMinute] = self::clock((string) $data['starts_time']);
        [$endHour, $endMinute] = self::clock((string) $data['ends_time']);

        return [
            'all_day' => false,
            'starts_at' => $start->copy()->setTime($startHour, $startMinute)->utc(),
            'ends_at' => $start->copy()->setTime($endHour, $endMinute)->utc(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function problem(array $data): ?string
    {
        if (! in_array($data['schedule'] ?? '', ['timed', 'all_day'], true)) {
            return null;
        }

        if (! self::isDate($data['starts_on'] ?? null)) {
            return null;
        }

        if (($data['schedule'] ?? '') === 'timed' && (! self::isTime($data['starts_time'] ?? null) || ! self::isTime($data['ends_time'] ?? null))) {
            return null;
        }

        $span = self::fromForm($data);

        if ($span['ends_at']->lte($span['starts_at'])) {
            return 'The end must be after the start.';
        }

        return null;
    }

    /**
     * Admin datetimes arrive in UTC. An all-day choice covers each local calendar day.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyAdmin(array $data): array
    {
        $timezone = config('events.timezone');
        $allDay = (bool) ($data['all_day'] ?? false);
        $start = Carbon::parse($data['starts_at'])->utc()->timezone($timezone);
        $end = isset($data['ends_at']) && $data['ends_at'] !== null && $data['ends_at'] !== ''
            ? Carbon::parse($data['ends_at'])->utc()->timezone($timezone)
            : null;

        if ($allDay) {
            $start = $start->startOfDay();
            $end = ($end ?? $start)->copy()->endOfDay()->startOfSecond();
        } elseif ($end === null) {
            throw ValidationException::withMessages([
                'ends_at' => 'Enter an end time.',
            ]);
        }

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'ends_at' => 'The end must be after the start.',
            ]);
        }

        $data['all_day'] = $allDay;
        $data['starts_at'] = $start->copy()->utc();
        $data['ends_at'] = $end->copy()->utc();

        return $data;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function clock(string $value): array
    {
        [$hour, $minute] = explode(':', $value);

        return [(int) $hour, (int) $minute];
    }

    private static function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $parsed = Carbon::createFromFormat('Y-m-d', $value);

        return $parsed instanceof Carbon && $parsed->format('Y-m-d') === $value;
    }

    private static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}

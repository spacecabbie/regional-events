<?php

namespace App\Events;

use App\Locations\ValidLocation;

class EventRules
{
    /**
     * @return array<int, mixed>
     */
    public static function name(): array
    {
        return ['required', 'string', 'max:200'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function schedule(): array
    {
        return ['required', 'in:timed,all_day'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function startDate(): array
    {
        return ['required', 'date_format:Y-m-d', new NotBeforeToday];
    }

    /**
     * @return array<int, mixed>
     */
    public static function endDate(): array
    {
        return ['required', 'date_format:Y-m-d', new ConsistentSchedule];
    }

    /**
     * @return array<int, mixed>
     */
    public static function clock(): array
    {
        return ['exclude_if:schedule,all_day', 'required_if:schedule,timed', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function adminStartsAt(): array
    {
        return ['required', 'date'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function adminEndsAt(): array
    {
        return ['required', 'date'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function location(): array
    {
        return ['required', 'string', 'max:2000', new ValidLocation];
    }

    /**
     * @return array<int, mixed>
     */
    public static function email(): array
    {
        return ['required', 'email:rfc', 'max:255'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function adminEmail(): array
    {
        return ['nullable', 'email:rfc', 'max:255'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function flyer(): array
    {
        return ['nullable', 'file', 'mimetypes:'.implode(',', config('events.flyer_mimes')), 'max:'.config('events.upload_max_kilobytes')];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function submission(): array
    {
        return [
            'name' => self::name(),
            'schedule' => self::schedule(),
            'starts_on' => self::startDate(),
            'ends_on' => self::endDate(),
            'starts_time' => self::clock(),
            'ends_time' => self::clock(),
            'location' => self::location(),
            'email' => self::email(),
            'flyer' => self::flyer(),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function edit(): array
    {
        return [
            'name' => self::name(),
            'schedule' => self::schedule(),
            'starts_on' => self::startDate(),
            'ends_on' => self::endDate(),
            'starts_time' => self::clock(),
            'ends_time' => self::clock(),
            'location' => self::location(),
            'flyer' => self::flyer(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'schedule' => 'time',
            'starts_on' => 'start date',
            'ends_on' => 'end date',
            'starts_time' => 'start time',
            'ends_time' => 'end time',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function manageRequest(): array
    {
        return [
            'email' => self::email(),
        ];
    }
}

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
    public static function startsAt(): array
    {
        return ['required', 'date', new NotBeforeToday];
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
        return ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('events.upload_max_kilobytes')];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function submission(): array
    {
        return [
            'name' => self::name(),
            'starts_at' => self::startsAt(),
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
            'starts_at' => self::startsAt(),
            'location' => self::location(),
            'flyer' => self::flyer(),
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

<?php

namespace App\Events\Filters;

use App\Events\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * One Lisbon day, the Monday–Sunday week that contains a day, or a full calendar month.
 */
final class Period implements FiltersEvents
{
    public function __construct(
        public readonly string $range,
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    /**
     * @param  Builder<Event>  $query
     */
    public function apply(Builder $query): void
    {
        $query->confirmed()->overlapping($this->start, $this->end);
    }
}

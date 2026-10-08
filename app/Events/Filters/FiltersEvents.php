<?php

namespace App\Events\Filters;

use App\Events\Event;
use Illuminate\Database\Eloquent\Builder;

interface FiltersEvents
{
    /**
     * @param  Builder<Event>  $query
     */
    public function apply(Builder $query): void;
}

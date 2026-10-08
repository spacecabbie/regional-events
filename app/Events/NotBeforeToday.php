<?php

namespace App\Events;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotBeforeToday implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $start = Carbon::parse((string) $value, config('events.timezone'));
        } catch (\Throwable) {
            $fail('Enter a valid date and time.');

            return;
        }

        $today = now()->timezone(config('events.timezone'))->startOfDay();

        if ($start->lt($today)) {
            $fail('The event must be today or later, in Portugal time.');
        }
    }
}

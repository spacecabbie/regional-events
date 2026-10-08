<?php

namespace App\Events;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConsistentSchedule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = EventSchedule::problem(request()->all());

        if ($message !== null) {
            $fail($message);
        }
    }
}

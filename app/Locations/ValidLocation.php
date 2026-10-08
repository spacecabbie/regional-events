<?php

namespace App\Locations;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLocation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        try {
            app(LocationParser::class)->parse($value);
        } catch (InvalidLocation $exception) {
            $fail($exception->getMessage());
        }
    }
}

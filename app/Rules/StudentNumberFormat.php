<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StudentNumberFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(19|20)\d{2}\d{4}$/', $value)) {
            $fail('The :attribute must be exactly 8 digits: a 4-digit enrollment year (e.g. 2024) followed by a 4-digit registration number (e.g. 0765).');
        }
    }
}

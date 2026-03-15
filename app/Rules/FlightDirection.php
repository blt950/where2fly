<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FlightDirection implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $allowedDirection = [
            0, 'N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW',
        ];

        if (! in_array($value, $allowedDirection)) {
            $fail("The $attribute is invalid.");
        }
    }
}

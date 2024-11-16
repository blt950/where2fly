<?php

namespace App\Rules;

use App\Models\Aircraft;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAircrafts implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $whitelist = Aircraft::orderBy('icao')->pluck('icao')->toArray();

        foreach ($value as $aircraft) {
            if (! in_array($aircraft, $whitelist)) {
                $fail('Selected aircraft is not valid.');
            }
        }
    }
}

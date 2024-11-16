<?php

namespace App\Rules;

use App\Models\Airline;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAirlines implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $whitelist = Airline::where('has_flights', true)->orderBy('name')->pluck('icao_code')->toArray();

        foreach ($value as $airline) {
            if (! in_array($airline, $whitelist)) {
                $fail('Selected airline is not valid.');
            }
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class AirportExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $exists = DB::table('airports')
            ->where('icao', $value)
            ->orWhere('local_code', $value)
            ->exists();

        if (! $exists) {
            $fail('Airport not found.');
        }

    }
}

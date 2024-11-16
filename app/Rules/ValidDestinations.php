<?php

namespace App\Rules;

use App\Http\Controllers\SearchController;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDestinations implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $whitelist = ['Anywhere', 'Domestic', 'C-AF', 'C-AS', 'C-EU', 'C-NA', 'C-OC', 'C-SA'];

        $countries = SearchController::$countries;
        $usStates = SearchController::$usStates;

        foreach ($countries as $iso => $country) {
            $whitelist[] = $iso;
        }

        foreach ($usStates as $iso => $state) {
            $whitelist[] = 'US-' . $iso;
        }

        $exists = ! in_array($value, $whitelist);

        if (! $exists) {
            $fail('Not a valid destination.');
        }

    }
}

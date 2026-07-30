<?php

namespace App\Rules;

use App\Helpers\CountryHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidDestinations implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $whitelist = [
            'Anywhere', 'Domestic', 'C-AF', 'C-AS', 'C-EU', 'C-NA', 'C-OC', 'C-SA',
            ...array_keys(CountryHelper::names()),
            ...array_map(fn ($iso) => 'US-' . $iso, array_keys(CountryHelper::US_STATES)),
        ];

        $exists = ! in_array($value, $whitelist);

        if (! $exists) {
            $fail('Not a valid destination.');
        }

    }
}

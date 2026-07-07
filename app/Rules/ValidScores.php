<?php

namespace App\Rules;

use App\Http\Controllers\ScoreController;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidScores implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $whitelist = array_keys(ScoreController::$score_types);

        foreach ($value as $score => $scoreValue) {
            if (! in_array($score, $whitelist)) {
                $fail('Not a valid parameter.');
            }

            if (! in_array((string) $scoreValue, ['-1', '0', '1'], true)) {
                $fail('Score values must be -1, 0 or 1.');
            }
        }
    }
}

<?php

namespace App\Rules;

use App\Http\Controllers\ScoreController;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidScores implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $whitelist = [];
        foreach (ScoreController::$score_types as $k => $score_type) {
            $whitelist[] = $k;
        }

        foreach ($value as $score => $value) {
            if (! in_array($score, $whitelist)) {
                $fail('Not a valid parameter.');
            }
        }
    }
}

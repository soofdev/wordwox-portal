<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class ValidChildAge implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if value is empty (let required rule handle this)
        if (empty($value)) {
            return;
        }

        try {
            $birthDate = Carbon::parse($value);
            $age = $birthDate->age;

            if ($age < 0 || $age > 18) {
                $fail('Child must be between 0 and 18 years old.');
                return;
            }
        } catch (\Exception $e) {
            $fail('Please enter a valid date of birth.');
            return;
        }
    }
}

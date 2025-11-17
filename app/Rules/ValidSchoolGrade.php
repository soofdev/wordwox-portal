<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSchoolGrade implements ValidationRule
{
    protected $validGrades = [
        'toddler', 'pre-1', 'pre-2', 'kg-1', 'kg-2',
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Let required rule handle empty values
        }

        if (!in_array($value, $this->validGrades)) {
            $fail('Please select a valid school grade.');
        }
    }
}

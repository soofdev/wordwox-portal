<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidFullName implements ValidationRule
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

        // Trim and normalize spaces
        $normalizedName = $this->normalizeFullName($value);

        // Check if name has at least two parts (words)
        $nameParts = explode(' ', $normalizedName);
        $meaningfulParts = array_filter($nameParts, function($part) {
            return !empty(trim($part));
        });

        if (count($meaningfulParts) < 2) {
            $fail('Full name must include first and last name.');
            return;
        }
    }

    /**
     * Normalize full name by trimming and reducing multiple spaces to single spaces
     */
    private function normalizeFullName($name)
    {
        // Trim leading and trailing spaces
        $normalized = trim($name);
        
        // Replace multiple spaces with single space
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return $normalized;
    }
} 
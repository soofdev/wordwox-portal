<?php

namespace App\Rules;

use App\Models\OrgUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueFamilyMemberPhone implements ValidationRule
{
    protected $orgId;
    protected $phoneCountry;
    protected $excludeId;
    protected $familyPhones;

    /**
     * @param int $orgId Organization ID
     * @param string $phoneCountry Phone country code
     * @param int|null $excludeId ID to exclude from database check
     * @param array $familyPhones Array of phone numbers from other family members in current registration
     */
    public function __construct($orgId, $phoneCountry, $excludeId = null, $familyPhones = [])
    {
        $this->orgId = $orgId;
        $this->phoneCountry = $phoneCountry;
        $this->excludeId = $excludeId;
        $this->familyPhones = array_filter($familyPhones); // Remove empty values
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if phone number is empty
        if (empty($value)) {
            return;
        }

        // Normalize phone number for comparison
        $normalizedValue = $this->normalizePhoneNumber($value);

        // Check uniqueness within family members first
        foreach ($this->familyPhones as $familyPhone) {
            if ($this->normalizePhoneNumber($familyPhone) === $normalizedValue) {
                $fail('This phone number is already used by another family member.');
                return;
            }
        }

        // Check uniqueness across organization (including soft-deleted, excluding archived)
        $query = OrgUser::where('org_id', $this->orgId)
                       ->where('phoneCountry', $this->phoneCountry)
                       ->whereRaw('TRIM(LEADING "0" FROM phoneNumber) = ?', [$normalizedValue])
                       ->where(function ($query) {
                           $query->whereNull('deleted_at')
                                 ->orWhereNotNull('deleted_at'); // Include soft-deleted
                       })
                       ->where(function ($query) {
                           $query->where('isArchived', '!=', 1)
                                 ->orWhereNull('isArchived'); // Exclude archived
                       });

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('This phone number is already registered.');
            return;
        }
    }

    /**
     * Normalize phone number by removing leading zeros and non-digit characters
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return $phoneNumber;
        }
        
        // Remove all non-digit characters except + (for international format)
        $normalized = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Remove leading zeros (but keep the number if it's all zeros)
        $normalized = ltrim($normalized, '0') ?: '0';
        
        return $normalized;
    }
} 
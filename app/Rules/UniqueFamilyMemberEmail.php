<?php

namespace App\Rules;

use App\Models\OrgUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueFamilyMemberEmail implements ValidationRule
{
    protected $orgId;
    protected $excludeId;
    protected $familyEmails;

    /**
     * @param int $orgId Organization ID
     * @param int|null $excludeId ID to exclude from database check
     * @param array $familyEmails Array of email addresses from other family members in current registration
     */
    public function __construct($orgId, $excludeId = null, $familyEmails = [])
    {
        $this->orgId = $orgId;
        $this->excludeId = $excludeId;
        $this->familyEmails = array_filter($familyEmails); // Remove empty values
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if email is empty
        if (empty($value)) {
            return;
        }

        // Check uniqueness within family members first
        if (in_array($value, $this->familyEmails)) {
            $fail('This email address is already used by another family member.');
            return;
        }

        // Check uniqueness across organization (including soft-deleted, excluding archived)
        $query = OrgUser::where('org_id', $this->orgId)
                       ->where('email', $value)
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
            $fail('This email address is already registered.');
            return;
        }
    }
} 
<?php

namespace App\Rules;

use App\Models\OrgUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueOrgUserPhone implements ValidationRule
{
    protected $orgId;
    protected $phoneCountry;
    protected $excludeId;

    public function __construct($orgId, $phoneCountry, $excludeId = null)
    {
        $this->orgId = $orgId;
        $this->phoneCountry = $phoneCountry;
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = OrgUser::where('org_id', $this->orgId)
                       ->where('phoneNumber', $value)
                       ->where('phoneCountry', $this->phoneCountry)
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
        }
    }
}
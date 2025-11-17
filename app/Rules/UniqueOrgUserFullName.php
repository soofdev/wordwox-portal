<?php

namespace App\Rules;

use App\Models\OrgUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueOrgUserFullName implements ValidationRule
{
    protected $orgId;
    protected $excludeId;

    public function __construct($orgId, $excludeId = null)
    {
        $this->orgId = $orgId;
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = OrgUser::where('org_id', $this->orgId)
                       ->where('fullName', $value)
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
            $fail('This name is already registered in your organization.');
        }
    }
}
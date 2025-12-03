<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * TenantScope - Global scope to automatically filter queries by org_id
 * 
 * When authenticated: Uses the authenticated user's org_id
 * When not authenticated: Uses the default org_id from environment variable
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $orgId = null;
        $table = $model->getTable();
        
        // Try to get org_id from authenticated user
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user has orgUser_id
            if (isset($user->orgUser_id) && $user->orgUser_id) {
                // Use subquery to get org_id directly from database
                // This avoids loading the relationship and potential errors
                $builder->whereIn($table . '.org_id', function ($query) use ($user) {
                    $query->select('org_id')
                          ->from('orgUser')
                          ->where('id', $user->orgUser_id);
                });
                return; // Early return since we applied the subquery
            }
        }
        
        // Fallback to environment variable when not authenticated or no user org
        if (!$orgId) {
            $orgId = env('CMS_DEFAULT_ORG_ID', env('DEFAULT_ORG_ID', null));
        }
        
        // Apply org_id filter if we have a value
        if ($orgId) {
            $builder->where($table . '.org_id', $orgId);
        }
    }
}
<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Tenantable
{
    /**
     * Boot the tenantable trait
     * Automatically applies tenant scope and sets org_id on creation
     */
    protected static function bootTenantable()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (!$model->org_id) {
                if (Auth::check() && Auth::user()->orgUser) {
                    // Use authenticated user's org_id
                $model->org_id = Auth::user()->orgUser->org_id;
                } else {
                    // Fallback to environment variable when not authenticated
                    $model->org_id = env('CMS_DEFAULT_ORG_ID', env('DEFAULT_ORG_ID', null));
                }
            }
        });
    }

    /**
     * Scope to query across all tenants (bypass tenant filtering)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
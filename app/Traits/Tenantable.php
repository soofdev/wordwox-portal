<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Tenantable
{
    protected static function bootTenantable()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (!$model->org_id && Auth::check()) {
                $model->org_id = Auth::user()->orgUser->org_id;
            }
        });
    }

    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
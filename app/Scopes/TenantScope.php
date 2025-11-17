<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check() && Auth::user()->orgUser_id) {
            $table = $model->getTable();
            $orgUserId = Auth::user()->orgUser_id;
            
            // Use a subquery to get the org_id from orgUser table
            $builder->whereIn($table . '.org_id', function ($query) use ($orgUserId) {
                $query->select('org_id')
                      ->from('orgUser')
                      ->where('id', $orgUserId);
            });
        }
    }
}
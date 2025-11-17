<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * RBAC Category Model
 * 
 * Groups tasks for better organization in admin interfaces
 */
class RbacCategory extends BaseWWModel
{
    protected $table = 'rbacCategory';
    
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'module',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected $attributes = [
        'order' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get all tasks in this category
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(RbacTask::class, 'rbacCategory_id');
    }

    /**
     * Get tasks ordered by display order
     */
    public function orderedTasks(): HasMany
    {
        return $this->tasks()->orderBy('order')->orderBy('name');
    }

    /**
     * Scope to get categories for a specific module
     */
    public function scopeForModule($query, ?string $module)
    {
        if ($module === null) {
            return $query->whereNull('module');
        }
        
        return $query->where('module', $module);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}


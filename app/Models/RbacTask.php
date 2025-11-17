<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * RBAC Task Model
 * 
 * Represents individual permissions/capabilities in the system
 */
class RbacTask extends BaseWWModel
{
    protected $table = 'rbacTask';
    
    protected $fillable = [
        'uuid',
        'rbacCategory_id',
        'name',
        'slug',
        'description',
        'module',
        'order',
    ];

    protected $casts = [
        'rbacCategory_id' => 'integer',
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

            // Auto-generate slug from name if not provided
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name, '_');
            }
        });
    }

    /**
     * Get the category this task belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RbacCategory::class, 'rbacCategory_id');
    }

    /**
     * Get all role-task assignments for this task
     */
    public function roleTasks(): HasMany
    {
        return $this->hasMany(RbacRoleTask::class, 'rbacTask_id');
    }

    /**
     * Get all roles that have this task assigned (active only)
     */
    public function activeRoles(): BelongsToMany
    {
        return $this->belongsToMany(RbacRole::class, 'rbacRoleTask', 'rbacTask_id', 'rbacRole_id')
                    ->wherePivot('isActive', true)
                    ->withPivot(['uuid', 'isActive']);
    }

    /**
     * Scope to get tasks for a specific module
     */
    public function scopeForModule($query, ?string $module)
    {
        if ($module === null) {
            return $query->whereNull('module');
        }
        
        return $query->where('module', $module);
    }

    /**
     * Scope to get tasks that work in a specific module (including legacy)
     */
    public function scopeAvailableInModule($query, string $module)
    {
        return $query->where(function ($q) use ($module) {
            $q->where('module', $module)
              ->orWhereNull('module'); // Include legacy tasks
        });
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Find task by slug
     */
    public static function findBySlug(string $slug, ?string $module = null): ?self
    {
        $query = static::where('slug', $slug);
        
        if ($module !== null) {
            $query->where(function ($q) use ($module) {
                $q->where('module', $module)
                  ->orWhereNull('module'); // Include legacy tasks
            });
        }

        return $query->first();
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * RBAC Role-Task Pivot Model
 * 
 * Many-to-many relationship between roles and tasks
 */
class RbacRoleTask extends BaseWWModel
{
    protected $table = 'rbacRoleTask';
    
    protected $fillable = [
        'uuid',
        'rbacRole_id',
        'rbacTask_id',
        'module',
        'isActive',
    ];

    protected $casts = [
        'rbacRole_id' => 'integer',
        'rbacTask_id' => 'integer',
        'isActive' => 'boolean',
    ];

    protected $attributes = [
        'isActive' => true,
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
     * Get the role this assignment belongs to
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RbacRole::class, 'rbacRole_id');
    }

    /**
     * Get the task this assignment belongs to
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(RbacTask::class, 'rbacTask_id');
    }

    /**
     * Scope to get only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Toggle the active status of this assignment
     */
    public function toggle(): bool
    {
        $this->isActive = !$this->isActive;
        return $this->save();
    }
}


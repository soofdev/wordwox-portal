<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * RBAC Role Model
 * 
 * Organization-scoped roles with module awareness
 */
class RbacRole extends BaseWWModel
{
    use Tenantable;

    protected $table = 'rbacRole';
    
    protected $fillable = [
        'uuid',
        'org_id',
        'name',
        'slug',
        'module',
        'order',
        'isActive',
        'isFixed',
        'isRequired',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'order' => 'integer',
        'isActive' => 'boolean',
        'isFixed' => 'boolean',
        'isRequired' => 'boolean',
    ];

    protected $attributes = [
        'isActive' => true,
        'isFixed' => false,
        'isRequired' => false,
        'order' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }

            // Auto-generate slug from name
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }

            // Set org_id from authenticated user if not set
            if (empty($model->org_id) && auth()->check() && auth()->user()->orgUser) {
                $model->org_id = auth()->user()->orgUser->org_id;
            }
        });
    }

    /**
     * Get the organization that owns this role
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get all role-task assignments for this role
     */
    public function roleTask(): HasMany
    {
        return $this->hasMany(RbacRoleTask::class, 'rbacRole_id');
    }

    /**
     * Get all active tasks assigned to this role
     */
    public function activeTasks(): BelongsToMany
    {
        return $this->belongsToMany(RbacTask::class, 'rbacRoleTask', 'rbacRole_id', 'rbacTask_id')
                    ->wherePivot('isActive', true)
                    ->withPivot(['uuid', 'isActive']);
    }

    /**
     * Get all role-user assignments for this role
     */
    public function roleUsers(): HasMany
    {
        return $this->hasMany(RbacRoleUser::class, 'rbacRole_id');
    }

    /**
     * Get all active users assigned to this role
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->belongsToMany(OrgUser::class, 'rbacRoleUser', 'rbacRole_id', 'orgUser_id')
                    ->wherePivot('isDeleted', false)
                    ->withPivot(['uuid', 'isDeleted']);
    }

    /**
     * Check if this role has a specific task
     */
    public function hasTask(string $taskSlug, ?string $module = null): bool
    {
        $query = $this->activeTasks()
                      ->where('rbacTask.slug', $taskSlug);

        if ($module !== null) {
            $query->where(function ($q) use ($module) {
                $q->where('rbacTask.module', $module)
                  ->orWhereNull('rbacTask.module'); // Include legacy tasks
            });
        }

        return $query->exists();
    }

    /**
     * Scope to get roles for a specific organization
     */
    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get roles for a specific module
     */
    public function scopeForModule($query, ?string $module)
    {
        if ($module === null) {
            return $query->whereNull('module');
        }
        
        return $query->where('module', $module);
    }

    /**
     * Scope to get only active roles
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Get role by name within organization
     */
    public static function findByName(string $name, int $orgId, ?string $module = 'foh'): ?self
    {
        return static::where('name', $name)
                    ->where('org_id', $orgId)
                    ->where('module', $module)
                    ->first();
    }
}


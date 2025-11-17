<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * RBAC Role-User Pivot Model
 * 
 * Many-to-many relationship between roles and users with organization context
 */
class RbacRoleUser extends BaseWWModel
{
    use Tenantable;

    protected $table = 'rbacRoleUser';
    
    protected $fillable = [
        'uuid',
        'org_id',
        'rbacRole_id',
        'orgUser_id',
        'module',
        'isDeleted',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'rbacRole_id' => 'integer',
        'orgUser_id' => 'integer',
        'isDeleted' => 'boolean',
    ];

    protected $attributes = [
        'isDeleted' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }

            // Set org_id from authenticated user if not set
            if (empty($model->org_id) && auth()->check() && auth()->user()->orgUser) {
                $model->org_id = auth()->user()->orgUser->org_id;
            }
        });
    }

    /**
     * Get the organization this assignment belongs to
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the role this assignment belongs to
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RbacRole::class, 'rbacRole_id');
    }

    /**
     * Get the user this assignment belongs to
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Scope to get only active assignments (not soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->where('isDeleted', false);
    }

    /**
     * Scope to get assignments for a specific organization
     */
    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Soft delete this assignment (maintains audit trail)
     */
    public function softDelete(): bool
    {
        $this->isDeleted = true;
        return $this->save();
    }

    /**
     * Restore this assignment
     */
    public function restore(): bool
    {
        $this->isDeleted = false;
        return $this->save();
    }

    /**
     * Find existing assignment
     */
    public static function findAssignment(int $orgUserId, int $roleId, int $orgId): ?self
    {
        return static::where('orgUser_id', $orgUserId)
                    ->where('rbacRole_id', $roleId)
                    ->where('org_id', $orgId)
                    ->first();
    }

    /**
     * Create or restore assignment
     */
    public static function assignRole(int $orgUserId, int $roleId, int $orgId): self
    {
        $existing = static::findAssignment($orgUserId, $roleId, $orgId);

        if ($existing) {
            if ($existing->isDeleted) {
                $existing->restore();
            }
            return $existing;
        }

        return static::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'rbacRole_id' => $roleId,
            'orgUser_id' => $orgUserId,
            'isDeleted' => false,
        ]);
    }

    /**
     * Remove role assignment (soft delete)
     */
    public static function removeRole(int $orgUserId, int $roleId, int $orgId): bool
    {
        $assignment = static::findAssignment($orgUserId, $roleId, $orgId);

        if ($assignment && !$assignment->isDeleted) {
            return $assignment->softDelete();
        }

        return false;
    }
}


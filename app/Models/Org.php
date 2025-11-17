<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Org extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'org';
    protected $dateFormat = 'U';

    protected $fillable = [
        'name',
        'timezone',
        'logoFilePath',
        'settings',
        'sysCpuntry_id',
        'uuid',
    ];

    protected $casts = [
        'sysCpuntry_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // Relationships
    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'org_id');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, OrgUser::class, 'org_id', 'id', 'id', 'user_id');
    }

    public function orgPlans()
    {
        return $this->hasMany(OrgPlan::class, 'org_id');
    }

    public function orgLocations()
    {
        return $this->hasMany(OrgLocation::class, 'org_id');
    }

    public function orgDiscounts()
    {
        return $this->hasMany(Discount::class, 'org_id');
    }

    public function orgUserPlans()
    {
        return $this->hasMany(OrgUserPlan::class, 'org_id');
    }

    public function sysCountry()
    {
        return $this->belongsTo(SysCountry::class, 'sysCpuntry_id');
    }

    public function orgSettingsFeatures()
    {
        return $this->hasOne(OrgSettingsFeatures::class, 'org_id');
    }

    // RBAC Relationships
    public function rbacRoles()
    {
        return $this->hasMany(RbacRole::class, 'org_id');
    }

    public function activeRbacRoles()
    {
        return $this->rbacRoles()->where('isActive', true);
    }

    public function rbacRoleUsers()
    {
        return $this->hasMany(RbacRoleUser::class, 'org_id');
    }

    public function activeRbacRoleUsers()
    {
        return $this->rbacRoleUsers()->where('isDeleted', false);
    }

    /**
     * Generate UUID if not exists
     */
    public function generateUuidIfMissing(): void
    {
        if (empty($this->uuid)) {
            $this->uuid = \Illuminate\Support\Str::uuid()->toString();
            $this->save();
        }
    }

    /**
     * Find organization by UUID
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find organization by UUID or fail
     */
    public static function findByUuidOrFail(string $uuid): self
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Get the public registration URL for this organization
     */
    public function getPublicRegistrationUrl(): string
    {
        // Ensure UUID exists
        if (empty($this->uuid)) {
            $this->generateUuidIfMissing();
        }

        return route('register.org', $this->uuid);
    }
}
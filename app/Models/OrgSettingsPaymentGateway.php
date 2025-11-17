<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Traits\Tenantable;

/**
 * OrgSettingsPaymentGateway Model
 * 
 * Represents payment gateway settings for organizations.
 * Stores organization-specific payment gateway configurations.
 */
class OrgSettingsPaymentGateway extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'orgSettingsPaymentGateway';

    protected $fillable = [
        'org_id',
        'gateway_name',
        'gateway_type',
        'is_active',
        'settings',
        'api_key',
        'api_secret',
        'test_mode',
        'webhook_secret',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'is_active' => 'boolean',
        'test_mode' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this payment gateway setting
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Scope to get active payment gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get payment gateways by type
     */
    public function scopeByType($query, string $gatewayType)
    {
        return $query->where('gateway_type', $gatewayType);
    }

    /**
     * Scope to get payment gateways by name
     */
    public function scopeByName($query, string $gatewayName)
    {
        return $query->where('gateway_name', $gatewayName);
    }

    /**
     * Get MyFatoorah settings for an organization
     * Uses Tenantable trait for automatic org scoping
     */
    public static function getMyFatoorahForOrg(int $orgId): ?self
    {
        return static::where('gateway', 'myfatoorah')
                    ->whereNull('deleted_at')
                    ->first();
    }

    /**
     * Check if MyFatoorah is configured and active for an organization
     * Uses Tenantable trait for automatic org scoping
     */
    public static function isMyFatoorahAvailable(int $orgId): bool
    {
        return static::where('gateway', 'myfatoorah')
                    ->whereNull('deleted_at')
                    ->exists();
    }

    /**
     * Get MyFatoorah settings using raw DB query 
     * Note: Raw queries bypass Tenantable trait, so org_id filter is explicit
     */
    public static function getMyFatoorahForOrgRaw(int $orgId): ?\stdClass
    {
        return DB::table('orgSettingsPaymentGateway')
            ->where('org_id', $orgId)
            ->where('gateway', 'myfatoorah')
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Get MyFatoorah settings for current tenant (uses Tenantable)
     */
    public static function getMyFatoorahForCurrentOrg(): ?self
    {
        return static::where('gateway', 'myfatoorah')
                    ->whereNull('deleted_at')
                    ->first();
    }
}


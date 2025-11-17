<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysPaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'sysPaymentMethod';

    protected $fillable = [
        'name',
        'shortName',
        'value',
        'fa_icon',
        'description',
        'status',
        'sysCountry_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'sysCountry_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function sysCountry()
    {
        return $this->belongsTo(SysCountry::class, 'sysCountry_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('sysCountry_id');
    }

    public function scopeForCountry($query, $countryId)
    {
        return $query->where('sysCountry_id', $countryId);
    }

    // Helper methods
    public function isGlobal(): bool
    {
        return is_null($this->sysCountry_id);
    }

    public function isCountrySpecific(): bool
    {
        return !is_null($this->sysCountry_id);
    }

    /**
     * Get payment methods available for a specific country
     * Returns both global methods and country-specific methods
     */
    public static function getAvailableForCountry(?int $countryId): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->active()
            ->where(function ($query) use ($countryId) {
                $query->whereNull('sysCountry_id') // Global methods
                      ->orWhere('sysCountry_id', $countryId); // Country-specific methods
            })
            ->orderBy('id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get payment methods available for an organization
     */
    public static function getAvailableForOrg(int $orgId): \Illuminate\Database\Eloquent\Collection
    {
        $org = \App\Models\Org::find($orgId);
        $countryId = $org ? $org->sysCpuntry_id : null;
        
        return static::getAvailableForCountry($countryId);
    }
}
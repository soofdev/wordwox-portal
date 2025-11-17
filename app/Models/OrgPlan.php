<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgPlan extends BaseWWModel
{
    use HasFactory, Tenantable;

    protected $table = 'orgPlan';
    protected $dateFormat = 'U';

    // Plan types
    const TYPE_MEMBERSHIP = 1;
    const TYPE_DROPIN = 2;
    const TYPE_PT = 3;
    const TYPE_OPENGYM = 4;
    const TYPE_PROGRAM = 5;

    // Venues
    const VENUE_GEO = 1;
    const VENUE_TELE = 2;
    const VENUE_ALL = 99;

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'type',
        'venue',
        'price',
        'pricePerSession',
        'currency',
        'cycleDuration',
        'cycleUnit',
        'totalQuota',
        'dailyQuota',
        'isActive',
        'is_upcharge_plan',
        'sortOrder',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'type' => 'integer',
        'venue' => 'integer',
        'price' => 'decimal:2',
        'pricePerSession' => 'decimal:2',
        'cycleDuration' => 'integer',
        'totalQuota' => 'integer',
        'dailyQuota' => 'integer',
        'isActive' => 'boolean',
        'is_upcharge_plan' => 'boolean',
        'sortOrder' => 'integer',
    ];

    protected $attributes = [
        'isActive' => true,
        'is_upcharge_plan' => false,
        'type' => self::TYPE_MEMBERSHIP,
        'venue' => self::VENUE_GEO,
        'currency' => 'USD',
        'cycleUnit' => 'month',
        'cycleDuration' => 1,
    ];

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgUserPlans()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgPlan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeMainPlans($query)
    {
        return $query->where('is_upcharge_plan', false);
    }

    public function scopeUpchargePlans($query)
    {
        return $query->where('is_upcharge_plan', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_MEMBERSHIP => 'Group Classes',
            self::TYPE_DROPIN => 'Drop In',
            self::TYPE_PT => 'Personal Training',
            self::TYPE_OPENGYM => 'Open Gym',
            self::TYPE_PROGRAM => 'Programs',
            default => 'Other'
        };
    }

    public function getVenueLabelAttribute()
    {
        return match($this->venue) {
            self::VENUE_GEO => 'In-Person',
            self::VENUE_TELE => 'Virtual',
            self::VENUE_ALL => 'Both',
            default => 'Unknown'
        };
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getDurationTextAttribute()
    {
        $unit = $this->cycleUnit === 'day' ? 'day' : 'month';
        $plural = $this->cycleDuration > 1 ? $unit . 's' : $unit;
        return $this->cycleDuration . ' ' . $plural;
    }

    public function getCompatibleUpchargePlans()
    {
        // This would contain business logic for determining compatible upcharge plans
        // For now, return empty collection
        return collect();
    }
}
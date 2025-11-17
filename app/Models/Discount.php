<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends BaseWWModel
{
    use HasFactory, Tenantable;

    protected $table = 'orgDiscount';
    protected $dateFormat = 'U';

    // Discount units
    const UNIT_PERCENT = 'percent';
    const UNIT_FIXED = 'fixed';

    protected $fillable = [
        'uuid',
        'org_id',
        'orgLocation_id',
        'name',
        'description',
        'can_be_used_for_new_memberships',
        'can_be_used_for_renewal_memberships',
        'available_to_all_staff',
        'code',
        'value',
        'unit',
        'duration',
        'canCombine',
        'limitGender',
        'limitAmount',
        'limitUsers',
        'limitPerUser',
        'startDateTime',
        'endDateTime',
        'status',
        'isDeleted',
        'deleted_at'
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgLocation_id' => 'integer',
        'value' => 'decimal:2',
        'limitAmount' => 'decimal:2',
        'limitUsers' => 'integer',
        'limitPerUser' => 'integer',
        'startDateTime' => 'datetime',
        'endDateTime' => 'datetime',
        'status' => 'integer',
        'canCombine' => 'boolean',
        'can_be_used_for_new_memberships' => 'boolean',
        'can_be_used_for_renewal_memberships' => 'boolean',
        'available_to_all_staff' => 'boolean',
        'isDeleted' => 'boolean',
    ];

    protected $attributes = [
        'status' => 1, // Active status
        'unit' => self::UNIT_PERCENT,
        'can_be_used_for_new_memberships' => true,
        'can_be_used_for_renewal_memberships' => true,
        'available_to_all_staff' => true,
        'canCombine' => false,
        'isDeleted' => false,
    ];

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgUserPlans()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgDiscount_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('startDateTime')
              ->orWhere('startDateTime', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('endDateTime')
              ->orWhere('endDateTime', '>=', $now);
        });
    }

    public function scopeForNewMemberships($query)
    {
        return $query->where('can_be_used_for_new_memberships', true);
    }

    public function scopeAvailableToAllStaff($query)
    {
        return $query->where('available_to_all_staff', true);
    }

    // Helper methods
    public function getFormattedValueAttribute()
    {
        if ($this->unit === self::UNIT_PERCENT) {
            return $this->value . '%';
        } else {
            return '$' . number_format($this->value, 2);
        }
    }

    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->formatted_value . ')';
    }

    public function calculateDiscount($amount)
    {
        if ($this->unit === self::UNIT_PERCENT) {
            return $amount * ($this->value / 100);
        } else {
            return $this->value;
        }
    }

    public function isValidForAmount($amount)
    {
        if ($this->limitAmount && $amount < $this->limitAmount) {
            return false;
        }
        
        return true;
    }

    public function isCurrentlyValid()
    {
        $now = now();
        
        if ($this->startDateTime && $now->lt($this->startDateTime)) {
            return false;
        }
        
        if ($this->endDateTime && $now->gt($this->endDateTime)) {
            return false;
        }
        
        if ($this->limitUsers && $this->getCurrentUsageCount() >= $this->limitUsers) {
            return false;
        }
        
        return $this->status === 1;
    }

    public function getCurrentUsageCount()
    {
        // This would count current usage from orgUserPlan table
        return $this->orgUserPlans()->count();
    }

    public function canBeUsedForNewMemberships()
    {
        return $this->can_be_used_for_new_memberships && $this->isCurrentlyValid();
    }
}
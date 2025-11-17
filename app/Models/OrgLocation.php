<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgLocation extends BaseWWModel
{
    use HasFactory, Tenantable;

    protected $table = 'orgLocation';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'zipCode',
        'phoneNumber',
        'email',
        'timezone',
        'isActive',
        'isDefault',
        'sortOrder',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'isActive' => 'boolean',
        'isDefault' => 'boolean',
        'sortOrder' => 'integer',
    ];

    protected $attributes = [
        'isActive' => true,
        'isDefault' => false,
        'country' => 'US',
    ];

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgUserPlans()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgLocation_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('isDefault', true);
    }

    // Helper methods
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zipCode,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    public function getDisplayNameAttribute()
    {
        return $this->name . ($this->city ? ' (' . $this->city . ')' : '');
    }
}
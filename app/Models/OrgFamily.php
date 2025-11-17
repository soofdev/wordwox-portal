<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgFamily extends BaseWWModel
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'orgFamily';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id',
        'name',
    ];

    protected $casts = [
        'org_id' => 'integer',
    ];

    /**
     * Get the organization that owns this family
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get all family members
     */
    public function familyUsers(): HasMany
    {
        return $this->hasMany(OrgFamilyUser::class, 'orgFamily_id');
    }

    /**
     * Get all family members with their user data
     */
    public function members()
    {
        return $this->familyUsers()->with('orgUser');
    }

    /**
     * Get family parents
     */
    public function parents()
    {
        return $this->familyUsers()->where('level', 'parent')->with('orgUser');
    }

    /**
     * Get family children
     */
    public function children()
    {
        return $this->familyUsers()->where('level', 'child')->with('orgUser');
    }

}

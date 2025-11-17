<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'program';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'type',
        'cycleType',
        'cycleDays',
        'org_id',
        'orgLocation_id',
        'name',
        'description',
        'color',
        'isFoundation',
        'isFoundationRequired',
        'subscriberDailyLimitEnabled',
        'subscriberDailyLimit',
        'subscriberLateCancelWindow',
        'subscriberLateCancel',
        'isActive',
        'isDeleted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'cycleType' => 'integer',
        'cycleDays' => 'integer',
        'org_id' => 'integer',
        'orgLocation_id' => 'integer',
        'isFoundation' => 'boolean',
        'isFoundationRequired' => 'boolean',
        'subscriberDailyLimitEnabled' => 'boolean',
        'subscriberDailyLimit' => 'integer',
        'subscriberLateCancelWindow' => 'integer',
        'subscriberLateCancel' => 'integer',
        'isActive' => 'boolean',
        'isDeleted' => 'boolean',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'deleted_at' => 'timestamp',
    ];

    /**
     * Indicates if the model should use timestamps.
     * Using custom timestamp handling since they're stored as integers.
     */
    public $timestamps = false;

    /**
     * Get the organization that owns this program.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the organization location where this program is offered.
     */
    public function orgLocation()
    {
        return $this->belongsTo(OrgLocation::class, 'orgLocation_id');
    }

    /**
     * Get the events associated with this program.
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'program_id');
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to exclude soft deleted records.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('isDeleted', false);
    }

    /**
     * Scope to get only active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }
}

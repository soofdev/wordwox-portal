<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'event';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'org_id',
        'orgLocation_id',
        'program_id',
        'room_id',
        'schedule_id',
        'ptSchedule_id',
        'type',
        'name',
        'note',
        'startDateTimeLoc',
        'endDateTimeLoc',
        'timezone_long',
        'timezone_offset',
        'startDateTime',
        'endDateTime',
        'reservation',
        'capacity',
        'waitListMode',
        'dropIn',
        'dropInPublic',
        'dropInPublicPayment',
        'skillLevel',
        'venue',
        'status',
        'isActive',
        'isCanceled',
        'isDeleted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'org_id' => 'integer',
        'orgLocation_id' => 'integer',
        'program_id' => 'integer',
        'room_id' => 'integer',
        'schedule_id' => 'integer',
        'ptSchedule_id' => 'integer',
        'reservationRequiresActivePlan' => 'boolean',
        'reservationCountsTowardsQuota' => 'boolean',
        'quotaMultiple' => 'float',
        'startDateTimeLoc' => 'datetime',
        'endDateTimeLoc' => 'datetime',
        'startDateTime' => 'datetime',
        'endDateTime' => 'datetime',
        'reservation' => 'boolean',
        'capacity' => 'integer',
        'waitListMode' => 'integer',
        'dropIn' => 'integer',
        'dropInPublic' => 'boolean',
        'dropInPublicPayment' => 'boolean',
        'skillLevel' => 'integer',
        'venue' => 'integer',
        'assignmentSignIn' => 'boolean',
        'subscriberSignIn' => 'boolean',
        'status' => 'integer',
        'isActive' => 'boolean',
        'isCanceled' => 'boolean',
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
     * Get the organization that owns this event.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the program associated with this event.
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Get the organization location where this event takes place.
     */
    public function orgLocation()
    {
        return $this->belongsTo(OrgLocation::class, 'orgLocation_id');
    }

    /**
     * Get the formatted class name (start time - program name).
     */
    public function getFormattedClassNameAttribute()
    {
        $startTime = '';
        if ($this->startDateTimeLoc) {
            $startTime = $this->startDateTimeLoc->format('g:i A');
        }
        
        $programName = $this->program?->name ?? 'Unknown Program';
        
        return $startTime ? "{$startTime} - {$programName}" : $programName;
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
     * Scope to get only active events.
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }
}

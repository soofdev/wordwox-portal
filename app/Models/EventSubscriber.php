<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

  // Booking statuses
const STATUS_BOOKED = 1;
const STATUS_CHECKED_IN = 2;
const STATUS_COMPLETED = 3;
const STATUS_CANCELLED = 4;
const STATUS_NO_SHOW = 5;
  
class EventSubscriber extends Model
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'eventSubscriber';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'org_id',
        'program_id',
        'schedule_id',
        'by_orgUser_id',
        'orgUser_id',
        'orgUserPlan_id',
        'orgPlan_id',
        'user_id',
        'event_id',
        'event_startDateTime',
        'event_endDateTime',
        'status',
        'type',
        'reservationRequiresActivePlan',
        'reservationCountsTowardsQuota',
        'quotaMultiple',
        'fullName',
        'email',
        'phoneCountry',
        'phoneNumber',
        'cancellation',
        'invoiceTotal',
        'invoiceTotalPaid',
        'invoiceCurrency',
        'invoiceStatus',
        'invoiceDue',
        'invoiceMethod',
        'invoiceReceipt',
        'isCanceled',
        'isDeleted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'org_id' => 'integer',
        'program_id' => 'integer',
        'schedule_id' => 'integer',
        'by_orgUser_id' => 'integer',
        'orgUser_id' => 'integer',
        'orgPlan_id' => 'integer',
        'user_id' => 'integer',
        'event_id' => 'integer',
        'event_startDateTime' => 'datetime',
        'event_endDateTime' => 'datetime',
        'status' => 'integer',
        'type' => 'integer',
        'reservationRequiresActivePlan' => 'boolean',
        'reservationCountsTowardsQuota' => 'float',
        'quotaMultiple' => 'float',
        'phoneCountry' => 'integer',
        'phoneNumber' => 'integer',
        'eventReminderEmail' => 'datetime',
        'eventReminderCall' => 'datetime',
        'teleEventLinkEmail' => 'integer',
        'workoutEmailSent' => 'integer',
        'workoutPushSent' => 'integer',
        'cancellation' => 'integer',
        'invoiceTotal' => 'decimal:4',
        'invoiceTotalPaid' => 'decimal:4',
        'invoiceStatus' => 'integer',
        'invoiceDue' => 'datetime',
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
     * Get the organization that owns this event subscriber.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgEvent()
    {
        return $this->belongsTo(OrgEvent::class, 'orgEvent_id');
    }
    /**
     * Get the organization user that this subscription belongs to.
     */
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }


    public function orgUserPlan()
    {
        return $this->belongsTo(OrgUserPlan::class, 'orgUserPlan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(OrgUser::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('booking_status', self::STATUS_BOOKED)
                    ->where('isDeleted', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('booking_status', self::STATUS_COMPLETED);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereHas('orgEvent', function($q) {
            $q->where('start_datetime', '>', now());
        })->where('booking_status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('booking_status', self::STATUS_CHECKED_IN);
    }

    // Helper methods
    public function getStatusLabelAttribute()
    {
        return match($this->booking_status) {
            self::STATUS_BOOKED => 'Booked',
            self::STATUS_CHECKED_IN => 'Checked In',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_NO_SHOW => 'No Show',
            default => 'Unknown'
        };
    }

    public function isUpcoming()
    {
        return $this->orgEvent && $this->orgEvent->isUpcoming() &&
               $this->booking_status != self::STATUS_CANCELLED;
    }

    public function isCompleted()
    {
        return $this->booking_status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->booking_status === self::STATUS_CANCELLED;
    }

    public function isCheckedIn()
    {
        return $this->booking_status === self::STATUS_CHECKED_IN;
    }

    /**
     * Get the user associated with this subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the event associated with this subscription.
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get the program associated with this subscription.
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Get the formatted class name (start time - program name).
     */
    public function getFormattedClassNameAttribute()
    {
        $startTime = '';
        if ($this->event_startDateTime) {
            $startTime = $this->event_startDateTime->format('g:i A');
        }
        
        $programName = $this->program?->name ?? ($this->event?->program?->name ?? 'Unknown Program');
        
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
}

<?php

namespace App\Models;

use App\Traits\Tenantable;
use App\Services\Yii2QueueDispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrgUserPlanHold;

class OrgUserPlan extends BaseWWModel
{
    use HasFactory, Tenantable;

    protected $table = 'orgUserPlan';
    // Note: No dateFormat override - use BaseWWModel's Unix timestamps for created_at/updated_at

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

    // Invoice statuses
    const INVOICE_STATUS_PENDING = 1;
    const INVOICE_STATUS_PAID = 2;
    const INVOICE_STATUS_FREE = 7;

    // Plan statuses (matching core system)
    const STATUS_NONE = 0;
    const STATUS_UPCOMING = 1;
    const STATUS_ACTIVE = 2;        // Active status - must match core system
    const STATUS_HOLD = 3;
    const STATUS_CANCELED = 4;
    const STATUS_DELETED = 5;
    const STATUS_EXPIRED_LIMIT = 98;
    const STATUS_EXPIRED = 99;

    protected $fillable = [
        'uuid',
        'org_id',
        'orgLocation_id',
        'orgUser_id',
        'orgPlan_id',
        'orgDiscount_id',
        'name',
        'type',
        'venue',
        'price',
        'pricePerSession',
        'currency',
        'startDate',
        'startDateLoc',
        'endDate',
        'endDateLoc',
        'durationDays',
        'totalQuota',
        'totalQuotaConsumed',
        'dailyQuota',
        'orgDiscount_value',
        'orgDiscount_unit',
        'invoiceTotal',
        'invoiceTotalPaid',
        'invoiceCurrency',
        'invoiceStatus',
        'invoiceDue',
        'invoiceMethod',
        'invoiceReceipt',
        'note',
        'status',
        'sold_by',
        'sold_in',
        'created_by',
        'isCanceled',
        'isDeleted',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgLocation_id' => 'integer',
        'orgUser_id' => 'integer',
        'orgPlan_id' => 'integer',
        'orgDiscount_id' => 'integer',
        'type' => 'integer',
        'venue' => 'integer',
        'price' => 'decimal:2',
        'pricePerSession' => 'decimal:2',
        // Date fields commented out to match wodworx-core behavior - handled as raw values
        //'startDate' => 'datetime',
        //'startDateLoc' => 'date',
        //'endDate' => 'datetime',
        //'endDateLoc' => 'date',
        'durationDays' => 'integer',
        'totalQuota' => 'integer',
        'totalQuotaConsumed' => 'integer',
        'dailyQuota' => 'integer',
        'orgDiscount_value' => 'decimal:2',
        'invoiceTotal' => 'decimal:2',
        'invoiceTotalPaid' => 'decimal:2',
        'invoiceStatus' => 'integer',
        'invoiceDue' => 'datetime:Y-m-d',
        'status' => 'integer',
        'sold_by' => 'integer',
        'sold_in' => 'integer',
        'created_by' => 'integer',
        'isCanceled' => 'boolean',
        'isDeleted' => 'boolean',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'isCanceled' => false,
        'isDeleted' => false,
        'totalQuotaConsumed' => 0,
        'invoiceStatus' => self::INVOICE_STATUS_PAID,
        'currency' => 'USD',
    ];

    // No custom date handling - inherit BaseWWModel behavior
    // created_at/updated_at use Unix timestamps, other dates are raw values

    protected static function booted()
    {
        static::created(function ($model) {
            // Dispatch Yii2 queue job for post-creation processing
            $dispatcher = new Yii2QueueDispatcher();
            $dispatcher->dispatch('common\jobs\plan\OrgUserPlanCreatedJob', ['id' => $model->id]);
        });

        static::updated(function ($model) {
            // Dispatch appropriate Yii2 queue job based on what was updated
            // But only if the job exists in the wod-worx project
            $dispatcher = new Yii2QueueDispatcher();

            if ($model->isDirty('isCanceled') && $model->isCanceled) {
                // Check if OrgUserPlanCanceledJob exists before dispatching
                if (self::jobExistsInWodWorx('common\jobs\plan\OrgUserPlanCanceledJob')) {
                    $dispatcher->dispatch('common\jobs\plan\OrgUserPlanCanceledJob', ['id' => $model->id]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Job not found in wod-worx: common\jobs\plan\OrgUserPlanCanceledJob');
                }
            } elseif ($model->isDirty('isCanceled') && !$model->isCanceled) {
                // Check if OrgUserPlanUnCancelCompleteJob exists before dispatching (restoration)
                if (self::jobExistsInWodWorx('common\jobs\plan\OrgUserPlanUnCancelCompleteJob')) {
                    $dispatcher->dispatch('common\jobs\plan\OrgUserPlanUnCancelCompleteJob', ['id' => $model->id]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Job not found in wod-worx: common\jobs\plan\OrgUserPlanUnCancelCompleteJob');
                }
            } else {
                // Check if OrgUserPlanUpdateCompleteJob exists before dispatching
                if (self::jobExistsInWodWorx('common\jobs\plan\OrgUserPlanUpdateCompleteJob')) {
                    $dispatcher->dispatch('common\jobs\plan\OrgUserPlanUpdateCompleteJob', ['id' => $model->id]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Job not found in wod-worx: common\jobs\plan\OrgUserPlanUpdateCompleteJob');
                }
            }
        });
    }

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    public function orgPlan()
    {
        return $this->belongsTo(OrgPlan::class, 'orgPlan_id');
    }

    public function orgLocation()
    {
        return $this->belongsTo(OrgLocation::class, 'orgLocation_id');
    }

    public function orgDiscount()
    {
        return $this->belongsTo(Discount::class, 'orgDiscount_id');
    }

    public function soldBy()
    {
        return $this->belongsTo(OrgUser::class, 'sold_by');
    }

    public function soldIn()
    {
        return $this->belongsTo(OrgLocation::class, 'sold_in');
    }

    public function createdBy()
    {
        return $this->belongsTo(OrgUser::class, 'created_by');
    }

    // Booking relationships
    public function bookings()
    {
        return $this->hasManyThrough(
            OrgEvent::class,
            EventSubscriber::class,
            'orgUserPlan_id', // Foreign key on EventSubscriber
            'id',             // Foreign key on OrgEvent
            'id',             // Local key on OrgUserPlan
            'orgEvent_id'     // Local key on EventSubscriber
        );
    }

    public function eventSubscribers()
    {
        return $this->hasMany(EventSubscriber::class, 'orgUserPlan_id');
    }

    public function activeBookings()
    {
        return $this->hasManyThrough(
            OrgEvent::class,
            EventSubscriber::class,
            'orgUserPlan_id',
            'id',
            'id',
            'orgEvent_id'
        )->where('eventSubscriber.booking_status', EventSubscriber::STATUS_BOOKED)
         ->where('eventSubscriber.isDeleted', false);
    }

    public function completedBookings()
    {
        return $this->hasManyThrough(
            OrgEvent::class,
            EventSubscriber::class,
            'orgUserPlan_id',
            'id',
            'id',
            'orgEvent_id'
        )->where('eventSubscriber.booking_status', EventSubscriber::STATUS_COMPLETED);
    }

    public function upcomingBookings()
    {
        return $this->hasManyThrough(
            OrgEvent::class,
            EventSubscriber::class,
            'orgUserPlan_id',
            'id',
            'id',
            'orgEvent_id'
        )->where('orgEvent.start_datetime', '>', now())
         ->where('eventSubscriber.booking_status', '!=', EventSubscriber::STATUS_CANCELLED)
         ->where('eventSubscriber.isDeleted', false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('isDeleted', false);
    }

    public function scopeCanceled($query)
    {
        return $query->where('isCanceled', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpired($query)
    {
        return $query->where('endDateLoc', '<', now()->format('Y-m-d'));
    }

    public function scopeUpcoming($query)
    {
        return $query->where('startDateLoc', '>', now()->format('Y-m-d'));
    }

    public function scopeCurrent($query)
    {
        $today = now()->format('Y-m-d');
        return $query->where('startDateLoc', '<=', $today)
                    ->where('endDateLoc', '>=', $today);
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

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_NONE => 'None',
            self::STATUS_UPCOMING => 'Upcoming',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_HOLD => 'Hold',
            self::STATUS_CANCELED => 'Canceled',
            self::STATUS_DELETED => 'Deleted',
            self::STATUS_EXPIRED_LIMIT => 'Expired (Limit)',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown'
        };
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getFormattedInvoiceTotalAttribute()
    {
        return number_format($this->invoiceTotal, 2) . ' ' . $this->currency;
    }

    public function getRemainingQuotaAttribute()
    {
        if (!$this->totalQuota) {
            return null;
        }
        return max(0, $this->totalQuota - $this->totalQuotaConsumed);
    }

    public function getQuotaUsagePercentAttribute()
    {
        if (!$this->totalQuota) {
            return 0;
        }
        return min(100, ($this->totalQuotaConsumed / $this->totalQuota) * 100);
    }

    public function isExpired()
    {
        return $this->endDateLoc && $this->endDateLoc < now()->format('Y-m-d');
    }


    public function isUpcoming()
    {
        return $this->startDateLoc > now()->format('Y-m-d');
    }

    public function isCurrent()
    {
        $today = now()->format('Y-m-d');
        return $this->startDateLoc <= $today && $this->endDateLoc >= $today;
    }

    public function hasQuotaRemaining()
    {
        if (!$this->totalQuota) {
            return true; // Unlimited
        }
        return $this->totalQuotaConsumed < $this->totalQuota;
    }

    public function canBeUsed()
    {
        return $this->isActive &&
               !$this->isCanceled &&
               $this->isCurrent() &&
               $this->hasQuotaRemaining();
    }

    /**
     * Get the remaining days in the membership period from today
     *
     * @return int|null Number of days remaining, null if no end date
     */
    public function getRemainingDaysInPeriod()
    {
        if (!$this->endDateLoc) {
            return null;
        }

        $today = now()->startOfDay();
        $endDate = \Carbon\Carbon::parse($this->endDateLoc)->startOfDay();

        // If the membership has already expired, return 0
        if ($endDate->isPast()) {
            return 0;
        }

        // Use diffInDays with absolute value and cast to integer to ensure whole number
        return (int) $today->diffInDays($endDate, false);
    }

    /**
     * Get the total duration of the membership in days
     *
     * @return int|null Total days in the membership period
     */
    public function getTotalDurationDays()
    {
        if (!$this->startDateLoc || !$this->endDateLoc) {
            return null;
        }

        $startDate = \Carbon\Carbon::parse($this->startDateLoc)->startOfDay();
        $endDate = \Carbon\Carbon::parse($this->endDateLoc)->startOfDay();

        return $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
    }

    /**
     * Check if this membership can be modified
     * 
     * @return bool
     */
    public function getCanBeModifiedAttribute(): bool
    {
        // Don't allow modification if membership is deleted or canceled
        if ($this->isDeleted || $this->isCanceled) {
            return false;
        }
        
        // Don't allow modification if status is deleted
        if ($this->status === self::STATUS_DELETED) {
            return false;
        }
        
        // Allow modification for all other cases
        return true;
    }

    /**
     * Check if membership can be reinstated (matching Core system)
     * 
     * @return bool
     */
    public function getCanBeReinstatedAttribute(): bool
    {
        return $this->isCanceled && !$this->isDeleted;
    }

    /**
     * Check if membership can be transferred (matching Core system logic)
     */
    public function getCanBeTransferredAttribute(): bool
    {
        // Cannot transfer if membership is null
        if (!$this->exists) {
            return false;
        }

        // Cannot transfer cancelled memberships
        if ($this->isCanceled || $this->status === self::STATUS_CANCELED) {
            return false;
        }

        // Cannot transfer deleted memberships
        if ($this->isDeleted || $this->status === self::STATUS_DELETED) {
            return false;
        }

        // Cannot transfer expired memberships
        if (in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_EXPIRED_LIMIT])) {
            return false;
        }

        // Cannot transfer memberships on hold
        if ($this->status === self::STATUS_HOLD) {
            return false;
        }

        // Can only transfer active or upcoming memberships
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_UPCOMING])) {
            return false;
        }

        return true;
    }

    /**
     * Check if membership can be upgraded (matching Core system logic)
     */
    public function getCanBeUpgradedAttribute(): bool
    {
        // Cannot upgrade if membership is null
        if (!$this->exists) {
            return false;
        }

        // Cannot upgrade cancelled memberships
        if ($this->isCanceled || $this->status === self::STATUS_CANCELED) {
            return false;
        }

        // Cannot upgrade deleted memberships
        if ($this->isDeleted || $this->status === self::STATUS_DELETED) {
            return false;
        }

        // Cannot upgrade expired memberships
        if (in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_EXPIRED_LIMIT])) {
            return false;
        }

        // Cannot upgrade memberships on hold
        if ($this->status === self::STATUS_HOLD) {
            return false;
        }

        // Can only upgrade active or upcoming memberships
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_UPCOMING])) {
            return false;
        }

        return true;
    }

    public function getDurationDaysAttribute()
    {
        if (!$this->startDateLoc || !$this->endDateLoc) {
            return null;
        }

        $startDate = \Carbon\Carbon::parse($this->startDateLoc)->startOfDay();
        $endDate = \Carbon\Carbon::parse($this->endDateLoc)->startOfDay();

        return $startDate->diffInDays($endDate);
    }

    /**
     * Get the holds for this membership
     */
    public function orgUserPlanHolds()
    {
        return $this->hasMany(OrgUserPlanHold::class, 'orgUserPlan_id');
    }

    public function getPricePerSessionAttribute()
    {
        if (!$this->totalQuota || $this->totalQuota == 0 || !$this->price) {
            return 0;
        }
        return round($this->price / $this->totalQuota, 2);
    }

    /**
     * Check if a Yii2 job exists in the wod-worx project before dispatching
     *
     * @param string $jobClass The job class name (e.g., 'common\jobs\plan\OrgUserPlanCanceledJob')
     * @return bool
     */
    protected static function jobExistsInWodWorx(string $jobClass): bool
    {
        // Convert namespace to file path
        $filePath = str_replace('\\', '/', $jobClass) . '.php';
        $fullPath = '/Users/macbook1993/wod-worx/' . $filePath;
        
        $exists = file_exists($fullPath);
        
        \Illuminate\Support\Facades\Log::info("Checking job existence: {$jobClass}", [
            'file_path' => $fullPath,
            'exists' => $exists
        ]);
        
        return $exists;
    }

}

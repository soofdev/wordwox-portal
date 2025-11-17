<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\OrgUserPlanHoldStatus;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrgUserPlanHold extends BaseWWModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'orgUserPlanHold';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     * Inherits from BaseWWModel which has timestamps = true
     */

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'org_id',
        'orgUser_id',
        'byOrgUser_id',
        'orgUserPlan_id',
        'startDateTime',
        'endDateTime',
        'note',
        'groupName',
        'notifyEmail',
        'notifyPush',
        'status',
        'isCanceled',
        'preBookingBehaviorOnHoldStart',
        'preBookingBehaviorOnHoldEnd',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        // Datetime casts removed due to conflict with BaseWWModel
        // 'startDateTime' => 'datetime',
        // 'endDateTime' => 'datetime',
        'notifyEmail' => 'boolean',
        'notifyPush' => 'boolean',
        'isCanceled' => 'boolean',
        'status' => OrgUserPlanHoldStatus::class,
        // created_at and updated_at are handled by BaseWWModel
    ];

    /**
     * Override boot method to prevent BaseWWModel from interfering with datetime attributes
     */
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         // Set org_id only if not already set and if we have an authenticated user
    //         if (empty($model->org_id) && auth()->check() && auth()->user() && auth()->user()->orgUser) {
    //             $model->org_id = auth()->user()->orgUser->org_id;
    //         }

    //         // Set uuid only if not already set
    //         if (empty($model->uuid)) {
    //             $model->uuid = Str::uuid();
    //         }
    //     });
    // }

    /**
     * Get the organization that owns the hold.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the user that owns the hold.
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the user who created the hold.
     */
    public function byOrgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'byOrgUser_id');
    }

    /**
     * Get the membership plan that this hold belongs to.
     */
    public function orgUserPlan(): BelongsTo
    {
        return $this->belongsTo(OrgUserPlan::class, 'orgUserPlan_id');
    }

    /**
     * Check if the hold is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === OrgUserPlanHoldStatus::Active && !$this->isCanceled;
    }

    /**
     * Check if the hold is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->status === OrgUserPlanHoldStatus::Upcoming && !$this->isCanceled;
    }

    /**
     * Check if the hold is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === OrgUserPlanHoldStatus::Expired) {
            return true;
        }
        
        if ($this->endDateTime) {
            $endDate = is_string($this->endDateTime) ? \Carbon\Carbon::parse($this->endDateTime) : $this->endDateTime;
            return $endDate->isPast();
        }
        
        return false;
    }

    /**
     * Check if the hold is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->isCanceled || $this->status === OrgUserPlanHoldStatus::Canceled;
    }

    /**
     * Get the duration of the hold in days.
     */
    public function getDurationInDays(): int
    {
        if (!$this->startDateTime || !$this->endDateTime) {
            return 0;
        }

        $startDate = is_string($this->startDateTime) ? \Carbon\Carbon::parse($this->startDateTime) : $this->startDateTime;
        $endDate = is_string($this->endDateTime) ? \Carbon\Carbon::parse($this->endDateTime) : $this->endDateTime;
        
        return $startDate->diffInDays($endDate);
    }

    /**
     * Get the remaining days of the hold.
     */
    public function getRemainingDays(): int
    {
        if (!$this->endDateTime || $this->isExpired()) {
            return 0;
        }

        $endDate = is_string($this->endDateTime) ? \Carbon\Carbon::parse($this->endDateTime) : $this->endDateTime;
        return max(0, now()->diffInDays($endDate, false));
    }

    /**
     * Scope to get active holds.
     */
    public function scopeActive($query)
    {
        return $query->where('status', OrgUserPlanHoldStatus::Active)
                    ->where('isCanceled', false);
    }

    /**
     * Scope to get upcoming holds.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', OrgUserPlanHoldStatus::Upcoming)
                    ->where('isCanceled', false);
    }

    /**
     * Scope to get expired holds.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', OrgUserPlanHoldStatus::Expired)
                    ->orWhere(function($q) {
                        $q->where('endDateTime', '<', now())
                          ->where('status', '!=', OrgUserPlanHoldStatus::Canceled);
                    });
    }

    /**
     * Scope to get canceled holds.
     */
    public function scopeCanceled($query)
    {
        return $query->where('isCanceled', true)
                    ->orWhere('status', OrgUserPlanHoldStatus::Canceled);
    }

    /**
     * Scope to get non-canceled holds.
     */
    public function scopeNotCanceled($query)
    {
        return $query->where('isCanceled', false)
                    ->where('status', '!=', OrgUserPlanHoldStatus::Canceled);
    }
}

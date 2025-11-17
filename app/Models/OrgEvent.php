<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrgEvent extends Model
{
    use HasFactory;

    protected $table = 'orgEvent';

    protected $fillable = [
        'org_id',
        'orgLocation_id',
        'name',
        'description',
        'event_type',
        'start_datetime',
        'end_datetime',
        'capacity',
        'status',
        'instructor_id',
        'is_recurring',
        'price',
        'currency',
        'created_by',
        'isDeleted'
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgLocation_id' => 'integer',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'capacity' => 'integer',
        'status' => 'integer',
        'instructor_id' => 'integer',
        'is_recurring' => 'boolean',
        'price' => 'decimal:2',
        'created_by' => 'integer',
        'isDeleted' => 'boolean'
    ];

    // Event types
    const TYPE_CLASS = 1;
    const TYPE_SESSION = 2;
    const TYPE_APPOINTMENT = 3;
    const TYPE_WORKSHOP = 4;

    // Event statuses
    const STATUS_SCHEDULED = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function orgLocation()
    {
        return $this->belongsTo(OrgLocation::class, 'orgLocation_id');
    }

    public function instructor()
    {
        return $this->belongsTo(OrgUser::class, 'instructor_id');
    }

    public function subscribers()
    {
        return $this->hasMany(EventSubscriber::class, 'orgEvent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(OrgUser::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('isDeleted', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now())
                    ->where('status', '!=', self::STATUS_CANCELLED);
    }

    // Helper methods
    public function getTypeLabelAttribute()
    {
        return match($this->event_type) {
            self::TYPE_CLASS => 'Class',
            self::TYPE_SESSION => 'Session',
            self::TYPE_APPOINTMENT => 'Appointment',
            self::TYPE_WORKSHOP => 'Workshop',
            default => 'Event'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown'
        };
    }

    public function isUpcoming()
    {
        return $this->start_datetime > now() && $this->status != self::STATUS_CANCELLED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}

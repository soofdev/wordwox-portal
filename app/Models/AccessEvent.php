<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AccessEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'accessEvent';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'org_id',
        'orgUser_id',
        'uuid',
        'accessPoint',
        'accessDevice_uuid',
        'accessDevice_id',
        'accessDoor_uuid',
        'accessDoor_id',
        'accessDoor_number',
        'orgUserAccessKey_id', // Updated column name
        'accessKeySystem',
        'accessKeyType',
        'accessKeyID',
        'eventID',
        'eventType',
        'eventDetails',
        'eventTimestamp',
        'prev_access_timestamp',
        'checked_in_by', // New column for tracking who performed the check-in
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'org_id' => 'integer',
        'orgUser_id' => 'integer',
        'accessDevice_id' => 'integer',
        'accessDoor_id' => 'integer',
        'orgUserAccessKey_id' => 'integer', // Updated cast
        'eventTimestamp' => 'integer',
        'prev_access_timestamp' => 'timestamp',
        'checked_in_by' => 'integer', // Cast as integer (orgUser ID)
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
     * Get the organization that owns this access event.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the organization user that this access event belongs to.
     */
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the access key associated with this access event.
     */
    public function orgUserAccessKey()
    {
        return $this->belongsTo(OrgUserAccessKey::class, 'orgUserAccessKey_id');
    }

    /**
     * Get the organization user who performed the check-in.
     */
    public function checkedInBy()
    {
        return $this->belongsTo(OrgUser::class, 'checked_in_by');
    }

    /**
     * Get the formatted event timestamp.
     */
    public function getFormattedEventTimestampAttribute()
    {
        return $this->eventTimestamp ? date('Y-m-d H:i:s', $this->eventTimestamp) : null;
    }

    /**
     * Get the formatted created at timestamp.
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? date('Y-m-d H:i:s', $this->created_at) : null;
    }

    /**
     * Get the formatted updated at timestamp.
     */
    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? date('Y-m-d H:i:s', $this->updated_at) : null;
    }

    /**
     * Get the formatted event date.
     */
    public function getFormattedEventDateAttribute()
    {
        return $this->eventTimestamp ? Carbon::createFromTimestamp($this->eventTimestamp)->format('Y-m-d') : null;
    }

    /**
     * Get the formatted event time.
     */
    public function getFormattedEventTimeAttribute()
    {
        return $this->eventTimestamp ? Carbon::createFromTimestamp($this->eventTimestamp)->format('g:i A') : null;
    }

    /**
     * Get the formatted event date and time.
     */
    public function getFormattedEventDateTimeAttribute()
    {
        return $this->eventTimestamp ? Carbon::createFromTimestamp($this->eventTimestamp)->format('M j, Y g:i A') : null;
    }

    /**
     * Check if the event happened today.
     */
    public function getIstodayAttribute()
    {
        if (!$this->eventTimestamp) {
            return false;
        }

        return Carbon::createFromTimestamp($this->eventTimestamp)->isToday();
    }

    /**
     * Get human readable time difference.
     */
    public function getTimeAgoAttribute()
    {
        return $this->eventTimestamp ? Carbon::createFromTimestamp($this->eventTimestamp)->diffForHumans() : null;
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to filter by organization user.
     */
    public function scopeForOrgUser($query, $orgUserId)
    {
        return $query->where('orgUser_id', $orgUserId);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('eventType', $eventType);
    }

    /**
     * Scope to filter by access point.
     */
    public function scopeByAccessPoint($query, $accessPoint)
    {
        return $query->where('accessPoint', $accessPoint);
    }

    /**
     * Scope to filter by date range using eventTimestamp.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        return $query->whereBetween('eventTimestamp', [$startTimestamp, $endTimestamp]);
    }

    /**
     * Scope to get recent access events.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('eventTimestamp', 'desc')->limit($limit);
    }

    /**
     * Scope to get today's check-ins for an organization.
     */
    public function scopeTodaysCheckIns($query, $orgId)
    {
        $todayStart = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');

        return $query->where('org_id', $orgId)
                    ->where('eventType', 'check_in')
                    ->whereBetween('eventTimestamp', [$todayStart, $todayEnd]);
    }

    /**
     * Scope to get events for today.
     */
    public function scopeToday($query, $orgId = null)
    {
        $todayStart = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');

        $query = $query->whereBetween('eventTimestamp', [$todayStart, $todayEnd]);

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query;
    }

    /**
     * Scope to get check-ins for today.
     */
    public function scopeTodaysCheckInsOnly($query, $orgId = null)
    {
        return $this->scopeToday($query, $orgId)->where('eventType', 'check_in');
    }

    /**
     * Scope to get check-ins for a specific date.
     */
    public function scopeCheckInsForDate($query, $orgId, $date)
    {
        $startOfDay = strtotime($date . ' 00:00:00');
        $endOfDay = strtotime($date . ' 23:59:59');

        return $query->where('org_id', $orgId)
                    ->where('eventType', 'check_in')
                    ->whereBetween('eventTimestamp', [$startOfDay, $endOfDay]);
    }

    /**
     * Override the save method to handle custom timestamps.
     */
    public function save(array $options = [])
    {
        $now = time();

        if (!$this->exists) {
            $this->created_at = $now;
        }

        $this->updated_at = $now;

        return parent::save($options);
    }

    /**
     * Create a new access event record with current timestamp.
     */
    public static function createEvent(array $attributes)
    {
        $attributes['uuid'] = $attributes['uuid'] ?? \Str::uuid();
        $attributes['eventTimestamp'] = $attributes['eventTimestamp'] ?? time();

        return static::create($attributes);
    }


}

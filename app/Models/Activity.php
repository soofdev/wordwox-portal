<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Activity extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'activity';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'org_id',
        'type',
        'user_id',
        'orgUser_id',
        'object_id',
        'isDeleted',
        'activity_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'org_id' => 'integer',
        'type' => 'integer',
        'user_id' => 'integer',
        'orgUser_id' => 'integer',
        'object_id' => 'integer',
        'isDeleted' => 'boolean',
        'activity_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * Indicates if the model should use timestamps.
     * Using custom timestamp handling since they're stored as integers.
     */
    public $timestamps = false;

    // Activity Type Constants from Yii2 implementation
    
    // User Account Activities (100-199)
    const TYPE_ORG_USER_SIGNUP = 101;
    const TYPE_ORG_USER_VERIFY = 102;

    // Event Activities (200-299)
    const TYPE_EVENT_SUBSCRIBE = 201;
    const TYPE_EVENT_UNSUBSCRIBE = 202;
    const TYPE_EVENT_CANCEL = 202; // Same as unsubscribe
    const TYPE_EVENT_WAITLIST = 203;
    const TYPE_EVENT_SIGNIN = 204;
    const TYPE_EVENT_SUBSCRIBE_BY_ADMIN = 205;
    const TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN = 206;
    const TYPE_EVENT_SIGNIN_BY_ADMIN = 207;
    const TYPE_EVENT_UNSUBSCRIBE_WAITLIST = 208;
    const TYPE_EVENT_SUBSCRIBE_BY_COACH = 210;
    const TYPE_EVENT_UNSUBSCRIBE_BY_COACH = 211;
    const TYPE_EVENT_SIGNIN_BY_COACH = 212;
    const TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN = 213;
    const TYPE_EVENT_NOSHOW = 214;

    // Assignment Activities (300-399)
    const TYPE_ASSIGNMENT_CREATE = 301;
    const TYPE_ASSIGNMENT_CANCEL = 302;
    const TYPE_ASSIGNMENT_SIGNIN = 303;
    const TYPE_ASSIGNMENT_CANCEL_BY_ADMIN = 304;
    const TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN = 305;

    /**
     * Get the organization that owns this activity.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the organization user that this activity belongs to.
     */
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the user associated with this activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get a human-readable description of the related object.
     */
    public function getRelatedObjectDescriptionAttribute()
    {
        if (!$this->object_id) {
            return null;
        }

        // Return a description based on activity type with actual model data
        switch ($this->type) {
            // Event-related activities
            case self::TYPE_EVENT_SUBSCRIBE:
            case self::TYPE_EVENT_UNSUBSCRIBE:
            case self::TYPE_EVENT_CANCEL:
            case self::TYPE_EVENT_WAITLIST:
            case self::TYPE_EVENT_SIGNIN:
            case self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN:
            case self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN:
            case self::TYPE_EVENT_SIGNIN_BY_ADMIN:
            case self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST:
            case self::TYPE_EVENT_SUBSCRIBE_BY_COACH:
            case self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH:
            case self::TYPE_EVENT_SIGNIN_BY_COACH:
            case self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN:
            case self::TYPE_EVENT_NOSHOW:
                // Get the event subscriber and return formatted class name
                if ($this->eventSubscriber) {
                    return $this->eventSubscriber->formatted_class_name;
                }
                return "Class/Event #{$this->object_id}";

            // Assignment-related activities
            case self::TYPE_ASSIGNMENT_CREATE:
            case self::TYPE_ASSIGNMENT_CANCEL:
            case self::TYPE_ASSIGNMENT_SIGNIN:
            case self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN:
            case self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN:
                return "Assignment #{$this->object_id}";

            // User account activities - might reference the user plan
            case self::TYPE_ORG_USER_SIGNUP:
            case self::TYPE_ORG_USER_VERIFY:
                // Try to get the user plan if it exists
                if ($this->orgUserPlan) {
                    return $this->orgUserPlan->name;
                }
                return "User Account #{$this->object_id}";

            default:
                return "Object #{$this->object_id}";
        }
    }

    /**
     * Get the event associated with this activity (for event-related activities).
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'object_id');
    }

    /**
     * Get the event subscriber associated with this activity (for event-related activities).
     * Most event activities point to eventSubscriber records via object_id.
     */
    public function eventSubscriber()
    {
        return $this->belongsTo(EventSubscriber::class, 'object_id');
    }

    /**
     * Get the organization user plan associated with this activity.
     */
    public function orgUserPlan()
    {
        return $this->belongsTo(OrgUserPlan::class, 'object_id');
    }

    /**
     * Get the formatted activity timestamp.
     */
    public function getFormattedActivityAtAttribute()
    {
        return $this->activity_at ? Carbon::createFromTimestamp($this->activity_at) : null;
    }

    /**
     * Get the formatted created at timestamp.
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? Carbon::createFromTimestamp($this->created_at) : null;
    }

    /**
     * Get the formatted updated at timestamp.
     */
    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? Carbon::createFromTimestamp($this->updated_at) : null;
    }

    /**
     * Get human readable activity name from type.
     */
    public function getNameAttribute()
    {
        return match($this->type) {
            // User Account Activities
            self::TYPE_ORG_USER_SIGNUP => 'User Signup',
            self::TYPE_ORG_USER_VERIFY => 'Account Verification',
            
            // Event Activities
            self::TYPE_EVENT_SUBSCRIBE => 'Class Reservation',
            self::TYPE_EVENT_UNSUBSCRIBE => 'Reservation Cancelled',
            self::TYPE_EVENT_CANCEL => 'Class Cancelled',
            self::TYPE_EVENT_WAITLIST => 'Wait List Reservation',
            self::TYPE_EVENT_SIGNIN => 'Class Sign-in',
            self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN => 'Class Reservation (by Admin)',
            self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN => 'Reservation Cancelled (by Admin)',
            self::TYPE_EVENT_SIGNIN_BY_ADMIN => 'Class Sign-in (by Admin)',
            self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST => 'Wait List Cancelled',
            self::TYPE_EVENT_SUBSCRIBE_BY_COACH => 'Class Reservation (by Coach)',
            self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH => 'Reservation Cancelled (by Coach)',
            self::TYPE_EVENT_SIGNIN_BY_COACH => 'Class Sign-in (by Coach)',
            self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN => 'Cancelled by Sign-in',
            self::TYPE_EVENT_NOSHOW => 'No-Show',
            
            // Assignment Activities
            self::TYPE_ASSIGNMENT_CREATE => 'Assignment Created',
            self::TYPE_ASSIGNMENT_CANCEL => 'Assignment Cancelled',
            self::TYPE_ASSIGNMENT_SIGNIN => 'Assignment Sign-in',
            self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN => 'Assignment Cancelled (by Admin)',
            self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN => 'Assignment Sign-in (by Admin)',
            
            default => 'Unknown Activity'
        };
    }

    /**
     * Get activity icon based on type.
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            // User Account Activities
            self::TYPE_ORG_USER_SIGNUP => 'user-plus',
            self::TYPE_ORG_USER_VERIFY => 'check-circle',
            
            // Event Activities - Sign-ins
            self::TYPE_EVENT_SIGNIN,
            self::TYPE_EVENT_SIGNIN_BY_ADMIN,
            self::TYPE_EVENT_SIGNIN_BY_COACH => 'arrow-right',
            
            // Event Activities - Reservations
            self::TYPE_EVENT_SUBSCRIBE,
            self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_SUBSCRIBE_BY_COACH => 'plus',
            
            // Event Activities - Cancellations
            self::TYPE_EVENT_UNSUBSCRIBE,
            self::TYPE_EVENT_CANCEL,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN => 'x-mark',
            
            // Event Activities - Wait List
            self::TYPE_EVENT_WAITLIST => 'clock',
            self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST => 'clock',
            
            // Event Activities - No-Show
            self::TYPE_EVENT_NOSHOW => 'x-circle',
            
            // Assignment Activities
            self::TYPE_ASSIGNMENT_CREATE => 'document-text',
            self::TYPE_ASSIGNMENT_CANCEL,
            self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN => 'document-text',
            self::TYPE_ASSIGNMENT_SIGNIN,
            self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN => 'document-text',
            
            default => 'exclamation-triangle'
        };
    }

    /**
     * Get activity color based on type.
     */
    public function getColorAttribute()
    {
        return match($this->type) {
            // User Account Activities
            self::TYPE_ORG_USER_SIGNUP,
            self::TYPE_ORG_USER_VERIFY => 'blue',
            
            // Event Activities - Sign-ins (green for positive actions)
            self::TYPE_EVENT_SIGNIN,
            self::TYPE_EVENT_SIGNIN_BY_ADMIN,
            self::TYPE_EVENT_SIGNIN_BY_COACH => 'green',
            
            // Event Activities - Reservations (blue for bookings)
            self::TYPE_EVENT_SUBSCRIBE,
            self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_SUBSCRIBE_BY_COACH => 'blue',
            
            // Event Activities - Cancellations (orange/yellow for neutral)
            self::TYPE_EVENT_UNSUBSCRIBE,
            self::TYPE_EVENT_CANCEL,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN => 'yellow',
            
            // Event Activities - Wait List (purple for waiting)
            self::TYPE_EVENT_WAITLIST,
            self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST => 'purple',
            
            // Event Activities - No-Show (red for negative)
            self::TYPE_EVENT_NOSHOW => 'red',
            
            // Assignment Activities
            self::TYPE_ASSIGNMENT_CREATE => 'blue',
            self::TYPE_ASSIGNMENT_SIGNIN,
            self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN => 'green',
            self::TYPE_ASSIGNMENT_CANCEL,
            self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN => 'yellow',
            
            default => 'gray'
        };
    }

    /**
     * Get activity category for filtering.
     */
    public function getCategoryAttribute()
    {
        return match($this->type) {
            // User Account Activities
            self::TYPE_ORG_USER_SIGNUP,
            self::TYPE_ORG_USER_VERIFY => 'account',
            
            // Event Activities
            self::TYPE_EVENT_SUBSCRIBE,
            self::TYPE_EVENT_UNSUBSCRIBE,
            self::TYPE_EVENT_CANCEL,
            self::TYPE_EVENT_WAITLIST,
            self::TYPE_EVENT_SIGNIN,
            self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN,
            self::TYPE_EVENT_SIGNIN_BY_ADMIN,
            self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST,
            self::TYPE_EVENT_SUBSCRIBE_BY_COACH,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH,
            self::TYPE_EVENT_SIGNIN_BY_COACH,
            self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN,
            self::TYPE_EVENT_NOSHOW => 'event',
            
            // Assignment Activities
            self::TYPE_ASSIGNMENT_CREATE,
            self::TYPE_ASSIGNMENT_CANCEL,
            self::TYPE_ASSIGNMENT_SIGNIN,
            self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN,
            self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN => 'assignment',
            
            default => 'other'
        };
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
     * Scope to filter by activity type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by activity category.
     */
    public function scopeByCategory($query, $category)
    {
        $types = [];
        
        switch($category) {
            case 'account':
                $types = [self::TYPE_ORG_USER_SIGNUP, self::TYPE_ORG_USER_VERIFY];
                break;
            case 'event':
                $types = [
                    self::TYPE_EVENT_SUBSCRIBE, self::TYPE_EVENT_UNSUBSCRIBE, self::TYPE_EVENT_CANCEL,
                    self::TYPE_EVENT_WAITLIST, self::TYPE_EVENT_SIGNIN, self::TYPE_EVENT_SUBSCRIBE_BY_ADMIN,
                    self::TYPE_EVENT_UNSUBSCRIBE_BY_ADMIN, self::TYPE_EVENT_SIGNIN_BY_ADMIN,
                    self::TYPE_EVENT_UNSUBSCRIBE_WAITLIST, self::TYPE_EVENT_SUBSCRIBE_BY_COACH,
                    self::TYPE_EVENT_UNSUBSCRIBE_BY_COACH, self::TYPE_EVENT_SIGNIN_BY_COACH,
                    self::TYPE_EVENT_UNSUBSCRIBE_BY_SIGNIN, self::TYPE_EVENT_NOSHOW
                ];
                break;
            case 'assignment':
                $types = [
                    self::TYPE_ASSIGNMENT_CREATE, self::TYPE_ASSIGNMENT_CANCEL, self::TYPE_ASSIGNMENT_SIGNIN,
                    self::TYPE_ASSIGNMENT_CANCEL_BY_ADMIN, self::TYPE_ASSIGNMENT_SIGNIN_BY_ADMIN
                ];
                break;
        }
        
        if (!empty($types)) {
            return $query->whereIn('type', $types);
        }
        
        return $query;
    }

    /**
     * Scope to filter by date range using activity_at timestamp.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        return $query->whereBetween('activity_at', [$startTimestamp, $endTimestamp]);
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('activity_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get activities for today.
     */
    public function scopeToday($query, $orgId = null)
    {
        $todayStart = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');

        $query = $query->whereBetween('activity_at', [$todayStart, $todayEnd]);

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query;
    }

    /**
     * Scope to exclude soft deleted records.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('isDeleted', false);
    }
}

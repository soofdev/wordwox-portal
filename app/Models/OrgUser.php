<?php

namespace App\Models;

use App\Traits\Tenantable;
use App\Services\Yii2QueueDispatcher;
use App\Services\PhoneNumberService;
use Creagia\LaravelSignPad\Concerns\RequiresSignature;
use Creagia\LaravelSignPad\Contracts\CanBeSigned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrgUser extends BaseWWModel implements CanBeSigned
{
    use HasFactory, Tenantable, SoftDeletes, RequiresSignature;

    protected $table = 'orgUser';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id',
        'user_id',
        'fullName',
        'phoneNumber',
        'phoneCountry',
        'email',
        'gender',
        'dob',
        'address',
        'nationality',
        'nationality_country',
        'nationalID',
        'emergencyFullName',
        'emergencyEmail',
        'emergencyPhoneNumber',
        'emergencyRelation',
        'photoFileName',
        'photoFilePath',
        'portraitFileName',
        'portraitFilePath',
        'isOwner',
        'isAdmin',
        'isStaff',
        'isOnRoster',
        'isCustomer',
        'isGuest',
        'isKiosk',
        'isFohUser',
        'isActive',
        'isArchived',
        'status',
        'uuid',
        'token',
        'token_sms',
        'created_by',
        'deleted_by',
        'member_at',
        'addMemberInviteOption',
    ];

    protected $attributes = [
        'isCustomer' => true,
        'isOwner' => false,
        'isAdmin' => false,
        'isStaff' => false,
        'isOnRoster' => false,
        'isGuest' => false,
        'isKiosk' => false,
        'isFohUser' => false,
        'isActive' => true,
        'isArchived' => false,
    ];

    protected $casts = [
        'org_id' => 'integer',
        'user_id' => 'integer',
        'gender' => 'integer',
        'dob' => 'date:Y-m-d',
        'nationality' => 'integer',
        'isOwner' => 'boolean',
        'isAdmin' => 'boolean',
        'isStaff' => 'boolean',
        'isOnRoster' => 'boolean',
        'isCustomer' => 'boolean',
        'isGuest' => 'boolean',
        'isKiosk' => 'boolean',
        'isFohUser' => 'boolean',
        'isActive' => 'boolean',
        'isArchived' => 'boolean',
        'created_by' => 'integer',
        'deleted_by' => 'integer',
        'member_at' => 'timestamp',
        'addMemberInviteOption' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set org_id from authenticated user if not set
            if (empty($model->org_id) && auth()->check()) {
                $model->org_id = auth()->user()->orgUser->org_id;
            }

            // Set who created this user
            if (empty($model->created_by) && auth()->check() && auth()->user()->orgUser) {
                $model->created_by = auth()->user()->orgUser->id;
            }

            // Generate tokens if needed
            if (empty($model->token)) {
                $model->generateToken();
            }
            if (empty($model->token_sms)) {
                $model->generateTokenSMS();
            }

            $model->uuid = Str::uuid();
            $model->status = 0; // OrgUserStatus::None->value equivalent
        });
    }

    protected static function booted()
    {
        static::created(function ($model) {
            // Complete the creation process
            $dispatcher = new Yii2QueueDispatcher();
            $dispatcher->dispatch('common\jobs\user\OrgUserCreateCompleteJob', ['id' => $model->id]);
        });

        static::updated(function ($model) {
            // Check if isDeleted field was changed to true
            if ($model->isDirty('deleted_at') && $model->deleted_at) {
                // Complete the deletion process
                $dispatcher = new Yii2QueueDispatcher();
                $dispatcher->dispatch('common\jobs\user\OrgUserDeleteCompleteJob', ['id' => $model->id]);
            } elseif ($model->isDirty('deleted_at') && !$model->deleted_at) {
                $dispatcher = new Yii2QueueDispatcher();
                $dispatcher->dispatch('common\jobs\user\OrgUserRestoreCompleteJob', ['id' => $model->id]);
            } else {
                // Complete the update process
                $dispatcher = new Yii2QueueDispatcher();
                $dispatcher->dispatch('common\jobs\user\OrgUserUpdateCompleteJob', ['id' => $model->id]);
            }
        });
    }

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(OrgUser::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(OrgUser::class, 'deleted_by');
    }

    /**
     * Get all RBAC role assignments for this user (FOH module only)
     */
    public function rbacRoleUsers()
    {
        return $this->hasMany(RbacRoleUser::class, 'orgUser_id')
                    ->whereHas('role', function ($query) {
                        $query->where('module', 'foh')
                              ->where('isActive', true);
                    });
    }

    /**
     * Get all unique RBAC tasks the user has across all their active roles (FOH module only)
     */
    public function rbacTasks()
    {
        return RbacTask::whereIn('id', function ($query) {
            $query->select('rbacRoleTask.rbacTask_id')
                  ->from('rbacRoleTask')
                  ->join('rbacRoleUser', 'rbacRoleUser.rbacRole_id', '=', 'rbacRoleTask.rbacRole_id')
                  ->where('rbacRoleUser.orgUser_id', $this->id)
                  ->where('rbacRoleUser.org_id', $this->org_id)
                  ->where('rbacRoleUser.module', 'foh')  // Direct module filter
                  ->where('rbacRoleUser.isDeleted', false)
                  ->where('rbacRoleTask.module', 'foh')  // Direct module filter
                  ->where('rbacRoleTask.isActive', true);
        });
    }

    // Helper methods
    public function generateToken()
    {
        $this->token = Str::random(32) . '_' . time();
    }

    public function generateTokenSMS()
    {
        $this->token_sms = Str::random(5) . '_' . time();
    }

    public function getFullPhoneAttribute(): string
    {
        return ($this->phoneNumber ? '+'. $this->phoneCountry . ' ' . $this->phoneNumber : '');
    }

    /**
     * Get the full name attribute with proper capitalization
     * Automatically capitalizes the first letter of each word
     */
    public function getFullNameAttribute($value): string
    {
        if (empty($value)) {
            return '';
        }
        
        // Capitalize the first letter of each word
        return ucwords(strtolower($value));
    }

    public function getFirstNameAttribute(): string
    {
        return explode(' ', $this->fullName)[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        return explode(' ', $this->fullName)[1] ?? '';
    }

    public function getRole(): string
    {
        if ($this->isOwner || $this->isAdmin) {
            return 'admin';
        }

        if ($this->isStaff) {
            return 'staff';
        }

        if ($this->isOnRoster) {
            return 'coach';
        }

        return 'customer';
    }

    /**
     * Check if this org user has access to the Front of House (FOH) interface
     */
    public function canAccessFoh(): bool
    {
        return (bool) $this->isFohUser;
    }

    /**
     * Get the signature for this org user
     */
    public function signature()
    {
        return $this->morphOne(\App\Models\Signature::class, 'model');
    }

    /**
     * Check if this org user has been signed
     */
    public function hasBeenSigned(): bool
    {
        return $this->signature()->exists();
    }



    /**
     * Get the memberships for this org user
     */
    public function orgUserPlans()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgUser_id');
    }

    /**
     * Get all org user plan holds for this user
     */
    public function orgUserPlanHolds()
    {
        return $this->hasMany(OrgUserPlanHold::class, 'orgUser_id');
    }

    /**
     * Get the business intelligence data for this org user
     */
    public function biOrgUser()
    {
        return $this->hasOne(BiOrgUser::class, 'orgUser_id');
    }

    /**
     * Get active memberships for this org user
     */
    public function activeMemberships()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgUser_id')->active();
    }

    /**
     * Get the latest active membership
     */
    public function latestActiveMembership()
    {
        return $this->activeMemberships()
                    ->orderBy('startDate', 'desc')
                    ->orderBy('endDate', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
    }

    /**
     * Check if user has any active memberships
     */
    public function hasActiveMembership(): bool
    {
        return $this->activeMemberships()->exists();
    }

    /**
     * Scope to get only users with active memberships
     */
    public function scopeWithActiveMemberships($query)
    {
        return $query->whereHas('orgUserPlans', function ($q) {
            $q->where('status', OrgUserPlan::STATUS_ACTIVE)
              ->where('isDeleted', false);
        })->where('isArchived', false);
    }

    /**
     * Get formatted phone number for display (National format)
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (!$this->phoneNumber || !$this->phoneCountry) {
            return '';
        }

        $phoneService = app(PhoneNumberService::class);
        $fullNumber = $this->getFullPhoneNumber();

        return $phoneService->formatForDisplay($fullNumber, $this->phoneCountry) ?? $fullNumber;
    }

    /**
     * Get phone number in E.164 format for storage/API calls
     */
    public function getE164PhoneAttribute(): ?string
    {
        if (!$this->phoneNumber || !$this->phoneCountry) {
            return null;
        }

        $phoneService = app(PhoneNumberService::class);
        $fullNumber = $this->getFullPhoneNumber();

        return $phoneService->formatForStorage($fullNumber, $this->phoneCountry);
    }

    /**
     * Get phone number in international format
     */
    public function getInternationalPhoneAttribute(): string
    {
        if (!$this->phoneNumber || !$this->phoneCountry) {
            return '';
        }

        $phoneService = app(PhoneNumberService::class);
        $fullNumber = $this->getFullPhoneNumber();

        return $phoneService->formatForInternational($fullNumber, $this->phoneCountry) ?? $fullNumber;
    }

    /**
     * Check if the phone number is a mobile number
     */
    public function getIsMobilePhoneAttribute(): bool
    {
        if (!$this->phoneNumber || !$this->phoneCountry) {
            return false;
        }

        $phoneService = app(PhoneNumberService::class);
        $fullNumber = $this->getFullPhoneNumber();

        return $phoneService->isMobile($fullNumber, $this->phoneCountry);
    }

    /**
     * Get the full phone number (combining country code and number if needed)
     */
    private function getFullPhoneNumber(): string
    {
        // If the phone number already starts with +, return as is
        if (str_starts_with($this->phoneNumber, '+')) {
            return $this->phoneNumber;
        }

        // Otherwise, construct the full number
        $phoneService = app(PhoneNumberService::class);
        $countries = $phoneService->getSupportedCountries();

        if (isset($countries[$this->phoneCountry])) {
            return '+' . $countries[$this->phoneCountry]['code'] . $this->phoneNumber;
        }

        return $this->phoneNumber;
    }

    /**
     * Get the profile image URL from S3 storage
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if ($this->photoFilePath) {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->photoFilePath);
        }

        return null;
    }

    /**
     * Get the portrait image URL from S3 storage
     */
    public function getPortraitImageUrlAttribute(): ?string
    {
        if ($this->portraitFilePath) {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->portraitFilePath);
        }

        return null;
    }

    /**
     * Get profile image URL or fallback to generated avatar
     */
    public function getProfileImageOrAvatarAttribute(): string
    {
        if ($this->photoFilePath) {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->photoFilePath);
        }

        // Generate avatar with user initials
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->fullName) . '&size=60&background=6366f1&color=ffffff';
    }

    /**
     * Get user initials for avatar fallback
     */
    public function getInitialsAttribute(): string
    {
        $names = explode(' ', trim($this->fullName));
        $initials = '';

        foreach (array_slice($names, 0, 2) as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return $initials ?: 'U';
    }

    /**
     * Safely check if user has permission using the RBAC system
     * Maps legacy Spatie permission names to RBAC task slugs for backward compatibility
     *
     * @param string $permission The permission name to check (legacy Spatie format)
     * @return bool True if user has the permission, false if not
     */
    public function safeHasPermissionTo(string $permission): bool
    {
        // Map old Spatie permission names to new RBAC task slugs
        $permissionToTaskMap = [
            // Member Management
            'create members' => 'create_members',
            'view members' => 'view_members',
            'view member profile' => 'view_member_profile',
            'edit members' => 'edit_members',
            'delete members' => 'delete_members',

            // Membership Operations
            'create memberships' => 'create_memberships',
            'view memberships' => 'view_memberships',
            'edit memberships' => 'edit_memberships',
            'modify membership dates' => 'modify_membership_dates',
            'modify membership limits' => 'modify_membership_limits',
            'upcharge memberships' => 'upcharge_memberships',
            'cancel memberships' => 'cancel_memberships',
            'transfer memberships' => 'transfer_memberships',
            'hold memberships' => 'hold_memberships',
            'end hold' => 'end_hold',
            'cancel hold' => 'cancel_hold',
            'modify hold' => 'modify_hold',
            'upgrade memberships' => 'upgrade_memberships',
            'manage partial payments' => 'manage_partial_payments',

            // Check-in System
            'check in members' => 'check_in_members',
            'view check ins' => 'view_check_ins',

            // System Access
            'access dashboard' => 'access_dashboard',
            'select gym' => 'select_gym',

            // Administration
            'manage settings' => 'manage_settings',
            'view reports' => 'view_reports',
            'manage roles' => 'manage_roles',
            'manage org terms' => 'manage_org_terms',
        ];

        // Get the corresponding task slug or use the permission name as-is for unmapped permissions
        $taskSlug = $permissionToTaskMap[$permission] ?? $permission;

        try {
            // Use RBAC system to check permission
            $rbacService = app(\App\Services\RbacService::class);
            return $rbacService->hasTask($this, $taskSlug);
        } catch (\Exception $e) {
            // Log RBAC-related errors
            \Illuminate\Support\Facades\Log::error('RBAC permission check failed', [
                'permission' => $permission,
                'task_slug' => $taskSlug,
                'user_id' => $this->id,
                'org_id' => $this->org_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

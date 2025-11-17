<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrgUserConsent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'org_user_id',
        'org_consent_id',
        'consented',
        'consented_at',
        'ip_address',
        'user_agent',
        'consented_by_org_user_id',
        'registration_session_id',
        'context',
    ];

    protected $casts = [
        'org_user_id' => 'integer',
        'org_consent_id' => 'integer',
        'consented' => 'boolean',
        'consented_at' => 'datetime',
        'consented_by_org_user_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (!$model->consented_at) {
                $model->consented_at = now();
            }
            if (!$model->ip_address) {
                $model->ip_address = request()->ip();
            }
            if (!$model->user_agent) {
                $model->user_agent = request()->userAgent();
            }
        });
    }

    /**
     * Get the user this consent record belongs to
     */
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class);
    }

    /**
     * Get the consent type this record is for
     */
    public function orgConsent()
    {
        return $this->belongsTo(OrgConsent::class);
    }

    /**
     * Get the user who provided the consent (for parent consent)
     */
    public function consentedBy()
    {
        return $this->belongsTo(OrgUser::class, 'consented_by_org_user_id');
    }

    /**
     * Scope to get consents for a specific user
     */
    public function scopeForUser($query, int $orgUserId)
    {
        return $query->where('org_user_id', $orgUserId);
    }

    /**
     * Scope to get consents of a specific type
     */
    public function scopeForConsentType($query, int $orgConsentId)
    {
        return $query->where('org_consent_id', $orgConsentId);
    }

    /**
     * Scope to get consents from a specific context
     */
    public function scopeFromContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /**
     * Scope to get consents from a specific session
     */
    public function scopeFromSession($query, string $sessionId)
    {
        return $query->where('registration_session_id', $sessionId);
    }

    /**
     * Scope to get only consented records
     */
    public function scopeConsented($query)
    {
        return $query->where('consented', true);
    }

    /**
     * Scope to get only declined records
     */
    public function scopeDeclined($query)
    {
        return $query->where('consented', false);
    }

    /**
     * Scope to get parent consent records (where someone else provided consent)
     */
    public function scopeParentConsent($query)
    {
        return $query->whereNotNull('consented_by_org_user_id')
            ->whereColumn('consented_by_org_user_id', '!=', 'org_user_id');
    }

    /**
     * Scope to get self consent records
     */
    public function scopeSelfConsent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('consented_by_org_user_id')
              ->orWhereColumn('consented_by_org_user_id', '=', 'org_user_id');
        });
    }

    /**
     * Check if this is a parent consent (someone else provided consent)
     */
    public function isParentConsent(): bool
    {
        return $this->consented_by_org_user_id !== null && 
               $this->consented_by_org_user_id !== $this->org_user_id;
    }

    /**
     * Check if this is a self consent
     */
    public function isSelfConsent(): bool
    {
        return !$this->isParentConsent();
    }

    /**
     * Get a human-readable description of who provided the consent
     */
    public function getConsentProviderDescription(): string
    {
        if ($this->isParentConsent()) {
            $consenter = $this->consentedBy;
            return $consenter ? "Consented by: {$consenter->fullName}" : 'Consented by: Parent/Guardian';
        }
        
        return 'Self-consented';
    }

    /**
     * Get available consent contexts
     */
    public static function getAvailableContexts(): array
    {
        return [
            'registration' => 'Registration',
            'verification' => 'Verification',
            'membership_purchase' => 'Membership Purchase',
            'profile_update' => 'Profile Update',
            'check_in' => 'Check-in',
        ];
    }
}
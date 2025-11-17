<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrgConsent extends Model
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'org_id',
        'consent_name',
        'consent_key',
        'description',
        'is_active',
        'is_required',
        'display_contexts',
        'order',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'display_contexts' => 'array',
        'order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_required' => false,
        'order' => 0,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the organization that owns this consent
     */
    public function org()
    {
        return $this->belongsTo(Org::class);
    }

    /**
     * Get all user consent records for this consent type
     */
    public function userConsents()
    {
        return $this->hasMany(OrgUserConsent::class);
    }

    /**
     * Scope to get only active consents
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only required consents
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get consents for a specific context
     */
    public function scopeForContext($query, string $context)
    {
        return $query->whereJsonContains('display_contexts', $context);
    }

    /**
     * Scope to get consents ordered for display
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('consent_name');
    }

    /**
     * Scope to get consents for registration context
     */
    public function scopeForRegistration($query)
    {
        return $query->active()
            ->forContext('registration')
            ->ordered();
    }

    /**
     * Scope to get consents for verification context
     */
    public function scopeForVerification($query)
    {
        return $query->active()
            ->forContext('verification')
            ->ordered();
    }

    /**
     * Check if this consent should be displayed in a specific context
     */
    public function isDisplayedInContext(string $context): bool
    {
        return in_array($context, $this->display_contexts ?? []);
    }

    /**
     * Get all available display contexts
     */
    public static function getAvailableContexts(): array
    {
        return [
            'registration' => 'During Registration',
            'verification' => 'During Verification',
            'membership_purchase' => 'During Membership Purchase',
            'profile_update' => 'During Profile Update',
            'check_in' => 'During Check-in',
        ];
    }

    /**
     * Add a display context to this consent
     */
    public function addDisplayContext(string $context): void
    {
        $contexts = $this->display_contexts ?? [];
        if (!in_array($context, $contexts)) {
            $contexts[] = $context;
            $this->update(['display_contexts' => $contexts]);
        }
    }

    /**
     * Remove a display context from this consent
     */
    public function removeDisplayContext(string $context): void
    {
        $contexts = $this->display_contexts ?? [];
        $contexts = array_values(array_filter($contexts, fn($c) => $c !== $context));
        $this->update(['display_contexts' => $contexts]);
    }
}
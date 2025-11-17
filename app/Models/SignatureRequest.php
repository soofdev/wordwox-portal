<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SignatureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'org_user_id',
        'org_id',
        'token',
        'method',
        'status',
        'expires_at',
        'sent_at',
        'viewed_at',
        'agreed_at',
        'signed_at',
        'created_by',
    ];

    protected $casts = [
        'org_user_id' => 'integer',
        'org_id' => 'integer',
        'created_by' => 'integer',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'agreed_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->token)) {
                $model->token = self::generateSecureToken();
            }
        });
    }

    /**
     * Get the organization user this request is for.
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'org_user_id');
    }

    /**
     * Get the organization this request belongs to.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Generate a secure token for public access.
     */
    public static function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Check if the request has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the request is completed (signed).
     */
    public function isCompleted(): bool
    {
        return $this->status === 'signed';
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as viewed.
     */
    public function markAsViewed(): void
    {
        if ($this->status === 'sent') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    /**
     * Mark as agreed.
     */
    public function markAsAgreed(): void
    {
        if (in_array($this->status, ['sent', 'viewed'])) {
            $this->update([
                'status' => 'agreed',
                'agreed_at' => now(),
            ]);
        }
    }

    /**
     * Mark as signed (completed).
     */
    public function markAsSigned(): void
    {
        if ($this->status === 'agreed') {
            $this->update([
                'status' => 'signed',
                'signed_at' => now(),
            ]);
        }
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Mark as expired.
     */
    public function markAsExpired(): void
    {
        if (!$this->isCompleted()) {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Get public signature URL.
     */
    public function getPublicUrl(): string
    {
        return route('public.signature.terms.review', $this->token);
    }

    /**
     * Scope for active (non-expired, non-completed) requests.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNotIn('status', ['signed', 'expired', 'failed']);
    }

    /**
     * Scope for expired requests.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->where('status', '!=', 'signed');
    }
}
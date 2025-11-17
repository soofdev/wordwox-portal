<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrgMsgItem extends Model
{
    use HasFactory;

    protected $table = 'orgMsgItem';
    protected $dateFormat = 'U';

    protected $fillable = [
        'uuid',
        'org_id',
        'orgUser_id',
        'orgMsgBatch_id',
        'channel',
        'subject',
        'body',
        'status',
        'isCanceled',
        'isDeleted',
        'created_by',
        'process_on',
        'processed_at',
        'output',
        'cost',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgUser_id' => 'integer',
        'orgMsgBatch_id' => 'integer',
        'created_by' => 'integer',
        'process_on' => 'integer',
        'processed_at' => 'integer',
        'isCanceled' => 'boolean',
        'isDeleted' => 'boolean',
        'output' => 'array',
        'cost' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the organization that owns this message item.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the org user that this message was sent to.
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the SMS logs associated with this message item.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(LogSms::class, 'orgMsgItem_id');
    }

    /**
     * Mark the message as queued.
     */
    public function markAsQueued(): void
    {
        $this->update(['status' => 'queued']);
    }

    /**
     * Mark the message as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark the message as sent.
     */
    public function markAsSent(array $output = [], int $cost = 0): void
    {
        $this->update([
            'status' => 'sent',
            'output' => $output,
            'cost' => $cost,
            'processed_at' => now()->timestamp,
        ]);
    }

    /**
     * Mark the message as failed.
     */
    public function markAsFailed(array $output = []): void
    {
        $this->update([
            'status' => 'error',
            'output' => $output,
            'processed_at' => now()->timestamp,
        ]);
    }
}
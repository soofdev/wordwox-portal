<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LogSms extends Model
{
    use HasFactory;

    protected $table = 'logSms';
    protected $dateFormat = 'U';

    protected $fillable = [
        'uuid',
        'org_id',
        'orgUser_id',
        'gateway',
        'message',
        'from',
        'to',
        'price',
        'price_unit',
        'status',
        'requestHeader',
        'requestBody',
        'responseHeader',
        'responseBody',
        'visible',
        'orgMsgItem_id',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgUser_id' => 'integer',
        'orgMsgItem_id' => 'integer',
        'price' => 'decimal:2',
        'requestHeader' => 'array',
        'requestBody' => 'array',
        'responseHeader' => 'array',
        'responseBody' => 'array',
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
     * Get the organization that owns this SMS log.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the org user that this SMS was sent to.
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the message item associated with this SMS.
     */
    public function orgMsgItem(): BelongsTo
    {
        return $this->belongsTo(OrgMsgItem::class, 'orgMsgItem_id');
    }

    /**
     * Create a new SMS log entry.
     */
    public static function createFromSmsResult(
        string $gateway,
        int $orgId,
        ?int $orgUserId,
        string $from,
        string $to,
        string $message,
        string $status,
        array $responseData = [],
        ?int $orgMsgItemId = null,
        string $visible = 'all'
    ): self {
        return self::create([
            'gateway' => $gateway,
            'org_id' => $orgId,
            'orgUser_id' => $orgUserId,
            'from' => $from,
            'to' => $to,
            'message' => $message,
            'status' => $status,
            'responseBody' => $responseData,
            'orgMsgItem_id' => $orgMsgItemId,
            'visible' => $visible,
        ]);
    }
}
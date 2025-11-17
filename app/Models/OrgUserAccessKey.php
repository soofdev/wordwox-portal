<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgUserAccessKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orgUserAccessKey';

    /**
     * Access key system types
     */
    const SYSTEM_GANTNER = 'gantner';
    const SYSTEM_WODWORX = 'wodworx';

    /**
     * Access key types
     */
    const TYPE_CARD = 'card';
    const TYPE_FOB = 'fob';
    const TYPE_STICKER = 'sticker';
    const TYPE_QR = 'qr';

    /**
     * User types
     */
    const USER_TYPE_PLAN = 'plan';
    const USER_TYPE_GROUP = 'group';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'org_id',
        'orgUser_id',
        'type',
        'fullName',
        'user_id',
        'accessKeySystem',
        'accessKeyType',
        'accessKeyPartnerID',
        'accessKeyID',
        'startDateTime',
        'endDateTime',
        'isDeleted',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'org_id' => 'integer',
        'orgUser_id' => 'integer',
        'user_id' => 'integer',
        'startDateTime' => 'datetime',
        'endDateTime' => 'datetime',
        'isDeleted' => 'boolean',
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
     * Get the organization that owns this access key.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the organization user that this access key belongs to.
     */
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the user associated with this access key.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get access events for this key.
     */
    public function accessEvents()
    {
        return $this->hasMany(AccessEvent::class, 'orgUserAccessKey_id');
    }

    /**
     * Create a new access key for an organization user.
     */
    public static function createForOrgUser($orgUserId, array $attributes = [])
    {
        $orgUser = \App\Models\OrgUser::find($orgUserId);

        if (!$orgUser) {
            return null;
        }

        return static::create(array_merge([
            'org_id' => $orgUser->org_id,
            'orgUser_id' => $orgUserId,
            'user_id' => $orgUser->user_id,
            'fullName' => $orgUser->fullName,
            'type' => static::USER_TYPE_PLAN,
            'uuid' => \Str::uuid(),
        ], $attributes));
    }

    /**
     * Get all available access key systems.
     */
    public static function getAvailableSystems()
    {
        return [
            static::SYSTEM_GANTNER => 'Gantner',
            static::SYSTEM_WODWORX => 'WodWorx',
        ];
    }

    /**
     * Get all available access key types.
     */
    public static function getAvailableKeyTypes()
    {
        return [
            static::TYPE_CARD => 'Card',
            static::TYPE_FOB => 'Fob',
            static::TYPE_STICKER => 'Sticker',
            static::TYPE_QR => 'QR Code',
        ];
    }
}

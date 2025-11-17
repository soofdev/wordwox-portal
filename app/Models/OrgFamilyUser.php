<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgFamilyUser extends BaseWWModel
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'orgFamilyUser';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id',
        'orgFamily_id',
        'orgUser_id',
        'level', // 'parent' or 'child'
    ];

    protected $casts = [
        'org_id' => 'integer',
        'orgFamily_id' => 'integer',
        'orgUser_id' => 'integer',
    ];

    /**
     * Get the organization that owns this family user relationship
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the family this user belongs to
     */
    public function orgFamily(): BelongsTo
    {
        return $this->belongsTo(OrgFamily::class, 'orgFamily_id');
    }

    /**
     * Get the user in this family relationship
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }


    /**
     * Check if this family member is a parent
     */
    public function isParent(): bool
    {
        return $this->level === 'parent';
    }

    /**
     * Check if this family member is a child
     */
    public function isChild(): bool
    {
        return $this->level === 'child';
    }
}

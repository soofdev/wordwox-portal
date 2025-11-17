<?php

namespace App\Models;

use App\Enums\NoteType;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class OrgUserNote extends Model implements Auditable
{
    use HasFactory, SoftDeletes, Tenantable, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_id',
        'orgUser_id',
        'author_id',
        'note_type',
        'title',
        'content',
        'notify_member',
        'notify_staff_id',
        'reminder_at',
        'reminder_sent',
        'notable_type',
        'notable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'note_type' => NoteType::class,
        'notify_member' => 'boolean',
        'reminder_at' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    /**
     * Get the organization user that the note is about.
     */
    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }

    /**
     * Get the author of the note.
     */
    public function author(): BelongsTo
    {
        // always get with trashed because we need to show author even if they are deleted
        // author_id references the orgUser_id of the authenticated user, not the User id
        return $this->belongsTo(OrgUser::class, 'author_id')->withTrashed();
    }

    /**
     * Get the staff member to notify about this note.
     */
    public function notifyStaff(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'notify_staff_id');
    }

    /**
     * Get the parent notable model (OrgUserPlan, EventSubscriber, Invoice, etc).
     */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}




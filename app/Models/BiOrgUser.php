<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiOrgUser extends Model
{
    use HasFactory;

    protected $table = 'biOrgUser';
    public $timestamps = false;

    protected $fillable = [
        'orgUser_id',
        'orgUser_uuid',
        'org_id',
        'active_at',
        'full_name',
        'e_mail',
        'phone_number',
        'total_plans',
        'total_plans_days',
        'first_plan_id',
        'last_plan_id',
        'active_plan_id',
        'hold_plan_id',
        'first_class_sign_in_id',
        'last_class_sign_in_id',
        'total_event_sign_ins',
        'total_event_no_shows',
        'total_event_late_cancels',
        'total_event_wait_lists',
        'total_invoice_amount',
        'total_invoice_paid'
    ];

    protected $casts = [
        'total_invoice_amount' => 'decimal:2',
        'total_invoice_paid' => 'decimal:2',
    ];

    public function orgUser(): BelongsTo
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id');
    }
}
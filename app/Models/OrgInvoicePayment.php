<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrgInvoicePayment Model
 * 
 * Represents payment records for invoices in the system.
 * Based on Box project's OrgInvoicePayment model structure.
 */
class OrgInvoicePayment extends Model
{
    use Tenantable;
    /**
     * Payment Status Constants
     * Matching Box project's OrgInvoicePayment constants
     */
    const STATUS_PENDING = 1;
    const STATUS_PAID = 2;
    const STATUS_REFUNDED = 3;
    const STATUS_CANCELED = 6;

    /**
     * Payment Method Constants
     * Matching Box project's payment method values
     */
    const METHOD_FREE = 'free';
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_ONLINE = 'online';
    const METHOD_POS = 'pos';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CLIQ = 'cliq';
    const METHOD_INSTAPAY = 'instapay';
    const METHOD_CHEQUE = 'cheque';

    /**
     * The table associated with the model.
     */
    protected $table = 'orgInvoicePayment';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'org_id',
        'orgInvoice_id',
        'orgUserPlan_id',
        'amount',
        'currency',
        'method',
        'status',
        'gateway',
        'pp',
        'pp_id',        // MyFatoorah Payment ID
        'pp_number',    // MyFatoorah Invoice ID
        'paid_at',
        'created_by',
        'isDeleted',
        'isCanceled',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'timestamp',
        'isDeleted' => 'boolean',
        'isCanceled' => 'boolean',
    ];

    /**
     * Get the status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->getStatusText($this->status);
    }

    /**
     * Get the method label for display
     */
    public function getMethodLabelAttribute(): string
    {
        return $this->getMethodText($this->method);
    }

    /**
     * Get human-readable status text
     */
    public function getStatusText(int $status): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_CANCELED => 'Canceled',
        ];

        return $statuses[$status] ?? 'Unknown';
    }

    /**
     * Get human-readable method text
     */
    public function getMethodText(string $method): string
    {
        // Get payment method name from sysPaymentMethod table
        $paymentMethod = \DB::table('sysPaymentMethod')
            ->where('value', $method)
            ->where('status', 'active')
            ->first();
            
        if ($paymentMethod) {
            return $paymentMethod->name;
        }
        
        // Fallback mapping for methods not in sysPaymentMethod table
        $fallbackMethods = [
            self::METHOD_ONLINE => 'Online Payment',
            self::METHOD_FREE => 'Free',
            'gift_voucher' => 'Prepaid Gift Voucher',
            'amex' => 'American Express',
            'capital_bank' => 'Capital Bank',
            'network_etihad' => 'Network Etihad',
        ];
        
        return $fallbackMethods[$method] ?? 'Unknown';
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    /**
     * Get all available methods
     */
    public static function getMethods(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_CARD => 'Card',
            self::METHOD_ONLINE => 'Online',
            self::METHOD_POS => 'POS',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CLIQ => 'CLIQ Transfer',
            self::METHOD_INSTAPAY => 'Instapay',
            self::METHOD_CHEQUE => 'Cheque',
            self::METHOD_FREE => 'Free',
        ];
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Check if payment is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Relationships
     */

    /**
     * Get the organization that owns this payment
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get the invoice this payment belongs to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(OrgInvoice::class, 'orgInvoice_id');
    }

    /**
     * Get the membership plan this payment is for
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrgUserPlan::class, 'orgUserPlan_id');
    }

    /**
     * Get the user who created this payment
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */

    /**
     * Scope to get only active payments (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->where('isDeleted', 0);
    }

    /**
     * Scope to get only paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get only pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get only refunded payments
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    /**
     * Scope to get only canceled payments
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', self::STATUS_CANCELED);
    }
}

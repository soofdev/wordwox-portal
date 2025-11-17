<?php

namespace App\Enums;

enum InvoiceStatus: int
{
    case PENDING = 1;
    case PAID = 2;
    case PARTIALLY_PAID = 3;
    case REFUNDED = 4;
    case PARTIALLY_REFUNDED = 5;
    case CANCELED = 6;
    case FREE = 7;
    case PENDING_POS = 8;
    case DELETED = 9;

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.invoice_status.PENDING'),
            self::PAID->value => __('enums.invoice_status.PAID'),
            self::PARTIALLY_PAID->value => __('enums.invoice_status.PARTIALLY_PAID'),
            self::REFUNDED->value => __('enums.invoice_status.REFUNDED'),
            self::PARTIALLY_REFUNDED->value => __('enums.invoice_status.PARTIALLY_REFUNDED'),
            self::CANCELED->value => __('enums.invoice_status.CANCELED'),
            self::FREE->value => __('enums.invoice_status.FREE'),
            self::PENDING_POS->value => __('enums.invoice_status.PENDING_POS'),
            self::DELETED->value => __('enums.invoice_status.DELETED'),
        ];
    }

    public static function getLabel(self $value): string
    {
        return self::labels()[$value->value] ?? 'Unknown';
    }

    public static function getBadgeColor(self $value): string
    {
        return match($value) {
            self::PENDING, self::PENDING_POS => 'warning',
            self::PAID => 'success',
            self::PARTIALLY_PAID => 'info',
            self::REFUNDED, self::PARTIALLY_REFUNDED => 'success',
            self::CANCELED, self::DELETED => 'danger',
            self::FREE => 'purple',
            default => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => self::getLabel($case)])->toArray();
    }
    
    /**
     * Get the invoice statuses that should be included in financial calculations.
     * These are statuses that represent revenue (paid, partially paid, or pending).
     *
     * @return array<InvoiceStatus>
     */
    public static function getFinancialStatuses(): array
    {
        return [
            self::PENDING,
            self::PAID,
            self::PARTIALLY_PAID
        ];
    }
}


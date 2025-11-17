<?php

namespace App\Enums;

enum InvoicePaymentStatus: int
{
    case PENDING = 1;
    case PAID = 2;
    case REFUNDED = 3;
    case CANCELED = 6;
    case DELETED = 7;

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.invoice_payment_status.PENDING'),
            self::PAID->value => __('enums.invoice_payment_status.PAID'),
            self::REFUNDED->value => __('enums.invoice_payment_status.REFUNDED'),
            self::CANCELED->value => __('enums.invoice_payment_status.CANCELED'),
            self::DELETED->value => __('enums.invoice_payment_status.DELETED'),
        ];
    }

    public static function getLabel(self $value): string
    {
        return self::labels()[$value->value] ?? 'Unknown';
    }

    public static function getBadgeColor(self $value): string
    {
        return match($value) {
            self::PENDING => 'warning',
            self::PAID  => 'success',
            self::REFUNDED => 'success',
            self::CANCELED => 'danger',
            self::DELETED => 'danger',
            
            default => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => self::getLabel($case)])->toArray();
    }
}


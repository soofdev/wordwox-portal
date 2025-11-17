<?php

namespace App\Enums;

use App\Models\Invoice;

enum InvoiceItemType: string
{
    case MEMBERSHIP = 'membership';
    case DROP_IN = 'drop_in';
    case HOLD = 'hold';
    case TRANSFER = 'transfer';
    case LEVEL_PROMOTION = 'level_promotion';
    case UNKNOWN = 'unknown';

    /**
     * Get the label for the invoice item type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::MEMBERSHIP => __('enums.invoice_item_type.membership'),
            self::DROP_IN => __('enums.invoice_item_type.drop_in'),
            self::HOLD => __('enums.invoice_item_type.hold'),
            self::TRANSFER => __('enums.invoice_item_type.transfer'),
            self::LEVEL_PROMOTION => __('enums.invoice_item_type.level_promotion'),
            self::UNKNOWN => __('enums.invoice_item_type.unknown'),
        };
    }

    /**
     * Get the color for the invoice item type badge
     */
    public function getColor(): string
    {
        return match($this) {
            self::MEMBERSHIP => 'gray',
            self::DROP_IN => 'gray',
            self::HOLD => 'gray',
            self::TRANSFER => 'gray',
            self::LEVEL_PROMOTION => 'gray',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * Determine the invoice item type from an invoice
     */
    public static function fromInvoice(Invoice $invoice): self
    {
        // Check for membership (this is the most common type)
        if (!empty($invoice->orgUserPlan_id)) {
            return self::MEMBERSHIP;
        }
        
        // Check for drop-in events
        if (!empty($invoice->eventSubscriber_id)) {
            return self::DROP_IN;
        }
        
        // Check for transfer - if the invoice is related to a transfer
        // This might be stored in details field as JSON or in a relationship
        if (isset($invoice->details) && 
            str_contains(strtolower($invoice->details), 'transfer')) {
            return self::TRANSFER;
        }
        
        // Check for hold - if the invoice is related to a membership hold
        // This might be stored in details field as JSON or in a relationship
        if (isset($invoice->details) && 
            str_contains(strtolower($invoice->details), 'hold')) {
            return self::HOLD;
        }

        // Check for level promotion - if the invoice is related to a level promotion
        if (isset($invoice->details) && 
            str_contains(strtolower($invoice->details), 'level promotion')) {
            return self::LEVEL_PROMOTION;
        }

        return self::UNKNOWN;
    }
}


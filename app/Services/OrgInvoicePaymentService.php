<?php

namespace App\Services;

use App\Models\OrgInvoicePayment;
use App\Services\Yii2QueueDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrgInvoicePaymentService
{
    /**
     * Cancel a payment by ID
     *
     * @param int $paymentId
     * @param int $orgId
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelPayment(int $paymentId, int $orgId): array
    {
        try {
            // Handle fallback payments (id=1 means it's not a real database record)
            if ($paymentId == 1) {
                return [
                    'success' => false,
                    'message' => __('invoices.This payment cannot be canceled as it is a legacy payment record')
                ];
            }

            // Find the payment record
            $payment = DB::table('orgInvoicePayment')
                ->where('id', $paymentId)
                ->where('isDeleted', 0)
                ->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => __('invoices.Payment not found')
                ];
            }

            // Check if payment is already canceled
            if ($payment->isCanceled == 1 || $payment->status == OrgInvoicePayment::STATUS_CANCELED) {
                return [
                    'success' => false,
                    'message' => __('invoices.This payment is already canceled')
                ];
            }

            // Update payment status to canceled (following Box project approach)
            $updated = DB::table('orgInvoicePayment')
                ->where('id', $paymentId)
                ->update([
                    'isCanceled' => 1,
                    'status' => OrgInvoicePayment::STATUS_CANCELED, // Set status to 6 (STATUS_CANCELED)
                    'updated_at' => now()->timestamp,
                ]);

            if ($updated) {
                Log::info('Payment canceled successfully', [
                    'payment_id' => $paymentId,
                    'org_id' => $orgId,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'JOD'
                ]);

                // Dispatch job to Yii2 queue to handle post-cancellation processing (like Box project)
                $dispatcher = new Yii2QueueDispatcher();
                $dispatcher->dispatch('common\jobs\invoice\InvoicePaymentCancelCompleteJob', [
                    'id' => (string)$paymentId, // Payment ID for the job (not invoice ID)
                ]);

                return [
                    'success' => true,
                    'message' => __('invoices.Payment canceled successfully')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('invoices.Failed to cancel payment. Please try again')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error canceling payment', [
                'payment_id' => $paymentId,
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('invoices.Could not cancel payment. Please try again')
            ];
        }
    }

    /**
     * Check if a payment can be canceled
     *
     * @param int $paymentId
     * @param int $orgId
     * @return bool
     */
    public function canCancelPayment(int $paymentId, int $orgId): bool
    {
        // Fallback payments (id=1) cannot be canceled
        if ($paymentId == 1) {
            return false;
        }

        $payment = DB::table('orgInvoicePayment')
            ->where('id', $paymentId)
            ->where('org_id', $orgId)
            ->where('isDeleted', 0)
            ->where('isCanceled', 0)
            ->first();

        return $payment !== null;
    }

    /**
     * Get payment history for a membership
     *
     * @param int $membershipId
     * @param int $orgId
     * @return array
     */
    public function getPaymentHistory(int $membershipId, int $orgId): array
    {
        // Try to find orgInvoice record first
        $invoice = DB::table('orgInvoice')
            ->where('orgUserPlan_id', $membershipId)
            ->where('isDeleted', 0)
            ->first();

        Log::info('Invoice lookup for membership ' . $membershipId, [
            'invoice_found' => $invoice ? 'Yes' : 'No',
            'invoice_id' => $invoice->id ?? 'N/A'
        ]);

        if ($invoice) {
            $payments = DB::table('orgInvoicePayment')
                ->where('orgInvoice_id', $invoice->id)
                ->where('isDeleted', 0)
                ->orderBy('created_at', 'desc')
                ->get();

            return $payments->map(function ($payment) use ($orgId) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'JOD',
                    'method' => $this->getPaymentMethodText($payment->method, $orgId),
                    'method_raw' => $payment->method,
                    'status' => $this->getPaymentStatusText($payment->status),
                    'status_raw' => $payment->status,
                    'is_canceled' => $payment->isCanceled == 1,
                    'can_cancel' => $payment->isCanceled == 0,
                    'created_at' => $payment->created_at ? Carbon::createFromTimestamp($payment->created_at)->format('M d, Y H:i') : null,
                    'paid_at' => $payment->paid_at ? Carbon::createFromTimestamp($payment->paid_at)->format('M d, Y H:i') : null,
                ];
            })->toArray();
        }

        // Fallback: No orgInvoice found, return empty array or fallback data
        Log::info('Using fallback payment data for membership ' . $membershipId);
        return [];
    }

    /**
     * Get payment method text from sysPaymentMethod table
     *
     * @param string $method
     * @param int $orgId
     * @return string
     */
    private function getPaymentMethodText(string $method, int $orgId): string
    {
        // Get organization's country ID
        $org = DB::table('org')->where('id', $orgId)->first();
        $orgCountryId = $org->sysCountry_id ?? null;

        // Get payment method name from sysPaymentMethod table
        $query = DB::table('sysPaymentMethod')
            ->where('value', $method)
            ->where('status', 'active');

        if ($orgCountryId) {
            $query->where(function($q) use ($orgCountryId) {
                $q->where('sysCountry_id', $orgCountryId)
                  ->orWhereNull('sysCountry_id')
                  ->orWhere('sysCountry_id', '');
            });
        }

        $paymentMethod = $query->first();

        if ($paymentMethod) {
            return $paymentMethod->name;
        }

        // Fallback mapping
        $fallbackMethods = [
            'online' => 'Online Payment',
            'free' => 'Free',
            'gift_voucher' => 'Prepaid Gift Voucher',
            'amex' => 'American Express',
            'capital_bank' => 'Capital Bank',
            'network_etihad' => 'Network Etihad',
        ];

        return $fallbackMethods[$method] ?? ucfirst($method);
    }

    /**
     * Get payment status text
     *
     * @param int $status
     * @return string
     */
    private function getPaymentStatusText(int $status): string
    {
        return OrgInvoicePayment::getStatuses()[$status] ?? 'Unknown';
    }
}

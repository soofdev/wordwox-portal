<?php

namespace App\Services;

use App\Models\OrgUserPlan;
use App\Models\OrgUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for handling pro-rated membership transfers
 * Creates new membership for target member with remaining time/value
 */
class OrgUserPlanTransferProRateService
{
    /**
     * Transfer membership with pro-rating (creates new plan for target member)
     *
     * @param OrgUserPlan $orgUserPlan The original membership
     * @param array $data Transfer data including target member, fees, etc.
     * @param string|null $note Optional transfer note
     * @return OrgUserPlan|null The new membership for target member or null on failure
     */
    public function transferMembershipProRate(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        try {
            DB::beginTransaction();
            
            // Validate the target member exists
            $targetMember = OrgUser::find($data['transferToMember']);
            if (!$targetMember) {
                throw new \Exception('Target member not found');
            }
            
            // Store original member info for notes
            $originalMember = $orgUserPlan->orgUser;
            $originalMemberName = $originalMember->fullName ?? 'Unknown Member';
            $targetMemberName = $targetMember->fullName ?? 'Unknown Member';
            
            // Validate membership can be transferred
            if (!$this->canTransferMembership($orgUserPlan)) {
                throw new \Exception('This membership cannot be transferred');
            }
            
            // Calculate pro-rating
            $proRateCalculation = $this->calculateProRating($orgUserPlan);
            
            if ($proRateCalculation['remainingDays'] <= 0) {
                throw new \Exception('No remaining time to transfer');
            }
            
            // Create new membership for target member
            $newMembership = $this->createProRatedMembership($orgUserPlan, $targetMember, $proRateCalculation, $data, $note);
            
            // Update original membership (mark as partially transferred)
            $this->updateOriginalMembership($orgUserPlan, $proRateCalculation, $originalMemberName, $targetMemberName, $data, $note);
            
            // Handle payment if this is a paid transfer
            if ($data['paidTransfer'] ?? false) {
                $this->recordTransferPayment($newMembership, $data);
            }
            
            // Log the transfer
            Log::info('Pro-rated membership transfer completed', [
                'original_membership_id' => $orgUserPlan->id,
                'new_membership_id' => $newMembership->id,
                'from_member' => $originalMemberName,
                'to_member' => $targetMemberName,
                'remaining_days' => $proRateCalculation['remainingDays'],
                'pro_rated_amount' => $proRateCalculation['remainingAmount'],
                'paid_transfer' => $data['paidTransfer'] ?? false
            ]);
            
            DB::commit();
            return $newMembership;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pro-rated membership transfer failed', [
                'membership_id' => $orgUserPlan->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }
    
    /**
     * Calculate pro-rating based on time used vs remaining
     *
     * @param OrgUserPlan $orgUserPlan
     * @return array
     */
    private function calculateProRating(OrgUserPlan $orgUserPlan): array
    {
        $startDate = Carbon::parse($orgUserPlan->startDateLoc);
        $endDate = Carbon::parse($orgUserPlan->endDateLoc);
        $currentDate = Carbon::now();
        
        // Calculate total duration
        $totalDays = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
        
        // Calculate used days (from start to now)
        $usedDays = $startDate->diffInDays($currentDate);
        if ($usedDays < 0) $usedDays = 0; // If membership hasn't started yet
        if ($usedDays > $totalDays) $usedDays = $totalDays; // If membership has expired
        
        // Calculate remaining days
        $remainingDays = $totalDays - $usedDays;
        if ($remainingDays < 0) $remainingDays = 0;
        
        // Calculate financial pro-rating
        $originalPrice = $orgUserPlan->price ?? 0;
        $usedAmount = $totalDays > 0 ? ($usedDays / $totalDays) * $originalPrice : 0;
        $remainingAmount = $originalPrice - $usedAmount;
        
        return [
            'totalDays' => $totalDays,
            'usedDays' => $usedDays,
            'remainingDays' => $remainingDays,
            'originalPrice' => $originalPrice,
            'usedAmount' => round($usedAmount, 2),
            'remainingAmount' => round($remainingAmount, 2),
            'usagePercentage' => $totalDays > 0 ? round(($usedDays / $totalDays) * 100, 2) : 0,
            'remainingPercentage' => $totalDays > 0 ? round(($remainingDays / $totalDays) * 100, 2) : 0
        ];
    }
    
    /**
     * Create new pro-rated membership for target member
     *
     * @param OrgUserPlan $originalMembership
     * @param OrgUser $targetMember
     * @param array $proRateCalculation
     * @param array $data
     * @param string|null $note
     * @return OrgUserPlan
     */
    private function createProRatedMembership(OrgUserPlan $originalMembership, OrgUser $targetMember, array $proRateCalculation, array $data, ?string $note): OrgUserPlan
    {
        $newMembership = new OrgUserPlan();
        
        // Copy basic information from original
        $newMembership->name = $originalMembership->name . ' (Transferred)';
        $newMembership->type = $originalMembership->type;
        $newMembership->orgUser_id = $targetMember->id;
        $newMembership->orgPlan_id = $originalMembership->orgPlan_id;
        $newMembership->org_id = $originalMembership->org_id;
        
        // Set pro-rated dates (from now to original end date)
        $newMembership->startDateLoc = Carbon::now();
        $newMembership->endDateLoc = $originalMembership->endDateLoc;
        
        // Set pro-rated pricing
        $newMembership->price = $proRateCalculation['remainingAmount'];
        $newMembership->currency = $originalMembership->currency;
        $newMembership->invoiceTotal = $proRateCalculation['remainingAmount'];
        $newMembership->invoiceCurrency = $originalMembership->invoiceCurrency;
        
        // Copy quota information (pro-rated)
        $remainingPercentage = $proRateCalculation['remainingPercentage'] / 100;
        $newMembership->totalQuota = $originalMembership->totalQuota ? 
            round($originalMembership->totalQuota * $remainingPercentage) : null;
        $newMembership->dailyQuota = $originalMembership->dailyQuota;
        $newMembership->totalQuotaConsumed = 0; // Start fresh for new member
        
        // Set status
        $newMembership->status = OrgUserPlan::STATUS_ACTIVE;
        $newMembership->isCanceled = false;
        
        // Add transfer note
        $transferNote = $this->buildProRateTransferNote($originalMembership, $proRateCalculation, $data, $note);
        $newMembership->note = json_encode([
            'transfer_info' => [
                'original_membership_id' => $originalMembership->id,
                'transferred_from' => $originalMembership->orgUser->fullName ?? 'Unknown',
                'transfer_date' => Carbon::now()->toISOString(),
                'pro_rate_calculation' => $proRateCalculation,
                'note' => $transferNote
            ]
        ]);
        
        $newMembership->created_at = Carbon::now();
        $newMembership->updated_at = Carbon::now();
        
        $newMembership->save();
        
        return $newMembership;
    }
    
    /**
     * Update original membership after pro-rated transfer
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $proRateCalculation
     * @param string $originalMemberName
     * @param string $targetMemberName
     * @param array $data
     * @param string|null $note
     */
    private function updateOriginalMembership(OrgUserPlan $orgUserPlan, array $proRateCalculation, string $originalMemberName, string $targetMemberName, array $data, ?string $note): void
    {
        // Update end date to current date (member keeps used portion)
        $orgUserPlan->endDateLoc = Carbon::now();
        
        // Update pricing to reflect used portion
        $orgUserPlan->price = $proRateCalculation['usedAmount'];
        $orgUserPlan->invoiceTotal = $proRateCalculation['usedAmount'];
        
        // Add transfer note
        $transferNote = "Pro-rated transfer to {$targetMemberName} on " . Carbon::now()->format('Y-m-d H:i:s');
        $transferNote .= " (Kept {$proRateCalculation['usedDays']} days, transferred {$proRateCalculation['remainingDays']} days)";
        
        if ($note) {
            $transferNote .= " - Note: {$note}";
        }
        
        $this->addNoteToMembership($orgUserPlan, $transferNote);
        
        $orgUserPlan->updated_at = Carbon::now();
        $orgUserPlan->save();
    }
    
    /**
     * Check if a membership can be transferred
     *
     * @param OrgUserPlan $orgUserPlan
     * @return bool
     */
    private function canTransferMembership(OrgUserPlan $orgUserPlan): bool
    {
        // Cannot transfer cancelled memberships
        if ($orgUserPlan->isCanceled || $orgUserPlan->status == OrgUserPlan::STATUS_CANCELED) {
            return false;
        }
        
        // Cannot transfer expired memberships
        if ($orgUserPlan->status == OrgUserPlan::STATUS_EXPIRED || 
            $orgUserPlan->status == OrgUserPlan::STATUS_EXPIRED_LIMIT) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Build pro-rate transfer note
     *
     * @param OrgUserPlan $originalMembership
     * @param array $proRateCalculation
     * @param array $data
     * @param string|null $note
     * @return string
     */
    private function buildProRateTransferNote(OrgUserPlan $originalMembership, array $proRateCalculation, array $data, ?string $note): string
    {
        $transferNote = "Pro-rated membership transfer from {$originalMembership->orgUser->fullName} on " . Carbon::now()->format('Y-m-d H:i:s');
        $transferNote .= " (Receiving {$proRateCalculation['remainingDays']} days of {$proRateCalculation['totalDays']} total days)";
        $transferNote .= " (Pro-rated amount: {$proRateCalculation['remainingAmount']} of {$proRateCalculation['originalPrice']} original price)";
        
        if ($data['paidTransfer'] ?? false) {
            $transferNote .= " (Paid transfer: {$data['transferFee']} via {$data['paymentMethod']})";
            if (!empty($data['receiptReference'])) {
                $transferNote .= " [Ref: {$data['receiptReference']}]";
            }
        }
        
        if ($note) {
            $transferNote .= " - Note: {$note}";
        }
        
        return $transferNote;
    }
    
    /**
     * Add note to membership notes
     *
     * @param OrgUserPlan $orgUserPlan
     * @param string $note
     */
    private function addNoteToMembership(OrgUserPlan $orgUserPlan, string $note): void
    {
        $existingNotes = $orgUserPlan->note ? json_decode($orgUserPlan->note, true) : [];
        
        if (!is_array($existingNotes)) {
            $existingNotes = [];
        }
        
        // Add transfer note
        if (!isset($existingNotes['transfers'])) {
            $existingNotes['transfers'] = [];
        }
        
        $existingNotes['transfers'][] = [
            'note' => $note,
            'created_at' => Carbon::now()->toISOString()
        ];
        
        $orgUserPlan->note = json_encode($existingNotes);
    }
    
    /**
     * Record transfer payment information
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $data
     */
    private function recordTransferPayment(OrgUserPlan $orgUserPlan, array $data): void
    {
        // Add payment info to the new membership notes
        $paymentInfo = [
            'amount' => $data['transferFee'],
            'method' => $data['paymentMethod'],
            'reference' => $data['receiptReference'] ?? null,
            'recorded_at' => Carbon::now()->toISOString()
        ];
        
        $existingNotes = $orgUserPlan->note ? json_decode($orgUserPlan->note, true) : [];
        if (!is_array($existingNotes)) {
            $existingNotes = [];
        }
        
        if (!isset($existingNotes['payments'])) {
            $existingNotes['payments'] = [];
        }
        
        $existingNotes['payments'][] = $paymentInfo;
        $orgUserPlan->note = json_encode($existingNotes);
        $orgUserPlan->save();
    }
}

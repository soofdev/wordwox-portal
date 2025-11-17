<?php

namespace App\Services;

use App\Models\OrgUserPlan;
use App\Models\OrgUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for handling membership transfers
 * Handles simple ownership transfers without pro-rating
 */
class OrgUserPlanTransferService
{
    /**
     * Transfer membership to another member (simple ownership change)
     *
     * @param OrgUserPlan $orgUserPlan The membership to transfer
     * @param array $data Transfer data including target member, fees, etc.
     * @param string|null $note Optional transfer note
     * @return OrgUserPlan|null The transferred membership or null on failure
     */
    public function transferMembership(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
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
            
            // Create transfer record for audit trail
            $this->createTransferRecord($orgUserPlan, $originalMember, $targetMember, $data, $note);
            
            // Update membership ownership
            $orgUserPlan->orgUser_id = $targetMember->id;
            $orgUserPlan->updated_at = Carbon::now();
            
            // Add transfer note to membership notes
            $transferNote = $this->buildTransferNote($originalMemberName, $targetMemberName, $data, $note);
            $this->addNoteToMembership($orgUserPlan, $transferNote);
            
            // Handle payment if this is a paid transfer
            if ($data['paidTransfer'] ?? false) {
                $this->recordTransferPayment($orgUserPlan, $data);
            }
            
            // Save the updated membership
            $orgUserPlan->save();
            
            // Log the transfer
            Log::info('Membership transferred successfully', [
                'membership_id' => $orgUserPlan->id,
                'from_member' => $originalMemberName,
                'to_member' => $targetMemberName,
                'paid_transfer' => $data['paidTransfer'] ?? false,
                'transfer_fee' => $data['transferFee'] ?? 0
            ]);
            
            DB::commit();
            return $orgUserPlan;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Membership transfer failed', [
                'membership_id' => $orgUserPlan->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
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
     * Create a transfer record for audit trail
     *
     * @param OrgUserPlan $orgUserPlan
     * @param OrgUser $fromMember
     * @param OrgUser $toMember
     * @param array $data
     * @param string|null $note
     */
    private function createTransferRecord(OrgUserPlan $orgUserPlan, OrgUser $fromMember, OrgUser $toMember, array $data, ?string $note): void
    {
        // This would typically create a record in a transfers table
        // For now, we'll just log it
        Log::info('Transfer record created', [
            'membership_id' => $orgUserPlan->id,
            'from_member_id' => $fromMember->id,
            'to_member_id' => $toMember->id,
            'transfer_data' => $data,
            'note' => $note,
            'created_at' => Carbon::now()
        ]);
    }
    
    /**
     * Build transfer note for membership
     *
     * @param string $fromMember
     * @param string $toMember
     * @param array $data
     * @param string|null $note
     * @return string
     */
    private function buildTransferNote(string $fromMember, string $toMember, array $data, ?string $note): string
    {
        $transferNote = "Membership transferred from {$fromMember} to {$toMember} on " . Carbon::now()->format('Y-m-d H:i:s');
        
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
        // This would typically create a payment record
        // For now, we'll add it to the membership notes
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
    }
}

<?php

namespace App\Services;

use App\Models\OrgUserPlan;
use App\Models\OrgUserPlanHold;
use App\Enums\OrgUserPlanStatus;
use App\Enums\OrgUserPlanHoldStatus;
use App\Enums\NoteType;
use App\Jobs\SendHoldNotificationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotesService;
use App\Services\Yii2QueueDispatcher;

class OrgUserPlanHoldService
{
    /**
     * @var NotesService
     */
    protected $notesService;

    /**
     * OrgUserPlanHoldService constructor.
     *
     * @param NotesService $notesService
     */
    public function __construct(NotesService $notesService)
    {
        $this->notesService = $notesService;
    }

    /**
     * Create a new hold for an OrgUserPlan
     *
     * @param OrgUserPlan $orgUserPlan The plan to put on hold
     * @param array $data The form data containing hold details
     * @param string|null $note Optional note explaining the hold reason
     * @return OrgUserPlanHold|null The created hold or null if creation failed
     */
    public function createHold(OrgUserPlan $orgUserPlan, array $data, ?string $note = null, bool $isBulkHold = false): ?OrgUserPlanHold
    {
        // Check if the plan can be modified (skip this check for bulk holds)
        if (!$isBulkHold && !$orgUserPlan->can_be_modified) {
            \Log::warning('CreateHold: Plan cannot be modified', ['membership_id' => $orgUserPlan->id]);
            return null;
        }

        // Check if the plan has holds enabled (skip this check for bulk holds)
        if (!$isBulkHold && !$orgUserPlan->isHoldEnabled) {
            \Log::warning('CreateHold: Holds not enabled', ['membership_id' => $orgUserPlan->id]);
            return null;
        }

        // Check if the plan has reached its hold limit
        if ($orgUserPlan->holdCount >= $orgUserPlan->holdLimitCount && $orgUserPlan->holdLimitCount > 0) {
            \Log::warning('CreateHold: Hold count limit reached', [
                'membership_id' => $orgUserPlan->id,
                'current_count' => $orgUserPlan->holdCount,
                'limit' => $orgUserPlan->holdLimitCount
            ]);
            return null;
        }

        // Check if the hold duration exceeds the maximum allowed
        // Note: End date is the resume date and is not included in the duration
        $startDate = Carbon::parse($data['startDate']);
        $endDate = Carbon::parse($data['endDate']);
        $holdDuration = $startDate->diffInDays($endDate); // End date NOT included in duration
        
        if ($orgUserPlan->holdDays + $holdDuration > $orgUserPlan->holdLimitDays && $orgUserPlan->holdLimitDays > 0) {
            \Log::warning('CreateHold: Hold days limit exceeded', [
                'membership_id' => $orgUserPlan->id,
                'current_days' => $orgUserPlan->holdDays,
                'adding_days' => $holdDuration,
                'limit' => $orgUserPlan->holdLimitDays
            ]);
            return null;
        }

        // Check for overlapping holds
        $overlappingHold = $this->getOverlappingHold(
            $orgUserPlan->id, 
            $startDate, 
            $endDate
        );

        if ($overlappingHold) {
            \Log::warning('CreateHold: Overlapping hold detected', [
                'membership_id' => $orgUserPlan->id,
                'existing_hold_id' => $overlappingHold->id,
                'existing_hold_status' => $overlappingHold->status,
                'existing_start' => $overlappingHold->startDateTime,
                'existing_end' => $overlappingHold->endDateTime,
                'requested_start' => $startDate->format('Y-m-d H:i:s'),
                'requested_end' => $endDate->format('Y-m-d H:i:s')
            ]);
            return null;
        }


        // Begin transaction
        DB::beginTransaction();

        try {
            // Create the hold
            $hold = new OrgUserPlanHold();
            $hold->org_id = $orgUserPlan->org_id;
            $hold->orgUser_id = $orgUserPlan->orgUser_id;
            // Set byOrgUser_id to the authenticated user's orgUser ID, not the user ID
            $hold->byOrgUser_id = (auth()->check() && auth()->user()->orgUser) 
                ? auth()->user()->orgUser->id 
                : $orgUserPlan->by_orgUser_id;
            $hold->orgUserPlan_id = $orgUserPlan->id;
            // Format dates as MySQL datetime strings instead of relying on model conversion
            $hold->startDateTime = $startDate->format('Y-m-d H:i:s');
            $hold->endDateTime = $endDate->format('Y-m-d H:i:s');
            $hold->note = $note ?? $data['note'] ?? null;
            // Only set groupName for bulk holds, leave null for individual holds
            $hold->groupName = isset($data['groupName']) ? $data['groupName'] : null;
            $hold->notifyEmail = $data['notifyEmail'] ?? false;
            $hold->notifyPush = $data['notifyPush'] ?? false;
            
        // Determine hold status based on start date
        $today = Carbon::today();
        if ($startDate->lte($today)) {
            // Hold starts today or in the past - make it active
            $hold->status = OrgUserPlanHoldStatus::Active->value;
        } else {
            // Hold starts in the future - make it upcoming
            $hold->status = OrgUserPlanHoldStatus::Upcoming->value;
        }
            
            // Timestamps are handled by BaseWWModel
            // $hold->preBookingBehaviorOnHoldStart = $data['preBookingBehaviorOnHoldStart'] ?? null;
            // $hold->preBookingBehaviorOnHoldEnd = $data['preBookingBehaviorOnHoldEnd'] ?? null;
            
            // Save the hold
            $success = $hold->save();

            if (!$success) {
                \Log::error('CreateHold: Failed to save hold', [
                    'membership_id' => $orgUserPlan->id,
                    'errors' => $hold->getErrors() ?? 'No errors available',
                    'validation_errors' => method_exists($hold, 'errors') ? $hold->errors()->toArray() : 'N/A'
                ]);
                DB::rollBack();
                return null;
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CreateHold: Exception during hold creation', [
                'membership_id' => $orgUserPlan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }

        // Update the plan status to Hold if the hold is active now
        if ($hold->status instanceof OrgUserPlanHoldStatus && $hold->status === OrgUserPlanHoldStatus::Active) {
            try {
                $orgUserPlan->status = OrgUserPlanStatus::Hold->value;
                $orgUserPlan->save();
            } catch (\Exception $e) {
                \Log::error('OrgUserPlanHoldService: Failed to update membership status', [
                    'membership_id' => $orgUserPlan->id,
                    'error' => $e->getMessage()
                ]);
                // Continue anyway - the hold record was created successfully
            }
        }

        try {
            // Update the hold count on the plan
            $orgUserPlan->holdCount = ($orgUserPlan->holdCount ?? 0) + 1;
            $orgUserPlan->holdDays = ($orgUserPlan->holdDays ?? 0) + $holdDuration;
            $orgUserPlan->save();
        } catch (\Exception $e) {
            // Continue anyway - this is not critical
        }

        try {
            // Add a note about the hold
            // Get the author ID from the authenticated user or use a fallback
            $authorId = null;
            if (auth()->check() && auth()->user()->orgUser) {
                $authorId = auth()->user()->orgUser->id;
            } else {
                // Fallback: use the membership creator
                $authorId = $orgUserPlan->by_orgUser_id ?? 1;
            }
            
            $this->notesService->addNote(
                $orgUserPlan,
                'Membership Plan Put On Hold',
                "Plan '{$orgUserPlan->name}' was put on hold from {$startDate->format('M d, Y')} to {$endDate->format('M d, Y')}." . 
                ($note ? "\n\nReason: {$note}" : ""),
                NoteType::GENERAL,
                $authorId
            );
        } catch (\Exception $e) {
            // Continue anyway - this is not critical
        }

        DB::commit();
        
        // Dispatch notification job if notifications are enabled
        if ($data['notifyEmail'] || $data['notifyPush']) {
            SendHoldNotificationJob::dispatch(
                $hold->id,
                'created',
                $data['notifyEmail'] ?? false,
                $data['notifyPush'] ?? false
            );
            
            Log::info('OrgUserPlanHoldService: Notification job dispatched', [
                'hold_id' => $hold->id,
                'notify_email' => $data['notifyEmail'] ?? false,
                'notify_push' => $data['notifyPush'] ?? false
            ]);
        }
        
        return $hold;

        // try {
            
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('Failed to create hold: ' . $e->getMessage(), [
        //         'orgUserPlan_id' => $orgUserPlan->id,
        //         'exception' => $e
        //     ]);
        //     return null;
        // }
    }

    /**
     * Cancel an existing hold
     *
     * @param OrgUserPlanHold $hold The hold to cancel
     * @param string|null $cancelNote Optional note explaining the cancellation reason
     * @return bool Whether the cancellation was successful
     */
    public function cancelHold(OrgUserPlanHold $hold, ?string $cancelNote = null): bool
    {
        // Check if the hold is already canceled
        if ($hold->isCanceled) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Get the associated plan and original status before modifying the hold
            $orgUserPlan = $hold->orgUserPlan;
            $originalStatus = $hold->status;
            
            // Cancel the hold
            $hold->isCanceled = true;
            // Set the status to Canceled (the booted method will handle this automatically based on isCanceled)
            $success = $hold->save();

            if (!$success) {
                DB::rollBack();
                return false;
            }

            // Always update the plan status when cancelling a hold
            if ($orgUserPlan) {
                $oldPlanStatus = $orgUserPlan->status;
                $oldEndDate = $orgUserPlan->endDateLoc;
                $holdDays = 0;
                
                // If the hold was active, extend the membership end date
                if ($originalStatus === OrgUserPlanHoldStatus::Active->value) {
                    // Calculate hold duration in days (from start to cancellation)
                    $holdStartDate = Carbon::parse($hold->startDateTime);
                    $holdCancelDate = Carbon::now(); // Hold is being cancelled now
                    $holdDays = $holdStartDate->diffInDays($holdCancelDate);
                    
                       // Extend the membership end date by the hold duration
                       if ($orgUserPlan->endDateLoc) {
                           $currentEndDate = Carbon::parse($orgUserPlan->endDateLoc);
                           $newEndDate = $currentEndDate->addDays($holdDays);
                           $orgUserPlan->endDateLoc = $newEndDate->format('Y-m-d');
                           $orgUserPlan->saveQuietly();
                       }
                }
                
                // Always reactivate the plan (determine appropriate status based on dates)
                $this->activatePlan($orgUserPlan);
            }

            // Add a note about the cancellation
            if ($orgUserPlan) {
                // Get the author ID from the authenticated user or use a fallback
                $authorId = null;
                if (auth()->check() && auth()->user()->orgUser) {
                    $authorId = auth()->user()->orgUser->id;
                } else {
                    // Fallback: use the hold creator or the membership creator
                    $authorId = $hold->byOrgUser_id ?? $orgUserPlan->by_orgUser_id ?? 1;
                }
                
                $this->notesService->addNote(
                    $orgUserPlan,
                    'Membership Plan Hold Canceled',
                    "Hold from " . (is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('M d, Y') : $hold->startDateTime->format('M d, Y')) . " to " . (is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('M d, Y') : $hold->endDateTime->format('M d, Y')) . " was canceled." . 
                    ($cancelNote ? "\n\nReason: {$cancelNote}" : ""),
                    NoteType::GENERAL,
                    $authorId
                );
            }

            DB::commit();
            
            // Dispatch notification job for hold cancellation
            if ($hold->notifyEmail || $hold->notifyPush) {
                SendHoldNotificationJob::dispatch(
                    $hold->id,
                    'cancelled',
                    $hold->notifyEmail ?? false,
                    $hold->notifyPush ?? false
                );
                
                Log::info('OrgUserPlanHoldService: Cancel hold notification job dispatched', [
                    'hold_id' => $hold->id,
                    'notify_email' => $hold->notifyEmail ?? false,
                    'notify_push' => $hold->notifyPush ?? false
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel hold: ' . $e->getMessage(), [
                'hold_id' => $hold->id,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * End a hold by setting the end date to now.
     *
     * @param OrgUserPlanHold $hold The hold to end
     * @param string|null $note Optional note explaining why the hold is ending
     * @param int|null $endedBy Optional user ID who ended the hold (for notes)
     * @return bool Whether ending the hold was successful
     */
    public function endHold(OrgUserPlanHold $hold, ?string $note = null, ?int $endedBy = null): bool
    {
        // Check if the hold is already canceled or expired
        if ($hold->isCanceled || $hold->status === OrgUserPlanHoldStatus::Expired) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Set the end date to today (start of day)
            $originalEndDate = is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime) : $hold->endDateTime->copy();
            $hold->endDateTime = Carbon::today(); // Today at 00:00:00
            $hold->status = OrgUserPlanHoldStatus::Expired->value;
            $success = $hold->save();

            if (!$success) {
                DB::rollBack();
                return false;
            }

            // Get the associated plan
            $orgUserPlan = $hold->orgUserPlan;

                // Reactivate the plan and extend end date
                if ($orgUserPlan) {
                    $oldStatus = $orgUserPlan->status;
                    $oldEndDate = $orgUserPlan->endDateLoc;
                    
                    // Calculate hold duration in days
                    $holdStartDate = Carbon::parse($hold->startDateTime);
                    $holdEndDate = Carbon::parse($hold->endDateTime);
                    $holdDays = $holdStartDate->diffInDays($holdEndDate);
                    
                    // Extend the membership end date by the hold duration
                    if ($orgUserPlan->endDateLoc) {
                        $currentEndDate = Carbon::parse($orgUserPlan->endDateLoc);
                        $newEndDate = $currentEndDate->addDays($holdDays);
                        $orgUserPlan->endDateLoc = $newEndDate->format('Y-m-d');
                        
                        // Save without triggering model events (to avoid Yii2 jobs)
                        $orgUserPlan->saveQuietly();
                    }
                    
                    $activateResult = $this->activatePlan($orgUserPlan);
                $newStatus = $orgUserPlan->fresh()->status;
                
                \Log::info('EndHold: Plan status and date update', [
                    'membership_id' => $orgUserPlan->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'old_end_date' => $oldEndDate,
                    'new_end_date' => $orgUserPlan->endDateLoc,
                    'hold_days' => $holdDays,
                    'activate_result' => $activateResult,
                    'start_date_loc' => $orgUserPlan->startDateLoc,
                    'today' => Carbon::now()->format('Y-m-d'),
                    'start_vs_today' => $orgUserPlan->startDateLoc . ' vs ' . Carbon::now()->format('Y-m-d'),
                    'start_is_future' => $orgUserPlan->startDateLoc > Carbon::now()->format('Y-m-d')
                ]);
            }

            // Add a note about ending the hold early
            if ($orgUserPlan && $note) {
                $this->notesService->addNote(
                    $orgUserPlan,
                    'Membership Plan Hold Ended Early',
                    "Hold originally scheduled to end on {$originalEndDate->format('M d, Y')} was ended early on " . (is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('M d, Y') : $hold->endDateTime->format('M d, Y')) . "." . 
                    ($note ? "\n\nReason: {$note}" : ""),
                    NoteType::GENERAL,
                    $endedBy, // authorId
                    false,    // notifyMember
                    null,     // notifyStaffId
                    null,     // reminderAt
                    null      // orgUserId
                );
            }

            DB::commit();
            
            // Dispatch Yii2 queue job for hold expiration completion
            $yii2Dispatcher = new Yii2QueueDispatcher();
            $yii2Dispatcher->dispatch(
                'common\jobs\plan\OrgUserPlanHoldExpireCompleteJob',
                ['id' => $hold->id]
            );
            
            Log::info('OrgUserPlanHoldService: Yii2 hold expire complete job dispatched', [
                'hold_id' => $hold->id,
                'job_class' => 'common\jobs\plan\OrgUserPlanHoldExpireCompleteJob'
            ]);
            
            // Dispatch notification job for hold end
            if ($hold->notifyEmail || $hold->notifyPush) {
                SendHoldNotificationJob::dispatch(
                    $hold->id,
                    'ended',
                    $hold->notifyEmail ?? false,
                    $hold->notifyPush ?? false
                );
                
                Log::info('OrgUserPlanHoldService: End hold notification job dispatched', [
                    'hold_id' => $hold->id,
                    'notify_email' => $hold->notifyEmail ?? false,
                    'notify_push' => $hold->notifyPush ?? false
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to end hold: ' . $e->getMessage(), [
                'hold_id' => $hold->id,
                'exception' => $e
            ]);
            return false;
        }
    }


    /**
     * Activate the associated plan for a hold.
     *
     * @param OrgUserPlan $orgUserPlan The plan to activate
     * @return bool Whether the activation was successful
     */
    protected function activatePlan(OrgUserPlan $orgUserPlan): bool
    {
        // Set the appropriate status based on dates
        $orgUserPlan->status = $this->determineAppropriateStatus($orgUserPlan);
        // Use saveQuietly to avoid triggering Yii2 jobs during bulk operations
        return $orgUserPlan->saveQuietly();
    }

    /**
     * Determine the appropriate status for a plan based on its dates
     *
     * @param OrgUserPlan $orgUserPlan The plan to check
     * @return int The appropriate status
     */
    private function determineAppropriateStatus(OrgUserPlan $orgUserPlan): int
    {
        $today = Carbon::now()->format('Y-m-d');
        
        \Log::info('Determining appropriate status', [
            'membership_id' => $orgUserPlan->id,
            'today' => $today,
            'startDateLoc' => $orgUserPlan->startDateLoc,
            'endDateLoc' => $orgUserPlan->endDateLoc,
            'start_comparison' => $orgUserPlan->startDateLoc > $today ? 'start > today (upcoming)' : 'start <= today',
            'end_comparison' => $orgUserPlan->endDateLoc && $orgUserPlan->endDateLoc < $today ? 'end < today (expired)' : 'end >= today or null'
        ]);
        
        if ($orgUserPlan->startDateLoc > $today) {
            \Log::info('Status determined: Upcoming', ['membership_id' => $orgUserPlan->id]);
            return OrgUserPlanStatus::Upcoming->value;
        } elseif ($orgUserPlan->endDateLoc && $orgUserPlan->endDateLoc < $today) {
            \Log::info('Status determined: Expired', ['membership_id' => $orgUserPlan->id]);
            return OrgUserPlanStatus::Expired->value;
        } else {
            \Log::info('Status determined: Active', ['membership_id' => $orgUserPlan->id]);
            return OrgUserPlanStatus::Active->value;
        }
    }

    /**
     * Check if there are any overlapping holds for the same plan.
     *
     * @param int $planId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param array $excludeIds
     * @return \App\Models\OrgUserPlanHold|null
     */
    public function getOverlappingHold(int $planId, Carbon $startDate, Carbon $endDate, array $excludeIds = []): ?OrgUserPlanHold
    {
        $query = OrgUserPlanHold::where('orgUserPlan_id', $planId)
            ->where('isCanceled', false)
            // Only check for Active and Upcoming holds, not Expired or Canceled
            ->whereIn('status', [OrgUserPlanHoldStatus::Active->value, OrgUserPlanHoldStatus::Upcoming->value])
            ->where(function($query) use ($startDate, $endDate) {
                // Start date falls within an existing hold
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->where('startDateTime', '<=', $startDate)
                      ->where('endDateTime', '>=', $startDate);
                })
                // End date falls within an existing hold
                ->orWhere(function($q) use ($startDate, $endDate) {
                    $q->where('startDateTime', '<=', $endDate)
                      ->where('endDateTime', '>=', $endDate);
                })
                // Hold completely contains the new date range
                ->orWhere(function($q) use ($startDate, $endDate) {
                    $q->where('startDateTime', '>=', $startDate)
                      ->where('endDateTime', '<=', $endDate);
                });
            });
            
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        return $query->first();
    }

    /**
     * Modify an existing hold
     *
     * @param OrgUserPlanHold $hold The hold to modify
     * @param array $data The form data containing new hold details
     * @param string|null $note Optional note explaining the modification reason
     * @return bool Whether the modification was successful
     */
    public function modifyHold(OrgUserPlanHold $hold, array $data, ?string $note = null): bool
    {
        \Log::info('OrgUserPlanHoldService::modifyHold started', [
            'hold_id' => $hold->id,
            'data' => $data,
            'note' => $note
        ]);

        // Check if the hold can be modified
        if ($hold->isCanceled) {
            \Log::error('ModifyHold failed: Hold is canceled', ['hold_id' => $hold->id]);
            return false;
        }

        $orgUserPlan = $hold->orgUserPlan;
        if (!$orgUserPlan) {
            \Log::error('ModifyHold failed: No orgUserPlan found', ['hold_id' => $hold->id]);
            return false;
        }

        $newStartDate = Carbon::parse($data['startDate']);
        $newEndDate = Carbon::parse($data['endDate']);
        $currentStatus = $hold->status;
        $today = Carbon::now();

        \Log::info('ModifyHold validation data', [
            'hold_id' => $hold->id,
            'newStartDate' => $newStartDate->format('Y-m-d'),
            'newEndDate' => $newEndDate->format('Y-m-d'),
            'currentStatus' => $currentStatus,
            'today' => $today->format('Y-m-d'),
            'isCanceled' => $hold->isCanceled
        ]);

        DB::beginTransaction();

        try {
            // Validation based on hold status
            $statusValue = $currentStatus instanceof \App\Enums\OrgUserPlanHoldStatus ? $currentStatus->value : $currentStatus;
            
            if ($statusValue === OrgUserPlanHoldStatus::Upcoming->value) {
                \Log::info('ModifyHold: Processing upcoming hold');
                
                // For upcoming holds, allow full modification like creating a new hold
                if (!$orgUserPlan->can_be_modified) {
                    \Log::error('ModifyHold failed: Plan cannot be modified', ['hold_id' => $hold->id]);
                    DB::rollBack();
                    return false;
                }

                if (!$orgUserPlan->isHoldEnabled) {
                    \Log::error('ModifyHold failed: Holds not enabled', ['hold_id' => $hold->id]);
                    DB::rollBack();
                    return false;
                }

                // Check for overlapping holds (excluding current hold)
                $overlappingHold = $this->getOverlappingHold(
                    $orgUserPlan->id, 
                    $newStartDate, 
                    $newEndDate,
                    [$hold->id]
                );

                if ($overlappingHold) {
                    \Log::error('ModifyHold failed: Overlapping hold found', [
                        'hold_id' => $hold->id,
                        'overlapping_hold_id' => $overlappingHold->id
                    ]);
                    DB::rollBack();
                    return false;
                }

                // Update hold dates
                $hold->startDateTime = $newStartDate->format('Y-m-d H:i:s');
                $hold->endDateTime = $newEndDate->format('Y-m-d H:i:s');
                
                // Update hold status based on new start date
                $today = Carbon::today();
                if ($newStartDate->lte($today)) {
                    // Hold starts today or in the past - make it active
                    $hold->status = OrgUserPlanHoldStatus::Active->value;
                    
                    // Update membership status to Hold
                    $orgUserPlan->status = OrgUserPlanStatus::Hold->value;
                    $orgUserPlan->save();
                    
                    \Log::info('ModifyHold: Updated hold and membership status to active/hold', [
                        'hold_id' => $hold->id,
                        'membership_id' => $orgUserPlan->id,
                        'new_start_date' => $newStartDate->format('Y-m-d'),
                        'today' => $today->format('Y-m-d')
                    ]);
                } else {
                    // Hold starts in the future - keep it upcoming
                    $hold->status = OrgUserPlanHoldStatus::Upcoming->value;
                    
                    \Log::info('ModifyHold: Hold remains upcoming', [
                        'hold_id' => $hold->id,
                        'new_start_date' => $newStartDate->format('Y-m-d'),
                        'today' => $today->format('Y-m-d')
                    ]);
                }

            } elseif ($statusValue === OrgUserPlanHoldStatus::Active->value) {
                \Log::info('ModifyHold: Processing active hold');
                
                // For active holds, only allow end date modification
                $currentStartDate = Carbon::parse($hold->startDateTime);
                if (!$newStartDate->isSameDay($currentStartDate)) {
                    \Log::error('ModifyHold failed: Start date changed for active hold', ['hold_id' => $hold->id]);
                    DB::rollBack();
                    return false;
                }

                // Validate new end date is not before today
                if ($newEndDate->lt($today->startOfDay())) {
                    \Log::error('ModifyHold failed: End date is before today for active hold', ['hold_id' => $hold->id]);
                    DB::rollBack();
                    return false;
                }

                // Check for overlapping holds with new end date (excluding current hold)
                $overlappingHold = $this->getOverlappingHold(
                    $orgUserPlan->id, 
                    $currentStartDate, 
                    $newEndDate,
                    [$hold->id]
                );

                if ($overlappingHold) {
                    \Log::error('ModifyHold failed: Overlapping hold found for active hold', [
                        'hold_id' => $hold->id,
                        'overlapping_hold_id' => $overlappingHold->id
                    ]);
                    DB::rollBack();
                    return false;
                }

                // Update end date only
                $hold->endDateTime = $newEndDate->format('Y-m-d H:i:s');

                // If end date is changed to today, end the hold immediately
                if ($newEndDate->isSameDay($today)) {
                    \Log::info('ModifyHold: Ending active hold immediately as end date is today', ['hold_id' => $hold->id]);
                    $hold->status = OrgUserPlanHoldStatus::Expired->value;
                    
                    // Reactivate the membership
                    $this->activatePlan($orgUserPlan);
                }

            } else {
                // Cannot modify expired or other status holds
                \Log::error('ModifyHold failed: Invalid status for modification', [
                    'hold_id' => $hold->id,
                    'status' => $statusValue
                ]);
                DB::rollBack();
                return false;
            }

            // Update hold note if provided
            if ($note) {
                $hold->note = $note;
            }

            // Update timestamps
            $hold->updated_at = now()->timestamp;
            
            // Save the hold
            $success = $hold->save();
            if (!$success) {
                \Log::error('ModifyHold failed: Failed to save hold model', ['hold_id' => $hold->id]);
                DB::rollBack();
                return false;
            }

            // Add a note about the modification
            if ($orgUserPlan) {
                // Get the author ID from the authenticated user or use a fallback
                $authorId = null;
                if (auth()->check() && auth()->user()->orgUser) {
                    $authorId = auth()->user()->orgUser->id;
                } else {
                    // Fallback: use the hold creator or the membership creator
                    $authorId = $hold->byOrgUser_id ?? $orgUserPlan->by_orgUser_id ?? 1;
                }
                
                $this->notesService->addNote(
                    $orgUserPlan,
                    'Membership Plan Hold Modified',
                    "Hold dates were modified. New dates: {$newStartDate->format('M d, Y')} to {$newEndDate->format('M d, Y')}." . 
                    ($note ? "\n\nReason: {$note}" : ""),
                    NoteType::GENERAL,
                    $authorId
                );
            }

            DB::commit();
            \Log::info('ModifyHold: Successfully modified hold', ['hold_id' => $hold->id]);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to modify hold: ' . $e->getMessage(), [
                'hold_id' => $hold->id,
                'exception' => $e
            ]);
            return false;
        }
    }
}
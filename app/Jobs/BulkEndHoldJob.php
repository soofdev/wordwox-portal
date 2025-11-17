<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\OrgUserPlan;
use App\Models\OrgUserPlanHold;
use App\Services\OrgUserPlanHoldService;
use App\Jobs\SendHoldNotificationJob;
use Illuminate\Support\Facades\Log;

class BulkEndHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orgId;
    public $planId; // null for all holds
    public $endData;
    public $endedBy;

    /**
     * Create a new job instance.
     */
    public function __construct($orgId, $planId, $endData, $endedBy)
    {
        $this->orgId = $orgId;
        $this->planId = $planId;
        $this->endData = $endData;
        $this->endedBy = $endedBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('BulkEndHoldJob started', [
            'orgId' => $this->orgId,
            'planId' => $this->planId,
            'groupName' => $this->endData['groupName'] ?? null,
            'endedBy' => $this->endedBy
        ]);

        try {
            $holdService = app(OrgUserPlanHoldService::class);
            $successCount = 0;
            $failureCount = 0;
            $skippedCount = 0;

            // Check if we have the new hold table
            if ($this->holdTableExists()) {
                $holds = $this->getHoldsFromTable();
                
                foreach ($holds as $hold) {
                    try {
                        $success = $holdService->endHold($hold, $this->endData['reason'], $this->endedBy);
                        
                        if ($success) {
                            $successCount++;
                            Log::info('BulkEndHoldJob: Hold ended successfully', [
                                'hold_id' => $hold->id,
                                'membership_id' => $hold->orgUserPlan_id
                            ]);
                            
                            // Dispatch notification job if notifications are enabled
                            if ($hold->notifyEmail || $hold->notifyPush) {
                                SendHoldNotificationJob::dispatch(
                                    $hold->id,
                                    'ended',
                                    $hold->notifyEmail ?? false,
                                    $hold->notifyPush ?? false
                                );
                            }
                        } else {
                            $failureCount++;
                            Log::warning('BulkEndHoldJob: Hold end failed', [
                                'hold_id' => $hold->id,
                                'reason' => 'Service returned false'
                            ]);
                        }
                    } catch (\Exception $e) {
                        $failureCount++;
                        Log::error('BulkEndHoldJob: Exception ending hold', [
                            'hold_id' => $hold->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                // Fallback: handle note-based holds
                $memberships = $this->getMembershipsWithNoteHolds();
                
                foreach ($memberships as $membership) {
                    try {
                        $success = $this->endNoteBasedHold($membership);
                        
                        if ($success) {
                            $successCount++;
                            Log::info('BulkEndHoldJob: Note-based hold ended successfully', [
                                'membership_id' => $membership->id
                            ]);
                        } else {
                            $failureCount++;
                            Log::warning('BulkEndHoldJob: Note-based hold end failed', [
                                'membership_id' => $membership->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        $failureCount++;
                        Log::error('BulkEndHoldJob: Exception ending note-based hold', [
                            'membership_id' => $membership->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('BulkEndHoldJob completed', [
                'orgId' => $this->orgId,
                'planId' => $this->planId,
                'groupName' => $this->endData['groupName'] ?? null,
                'success' => $successCount,
                'failed' => $failureCount,
                'skipped' => $skippedCount
            ]);

        } catch (\Exception $e) {
            Log::error('BulkEndHoldJob failed', [
                'orgId' => $this->orgId,
                'planId' => $this->planId,
                'groupName' => $this->endData['groupName'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get holds from the OrgUserPlanHold table
     */
    private function getHoldsFromTable()
    {
        $query = OrgUserPlanHold::where('org_id', $this->orgId)
            ->where('isCanceled', false)
            ->whereIn('status', [
                \App\Enums\OrgUserPlanHoldStatus::Active->value,
                \App\Enums\OrgUserPlanHoldStatus::Upcoming->value
            ]);

        // Filter by group name if specified (new group-based approach)
        if (isset($this->endData['groupName']) && !empty($this->endData['groupName'])) {
            $query->where('groupName', $this->endData['groupName']);
        }

        // Filter by plan if specified
        if ($this->planId) {
            $query->whereHas('orgUserPlan', function($q) {
                $q->where('orgPlan_id', $this->planId);
            });
        }

        // Filter by specific users if specified
        if (isset($this->endData['selectedUserIds']) && !empty($this->endData['selectedUserIds'])) {
            $query->whereIn('orgUser_id', $this->endData['selectedUserIds']);
        }

        return $query->get();
    }

    /**
     * Get memberships with note-based holds
     */
    private function getMembershipsWithNoteHolds()
    {
        $query = OrgUserPlan::where('org_id', $this->orgId)
            ->where('status', OrgUserPlan::STATUS_HOLD);

        // Filter by plan if specified
        if ($this->planId) {
            $query->where('orgPlan_id', $this->planId);
        }

        // Filter by specific users if specified
        if (isset($this->endData['selectedUserIds']) && !empty($this->endData['selectedUserIds'])) {
            $query->whereIn('orgUser_id', $this->endData['selectedUserIds']);
        }

        return $query->get();
    }

    /**
     * End a note-based hold
     */
    private function endNoteBasedHold($membership): bool
    {
        try {
            if (!$membership->note) {
                return false;
            }

            $noteData = json_decode($membership->note, true);
            if (!is_array($noteData) || !isset($noteData['holdInfo'])) {
                return false;
            }

            // Update hold info to mark as ended
            $noteData['holdInfo']['endDate'] = now()->format('Y-m-d');
            $noteData['holdInfo']['endedAt'] = now()->toDateTimeString();
            $noteData['holdInfo']['endedBy'] = $this->endedBy;
            $noteData['holdInfo']['status'] = 'ended';
            
            if ($this->endData['reason']) {
                $noteData['holdInfo']['endReason'] = $this->endData['reason'];
            }

            // Restore membership to active status
            $membership->update([
                'status' => OrgUserPlan::STATUS_ACTIVE,
                'note' => json_encode($noteData)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('BulkEndHoldJob: Failed to end note-based hold', [
                'membership_id' => $membership->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if the orgUserPlanHold table exists
     */
    private function holdTableExists(): bool
    {
        try {
            return \Schema::hasTable('orgUserPlanHold');
        } catch (\Exception $e) {
            return false;
        }
    }
}

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
use Illuminate\Support\Facades\Schema;

class BulkHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orgId;
    public $planId; // null for all users
    public $holdData;
    public $createdBy;

    /**
     * Create a new job instance.
     */
    public function __construct($orgId, $planId, $holdData, $createdBy)
    {
        $this->orgId = $orgId;
        $this->planId = $planId;
        $this->holdData = $holdData;
        $this->createdBy = $createdBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('BulkHoldJob started', [
            'orgId' => $this->orgId,
            'planId' => $this->planId,
            'createdBy' => $this->createdBy
        ]);

        try {
            $holdService = app(OrgUserPlanHoldService::class);
            
            // Build query for memberships to hold
            $query = OrgUserPlan::where('org_id', $this->orgId)
                ->whereIn('status', [
                    OrgUserPlan::STATUS_ACTIVE,
                    OrgUserPlan::STATUS_UPCOMING
                ]);

            // Filter by plan if specified
            if ($this->planId) {
                $query->where('orgPlan_id', $this->planId);
            }

            // Filter by specific users if specified
            if (isset($this->holdData['selectedUserIds']) && !empty($this->holdData['selectedUserIds'])) {
                $query->whereIn('orgUser_id', $this->holdData['selectedUserIds']);
            }

            $memberships = $query->get();
            
            Log::info('BulkHoldJob processing memberships', [
                'count' => $memberships->count(),
                'orgId' => $this->orgId,
                'planId' => $this->planId
            ]);

            $successCount = 0;
            $failureCount = 0;
            $skippedCount = 0;

            foreach ($memberships as $membership) {
                try {
                    // Check if membership can be held based on comprehensive validation
                    $canHold = $this->canHoldMembership($membership);
                    
                    if (!$canHold['allowed']) {
                        // Log detailed information about why the membership was skipped
                        $logData = [
                            'membership_id' => $membership->id,
                            'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                            'reason' => $canHold['reason'],
                            'scenario' => $canHold['scenario']
                        ];

                        // Add additional context based on scenario
                        switch ($canHold['scenario']) {
                            case 'invalid_dates':
                                $logData['validation_errors'] = $canHold['validation_errors'] ?? [];
                                break;
                            case 'active_hold_exists':
                                $logData['existing_holds'] = $canHold['existing_holds'] ?? [];
                                $logData['hold_history_count'] = count($canHold['hold_history'] ?? []);
                                break;
                            case 'overlapping_holds':
                                $logData['overlapping_holds'] = $canHold['overlapping_holds'] ?? [];
                                $logData['hold_history_count'] = count($canHold['hold_history'] ?? []);
                                break;
                        }

                        Log::info('BulkHoldJob: Membership skipped with detailed validation', $logData);
                        $skippedCount++;
                        continue;
                    }

                    // Apply scenario-specific logic
                    $adjustedHoldData = $this->adjustHoldDataForScenario($membership, $this->holdData, $canHold['scenario']);

                    // Create the hold
                    $hold = $holdService->createHold($membership, $adjustedHoldData, $this->holdData['reason']);

                    if ($hold) {
                        $successCount++;
                        Log::info('BulkHoldJob: Hold created successfully', [
                            'membership_id' => $membership->id,
                            'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                            'hold_id' => $hold->id,
                            'scenario' => $canHold['scenario'],
                            'start_date' => $adjustedHoldData['startDate'],
                            'end_date' => $adjustedHoldData['endDate']
                        ]);
                        
                        // Dispatch notification job if notifications are enabled
                        if ($this->holdData['notifyEmail'] || $this->holdData['notifyPush']) {
                            SendHoldNotificationJob::dispatch(
                                $hold->id,
                                'created',
                                $this->holdData['notifyEmail'] ?? false,
                                $this->holdData['notifyPush'] ?? false
                            );
                        }
                    } else {
                        $failureCount++;
                        Log::warning('BulkHoldJob: Hold creation failed', [
                            'membership_id' => $membership->id,
                            'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                            'reason' => 'Service returned null'
                        ]);
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('BulkHoldJob: Exception creating hold', [
                        'membership_id' => $membership->id,
                        'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('BulkHoldJob completed', [
                'orgId' => $this->orgId,
                'planId' => $this->planId,
                'total' => $memberships->count(),
                'success' => $successCount,
                'failed' => $failureCount,
                'skipped' => $skippedCount
            ]);

        } catch (\Exception $e) {
            Log::error('BulkHoldJob failed', [
                'orgId' => $this->orgId,
                'planId' => $this->planId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Check if membership can be held based on scenarios matrix with comprehensive validation
     */
    private function canHoldMembership($membership): array
    {
        $membershipStatus = $membership->status;
        // Note: We only process Active and Upcoming memberships, so no need to check for STATUS_HOLD

        // Step 1: Validate dates
        $dateValidation = $this->validateHoldDates($membership);
        if (!$dateValidation['valid']) {
            return [
                'allowed' => false,
                'reason' => $dateValidation['reason'],
                'scenario' => 'invalid_dates',
                'validation_errors' => $dateValidation['errors'] ?? []
            ];
        }

        // Step 2: Check for active holds
        $activeHoldCheck = $this->checkActiveHolds($membership);
        if (!$activeHoldCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $activeHoldCheck['reason'],
                'scenario' => 'active_hold_exists',
                'existing_holds' => $activeHoldCheck['holds'] ?? [],
                'hold_history' => $this->getHoldHistory($membership)
            ];
        }

        // Step 3: Check for overlapping holds
        $overlapCheck = $this->checkOverlappingHolds($membership);
        if (!$overlapCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $overlapCheck['reason'],
                'scenario' => 'overlapping_holds',
                'overlapping_holds' => $overlapCheck['holds'] ?? [],
                'hold_history' => $this->getHoldHistory($membership)
            ];
        }

        // Step 4: Check membership status eligibility
        if (in_array($membershipStatus, [
            OrgUserPlan::STATUS_CANCELED,
            OrgUserPlan::STATUS_EXPIRED,
            OrgUserPlan::STATUS_EXPIRED_LIMIT,
            OrgUserPlan::STATUS_DELETED
        ])) {
            return [
                'allowed' => false,
                'reason' => 'Cannot hold inactive membership',
                'scenario' => 'inactive_membership'
            ];
        }

        // Step 5: Determine scenario for valid holds
        if ($membershipStatus === OrgUserPlan::STATUS_ACTIVE) {
            return [
                'allowed' => true,
                'reason' => 'Active membership can be held',
                'scenario' => 'active_membership_new_hold'
            ];
        }

        if ($membershipStatus === OrgUserPlan::STATUS_UPCOMING) {
            return [
                'allowed' => true,
                'reason' => 'Upcoming membership can be held',
                'scenario' => 'upcoming_membership_new_hold'
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Unknown membership status',
            'scenario' => 'unknown_status'
        ];
    }

    /**
     * Adjust hold data based on scenario
     */
    private function adjustHoldDataForScenario($membership, $holdData, $scenario): array
    {
        $adjustedData = $holdData;

        switch ($scenario) {
            case 'active_membership_new_hold':
                // For active memberships, hold can start immediately or in future
                // No adjustments needed
                break;

            case 'upcoming_membership_new_hold':
                // For upcoming memberships, ensure hold doesn't start before membership
                $membershipStartDate = \Carbon\Carbon::parse($membership->startDateLoc);
                $holdStartDate = \Carbon\Carbon::parse($holdData['startDate']);

                if ($holdStartDate->lt($membershipStartDate)) {
                    // Adjust hold to start when membership starts
                    $adjustedData['startDate'] = $membershipStartDate->format('Y-m-d');
                    
                    Log::info('BulkHoldJob: Adjusted hold start date for upcoming membership', [
                        'membership_id' => $membership->id,
                        'original_hold_start' => $holdData['startDate'],
                        'adjusted_hold_start' => $adjustedData['startDate'],
                        'membership_start' => $membershipStartDate->format('Y-m-d')
                    ]);
                }
                break;
        }

        return $adjustedData;
    }

    /**
     * Validate hold start and end dates
     */
    private function validateHoldDates($membership): array
    {
        $errors = [];
        $startDate = \Carbon\Carbon::parse($this->holdData['startDate']);
        $endDate = \Carbon\Carbon::parse($this->holdData['endDate']);
        $today = \Carbon\Carbon::today();

        // Validate start date is not in the past
        if ($startDate->lt($today)) {
            $errors[] = 'Hold start date cannot be in the past';
        }

        // Validate end date is after start date
        if ($endDate->lte($startDate)) {
            $errors[] = 'Hold end date must be after start date';
        }

        // Validate hold duration is reasonable (not too long)
        $duration = $startDate->diffInDays($endDate);
        $maxDuration = 365; // Maximum 1 year hold
        if ($duration > $maxDuration) {
            $errors[] = "Hold duration cannot exceed {$maxDuration} days";
        }

        // For upcoming memberships, validate hold doesn't start before membership
        if ($membership->status === OrgUserPlan::STATUS_UPCOMING) {
            $membershipStartDate = \Carbon\Carbon::parse($membership->startDateLoc);
            if ($startDate->lt($membershipStartDate)) {
                // This is not an error, we'll adjust it automatically
                Log::info('BulkHoldJob: Hold start date adjusted for upcoming membership', [
                    'membership_id' => $membership->id,
                    'original_start' => $startDate->format('Y-m-d'),
                    'membership_start' => $membershipStartDate->format('Y-m-d')
                ]);
            }
        }

        return [
            'valid' => empty($errors),
            'reason' => empty($errors) ? 'Dates are valid' : implode(', ', $errors),
            'errors' => $errors
        ];
    }

    /**
     * Check for active holds on the membership
     */
    private function checkActiveHolds($membership): array
    {
        $activeHolds = [];

        // Note: We only process Active and Upcoming memberships, so no need to check for STATUS_HOLD

        // Check orgUserPlanHold table if it exists
        if ($this->holdTableExists()) {
            $dbActiveHolds = OrgUserPlanHold::where('orgUserPlan_id', $membership->id)
                ->where('isCanceled', false)
                ->where('status', \App\Enums\OrgUserPlanHoldStatus::Active)
                ->get();

            foreach ($dbActiveHolds as $hold) {
                $activeHolds[] = [
                    'id' => $hold->id,
                    'type' => 'database',
                    'status' => 'Active',
                    'start_date' => is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('Y-m-d') : $hold->startDateTime->format('Y-m-d'),
                    'end_date' => is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('Y-m-d') : $hold->endDateTime->format('Y-m-d'),
                    'source' => 'orgUserPlanHold_table'
                ];
            }
        }

        // Check note-based holds
        $noteBasedHold = $this->checkNoteBasedActiveHold($membership);
        if ($noteBasedHold) {
            $activeHolds[] = $noteBasedHold;
        }

        $hasActiveHolds = !empty($activeHolds);

        return [
            'allowed' => !$hasActiveHolds,
            'reason' => $hasActiveHolds 
                ? 'Membership has ' . count($activeHolds) . ' active hold(s)'
                : 'No active holds found',
            'holds' => $activeHolds
        ];
    }

    /**
     * Check for overlapping holds with the proposed dates
     */
    private function checkOverlappingHolds($membership): array
    {
        $overlappingHolds = [];
        $startDate = \Carbon\Carbon::parse($this->holdData['startDate']);
        $endDate = \Carbon\Carbon::parse($this->holdData['endDate']);

        // Check orgUserPlanHold table if it exists
        if ($this->holdTableExists()) {
            $dbOverlappingHolds = OrgUserPlanHold::where('orgUserPlan_id', $membership->id)
                ->where('isCanceled', false)
                ->where(function($query) use ($startDate, $endDate) {
                    // Check for any overlap: new hold overlaps with existing holds
                    $query->where(function($q) use ($startDate, $endDate) {
                        // Case 1: New hold starts during existing hold
                        $q->where('startDateTime', '<=', $startDate)
                          ->where('endDateTime', '>', $startDate);
                    })->orWhere(function($q) use ($startDate, $endDate) {
                        // Case 2: New hold ends during existing hold
                        $q->where('startDateTime', '<', $endDate)
                          ->where('endDateTime', '>=', $endDate);
                    })->orWhere(function($q) use ($startDate, $endDate) {
                        // Case 3: New hold completely contains existing hold
                        $q->where('startDateTime', '>=', $startDate)
                          ->where('endDateTime', '<=', $endDate);
                    })->orWhere(function($q) use ($startDate, $endDate) {
                        // Case 4: Existing hold completely contains new hold
                        $q->where('startDateTime', '<=', $startDate)
                          ->where('endDateTime', '>=', $endDate);
                    });
                })
                ->get();

            foreach ($dbOverlappingHolds as $hold) {
                // Get status label - check if it's already an enum instance
                if ($hold->status instanceof \App\Enums\OrgUserPlanHoldStatus) {
                    $statusLabel = $hold->status->label();
                } else {
                    // Handle raw database values - ensure we only pass integers to from()
                    try {
                        if (is_numeric($hold->status)) {
                            $statusValue = (int)$hold->status;
                            $statusLabel = \App\Enums\OrgUserPlanHoldStatus::from($statusValue)->label();
                        } else {
                            // If it's not numeric, fallback to string handling
                            $statusLabel = is_string($hold->status) ? ucfirst($hold->status) : 'Unknown';
                        }
                    } catch (\ValueError $e) {
                        $statusLabel = is_string($hold->status) ? ucfirst($hold->status) : 'Unknown';
                    }
                }
                
                $overlappingHolds[] = [
                    'id' => $hold->id,
                    'type' => 'database',
                    'status' => $statusLabel,
                    'start_date' => is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('Y-m-d') : $hold->startDateTime->format('Y-m-d'),
                    'end_date' => is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('Y-m-d') : $hold->endDateTime->format('Y-m-d'),
                    'overlap_type' => $this->determineOverlapType(
                        $startDate, 
                        $endDate, 
                        is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime) : $hold->startDateTime,
                        is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime) : $hold->endDateTime
                    ),
                    'source' => 'orgUserPlanHold_table'
                ];
            }
        }

        // Check note-based overlapping holds
        $noteBasedOverlap = $this->checkNoteBasedOverlappingHold($membership, $startDate, $endDate);
        if ($noteBasedOverlap) {
            $overlappingHolds[] = $noteBasedOverlap;
        }

        $hasOverlaps = !empty($overlappingHolds);

        return [
            'allowed' => !$hasOverlaps,
            'reason' => $hasOverlaps 
                ? 'Proposed dates overlap with ' . count($overlappingHolds) . ' existing hold(s)'
                : 'No overlapping holds found',
            'holds' => $overlappingHolds
        ];
    }

    /**
     * Get hold history for a membership
     */
    private function getHoldHistory($membership): array
    {
        $history = [];

        // Get from orgUserPlanHold table if it exists
        if ($this->holdTableExists()) {
            $dbHolds = OrgUserPlanHold::where('orgUserPlan_id', $membership->id)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($dbHolds as $hold) {
                // Determine status label safely
                $statusLabel = 'Unknown';
                if ($hold->isCanceled) {
                    $statusLabel = 'Canceled';
                } else {
                    // Check if it's already an enum instance
                    if ($hold->status instanceof \App\Enums\OrgUserPlanHoldStatus) {
                        $statusLabel = $hold->status->label();
                    } else {
                        // Handle raw database values - ensure we only pass integers to from()
                        try {
                            if (is_numeric($hold->status)) {
                                $statusValue = (int)$hold->status;
                                $statusLabel = \App\Enums\OrgUserPlanHoldStatus::from($statusValue)->label();
                            } else {
                                // If it's not numeric, fallback to string handling
                                $statusLabel = is_string($hold->status) ? ucfirst($hold->status) : 'Unknown';
                            }
                        } catch (\ValueError $e) {
                            $statusLabel = is_string($hold->status) ? ucfirst($hold->status) : 'Unknown';
                        }
                    }
                }
                
                $history[] = [
                    'id' => $hold->id,
                    'type' => 'database',
                    'status' => $statusLabel,
                    'start_date' => is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('Y-m-d') : $hold->startDateTime->format('Y-m-d'),
                    'end_date' => is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('Y-m-d') : $hold->endDateTime->format('Y-m-d'),
                    'created_at' => $hold->created_at,
                    'created_by' => $hold->byOrgUser_id,
                    'note' => $hold->note,
                    'source' => 'orgUserPlanHold_table'
                ];
            }
        }

        // Get note-based hold history
        $noteBasedHistory = $this->getNoteBasedHoldHistory($membership);
        if ($noteBasedHistory) {
            $history = array_merge($history, $noteBasedHistory);
        }

        // Sort by creation date (most recent first)
        usort($history, function($a, $b) {
            $aTime = $a['created_at'] ?? 0;
            $bTime = $b['created_at'] ?? 0;
            return $bTime <=> $aTime;
        });

        return $history;
    }

    /**
     * Check if orgUserPlanHold table exists
     */
    private function holdTableExists(): bool
    {
        try {
            return \Schema::hasTable('orgUserPlanHold');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check for note-based active hold
     */
    private function checkNoteBasedActiveHold($membership): ?array
    {
        if (!$membership->note) {
            return null;
        }

        $noteData = json_decode($membership->note, true);
        if (!isset($noteData['holdInfo'])) {
            return null;
        }

        $holdInfo = $noteData['holdInfo'];
        if (isset($holdInfo['status']) && $holdInfo['status'] === 'active') {
            return [
                'type' => 'note_based',
                'status' => 'Active',
                'start_date' => $holdInfo['startDate'] ?? 'Unknown',
                'end_date' => $holdInfo['endDate'] ?? 'Unknown',
                'source' => 'membership_note'
            ];
        }

        return null;
    }

    /**
     * Check for note-based overlapping hold
     */
    private function checkNoteBasedOverlappingHold($membership, $startDate, $endDate): ?array
    {
        if (!$membership->note) {
            return null;
        }

        $noteData = json_decode($membership->note, true);
        if (!isset($noteData['holdInfo'])) {
            return null;
        }

        $holdInfo = $noteData['holdInfo'];
        if (isset($holdInfo['startDate']) && isset($holdInfo['endDate'])) {
            $existingStart = \Carbon\Carbon::parse($holdInfo['startDate']);
            $existingEnd = \Carbon\Carbon::parse($holdInfo['endDate']);

            // Check for overlap
            if ($this->datesOverlap($startDate, $endDate, $existingStart, $existingEnd)) {
                return [
                    'type' => 'note_based',
                    'status' => $holdInfo['status'] ?? 'Unknown',
                    'start_date' => $holdInfo['startDate'],
                    'end_date' => $holdInfo['endDate'],
                    'overlap_type' => $this->determineOverlapType($startDate, $endDate, $existingStart, $existingEnd),
                    'source' => 'membership_note'
                ];
            }
        }

        return null;
    }

    /**
     * Get note-based hold history
     */
    private function getNoteBasedHoldHistory($membership): array
    {
        if (!$membership->note) {
            return [];
        }

        $noteData = json_decode($membership->note, true);
        if (!isset($noteData['holdInfo'])) {
            return [];
        }

        $holdInfo = $noteData['holdInfo'];
        return [[
            'type' => 'note_based',
            'status' => $holdInfo['status'] ?? 'Unknown',
            'start_date' => $holdInfo['startDate'] ?? 'Unknown',
            'end_date' => $holdInfo['endDate'] ?? 'Unknown',
            'created_at' => $holdInfo['createdAt'] ?? $membership->updated_at,
            'created_by' => $holdInfo['createdBy'] ?? 'System',
            'note' => $holdInfo['reason'] ?? $holdInfo['note'] ?? null,
            'source' => 'membership_note'
        ]];
    }

    /**
     * Check if two date ranges overlap
     */
    private function datesOverlap($start1, $end1, $start2, $end2): bool
    {
        return $start1->lt($end2) && $end1->gt($start2);
    }

    /**
     * Determine the type of overlap between date ranges
     */
    private function determineOverlapType($newStart, $newEnd, $existingStart, $existingEnd): string
    {
        if ($newStart->gte($existingStart) && $newEnd->lte($existingEnd)) {
            return 'new_within_existing';
        } elseif ($existingStart->gte($newStart) && $existingEnd->lte($newEnd)) {
            return 'existing_within_new';
        } elseif ($newStart->lt($existingStart) && $newEnd->gt($existingStart) && $newEnd->lte($existingEnd)) {
            return 'new_starts_before_existing';
        } elseif ($newStart->gte($existingStart) && $newStart->lt($existingEnd) && $newEnd->gt($existingEnd)) {
            return 'new_ends_after_existing';
        } else {
            return 'partial_overlap';
        }
    }
}

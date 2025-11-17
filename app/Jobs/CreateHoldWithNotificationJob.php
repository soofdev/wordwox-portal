<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrgUserPlan;
use App\Models\OrgUserPlanHold;
use App\Services\OrgUserPlanHoldService;
use App\Services\NotificationsHelper;
use App\Enums\OrgUserPlanHoldStatus;
use Carbon\Carbon;

/**
 * Individual Hold Creation Job (Yii2 equivalent in Laravel)
 * Creates a single hold with full notification support
 */
class CreateHoldWithNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orgUserPlanId;
    public int $byOrgUserId;
    public string $startDate;
    public string $endDate;
    public bool $notifyEmail;
    public bool $notifyPush;
    public ?string $reason;
    public ?string $groupName;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $orgUserPlanId,
        int $byOrgUserId,
        string $startDate,
        string $endDate,
        bool $notifyEmail = false,
        bool $notifyPush = false,
        ?string $reason = null,
        ?string $groupName = null
    ) {
        $this->orgUserPlanId = $orgUserPlanId;
        $this->byOrgUserId = $byOrgUserId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->notifyEmail = $notifyEmail;
        $this->notifyPush = $notifyPush;
        $this->reason = $reason;
        $this->groupName = $groupName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $membership = OrgUserPlan::with(['orgUser', 'orgPlan', 'org'])->find($this->orgUserPlanId);

        if (!$membership) {
            throw new \Exception("Membership not found: {$this->orgUserPlanId}");
        }

        Log::info('CreateHoldWithNotificationJob: Processing hold creation', [
            'membership_id' => $this->orgUserPlanId,
            'member_name' => $membership->orgUser->fullName ?? 'Unknown',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'group_name' => $this->groupName,
            // Add validation details for debugging
            'membership_status' => $membership->status,
            'can_be_modified' => $membership->can_be_modified,
            'isHoldEnabled' => $membership->isHoldEnabled,
            'holdCount' => $membership->holdCount,
            'holdLimitCount' => $membership->holdLimitCount,
            'holdDays' => $membership->holdDays,
            'holdLimitDays' => $membership->holdLimitDays
        ]);

        DB::beginTransaction();

        try {
            $holdService = app(OrgUserPlanHoldService::class);

            // Prepare hold data
            $holdData = [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'groupName' => $this->groupName,
                'notifyEmail' => $this->notifyEmail,
                'notifyPush' => $this->notifyPush,
                'note' => $this->reason
            ];

            // Create the hold (pass true to skip isHoldEnabled check for bulk holds)
            $hold = $holdService->createHold($membership, $holdData, $this->reason, true);

            if (!$hold) {
                // Log detailed validation failure reasons
                $startDate = Carbon::parse($this->startDate);
                $endDate = Carbon::parse($this->endDate);
                $holdDuration = $startDate->diffInDays($endDate);
                
                $reasons = [];
                if (!$membership->can_be_modified) $reasons[] = 'cannot be modified';
                if (!$membership->isHoldEnabled) $reasons[] = 'holds not enabled';
                if ($membership->holdCount >= $membership->holdLimitCount && $membership->holdLimitCount > 0) {
                    $reasons[] = "hold count limit reached ({$membership->holdCount}/{$membership->holdLimitCount})";
                }
                if ($membership->holdDays + $holdDuration > $membership->holdLimitDays && $membership->holdLimitDays > 0) {
                    $reasons[] = "hold days limit exceeded (current: {$membership->holdDays}, adding: {$holdDuration}, limit: {$membership->holdLimitDays})";
                }
                
                // Check for overlapping holds (only Active and Upcoming, not Expired)
                $overlappingHolds = \App\Models\OrgUserPlanHold::where('orgUserPlan_id', $membership->id)
                    ->where('isCanceled', false)
                    ->whereIn('status', ['Active', 'Upcoming'])
                    ->where(function($query) use ($startDate, $endDate) {
                        $query->where('startDateTime', '<=', $endDate->format('Y-m-d H:i:s'))
                              ->where('endDateTime', '>=', $startDate->format('Y-m-d H:i:s'));
                    })
                    ->get(['id', 'startDateTime', 'endDateTime', 'status']);
                
                if ($overlappingHolds->count() > 0) {
                    $overlaps = $overlappingHolds->map(function($hold) {
                        return "Hold #{$hold->id} ({$hold->status}): " . 
                               Carbon::parse($hold->startDateTime)->format('Y-m-d') . ' to ' . 
                               Carbon::parse($hold->endDateTime)->format('Y-m-d');
                    })->toArray();
                    $reasons[] = "overlapping holds exist: " . implode(', ', $overlaps);
                }
                
                Log::warning('CreateHoldWithNotificationJob: Validation failed', [
                    'membership_id' => $this->orgUserPlanId,
                    'reasons' => $reasons
                ]);
                
                throw new \Exception('Failed to create hold - validation failed: ' . implode(', ', $reasons));
            }

            DB::commit();

            Log::info('CreateHoldWithNotificationJob: Hold created successfully', [
                'hold_id' => $hold->id,
                'membership_id' => $this->orgUserPlanId,
                'status' => $hold->status,
                'notifications_handled_by_service' => 'SendHoldNotificationJob dispatched by OrgUserPlanHoldService'
            ]);

            // Note: Notifications are already handled by OrgUserPlanHoldService->createHold()
            // It dispatches SendHoldNotificationJob which handles both email and push notifications

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('CreateHoldWithNotificationJob: Failed to create hold', [
                'membership_id' => $this->orgUserPlanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Error notifications are logged above, no need for additional notification
            throw $e;
        }
    }

    /**
     * The number of seconds the job can run before timing out.
     */
    public function timeout(): int
    {
        return 60;
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CreateHoldWithNotificationJob: Job failed permanently', [
            'membership_id' => $this->orgUserPlanId,
            'error' => $exception->getMessage()
        ]);
    }
}


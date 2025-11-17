<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\OrgUserPlanHold;
use App\Services\OrgUserPlanHoldService;
use App\Services\NotificationsHelper;
use Carbon\Carbon;

/**
 * Individual Hold End Job (Yii2 equivalent in Laravel)
 * Ends a single hold with full notification support
 */
class EndHoldWithNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $holdId;
    public ?int $byOrgUserId;
    public ?string $reason;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $holdId,
        ?int $byOrgUserId = null,
        ?string $reason = null
    ) {
        $this->holdId = $holdId;
        $this->byOrgUserId = $byOrgUserId;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hold = OrgUserPlanHold::with(['orgUserPlan', 'orgUser', 'org'])->find($this->holdId);

        if (!$hold) {
            Log::error('EndHoldWithNotificationJob: Hold not found', [
                'hold_id' => $this->holdId
            ]);
            return;
        }

        if ($hold->isCanceled || $hold->status === 'Expired') {
            Log::info('EndHoldWithNotificationJob: Hold already ended or canceled', [
                'hold_id' => $this->holdId,
                'status' => $hold->status,
                'is_canceled' => $hold->isCanceled
            ]);
            return;
        }

        Log::info('EndHoldWithNotificationJob: Processing hold end', [
            'hold_id' => $this->holdId,
            'member_name' => $hold->orgUser->fullName ?? 'Unknown',
            'status' => $hold->status
        ]);

        try {
            $holdService = app(OrgUserPlanHoldService::class);

            // End the hold
            $success = $holdService->endHold($hold, $this->reason, $this->byOrgUserId);

            if (!$success) {
                throw new \Exception('Failed to end hold');
            }

            Log::info('EndHoldWithNotificationJob: Hold ended successfully', [
                'hold_id' => $this->holdId,
                'notifications_handled_by_service' => 'SendHoldNotificationJob dispatched by OrgUserPlanHoldService'
            ]);

            // Note: Notifications are already handled by OrgUserPlanHoldService->endHold()
            // It dispatches SendHoldNotificationJob which handles both email and push notifications

        } catch (\Exception $e) {
            Log::error('EndHoldWithNotificationJob: Failed to end hold', [
                'hold_id' => $this->holdId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
        Log::error('EndHoldWithNotificationJob: Job failed permanently', [
            'hold_id' => $this->holdId,
            'error' => $exception->getMessage()
        ]);
    }
}


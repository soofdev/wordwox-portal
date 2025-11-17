<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrgUserPlanHold;
use App\Services\OrgUserPlanHoldService;
use App\Services\NotificationsHelper;
use Carbon\Carbon;

/**
 * Laravel Job - Bulk Hold End by Group ID
 * Ends multiple holds within a specific group (bulk operation)
 * This is a Laravel equivalent of the Yii2 OrgUserPlanBulkHoldEndJob
 */
class BulkEndHoldByGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $groupName;
    public int $byOrgUserId;
    public ?string $reason;
    public int $orgId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $groupName, int $byOrgUserId, int $orgId, ?string $reason = null)
    {
        $this->groupName = $groupName;
        $this->byOrgUserId = $byOrgUserId;
        $this->orgId = $orgId;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->groupName)) {
            throw new \Exception('BulkEndHoldByGroupJob: Group name is required');
        }

        Log::info('BulkEndHoldByGroupJob: Starting bulk hold end operation', [
            'group_name' => $this->groupName,
            'org_id' => $this->orgId,
            'by_user' => $this->byOrgUserId,
            'reason' => $this->reason
        ]);

        // Check if holds table exists
        if (!$this->holdTableExists()) {
            Log::warning('BulkEndHoldByGroupJob: Hold table does not exist, skipping operation');
            return;
        }

        // Find ACTIVE holds in the specified group that can be ended
        // Only end Active holds (not Upcoming) - matching Yii2 box behavior
        $query = OrgUserPlanHold::with(['orgUserPlan', 'orgUser'])
            ->where('groupName', $this->groupName)
            ->where('org_id', $this->orgId)
            ->where('isDeleted', false)
            ->where('isCanceled', false)
            ->where('status', \App\Enums\OrgUserPlanHoldStatus::Active->value); // Only Active holds

        $holds = $query->get();

        if ($holds->isEmpty()) {
            Log::warning('BulkEndHoldByGroupJob: No holds found for group', [
                'group_name' => $this->groupName,
                'org_id' => $this->orgId
            ]);
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Dispatch Laravel jobs for hold ending with notifications
        // No external Yii2 dispatch - everything handled in FOH
        foreach ($holds as $hold) {
            try {
                Log::info('BulkEndHoldByGroupJob: Dispatching Laravel end hold job', [
                    'hold_id' => $hold->id,
                    'member_name' => $hold->orgUser->fullName ?? 'Unknown',
                    'membership_id' => $hold->orgUserPlan_id
                ]);

                // Dispatch Laravel job for hold ending with notifications
                EndHoldWithNotificationJob::dispatch(
                    $hold->id,
                    $this->byOrgUserId,
                    $this->reason
                );

                $successCount++;
                
                Log::info('BulkEndHoldByGroupJob: Laravel end hold job dispatched successfully', [
                    'hold_id' => $hold->id,
                    'member_name' => $hold->orgUser->fullName ?? 'Unknown'
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = 'Exception dispatching end hold job for hold ID ' . $hold->id . ': ' . $e->getMessage();
                $errors[] = $errorMessage;
                
                Log::error('BulkEndHoldByGroupJob: Exception occurred', [
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Log summary
        Log::info('BulkEndHoldByGroupJob: Bulk operation completed', [
            'group_name' => $this->groupName,
            'total_holds' => count($holds),
            'successful' => $successCount,
            'failed' => $errorCount,
            'errors' => $errors
        ]);

        // If there were errors, log them but don't fail the job
        if ($errorCount > 0) {
            Log::warning('BulkEndHoldByGroupJob: Some holds failed to end', [
                'group_name' => $this->groupName,
                'failed_count' => $errorCount,
                'errors' => $errors
            ]);
        }

        // Send summary notification
        // Note: Admin notifications disabled per user request
        // if ($successCount > 0 && !empty($holds)) {
        //     $this->sendBulkEndNotification($holds->first()->orgUserPlan->org ?? null, $successCount, $errorCount);
        // }

        Log::info("BulkEndHoldByGroupJob: Bulk Hold End Completed! Group: {$this->groupName}, Success: {$successCount}, Errors: {$errorCount}");
    }

    /**
     * Send notification about bulk hold end operation
     */
    private function sendBulkEndNotification($org, int $successCount, int $errorCount): void
    {
        if (!$org) {
            Log::warning('BulkEndHoldByGroupJob: No organization found for notification');
            return;
        }

        try {
            $notificationsHelper = new NotificationsHelper();
            
            $data = [
                'group_name' => $this->groupName,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_count' => $successCount + $errorCount,
                'ended_by' => $this->byOrgUserId,
                'reason' => $this->reason ?? 'Bulk operation',
                'ended_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'org_name' => $org->name ?? 'Unknown Organization'
            ];

            // Send email notification to organization admin
            $notificationsHelper->sendTemplateEmail(
                $org->id,
                'bulk-hold-end-notification', // Template name
                __('subscriptions.Bulk Hold End Completed - Group: :group_name', ['group_name' => $this->groupName]),
                $org->email ?? 'admin@example.com',
                $data,
                $this->byOrgUserId
            );

            Log::info('BulkEndHoldByGroupJob: Notification sent successfully', [
                'group_name' => $this->groupName,
                'org_email' => $org->email
            ]);

        } catch (\Exception $e) {
            Log::error('BulkEndHoldByGroupJob: Failed to send notification', [
                'error' => $e->getMessage(),
                'group_name' => $this->groupName
            ]);
        }
    }

    /**
     * Check if the hold table exists in the database
     */
    private function holdTableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('orgUserPlanHold');
        } catch (\Exception $e) {
            Log::error('BulkEndHoldByGroupJob: Error checking hold table existence', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * The number of seconds the job can run before timing out.
     */
    public function timeout(): int
    {
        return 300; // 5 minutes - longer timeout for bulk operations
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3; // Reduced retries for bulk operations

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, then 60s, then 120s between retries
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkEndHoldByGroupJob: Job failed permanently', [
            'group_name' => $this->groupName,
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optionally send failure notification (disabled per user request)
        // try {
        //     $notificationsHelper = new NotificationsHelper();
        //     
        //     $notificationsHelper->sendTemplateEmail(
        //         $this->orgId,
        //         'bulk-hold-end-failed', // Template name for failures
        //         __('subscriptions.Bulk Hold End Failed - Group: :group_name', ['group_name' => $this->groupName]),
        //         null, // Will use org admin email
        //         [
        //             'group_name' => $this->groupName,
        //             'error_message' => $exception->getMessage(),
        //             'failed_at' => Carbon::now()->format('Y-m-d H:i:s')
        //         ],
        //         $this->byOrgUserId
        //     );
        // } catch (\Exception $e) {
        //     Log::error('BulkEndHoldByGroupJob: Failed to send failure notification', [
        //         'error' => $e->getMessage()
        //     ]);
        // }
    }
}

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
use App\Services\OrgUserPlanHoldService;
use App\Services\NotificationsHelper;
use Carbon\Carbon;

/**
 * Laravel Job - Bulk Hold Creation by Group ID
 * Creates multiple holds for a group of memberships (bulk operation)
 * This is a Laravel equivalent of the Yii2 OrgUserPlanBulkHoldJob
 */
class BulkCreateHoldByGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $groupName;
    public int $byOrgUserId;
    public int $orgId;
    public string $startDate;
    public string $endDate;
    public ?string $reason;
    public bool $notifyEmail;
    public bool $notifyPush;
    public array $membershipIds;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $groupName,
        int $byOrgUserId,
        int $orgId,
        string $startDate,
        string $endDate,
        array $membershipIds,
        ?string $reason = null,
        bool $notifyEmail = false,
        bool $notifyPush = false
    ) {
        $this->groupName = $groupName;
        $this->byOrgUserId = $byOrgUserId;
        $this->orgId = $orgId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->membershipIds = $membershipIds;
        $this->reason = $reason;
        $this->notifyEmail = $notifyEmail;
        $this->notifyPush = $notifyPush;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->groupName) || empty($this->membershipIds)) {
            throw new \Exception('BulkCreateHoldByGroupJob: Group name and membership IDs are required');
        }

        Log::info('BulkCreateHoldByGroupJob: Starting bulk hold creation operation', [
            'group_name' => $this->groupName,
            'org_id' => $this->orgId,
            'by_user' => $this->byOrgUserId,
            'reason' => $this->reason,
            'membership_count' => count($this->membershipIds),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate
        ]);

        // Find all memberships to put on hold
        $memberships = OrgUserPlan::with(['orgUser', 'orgPlan'])
            ->whereIn('id', $this->membershipIds)
            ->where('org_id', $this->orgId)
            ->get();

        if ($memberships->isEmpty()) {
            Log::warning('BulkCreateHoldByGroupJob: No memberships found', [
                'group_name' => $this->groupName,
                'org_id' => $this->orgId,
                'membership_ids' => $this->membershipIds
            ]);
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Dispatch Laravel jobs for hold creation with notifications
        // No external Yii2 dispatch - everything handled in FOH
        foreach ($memberships as $membership) {
            try {
                Log::info('BulkCreateHoldByGroupJob: Dispatching Laravel hold job for membership', [
                    'membership_id' => $membership->id,
                    'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                    'plan_name' => $membership->orgPlan->name ?? 'Unknown',
                    'group_name' => $this->groupName
                ]);

                // Dispatch Laravel job for hold creation with notifications
                CreateHoldWithNotificationJob::dispatch(
                    $membership->id,
                    $this->byOrgUserId,
                    $this->startDate,
                    $this->endDate,
                    $this->notifyEmail,
                    $this->notifyPush,
                    $this->reason,
                    $this->groupName
                );

                $successCount++;
                
                Log::info('BulkCreateHoldByGroupJob: Laravel hold job dispatched successfully', [
                    'membership_id' => $membership->id,
                    'member_name' => $membership->orgUser->fullName ?? 'Unknown',
                    'group_name' => $this->groupName
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = 'Exception dispatching hold job for membership ID ' . $membership->id . ': ' . $e->getMessage();
                $errors[] = $errorMessage;
                
                Log::error('BulkCreateHoldByGroupJob: Exception occurred', [
                    'membership_id' => $membership->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Log summary
        Log::info('BulkCreateHoldByGroupJob: Bulk operation completed', [
            'group_name' => $this->groupName,
            'total_memberships' => count($memberships),
            'successful' => $successCount,
            'failed' => $errorCount,
            'errors' => $errors
        ]);

        // If there were errors, log them but don't fail the job
        if ($errorCount > 0) {
            Log::warning('BulkCreateHoldByGroupJob: Some holds failed to create', [
                'group_name' => $this->groupName,
                'failed_count' => $errorCount,
                'errors' => $errors
            ]);
        }

        // Send summary notification
        // Note: Admin notifications disabled per user request
        // if ($successCount > 0 && !empty($memberships)) {
        //     $this->sendBulkCreateNotification($memberships->first()->org ?? null, $successCount, $errorCount);
        // }

        Log::info("BulkCreateHoldByGroupJob: Bulk Hold Creation Completed! Group: {$this->groupName}, Success: {$successCount}, Errors: {$errorCount}");
    }

    /**
     * Send notification about bulk hold creation operation
     */
    private function sendBulkCreateNotification($org, int $successCount, int $errorCount): void
    {
        if (!$org) {
            Log::warning('BulkCreateHoldByGroupJob: No organization found for notification');
            return;
        }

        try {
            $notificationsHelper = new NotificationsHelper();
            
            $data = [
                'group_name' => $this->groupName,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_count' => $successCount + $errorCount,
                'created_by' => $this->byOrgUserId,
                'reason' => $this->reason ?? 'Bulk operation',
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'org_name' => $org->name ?? 'Unknown Organization'
            ];

            // Send email notification to organization admin
            $notificationsHelper->sendTemplateEmail(
                $org->id,
                'bulk-hold-create-notification', // Template name
                __('subscriptions.Bulk hold created for :count users. Group ID: :groupId', [
                    'count' => $successCount,
                    'groupId' => $this->groupName
                ]),
                $org->email ?? 'admin@example.com',
                $data,
                $this->byOrgUserId
            );

            Log::info('BulkCreateHoldByGroupJob: Notification sent successfully', [
                'group_name' => $this->groupName,
                'org_email' => $org->email
            ]);

        } catch (\Exception $e) {
            Log::error('BulkCreateHoldByGroupJob: Failed to send notification', [
                'error' => $e->getMessage(),
                'group_name' => $this->groupName
            ]);
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
        Log::error('BulkCreateHoldByGroupJob: Job failed permanently', [
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
        //         'bulk-hold-create-failed', // Template name for failures
        //         __('subscriptions.Bulk Hold Creation Failed - Group: :group_name', ['group_name' => $this->groupName]),
        //         null, // Will use org admin email
        //         [
        //             'group_name' => $this->groupName,
        //             'error_message' => $exception->getMessage(),
        //             'failed_at' => Carbon::now()->format('Y-m-d H:i:s')
        //         ],
        //         $this->byOrgUserId
        //     );
        // } catch (\Exception $e) {
        //     Log::error('BulkCreateHoldByGroupJob: Failed to send failure notification', [
        //         'error' => $e->getMessage()
        //     ]);
        // }
    }
}

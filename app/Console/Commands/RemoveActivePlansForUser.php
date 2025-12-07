<?php

namespace App\Console\Commands;

use App\Models\OrgUser;
use App\Models\OrgUserPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time command to remove all active plans for a specific user
 * 
 * Usage: php artisan remove-active-plans --email=sujood.malkawi993@gmail.com
 */
class RemoveActivePlansForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove-active-plans 
                            {--email= : User email address}
                            {--org-id=8 : Organization ID}
                            {--dry-run : Show what would be removed without actually removing}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all active plans for a specific user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $orgId = $this->option('org-id');
        $dryRun = $this->option('dry-run');

        if (!$email) {
            $this->error('Email is required. Use --email=sujood.malkawi993@gmail.com');
            return 1;
        }

        $this->info("🔍 Finding user with email: {$email}");
        $this->info("   Organization ID: {$orgId}");
        $this->newLine();

        // Find user by email
        $orgUser = OrgUser::where('org_id', $orgId)
            ->where('email', $email)
            ->first();

        if (!$orgUser) {
            $this->error("❌ User not found with email: {$email}");
            $this->info("   Searching in org_id: {$orgId}");
            return 1;
        }

        $this->info("✅ User found:");
        $this->line("   ID: {$orgUser->id}");
        $this->line("   Name: {$orgUser->fullName}");
        $this->line("   Email: {$orgUser->email}");
        $this->newLine();

        // Find all active plans
        $activePlans = OrgUserPlan::where('orgUser_id', $orgUser->id)
            ->where('org_id', $orgId)
            ->whereIn('status', [
                OrgUserPlan::STATUS_ACTIVE,
                OrgUserPlan::STATUS_UPCOMING,
                OrgUserPlan::STATUS_PENDING,
            ])
            ->where('isCanceled', false)
            ->where('isDeleted', false)
            ->get();

        if ($activePlans->isEmpty()) {
            $this->info("✅ No active plans found for this user.");
            return 0;
        }

        $this->info("📋 Found {$activePlans->count()} active plan(s):");
        $this->newLine();

        $tableData = [];
        foreach ($activePlans as $plan) {
            $tableData[] = [
                'ID' => $plan->id,
                'Plan ID' => $plan->orgPlan_id,
                'Status' => $this->getStatusName($plan->status),
                'Start Date' => $plan->startDateLoc ?? 'N/A',
                'End Date' => $plan->endDateLoc ?? 'N/A',
                'Price' => ($plan->price ?? 0) . ' ' . ($plan->currency ?? 'KWD'),
            ];
        }

        $this->table(
            ['ID', 'Plan ID', 'Status', 'Start Date', 'End Date', 'Price'],
            $tableData
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No changes will be made");
            $this->info("   To actually remove these plans, run without --dry-run flag");
            return 0;
        }

        // Confirm before proceeding (unless --force flag is used)
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to remove all these active plans?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info("🗑️  Removing active plans...");

        $removedCount = 0;
        foreach ($activePlans as $plan) {
            try {
                // Set status to CANCELED and mark as canceled
                $plan->status = OrgUserPlan::STATUS_CANCELED;
                $plan->isCanceled = true;
                $plan->save();

                $this->line("   ✅ Removed plan ID: {$plan->id} (Plan: {$plan->orgPlan_id})");
                $removedCount++;
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to remove plan ID: {$plan->id} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("✅ Successfully removed {$removedCount} active plan(s) for user: {$email}");

        return 0;
    }

    /**
     * Get status name
     */
    protected function getStatusName($status)
    {
        $statuses = [
            OrgUserPlan::STATUS_NONE => 'None',
            OrgUserPlan::STATUS_UPCOMING => 'Upcoming',
            OrgUserPlan::STATUS_ACTIVE => 'Active',
            OrgUserPlan::STATUS_HOLD => 'Hold',
            OrgUserPlan::STATUS_CANCELED => 'Canceled',
            OrgUserPlan::STATUS_DELETED => 'Deleted',
            OrgUserPlan::STATUS_PENDING => 'Pending',
            OrgUserPlan::STATUS_EXPIRED_LIMIT => 'Expired (Limit)',
            OrgUserPlan::STATUS_EXPIRED => 'Expired',
        ];

        return $statuses[$status] ?? "Unknown ({$status})";
    }
}


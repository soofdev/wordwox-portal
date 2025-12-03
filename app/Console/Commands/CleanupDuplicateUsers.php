<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command to clean up duplicate User records by phone number
 * 
 * This command finds and removes duplicate User records that have the same
 * phoneNumber and phoneCountry, keeping only the most recent one.
 */
class CleanupDuplicateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:cleanup-duplicates 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate User records by phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Finding duplicate User records by phone number...');

        // Find duplicates using raw SQL to handle soft-deleted records
        $duplicates = DB::select("
            SELECT 
                phoneNumber,
                phoneCountry,
                COUNT(*) as count,
                GROUP_CONCAT(id ORDER BY created_at DESC) as user_ids
            FROM `user`
            WHERE phoneNumber IS NOT NULL 
                AND phoneNumber != ''
                AND phoneCountry IS NOT NULL
                AND phoneCountry != ''
                AND deleted_at IS NULL
            GROUP BY phoneNumber, phoneCountry
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            $this->info('No duplicate User records found.');
            return Command::SUCCESS;
        }

        $this->warn("Found " . count($duplicates) . " duplicate phone number(s).");

        $totalToDelete = 0;
        $usersToDelete = [];

        foreach ($duplicates as $duplicate) {
            $userIds = explode(',', $duplicate->user_ids);
            $keepId = array_shift($userIds); // Keep the most recent (first in DESC order)
            $deleteIds = $userIds; // Delete the rest

            $this->line("Phone: +{$duplicate->phoneCountry} {$duplicate->phoneNumber}");
            $this->line("  Total duplicates: {$duplicate->count}");
            $this->line("  Keeping User ID: {$keepId}");
            $this->line("  Deleting User IDs: " . implode(', ', $deleteIds));

            $totalToDelete += count($deleteIds);
            $usersToDelete = array_merge($usersToDelete, $deleteIds);
        }

        if ($dryRun) {
            $this->warn("\nDRY RUN: Would delete {$totalToDelete} duplicate User record(s).");
            $this->info("Run without --dry-run to actually delete them.");
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirm("Are you sure you want to delete {$totalToDelete} duplicate User record(s)?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("\nDeleting duplicate User records...");

        $deleted = 0;
        foreach ($usersToDelete as $userId) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $user->delete(); // Soft delete
                    $deleted++;
                    $this->line("  Deleted User ID: {$userId}");
                }
            } catch (\Exception $e) {
                $this->error("  Failed to delete User ID {$userId}: " . $e->getMessage());
            }
        }

        $this->info("\nSuccessfully deleted {$deleted} duplicate User record(s).");
        return Command::SUCCESS;
    }
}


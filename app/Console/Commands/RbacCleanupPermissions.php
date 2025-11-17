<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RbacCategory;
use App\Models\RbacTask;
use App\Models\RbacRole;
use App\Models\RbacRoleTask;
use App\Models\RbacRoleUser;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RbacCleanupPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:cleanup-permissions 
                            {--org-id= : Target specific organization ID (if empty, processes all orgs)}
                            {--force : Force the operation without confirmation}
                            {--categories-only : Only delete categories and their tasks}
                            {--tasks-only : Only delete tasks}
                            {--roles-only : Only delete roles and assignments}
                            {--keep-categories : Keep categories but delete tasks and roles}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up RBAC data (categories, tasks, roles, assignments) for Portal module only';

    /**
     * Portal module constant - hardcoded for security
     */
    private const PORTAL_MODULE = 'portal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Hard-coded to Portal module only for security
        $module = self::PORTAL_MODULE;
        
        $this->info("🧹 RBAC Cleanup Tool for Portal module");
        $this->warn("⚠️  This command is restricted to Portal module only for security.");
        $this->newLine();

        // Validate organization if specified
        $targetOrgId = $this->option('org-id');
        if ($targetOrgId) {
            $org = Org::find($targetOrgId);
            if (!$org) {
                $this->error("❌ Organization with ID {$targetOrgId} not found.");
                return self::FAILURE;
            }
            $this->info("🏢 Target Organization: {$org->name} (ID: {$targetOrgId})");
        } else {
            $orgCount = Org::count();
            $this->info("🌐 Processing all organizations ({$orgCount} total)");
        }

        $this->newLine();

        // Show what will be affected
        $this->showCurrentState($module, $targetOrgId);

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
            $this->showWhatWouldBeDeleted($module, $targetOrgId);
            return self::SUCCESS;
        }

        // Confirmation unless --force is used
        if (!$this->option('force')) {
            $this->newLine();
            $this->warn("⚠️  This will permanently delete FOH RBAC data.");
            $this->warn("⚠️  This includes all FOH roles, tasks, and user assignments.");
            if (!$this->confirm('Are you sure you want to proceed with FOH cleanup?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('🗑️  Starting cleanup process...');

        try {
            DB::beginTransaction();

            $deletedCounts = [];

            // Delete in proper order to respect foreign key constraints
            if (!$this->option('categories-only') && !$this->option('tasks-only')) {
                $deletedCounts['roleAssignments'] = $this->cleanupRoleAssignments($module, $targetOrgId);
                $deletedCounts['roleTasks'] = $this->cleanupRoleTaskAssignments($module, $targetOrgId);
                $deletedCounts['roles'] = $this->cleanupRoles($module, $targetOrgId);
            }

            if (!$this->option('roles-only') && !$this->option('keep-categories')) {
                $deletedCounts['tasks'] = $this->cleanupTasks($module);
            }

            if (!$this->option('roles-only') && !$this->option('tasks-only') && !$this->option('keep-categories')) {
                $deletedCounts['categories'] = $this->cleanupCategories($module);
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ FOH RBAC cleanup completed successfully!");
            $this->showDeletionSummary($deletedCounts);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Show current state of the RBAC system for FOH module
     */
    private function showCurrentState(string $module, ?int $targetOrgId): void
    {
        $this->info("📊 Current FOH RBAC State:");

        // Count categories
        $categoryCount = RbacCategory::where('module', $module)->count();
        $this->line("   • Categories: {$categoryCount}");

        // Count tasks
        $taskCount = RbacTask::where('module', $module)->count();
        $this->line("   • Tasks: {$taskCount}");

        // Count roles (filtered by org if specified)
        $rolesQuery = RbacRole::where('module', $module);
        if ($targetOrgId) {
            $rolesQuery->where('org_id', $targetOrgId);
        }
        $roleCount = $rolesQuery->count();
        $orgSuffix = $targetOrgId ? " (in org {$targetOrgId})" : " (all orgs)";
        $this->line("   • Roles: {$roleCount}{$orgSuffix}");

        // Count role-task assignments
        $roleTaskQuery = RbacRoleTask::whereHas('role', function ($query) use ($module, $targetOrgId) {
            $query->where('module', $module);
            if ($targetOrgId) {
                $query->where('org_id', $targetOrgId);
            }
        });
        $roleTaskCount = $roleTaskQuery->count();
        $this->line("   • Role-Task Assignments: {$roleTaskCount}{$orgSuffix}");

        // Count user role assignments
        $userRoleQuery = RbacRoleUser::whereHas('role', function ($query) use ($module, $targetOrgId) {
            $query->where('module', $module);
            if ($targetOrgId) {
                $query->where('org_id', $targetOrgId);
            }
        });
        $userRoleCount = $userRoleQuery->count();
        $this->line("   • User Role Assignments: {$userRoleCount}{$orgSuffix}");

        $this->newLine();

        // Show existing roles with details
        $roles = $rolesQuery->with(['activeTasks', 'activeUsers'])->get();
        if ($roles->count() > 0) {
            $this->info("🎭 Existing Roles:");
            foreach ($roles as $role) {
                $taskCount = $role->activeTasks->count();
                $userCount = $role->activeUsers->count();
                $orgName = $role->org ? $role->org->name : 'Unknown';
                $this->line("   • {$role->name} ({$orgName}): {$taskCount} tasks, {$userCount} users");
            }
            $this->newLine();
        }

        // Show categories with task counts
        $categories = RbacCategory::where('module', $module)
                                 ->withCount(['tasks' => function ($query) use ($module) {
                                     $query->where('module', $module);
                                 }])
                                 ->orderBy('order')
                                 ->get();
        
        if ($categories->count() > 0) {
            $this->info("📁 Existing Categories:");
            foreach ($categories as $category) {
                $this->line("   • {$category->name}: {$category->tasks_count} tasks");
            }
        }
    }

    /**
     * Show what would be deleted in dry-run mode
     */
    private function showWhatWouldBeDeleted(string $module, ?int $targetOrgId): void
    {
        $this->newLine();
        $this->info('🔍 What would be deleted:');

        if (!$this->option('categories-only') && !$this->option('tasks-only')) {
            // User role assignments
            $userRoleQuery = RbacRoleUser::whereHas('role', function ($query) use ($module, $targetOrgId) {
                $query->where('module', $module);
                if ($targetOrgId) {
                    $query->where('org_id', $targetOrgId);
                }
            });
            $userRoleCount = $userRoleQuery->count();
            $this->line("   🔹 User Role Assignments: {$userRoleCount}");

            // Role-task assignments
            $roleTaskQuery = RbacRoleTask::whereHas('role', function ($query) use ($module, $targetOrgId) {
                $query->where('module', $module);
                if ($targetOrgId) {
                    $query->where('org_id', $targetOrgId);
                }
            });
            $roleTaskCount = $roleTaskQuery->count();
            $this->line("   🔹 Role-Task Assignments: {$roleTaskCount}");

            // Roles
            $rolesQuery = RbacRole::where('module', $module);
            if ($targetOrgId) {
                $rolesQuery->where('org_id', $targetOrgId);
            }
            $roleCount = $rolesQuery->count();
            $this->line("   🔹 Roles: {$roleCount}");
        }

        if (!$this->option('roles-only') && !$this->option('keep-categories')) {
            // Tasks
            $taskCount = RbacTask::where('module', $module)->count();
            $this->line("   🔹 Tasks: {$taskCount}");
        }

        if (!$this->option('roles-only') && !$this->option('tasks-only') && !$this->option('keep-categories')) {
            // Categories
            $categoryCount = RbacCategory::where('module', $module)->count();
            $this->line("   🔹 Categories: {$categoryCount}");
        }
    }

    /**
     * Clean up user role assignments
     */
    private function cleanupRoleAssignments(string $module, ?int $targetOrgId): int
    {
        $this->info('🔹 Cleaning up user role assignments...');

        $query = RbacRoleUser::whereHas('role', function ($query) use ($module, $targetOrgId) {
            $query->where('module', $module);
            if ($targetOrgId) {
                $query->where('org_id', $targetOrgId);
            }
        });

        $count = $query->count();
        $query->forceDelete(); // Use forceDelete to bypass soft delete timestamp issues

        $this->line("   Deleted {$count} user role assignments");
        return $count;
    }

    /**
     * Clean up role-task assignments
     */
    private function cleanupRoleTaskAssignments(string $module, ?int $targetOrgId): int
    {
        $this->info('🔹 Cleaning up role-task assignments...');

        $query = RbacRoleTask::whereHas('role', function ($query) use ($module, $targetOrgId) {
            $query->where('module', $module);
            if ($targetOrgId) {
                $query->where('org_id', $targetOrgId);
            }
        });

        $count = $query->count();
        $query->forceDelete(); // Use forceDelete to bypass soft delete timestamp issues

        $this->line("   Deleted {$count} role-task assignments");
        return $count;
    }

    /**
     * Clean up roles
     */
    private function cleanupRoles(string $module, ?int $targetOrgId): int
    {
        $this->info('🔹 Cleaning up roles...');

        $query = RbacRole::where('module', $module);
        if ($targetOrgId) {
            $query->where('org_id', $targetOrgId);
        }

        $count = $query->count();
        $query->forceDelete(); // Use forceDelete to bypass soft delete timestamp issues

        $this->line("   Deleted {$count} roles");
        return $count;
    }

    /**
     * Clean up tasks
     */
    private function cleanupTasks(string $module): int
    {
        $this->info('🔹 Cleaning up tasks...');

        $count = RbacTask::where('module', $module)->count();
        RbacTask::where('module', $module)->forceDelete(); // Use forceDelete to bypass soft delete timestamp issues

        $this->line("   Deleted {$count} tasks");
        return $count;
    }

    /**
     * Clean up categories
     */
    private function cleanupCategories(string $module): int
    {
        $this->info('🔹 Cleaning up categories...');

        $count = RbacCategory::where('module', $module)->count();
        RbacCategory::where('module', $module)->forceDelete(); // Use forceDelete to bypass soft delete timestamp issues

        $this->line("   Deleted {$count} categories");
        return $count;
    }

    /**
     * Show summary of what was deleted
     */
    private function showDeletionSummary(array $deletedCounts): void
    {
        $this->newLine();
        $this->info('📊 Deletion Summary:');

        $total = 0;
        foreach ($deletedCounts as $type => $count) {
            $label = ucfirst(str_replace(['_'], [' '], $type));
            $this->line("   • {$label}: {$count}");
            $total += $count;
        }

        $this->line("   • Total items deleted: {$total}");

        $this->newLine();
        $this->info('💡 Next steps:');
        $this->line('   • Recreate FOH RBAC data using: php artisan rbac:create-permissions');
        $this->line('   • The create-permissions command will automatically recreate all FOH roles and tasks');
    }
}

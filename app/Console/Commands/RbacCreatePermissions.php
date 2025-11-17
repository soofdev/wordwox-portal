<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RbacCategory;
use App\Models\RbacTask;
use App\Models\RbacRole;
use App\Models\RbacRoleTask;
use App\Models\RbacRoleUser;
use App\Models\OrgUser;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RbacCreatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:create-permissions 
                            {--org-id= : Target specific organization ID (if empty, processes all orgs)}
                            {--force : Force recreate existing permissions and roles}
                            {--categories-only : Only create categories, skip tasks and roles}
                            {--tasks-only : Only create tasks, skip categories and roles}
                            {--roles-only : Only create roles, skip categories and tasks}
                            {--no-assignments : Skip assigning admin users to Admin role}
                            {--detailed : Show detailed information about what is being created}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create RBAC categories, tasks, roles, and assignments for FOH module only';

    /**
     * FOH module constant - hardcoded for security
     */
    private const FOH_MODULE = 'foh';

    /**
     * RBAC Categories definition for FOH module
     */
    protected array $categories = [
        [
            'name' => 'Member Management',
            'module' => self::FOH_MODULE,
            'order' => 1,
        ],
        [
            'name' => 'Membership Management',
            'module' => self::FOH_MODULE,
            'order' => 2,
        ],
        [
            'name' => 'Check-in Management',
            'module' => self::FOH_MODULE,
            'order' => 3,
        ],
        [
            'name' => 'Settings & Administration',
            'module' => self::FOH_MODULE,
            'order' => 4,
        ],
        [
            'name' => 'Reports & Analytics',
            'module' => self::FOH_MODULE,
            'order' => 5,
        ],
    ];

    /**
     * RBAC Tasks definition mapped from FOH permissions
     */
    protected array $tasks = [
        // Member Management Category
        'Member Management' => [
            ['name' => 'Create Members', 'slug' => 'create_members'],
            ['name' => 'View Members', 'slug' => 'view_members'],
            ['name' => 'View Member Profile', 'slug' => 'view_member_profile'],
            ['name' => 'Edit Members', 'slug' => 'edit_members'],
            ['name' => 'Delete Members', 'slug' => 'delete_members'],
        ],
        
        // Membership Management Category
        'Membership Management' => [
            ['name' => 'Create Memberships', 'slug' => 'create_memberships'],
            ['name' => 'View Memberships', 'slug' => 'view_memberships'],
            ['name' => 'Edit Memberships', 'slug' => 'edit_memberships'],
            ['name' => 'Modify Membership Dates', 'slug' => 'modify_membership_dates'],
            ['name' => 'Modify Membership Limits', 'slug' => 'modify_membership_limits'],
            ['name' => 'Upcharge Memberships', 'slug' => 'upcharge_memberships'],
            ['name' => 'Cancel Memberships', 'slug' => 'cancel_memberships'],
            ['name' => 'Transfer Memberships', 'slug' => 'transfer_memberships'],
            ['name' => 'Hold Memberships', 'slug' => 'hold_memberships'],
            ['name' => 'View Holds', 'slug' => 'view_holds'],
            ['name' => 'End Hold', 'slug' => 'end_hold'],
            ['name' => 'End Bulk Hold', 'slug' => 'end_bulk_hold'],
            ['name' => 'Cancel Hold', 'slug' => 'cancel_hold'],
            ['name' => 'Modify Hold', 'slug' => 'modify_hold'],
            ['name' => 'Upgrade Memberships', 'slug' => 'upgrade_memberships'],
            ['name' => 'Manage Partial Payments', 'slug' => 'manage_partial_payments'],
        ],
        
        // Check-in Management Category
        'Check-in Management' => [
            ['name' => 'Check In Members', 'slug' => 'check_in_members'],
            ['name' => 'View Check Ins', 'slug' => 'view_check_ins'],
        ],
        
        // Settings & Administration Category
        'Settings & Administration' => [
            ['name' => 'Access Dashboard', 'slug' => 'access_dashboard'],
            ['name' => 'Select Gym', 'slug' => 'select_gym'],
            ['name' => 'Manage Settings', 'slug' => 'manage_settings'],
            ['name' => 'Manage Roles', 'slug' => 'manage_roles'],
            ['name' => 'Manage Org Terms', 'slug' => 'manage_org_terms'],
        ],
        
        // Reports & Analytics Category
        'Reports & Analytics' => [
            ['name' => 'View Reports', 'slug' => 'view_reports'],
        ],
    ];

    /**
     * Role definitions with their tasks (mapped from FOH roles)
     */
    protected array $roleDefinitions = [
        'Admin' => 'all', // Special case - gets all tasks
        'Sales' => [
            'access_dashboard',
            'create_members',
            'view_members',
            'view_member_profile',
            'edit_members',
            'create_memberships',
            'view_memberships',
            'edit_memberships',
            'modify_membership_dates',
            'modify_membership_limits',
            'upcharge_memberships',
            'cancel_memberships',
            'transfer_memberships',
            'hold_memberships',
            'view_holds',
            'end_hold',
            'end_bulk_hold',
            'cancel_hold',
            'modify_hold',
            'upgrade_memberships',
            'manage_partial_payments',
            'check_in_members',
            'view_check_ins',
        ],
        'Reception' => [
            'access_dashboard',
            'view_members',
            'view_member_profile',
            'create_members',
            'view_memberships',
            'view_holds',
            'check_in_members',
            'view_check_ins',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 RBAC Permissions Creation Tool for FOH Module');
        $this->warn('⚠️  This command is restricted to FOH module only for security.');
        
        // Validate and display organization scope
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

        try {
            DB::beginTransaction();

            // Create categories
            if (!$this->option('tasks-only') && !$this->option('roles-only')) {
                $this->createCategories();
            }

            // Create tasks
            if (!$this->option('categories-only') && !$this->option('roles-only')) {
                $this->createTasks();
            }

            // Create roles
            if (!$this->option('categories-only') && !$this->option('tasks-only')) {
                $this->createRoles();
            }

            // Assign admin users to Admin role
            if (!$this->option('no-assignments') && !$this->option('categories-only') && !$this->option('tasks-only')) {
                $this->assignAdminUsers();
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ FOH RBAC permissions system created successfully!');
            $this->showSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Creation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Create RBAC categories
     */
    protected function createCategories(): void
    {
        $this->info('📁 Creating RBAC categories...');

        $created = 0;
        $existing = 0;
        $createdList = [];
        $skippedList = [];

        foreach ($this->categories as $categoryData) {
            $category = RbacCategory::where('name', $categoryData['name'])
                                   ->where('module', $categoryData['module'])
                                   ->first();
            
            if ($category && !$this->option('force')) {
                $existing++;
                $skippedList[] = $categoryData['name'];
                if ($this->option('detailed')) {
                    $this->line("   → Skipped: '{$categoryData['name']}' (already exists)");
                }
                continue;
            }

            if ($category && $this->option('force')) {
                $category->forceDelete();
                if ($this->option('detailed')) {
                    $this->line("   🗑️  Deleted existing: '{$categoryData['name']}'");
                }
            }

            $newCategory = RbacCategory::create([
                'name' => $categoryData['name'],
                'module' => $categoryData['module'],
                'order' => $categoryData['order'],
            ]);
            
            $created++;
            $createdList[] = $categoryData['name'];
            
            if ($this->option('detailed')) {
                $this->line("   ✅ Created: '{$categoryData['name']}' (ID: {$newCategory->id})");
            }
        }

        $this->line("   ✓ Created: {$created} categories");
        if ($existing > 0) {
            $this->line("   → Skipped: {$existing} existing categories (use --force to recreate)");
        }

        // Show detailed lists in verbose mode
        if ($this->option('detailed') && !empty($createdList)) {
            $this->newLine();
            $this->info('📋 Created Categories:');
            foreach ($createdList as $category) {
                $this->line("   • {$category}");
            }
        }
    }

    /**
     * Create RBAC tasks
     */
    protected function createTasks(): void
    {
        $this->info('🔐 Creating RBAC tasks...');

        $created = 0;
        $existing = 0;
        $createdList = [];
        $skippedList = [];

        foreach ($this->tasks as $categoryName => $categoryTasks) {
            // Find the category
            $category = RbacCategory::where('name', $categoryName)
                                   ->where('module', self::FOH_MODULE)
                                   ->first();
            
            if (!$category) {
                $this->warn("   ⚠️  Category '{$categoryName}' not found, skipping tasks");
                continue;
            }

            foreach ($categoryTasks as $index => $taskData) {
                $task = RbacTask::where('slug', $taskData['slug'])
                               ->where('module', self::FOH_MODULE)
                               ->first();
                
                if ($task && !$this->option('force')) {
                    $existing++;
                    $skippedList[] = $taskData['name'];
                    if ($this->option('detailed')) {
                        $this->line("   → Skipped: '{$taskData['name']}' (already exists)");
                    }
                    continue;
                }

                if ($task && $this->option('force')) {
                    $task->forceDelete();
                    if ($this->option('detailed')) {
                        $this->line("   🗑️  Deleted existing: '{$taskData['name']}'");
                    }
                }

                $newTask = RbacTask::create([
                    'rbacCategory_id' => $category->id,
                    'name' => $taskData['name'],
                    'slug' => $taskData['slug'],
                    'module' => self::FOH_MODULE,
                    'order' => $index + 1,
                ]);
                
                $created++;
                $createdList[] = $taskData['name'];
                
                if ($this->option('detailed')) {
                    $this->line("   ✅ Created: '{$taskData['name']}' (Slug: {$taskData['slug']}, ID: {$newTask->id})");
                }
            }
        }

        $this->line("   ✓ Created: {$created} tasks");
        if ($existing > 0) {
            $this->line("   → Skipped: {$existing} existing tasks (use --force to recreate)");
        }

        // Show detailed lists in verbose mode
        if ($this->option('detailed') && !empty($createdList)) {
            $this->newLine();
            $this->info('📋 Created Tasks:');
            foreach ($createdList as $task) {
                $this->line("   • {$task}");
            }
        }
    }

    /**
     * Create RBAC roles and assign tasks
     */
    protected function createRoles(): void
    {
        $this->info('🎭 Creating RBAC roles...');

        $targetOrgId = $this->option('org-id');
        
        if ($targetOrgId) {
            $orgs = [Org::find($targetOrgId)];
        } else {
            $orgs = Org::all();
        }

        foreach ($orgs as $org) {
            $this->line("   🏢 Processing organization: {$org->name} (ID: {$org->id})");
            
            foreach ($this->roleDefinitions as $roleName => $roleTasks) {
                $this->createRole($roleName, $roleTasks, $org->id);
            }
        }
    }

    /**
     * Create a single role with its tasks for a specific organization
     */
    protected function createRole(string $roleName, $tasks, int $orgId): void
    {
        // Create or find role
        $role = RbacRole::where('name', $roleName)
                       ->where('org_id', $orgId)
                       ->where('module', self::FOH_MODULE)
                       ->first();
        
        if ($role && !$this->option('force')) {
            $this->line("     → Skipped: {$roleName} role (already exists)");
            if ($this->option('detailed')) {
                $existingTaskCount = $role->activeTasks()->count();
                $this->line("       Current tasks: {$existingTaskCount}");
            }
            return;
        }

        if ($role && $this->option('force')) {
            if ($this->option('detailed')) {
                $this->line("     🗑️  Deleted existing: '{$roleName}' role (ID: {$role->id})");
            }
            $role->forceDelete();
        }

        // Set role flags based on role type
        $isAdmin = ($roleName === 'Admin');
        
        $role = RbacRole::create([
            'name' => $roleName,
            'slug' => Str::slug($roleName),
            'org_id' => $orgId,
            'module' => self::FOH_MODULE,
            'isActive' => true,
            'isFixed' => $isAdmin,    // Only Admin role is fixed (tasks cannot be disabled)
            'isRequired' => true,     // All FOH roles are required (cannot be deleted or renamed)
        ]);

        if ($this->option('detailed')) {
            $this->line("     🆕 Created role: '{$roleName}' (ID: {$role->id})");
        }

        // Assign tasks
        if ($tasks === 'all') {
            // Admin gets all FOH tasks
            $allTasks = RbacTask::where('module', self::FOH_MODULE)->get();
            $this->assignTasksToRole($role, $allTasks);
            $taskCount = $allTasks->count();
            $this->line("     ✓ Created: {$roleName} role with ALL {$taskCount} FOH tasks");
            
            if ($this->option('detailed')) {
                $this->newLine();
                $this->info("🔑 {$roleName} Role Tasks (ALL FOH):");
                foreach ($allTasks as $task) {
                    $this->line("       • {$task->name} ({$task->slug})");
                }
            }
        } else {
            // Specific tasks
            $taskModels = RbacTask::whereIn('slug', $tasks)
                                 ->where('module', self::FOH_MODULE)
                                 ->get();
            $this->assignTasksToRole($role, $taskModels);
            $taskCount = $taskModels->count();
            $this->line("     ✓ Created: {$roleName} role with {$taskCount} tasks");
            
            if ($this->option('detailed')) {
                $this->newLine();
                $this->info("🔑 {$roleName} Role Tasks:");
                foreach ($taskModels as $task) {
                    $this->line("       • {$task->name} ({$task->slug})");
                }
                
                // Show missing tasks if any
                $missingTasks = array_diff($tasks, $taskModels->pluck('slug')->toArray());
                if (!empty($missingTasks)) {
                    $this->newLine();
                    $this->warn("⚠️  Missing tasks for {$roleName} role:");
                    foreach ($missingTasks as $missing) {
                        $this->line("       • {$missing} (task not found)");
                    }
                }
            }
        }
    }

    /**
     * Assign tasks to a role via RbacRoleTask pivot
     */
    protected function assignTasksToRole(RbacRole $role, $tasks): void
    {
        foreach ($tasks as $task) {
            RbacRoleTask::create([
                'rbacRole_id' => $role->id,
                'rbacTask_id' => $task->id,
                'module' => $role->module,  // Use the role's module instead of hardcoding
                'isActive' => true,
            ]);
        }
    }

    /**
     * Assign Admin role to all active, non-deleted organization admins
     */
    protected function assignAdminUsers(): void
    {
        $targetOrgId = $this->option('org-id');
        
        if ($targetOrgId) {
            $this->info("👤 Assigning Admin role to organization admins in org {$targetOrgId}...");
        } else {
            $this->info('👤 Assigning Admin role to organization admins across all orgs...');
        }

        // Build query with optional org filtering
        $query = OrgUser::where(function ($query) {
                $query->where('isAdmin', true)
                      ->orWhere('isOwner', true);
            })
            ->where('isActive', true)
            ->where('isDeleted', false)
            ->whereNull('deleted_at');
            
        // Apply org filter if specified
        if ($targetOrgId) {
            $query->where('org_id', $targetOrgId);
        }
        
        $orgAdmins = $query->get();

        if ($orgAdmins->isEmpty()) {
            $this->line('   → No admin users found to assign');
            if ($this->option('detailed')) {
                $this->newLine();
                $this->info('🔍 Admin User Search Criteria:');
                $this->line('   • isAdmin = true OR isOwner = true');
                $this->line('   • isActive = true');
                $this->line('   • deleted_at IS NULL');
                if ($targetOrgId) {
                    $this->line("   • org_id = {$targetOrgId}");
                } else {
                    $this->line('   • org_id = ANY (all organizations)');
                }
            }
            return;
        }

        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('👥 Found Admin Users:');
            foreach ($orgAdmins as $orgAdmin) {
                $orgName = $orgAdmin->org ? $orgAdmin->org->name : 'Unknown';
                $this->line("   • {$orgAdmin->fullName} (ID: {$orgAdmin->id}, Email: {$orgAdmin->email})");
                $this->line("     Organization: {$orgName} (ID: {$orgAdmin->org_id})");
            }
        }

        $assigned = 0;
        $skipped = 0;
        
        foreach ($orgAdmins as $orgAdmin) {
            // Find the Admin role for this user's organization
            $adminRole = RbacRole::where('name', 'Admin')
                                ->where('org_id', $orgAdmin->org_id)
                                ->where('module', self::FOH_MODULE)
                                ->first();
            
            if (!$adminRole) {
                $this->warn("   ⚠️  Admin role not found for org {$orgAdmin->org_id}, skipping user {$orgAdmin->fullName}");
                continue;
            }

            // Check if user already has this role
            $existingAssignment = RbacRoleUser::where('rbacRole_id', $adminRole->id)
                                            ->where('orgUser_id', $orgAdmin->id)
                                            ->where('org_id', $orgAdmin->org_id)
                                            ->where('isDeleted', false)
                                            ->first();
            
            if ($existingAssignment && !$this->option('force')) {
                $skipped++;
                if ($this->option('detailed')) {
                    $this->line("   → Skipped {$orgAdmin->fullName}: already has Admin role");
                }
                continue;
            }

            if ($existingAssignment && $this->option('force')) {
                $existingAssignment->forceDelete();
                if ($this->option('detailed')) {
                    $this->line("   🗑️  Removed existing Admin role assignment from {$orgAdmin->fullName}");
                }
            }
            
            // Create role assignment
            RbacRoleUser::create([
                'rbacRole_id' => $adminRole->id,
                'orgUser_id' => $orgAdmin->id,
                'org_id' => $orgAdmin->org_id,
                'module' => $adminRole->module,  // Use the role's module instead of hardcoding
                'isDeleted' => false,
            ]);
            
            $assigned++;
            if ($this->option('detailed')) {
                $this->line("   ✅ Assigned Admin role to: {$orgAdmin->fullName}");
            }

            // Ensure user has FOH access (required to access FOH interface)
            if (!$orgAdmin->isFohUser) {
                $orgAdmin->update(['isFohUser' => true]);
                if ($this->option('detailed')) {
                    $this->line("   🔑 Granted FOH access to: {$orgAdmin->fullName}");
                }
            }
        }

        $total = $orgAdmins->count();
        $this->line("   ✓ Assigned Admin role to {$assigned} of {$total} admin users");
        
        if ($this->option('detailed') && $skipped > 0) {
            $this->line("   → Skipped {$skipped} users (already had Admin role)");
        }
    }

    /**
     * Show summary of created categories, tasks, and roles
     */
    protected function showSummary(): void
    {
        $this->newLine();
        $this->info('📊 Summary:');
        
        $targetOrgId = $this->option('org-id');
        
        // Count FOH module items
        $categoryCount = RbacCategory::where('module', self::FOH_MODULE)->count();
        $taskCount = RbacTask::where('module', self::FOH_MODULE)->count();
        
        if ($targetOrgId) {
            $roleCount = RbacRole::where('module', self::FOH_MODULE)->where('org_id', $targetOrgId)->count();
        } else {
            $roleCount = RbacRole::where('module', self::FOH_MODULE)->count();
        }
        
        $this->line("   • FOH Categories: {$categoryCount}");
        $this->line("   • FOH Tasks: {$taskCount}");
        $this->line("   • FOH Roles: {$roleCount}" . ($targetOrgId ? " (in org {$targetOrgId})" : " (all orgs)"));

        // Show roles with their task counts and user counts (filtered by org if specified)
        $rolesQuery = RbacRole::with('activeTasks')->where('module', self::FOH_MODULE);
        if ($targetOrgId) {
            $rolesQuery->where('org_id', $targetOrgId);
        }
        $roles = $rolesQuery->get();
        
        foreach ($roles as $role) {
            $taskCount = $role->activeTasks->count();
            
            // Get user count for this role
            $userCount = RbacRoleUser::where('rbacRole_id', $role->id)
                                   ->where('isDeleted', false)
                                   ->count();
            
            $orgName = $role->org ? $role->org->name : 'Unknown';
            $this->line("     - {$role->name} ({$orgName}): {$taskCount} tasks, {$userCount} users");
        }

        $this->newLine();
        $this->info('💡 Next steps:');
        if ($targetOrgId) {
            $this->line("   • Assign users to roles via the FOH interface for org {$targetOrgId}");
        } else {
            $this->line('   • Assign users to roles via the FOH interface');
        }
        $this->line('   • Customize role task assignments as needed');
        $this->line('   • Test permission checking with new RBAC system');
    }
}

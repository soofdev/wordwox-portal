<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FohRole;
use App\Models\FohPermission;
use App\Models\FohPermissionCategory;

class AddFohPermission extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'foh:add-permission 
                            {name? : The permission name to add (optional, will prompt if not provided)}
                            {--to-all : Add to all existing roles}
                            {--to-role=* : Add to specific roles (can specify multiple)}';

    /**
     * The console command description.
     */
    protected $description = 'Add a new FOH permission and optionally assign it to roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get permission name - either from argument or prompt
        $permissionName = $this->argument('name');
        
        if (!$permissionName) {
            $permissionName = $this->ask('What is the name of the new permission?');
        }
        
        if (!$permissionName) {
            $this->error('Permission name is required');
            return self::FAILURE;
        }
        
        // Ask for category assignment
        $categoryId = $this->selectCategory();
        
        // Check if permission already exists
        $existingPermission = FohPermission::where('name', $permissionName)->first();
        
        if ($existingPermission) {
            $this->warn("Permission '{$permissionName}' already exists (ID: {$existingPermission->id})");
            // Update category if different
            if ($existingPermission->category_id != $categoryId) {
                $existingPermission->update(['category_id' => $categoryId]);
                $categoryName = $categoryId ? FohPermissionCategory::find($categoryId)->name : 'Uncategorized';
                $this->info("✅ Updated category to: {$categoryName}");
            }
        } else {
            // Create the new permission
            $permission = FohPermission::create([
                'name' => $permissionName,
                'category_id' => $categoryId
            ]);
            $categoryName = $categoryId ? FohPermissionCategory::find($categoryId)->name : 'Uncategorized';
            $this->info("✅ Created permission: '{$permissionName}' in category '{$categoryName}' (ID: {$permission->id})");
        }
        
        // Get the permission (whether it was just created or already existed)
        $permission = FohPermission::where('name', $permissionName)->first();
        
        // Ask about role assignment if not specified via options
        $addToAllRoles = false;
        
        if ($this->option('to-all')) {
            $addToAllRoles = true;
        } elseif ($this->option('to-role')) {
            // Handle specific roles from command line options
            $roleNames = $this->option('to-role');
            foreach ($roleNames as $roleName) {
                $role = FohRole::where('name', $roleName)->first();
                if ($role) {
                    if (!$role->hasPermissionTo($permissionName)) {
                        $role->givePermissionTo($permission);
                        $this->info("✅ Added '{$permissionName}' to role: {$role->name}");
                    } else {
                        $this->line("   → Role '{$role->name}' already has this permission");
                    }
                } else {
                    $this->error("❌ Role '{$roleName}' not found");
                }
            }
        } else {
            // Interactive prompt
            $this->newLine();
            $roles = FohRole::all();
            if ($roles->count() > 0) {
                $this->info('Available roles:');
                foreach ($roles as $role) {
                    $permissionCount = $role->permissions->count();
                    $hasPermission = $role->hasPermissionTo($permissionName) ? ' (already has this permission)' : '';
                    $this->line("   • {$role->name} ({$permissionCount} permissions){$hasPermission}");
                }
                
                $choice = $this->choice(
                    'How do you want to assign this permission?',
                    [
                        'Add to ALL roles (enabled by default)',
                        'Add to specific roles only', 
                        'Create permission only (no role assignment)'
                    ],
                    0
                );
                
                switch ($choice) {
                    case 'Add to ALL roles (enabled by default)':
                        $addToAllRoles = true;
                        break;
                    case 'Add to specific roles only':
                        $addToAllRoles = false;
                        $this->selectSpecificRoles($permission, $permissionName, $roles);
                        break;
                    case 'Create permission only (no role assignment)':
                        $addToAllRoles = false;
                        $this->info('Permission created but not assigned to any roles.');
                        break;
                }
            }
        }
        
        // Add to all roles if requested
        if ($addToAllRoles) {
            $roles = FohRole::all();
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permission);
                    $this->info("✅ Added '{$permissionName}' to role: {$role->name}");
                } else {
                    $this->line("   → Role '{$role->name}' already has this permission");
                }
            }
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('🧹 Permission cache cleared');
        
        return self::SUCCESS;
    }
    
    /**
     * Interactive selection of permission category
     */
    private function selectCategory(): ?int
    {
        $categories = FohPermissionCategory::active()->ordered()->get();
        
        if ($categories->isEmpty()) {
            $this->warn('No permission categories found. Permission will be uncategorized.');
            return null;
        }
        
        $this->newLine();
        $this->info('Available categories:');
        
        $choices = [];
        foreach ($categories as $category) {
            $permissionCount = $category->permissions()->count();
            $choices[] = "{$category->name} ({$permissionCount} permissions)";
        }
        $choices[] = 'Uncategorized (no category)';
        
        $selectedIndex = $this->choice(
            'Which category should this permission belong to?',
            $choices,
            0
        );
        
        // Find the selected category
        $selectedChoice = $choices[array_search($selectedIndex, $choices)];
        
        if ($selectedChoice === 'Uncategorized (no category)') {
            return null;
        }
        
        // Extract category name and find the category
        $categoryName = explode(' (', $selectedChoice)[0];
        $category = $categories->firstWhere('name', $categoryName);
        
        return $category ? $category->id : null;
    }

    /**
     * Interactive selection of specific roles
     */
    private function selectSpecificRoles($permission, string $permissionName, $roles): void
    {
        $this->newLine();
        $this->info('Select which roles should get this permission:');
        
        foreach ($roles as $role) {
            $hasPermission = $role->hasPermissionTo($permissionName);
            
            if ($hasPermission) {
                $this->line("   → Role '{$role->name}' already has this permission");
                continue;
            }
            
            $shouldAdd = $this->confirm("Add to '{$role->name}' role?", false);
            
            if ($shouldAdd) {
                $role->givePermissionTo($permission);
                $this->info("✅ Added '{$permissionName}' to role: {$role->name}");
            } else {
                $this->line("   → Skipped role: {$role->name}");
            }
        }
    }
}

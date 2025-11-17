<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FohPermission;
use App\Models\FohPermissionCategory;
use App\Models\FohRole;
use Illuminate\Support\Facades\DB;

class ModifyFohPermission extends Command
{
    protected $signature = 'foh:modify-permission 
                            {permission? : The permission ID or name to modify}
                            {--name= : New name for the permission}
                            {--category= : New category ID or name for the permission}
                            {--add-to-role=* : Add permission to specific roles}
                            {--remove-from-role=* : Remove permission from specific roles}';

    protected $description = 'Modify an existing FOH permission (name, category, role assignments)';

    public function handle(): int
    {
        $this->showCurrentPermissions();
        
        $permission = $this->selectPermission();
        if (!$permission) {
            return self::FAILURE;
        }
        
        $this->newLine();
        $this->info("Modifying permission: {$permission->name}");
        $this->line("Current category: " . ($permission->category ? $permission->category->name : 'Uncategorized'));
        $this->showCurrentRoleAssignments($permission);
        
        $modifications = $this->getModifications($permission);
        
        if (empty($modifications)) {
            $this->info('No changes requested. Permission unchanged.');
            return self::SUCCESS;
        }
        
        $this->applyModifications($permission, $modifications);
        
        $this->info('✅ Permission updated successfully!');
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('🧹 Permission cache cleared');
        
        return self::SUCCESS;
    }
    
    private function showCurrentPermissions(): void
    {
        $this->newLine();
        $this->info('Current permissions by category:');
        
        $categories = FohPermissionCategory::active()->ordered()->with('permissions')->get();
        
        foreach ($categories as $category) {
            if ($category->permissions->count() > 0) {
                $this->line("📁 {$category->name}:");
                foreach ($category->permissions->sortBy('name') as $permission) {
                    $roleCount = $permission->roles()->count();
                    $this->line("   • {$permission->name} (ID: {$permission->id}, {$roleCount} roles)");
                }
            }
        }
        
        $uncategorized = FohPermission::uncategorized()->orderBy('name')->get();
        if ($uncategorized->count() > 0) {
            $this->line("📁 Uncategorized:");
            foreach ($uncategorized as $permission) {
                $roleCount = $permission->roles()->count();
                $this->line("   • {$permission->name} (ID: {$permission->id}, {$roleCount} roles)");
            }
        }
    }
    
    private function selectPermission(): ?FohPermission
    {
        $permissionInput = $this->argument('permission');
        
        if ($permissionInput) {
            $permission = FohPermission::where('id', $permissionInput)
                ->orWhere('name', $permissionInput)
                ->first();
                
            if (!$permission) {
                $this->error("Permission '{$permissionInput}' not found");
                return null;
            }
            
            return $permission;
        }
        
        $permissions = FohPermission::orderBy('name')->get();
        
        if ($permissions->isEmpty()) {
            $this->error('No permissions found to modify');
            return null;
        }
        
        $choices = [];
        foreach ($permissions as $permission) {
            $categoryName = $permission->category ? $permission->category->name : 'Uncategorized';
            $roleCount = $permission->roles()->count();
            $choices[] = "{$permission->name} (ID: {$permission->id}, Category: {$categoryName}, {$roleCount} roles)";
        }
        
        $selectedChoice = $this->choice('Which permission do you want to modify?', $choices);
        
        preg_match('/\(ID: (\d+),/', $selectedChoice, $matches);
        $permissionId = $matches[1] ?? null;
        
        return FohPermission::find($permissionId);
    }
    
    private function showCurrentRoleAssignments(FohPermission $permission): void
    {
        $roles = $permission->roles;
        
        if ($roles->isEmpty()) {
            $this->line("Current roles: None");
        } else {
            $roleNames = $roles->pluck('name')->join(', ');
            $this->line("Current roles: {$roleNames}");
        }
    }
    
    private function getModifications(FohPermission $permission): array
    {
        $modifications = [];
        
        // Name modification
        $newName = $this->option('name');
        if (!$newName) {
            $newName = $this->ask('New name (leave empty to keep current)', $permission->name);
        }
        
        if ($newName && $newName !== $permission->name) {
            // Check if name already exists
            $existing = FohPermission::where('name', $newName)
                ->where('id', '!=', $permission->id)
                ->first();
                
            if ($existing) {
                $this->error("Permission name '{$newName}' already exists");
                return [];
            }
            
            $modifications['name'] = $newName;
        }
        
        // Category modification
        $newCategoryInput = $this->option('category');
        if ($newCategoryInput === null) {
            $newCategoryInput = $this->selectNewCategory($permission);
        }
        
        if ($newCategoryInput !== null) {
            $newCategory = $this->findCategory($newCategoryInput);
            if ($newCategoryInput === 'none' || $newCategoryInput === 'uncategorized') {
                $newCategoryId = null;
            } elseif ($newCategory) {
                $newCategoryId = $newCategory->id;
            } else {
                $this->error("Category '{$newCategoryInput}' not found");
                return [];
            }
            
            if ($newCategoryId !== $permission->category_id) {
                $modifications['category_id'] = $newCategoryId;
                $modifications['category_name'] = $newCategoryId ? $newCategory->name : 'Uncategorized';
            }
        }
        
        // Role modifications
        $addToRoles = $this->option('add-to-role');
        $removeFromRoles = $this->option('remove-from-role');
        
        if (empty($addToRoles) && empty($removeFromRoles)) {
            $roleModifications = $this->getRoleModifications($permission);
            if (!empty($roleModifications)) {
                $modifications = array_merge($modifications, $roleModifications);
            }
        } else {
            if (!empty($addToRoles)) {
                $modifications['add_to_roles'] = $addToRoles;
            }
            if (!empty($removeFromRoles)) {
                $modifications['remove_from_roles'] = $removeFromRoles;
            }
        }
        
        return $modifications;
    }
    
    private function selectNewCategory(FohPermission $permission): ?string
    {
        $categories = FohPermissionCategory::active()->ordered()->get();
        $currentCategory = $permission->category ? $permission->category->name : 'Uncategorized';
        
        $this->newLine();
        $this->info("Current category: {$currentCategory}");
        
        if ($categories->isEmpty()) {
            $this->warn('No categories available.');
            return null;
        }
        
        $choices = ['Keep current category'];
        foreach ($categories as $category) {
            $permissionCount = $category->permissions()->count();
            $choices[] = "{$category->name} ({$permissionCount} permissions)";
        }
        $choices[] = 'Uncategorized (no category)';
        
        $selectedChoice = $this->choice('Select new category:', $choices, 0);
        
        if ($selectedChoice === 'Keep current category') {
            return null;
        }
        
        if ($selectedChoice === 'Uncategorized (no category)') {
            return 'uncategorized';
        }
        
        $categoryName = explode(' (', $selectedChoice)[0];
        return $categoryName;
    }
    
    private function findCategory($input): ?FohPermissionCategory
    {
        return FohPermissionCategory::where('id', $input)
            ->orWhere('name', $input)
            ->first();
    }
    
    private function getRoleModifications(FohPermission $permission): array
    {
        $modifications = [];
        $allRoles = FohRole::all();
        $currentRoles = $permission->roles->pluck('id')->toArray();
        
        if ($allRoles->isEmpty()) {
            return $modifications;
        }
        
        $this->newLine();
        $manageRoles = $this->confirm('Do you want to modify role assignments?', false);
        
        if (!$manageRoles) {
            return $modifications;
        }
        
        $this->info('Current role assignments:');
        foreach ($allRoles as $role) {
            $hasPermission = in_array($role->id, $currentRoles);
            $status = $hasPermission ? '✅' : '❌';
            $this->line("   {$status} {$role->name}");
        }
        
        $addToRoles = [];
        $removeFromRoles = [];
        
        foreach ($allRoles as $role) {
            $hasPermission = in_array($role->id, $currentRoles);
            
            if ($hasPermission) {
                $remove = $this->confirm("Remove from '{$role->name}' role?", false);
                if ($remove) {
                    $removeFromRoles[] = $role->name;
                }
            } else {
                $add = $this->confirm("Add to '{$role->name}' role?", false);
                if ($add) {
                    $addToRoles[] = $role->name;
                }
            }
        }
        
        if (!empty($addToRoles)) {
            $modifications['add_to_roles'] = $addToRoles;
        }
        if (!empty($removeFromRoles)) {
            $modifications['remove_from_roles'] = $removeFromRoles;
        }
        
        return $modifications;
    }
    
    private function applyModifications(FohPermission $permission, array $modifications): void
    {
        DB::transaction(function () use ($permission, $modifications) {
            
            // Update basic fields
            $basicUpdates = [];
            
            if (isset($modifications['name'])) {
                $basicUpdates['name'] = $modifications['name'];
                $this->info("✅ Updated name: {$modifications['name']}");
            }
            
            if (isset($modifications['category_id'])) {
                $basicUpdates['category_id'] = $modifications['category_id'];
                $this->info("✅ Updated category: {$modifications['category_name']}");
            }
            
            if (!empty($basicUpdates)) {
                $permission->update($basicUpdates);
            }
            
            // Handle role modifications
            if (isset($modifications['add_to_roles'])) {
                foreach ($modifications['add_to_roles'] as $roleName) {
                    $role = FohRole::where('name', $roleName)->first();
                    if ($role && !$role->hasPermissionTo($permission->name)) {
                        $role->givePermissionTo($permission);
                        $this->info("✅ Added to role: {$roleName}");
                    }
                }
            }
            
            if (isset($modifications['remove_from_roles'])) {
                foreach ($modifications['remove_from_roles'] as $roleName) {
                    $role = FohRole::where('name', $roleName)->first();
                    if ($role && $role->hasPermissionTo($permission->name)) {
                        $role->revokePermissionTo($permission);
                        $this->info("✅ Removed from role: {$roleName}");
                    }
                }
            }
        });
    }
}
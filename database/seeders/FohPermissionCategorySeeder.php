<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FohPermissionCategory;
use App\Models\FohPermission;

class FohPermissionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            [
                'name' => 'Members',
                'description' => 'Permissions related to member management',
                'sort_order' => 1,
                'permissions' => [
                    'create members',
                    'view members', 
                    'view member profile',
                    'edit members',
                    'delete members',
                ]
            ],
            [
                'name' => 'Memberships',
                'description' => 'Permissions related to membership and subscription management',
                'sort_order' => 2,
                'permissions' => [
                    'create memberships',
                    'view memberships',
                    'edit memberships',
                    'modify membership dates',
                    'modify membership limits',
                    'upcharge memberships',
                    'cancel memberships',
                    'transfer memberships',
                    'hold memberships',
                    'upgrade memberships',
                    'manage partial payments',
                ]
            ],
            [
                'name' => 'Check-ins',
                'description' => 'Permissions related to member check-ins and attendance',
                'sort_order' => 3,
                'permissions' => [
                    'check in members',
                    'view check ins',
                ]
            ],
            [
                'name' => 'Settings & Administration',
                'description' => 'System settings, reports, and administrative functions',
                'sort_order' => 4,
                'permissions' => [
                    'manage settings',
                    'view reports',
                    'access dashboard',
                    'select gym',
                    'manage roles',
                    'manage org terms',
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            // Create category
            $category = FohPermissionCategory::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'sort_order' => $categoryData['sort_order'],
                'is_active' => true,
            ]);

            // Assign permissions to category
            foreach ($categoryData['permissions'] as $permissionName) {
                $permission = FohPermission::where('name', $permissionName)->first();
                if ($permission) {
                    $permission->update(['category_id' => $category->id]);
                    $this->command->info("✅ Assigned '{$permissionName}' to category '{$category->name}'");
                } else {
                    $this->command->warn("⚠️  Permission '{$permissionName}' not found - skipping");
                }
            }

            $this->command->info("📁 Created category: {$category->name} ({$category->permissions()->count()} permissions)");
        }

        // Report uncategorized permissions
        $uncategorized = FohPermission::uncategorized()->get();
        if ($uncategorized->count() > 0) {
            $this->command->warn("⚠️  Uncategorized permissions found:");
            foreach ($uncategorized as $permission) {
                $this->command->line("   • {$permission->name}");
            }
        }
    }
}
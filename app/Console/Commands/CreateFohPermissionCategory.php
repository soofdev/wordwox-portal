<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FohPermissionCategory;
use Illuminate\Support\Facades\DB;

class CreateFohPermissionCategory extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'foh:create-category 
                            {name? : The category name}
                            {--order= : The sort order position (1-based)}
                            {--description= : Optional description for the category}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new FOH permission category with automatic reordering';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get category name
        $name = $this->argument('name');
        if (!$name) {
            $name = $this->ask('What is the name of the new category?');
        }
        
        if (!$name) {
            $this->error('Category name is required');
            return self::FAILURE;
        }
        
        // Check if category already exists
        $existingCategory = FohPermissionCategory::where('name', $name)->first();
        if ($existingCategory) {
            $this->error("Category '{$name}' already exists");
            return self::FAILURE;
        }
        
        // Get description
        $description = $this->option('description');
        if (!$description) {
            $description = $this->ask('Enter a description for this category (optional)');
        }
        
        // Show current categories and get desired position
        $this->showCurrentCategories();
        $desiredOrder = $this->getDesiredOrder();
        
        if ($desiredOrder === null) {
            return self::FAILURE;
        }
        
        // Create the category with reordering
        $this->createCategoryWithReordering($name, $description, $desiredOrder);
        
        $this->info('✅ Category created successfully!');
        $this->showCurrentCategories();
        
        return self::SUCCESS;
    }
    
    /**
     * Show current categories with their order and permission counts
     */
    private function showCurrentCategories(): void
    {
        $categories = FohPermissionCategory::active()->ordered()->get();
        
        $this->newLine();
        $this->info('Current categories:');
        
        if ($categories->isEmpty()) {
            $this->line('   (No categories found)');
            return;
        }
        
        foreach ($categories as $index => $category) {
            $permissionCount = $category->permissions()->count();
            $position = $index + 1;
            $this->line("   {$position}. {$category->name} ({$permissionCount} permissions)");
            if ($category->description) {
                $this->line("      Description: {$category->description}");
            }
        }
    }
    
    /**
     * Get the desired order position from user
     */
    private function getDesiredOrder(): ?int
    {
        $currentCount = FohPermissionCategory::active()->count();
        $maxPosition = $currentCount + 1;
        
        $orderOption = $this->option('order');
        
        if ($orderOption) {
            $desiredOrder = (int) $orderOption;
            if ($desiredOrder < 1 || $desiredOrder > $maxPosition) {
                $this->error("Order must be between 1 and {$maxPosition}");
                return null;
            }
            return $desiredOrder;
        }
        
        $this->newLine();
        $this->info("Where should this category be positioned?");
        $this->line("   Enter a number between 1 and {$maxPosition}");
        $this->line("   1 = First position");
        if ($currentCount > 0) {
            $this->line("   {$maxPosition} = Last position (after all existing categories)");
        }
        
        $desiredOrder = $this->ask("Position", $maxPosition);
        $desiredOrder = (int) $desiredOrder;
        
        if ($desiredOrder < 1 || $desiredOrder > $maxPosition) {
            $this->error("Invalid position. Must be between 1 and {$maxPosition}");
            return null;
        }
        
        return $desiredOrder;
    }
    
    /**
     * Create category and reorder existing ones
     */
    private function createCategoryWithReordering(string $name, ?string $description, int $desiredOrder): void
    {
        DB::transaction(function () use ($name, $description, $desiredOrder) {
            // Get all existing categories ordered by sort_order
            $existingCategories = FohPermissionCategory::active()->ordered()->get();
            
            $this->info("🔄 Reordering categories...");
            
            // Update sort_order for existing categories
            foreach ($existingCategories as $index => $category) {
                $currentPosition = $index + 1;
                $newPosition = $currentPosition >= $desiredOrder ? $currentPosition + 1 : $currentPosition;
                
                if ($newPosition != $category->sort_order) {
                    $category->update(['sort_order' => $newPosition]);
                    $this->line("   → Moved '{$category->name}' from position {$category->sort_order} to {$newPosition}");
                }
            }
            
            // Create the new category
            $newCategory = FohPermissionCategory::create([
                'name' => $name,
                'description' => $description,
                'sort_order' => $desiredOrder,
                'is_active' => true,
            ]);
            
            $this->info("✅ Created category '{$name}' at position {$desiredOrder}");
        });
    }
}
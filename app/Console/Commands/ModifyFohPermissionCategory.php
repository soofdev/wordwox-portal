<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FohPermissionCategory;
use Illuminate\Support\Facades\DB;

class ModifyFohPermissionCategory extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'foh:modify-category 
                            {category? : The category ID or name to modify}
                            {--name= : New name for the category}
                            {--description= : New description for the category}
                            {--order= : New sort order position (1-based)}';

    /**
     * The console command description.
     */
    protected $description = 'Modify an existing FOH permission category (name, description, position)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Show current categories first
        $this->showCurrentCategories();
        
        // Get the category to modify
        $category = $this->selectCategory();
        if (!$category) {
            return self::FAILURE;
        }
        
        $this->newLine();
        $this->info("Modifying category: {$category->name}");
        $this->line("Current position: {$this->getCategoryPosition($category)}");
        if ($category->description) {
            $this->line("Current description: {$category->description}");
        }
        
        // Get modifications
        $modifications = $this->getModifications($category);
        
        if (empty($modifications)) {
            $this->info('No changes requested. Category unchanged.');
            return self::SUCCESS;
        }
        
        // Apply modifications
        $this->applyModifications($category, $modifications);
        
        $this->info('✅ Category updated successfully!');
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
            $this->line("   {$position}. {$category->name} (ID: {$category->id}, {$permissionCount} permissions)");
            if ($category->description) {
                $this->line("      Description: {$category->description}");
            }
        }
    }
    
    /**
     * Select category to modify
     */
    private function selectCategory(): ?FohPermissionCategory
    {
        $categoryInput = $this->argument('category');
        
        if ($categoryInput) {
            // Try to find by ID first, then by name
            $category = FohPermissionCategory::where('id', $categoryInput)
                ->orWhere('name', $categoryInput)
                ->first();
                
            if (!$category) {
                $this->error("Category '{$categoryInput}' not found");
                return null;
            }
            
            return $category;
        }
        
        // Interactive selection
        $categories = FohPermissionCategory::active()->ordered()->get();
        
        if ($categories->isEmpty()) {
            $this->error('No categories found to modify');
            return null;
        }
        
        $choices = [];
        foreach ($categories as $category) {
            $permissionCount = $category->permissions()->count();
            $choices[] = "{$category->name} (ID: {$category->id}, {$permissionCount} permissions)";
        }
        
        $selectedChoice = $this->choice('Which category do you want to modify?', $choices);
        
        // Extract ID from the choice
        preg_match('/\(ID: (\d+),/', $selectedChoice, $matches);
        $categoryId = $matches[1] ?? null;
        
        return FohPermissionCategory::find($categoryId);
    }
    
    /**
     * Get current position of category
     */
    private function getCategoryPosition(FohPermissionCategory $category): int
    {
        $categories = FohPermissionCategory::active()->ordered()->get();
        
        foreach ($categories as $index => $cat) {
            if ($cat->id === $category->id) {
                return $index + 1;
            }
        }
        
        return $category->sort_order;
    }
    
    /**
     * Get modifications from user input
     */
    private function getModifications(FohPermissionCategory $category): array
    {
        $modifications = [];
        
        // Name modification
        $newName = $this->option('name');
        if (!$newName) {
            $newName = $this->ask('New name (leave empty to keep current)', $category->name);
        }
        
        if ($newName && $newName !== $category->name) {
            // Check if name already exists
            $existing = FohPermissionCategory::where('name', $newName)
                ->where('id', '!=', $category->id)
                ->first();
                
            if ($existing) {
                $this->error("Category name '{$newName}' already exists");
                return [];
            }
            
            $modifications['name'] = $newName;
        }
        
        // Description modification
        $newDescription = $this->option('description');
        if ($newDescription === null) { // Only ask if not provided via option
            $currentDesc = $category->description ?: '(none)';
            $newDescription = $this->ask("New description (leave empty to keep current: {$currentDesc})", $category->description);
        }
        
        if ($newDescription !== $category->description) {
            $modifications['description'] = $newDescription;
        }
        
        // Position modification
        $newOrder = $this->option('order');
        $currentPosition = $this->getCategoryPosition($category);
        $maxPosition = FohPermissionCategory::active()->count();
        
        if (!$newOrder) {
            $this->newLine();
            $this->info("Current position: {$currentPosition} of {$maxPosition}");
            $newOrder = $this->ask("New position (1-{$maxPosition}, leave empty to keep current)", $currentPosition);
        }
        
        $newOrder = (int) $newOrder;
        
        if ($newOrder && $newOrder !== $currentPosition) {
            if ($newOrder < 1 || $newOrder > $maxPosition) {
                $this->error("Position must be between 1 and {$maxPosition}");
                return [];
            }
            
            $modifications['order'] = $newOrder;
        }
        
        return $modifications;
    }
    
    /**
     * Apply modifications to the category
     */
    private function applyModifications(FohPermissionCategory $category, array $modifications): void
    {
        DB::transaction(function () use ($category, $modifications) {
            
            // Handle position change first (requires reordering)
            if (isset($modifications['order'])) {
                $this->reorderCategories($category, $modifications['order']);
                unset($modifications['order']); // Remove from modifications array
            }
            
            // Apply other modifications
            if (!empty($modifications)) {
                $category->update($modifications);
                
                foreach ($modifications as $field => $value) {
                    $this->info("✅ Updated {$field}: {$value}");
                }
            }
        });
    }
    
    /**
     * Reorder categories when position changes
     */
    private function reorderCategories(FohPermissionCategory $categoryToMove, int $newPosition): void
    {
        $allCategories = FohPermissionCategory::active()->ordered()->get();
        $currentPosition = $this->getCategoryPosition($categoryToMove);
        
        if ($currentPosition === $newPosition) {
            return; // No change needed
        }
        
        $this->info("🔄 Moving category from position {$currentPosition} to {$newPosition}...");
        
        foreach ($allCategories as $index => $category) {
            $categoryCurrentPos = $index + 1;
            $newSortOrder = $categoryCurrentPos;
            
            if ($category->id === $categoryToMove->id) {
                // This is the category we're moving
                $newSortOrder = $newPosition;
            } elseif ($currentPosition < $newPosition) {
                // Moving down: shift categories between old and new position up
                if ($categoryCurrentPos > $currentPosition && $categoryCurrentPos <= $newPosition) {
                    $newSortOrder = $categoryCurrentPos - 1;
                }
            } else {
                // Moving up: shift categories between new and old position down
                if ($categoryCurrentPos >= $newPosition && $categoryCurrentPos < $currentPosition) {
                    $newSortOrder = $categoryCurrentPos + 1;
                }
            }
            
            if ($newSortOrder !== $category->sort_order) {
                $category->update(['sort_order' => $newSortOrder]);
                $this->line("   → Moved '{$category->name}' to position {$newSortOrder}");
            }
        }
    }
}
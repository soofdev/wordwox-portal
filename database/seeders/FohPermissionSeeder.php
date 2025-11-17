<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class FohPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder now delegates to the foh:create-permissions command
     * to avoid code duplication and ensure consistency.
     */
    public function run(): void
    {
        $this->command->info('🌱 FOH Permission Seeder - Delegating to foh:create-permissions command...');
        
        // Call the create-permissions command with force flag to ensure fresh setup
        Artisan::call('foh:create-permissions', ['--force' => true]);
        
        // Output the command result
        $output = Artisan::output();
        $this->command->line($output);
        
        $this->command->info('✅ FOH Permission Seeder completed successfully!');
    }
}

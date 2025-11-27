<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get database name
        $databaseName = DB::getDatabaseName();
        
        // Find foreign key constraint using REFERENTIAL_CONSTRAINTS (more reliable)
        $constraints = DB::select("
            SELECT rc.CONSTRAINT_NAME, kcu.COLUMN_NAME
            FROM information_schema.REFERENTIAL_CONSTRAINTS rc
            JOIN information_schema.KEY_COLUMN_USAGE kcu 
                ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME 
                AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
            WHERE rc.CONSTRAINT_SCHEMA = ? 
            AND rc.TABLE_NAME = 'cms_pages'
            AND kcu.COLUMN_NAME = 'orgPortal_id'
        ", [$databaseName]);
        
        // Drop all foreign keys that use orgPortal_id
        foreach ($constraints as $constraint) {
            if (isset($constraint->CONSTRAINT_NAME)) {
                try {
                    DB::statement("ALTER TABLE `cms_pages` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Continue if it fails
                }
            }
        }
        
        // Now drop the composite index that includes orgPortal_id
        // Find all indexes that include orgPortal_id
        $indexes = DB::select("SHOW INDEX FROM `cms_pages` WHERE Column_name = 'orgPortal_id'");
        foreach ($indexes as $index) {
            if ($index->Key_name !== 'PRIMARY') {
                try {
                    DB::statement("ALTER TABLE `cms_pages` DROP INDEX `{$index->Key_name}`");
                } catch (\Exception $e) {
                    // Continue if it fails
                }
            }
        }
        
        // Drop the column
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('orgPortal_id');
        });
        
        // Recreate index on org_id only
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->index('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            // Drop the org_id index we created
            $table->dropIndex(['org_id']);
            
            // Add the column back
            $table->integer('orgPortal_id')->after('org_id');
            
            // Recreate composite index
            $table->index(['org_id', 'orgPortal_id']);
            
            // Recreate foreign key
            $table->foreign('orgPortal_id')->references('id')->on('orgPortal');
        });
    }
};

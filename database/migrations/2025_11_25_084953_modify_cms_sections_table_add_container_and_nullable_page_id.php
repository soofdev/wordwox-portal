<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_sections', function (Blueprint $table) {
            // Make cms_page_id nullable for footer sections
            if (Schema::hasColumn('cms_sections', 'cms_page_id')) {
                $table->unsignedBigInteger('cms_page_id')->nullable()->change();
            }
            
            // Add container field if it doesn't exist
            if (!Schema::hasColumn('cms_sections', 'container')) {
                $table->string('container')->nullable()->after('css_classes');
            }
        });
        
        // Add index separately if container column exists
        if (Schema::hasColumn('cms_sections', 'container') && !$this->hasIndex('cms_sections', 'cms_sections_container_index')) {
            Schema::table('cms_sections', function (Blueprint $table) {
                $table->index('container');
            });
        }
    }
    
    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$table, $indexName]);
            return !empty($indexes);
        }
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_sections', function (Blueprint $table) {
            // Drop index
            $table->dropIndex(['container']);
            
            // Drop container column
            $table->dropColumn('container');
            
            // Revert cms_page_id to not nullable
            $table->unsignedBigInteger('cms_page_id')->nullable(false)->change();
        });
    }
};

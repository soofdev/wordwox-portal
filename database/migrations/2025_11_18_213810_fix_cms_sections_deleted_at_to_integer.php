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
            // Change deleted_at column to integer to match BaseWWModel expectations
            $table->integer('deleted_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_sections', function (Blueprint $table) {
            // Revert back to timestamp column
            $table->timestamp('deleted_at')->nullable()->change();
        });
    }
};

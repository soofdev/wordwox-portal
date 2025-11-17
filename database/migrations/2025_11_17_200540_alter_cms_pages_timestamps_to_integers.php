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
        Schema::table('cms_pages', function (Blueprint $table) {
            // Change timestamp columns to integers to match BaseWWModel expectations
            $table->integer('created_at')->change();
            $table->integer('updated_at')->change();
            $table->integer('published_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            // Revert back to timestamp columns
            $table->timestamp('created_at')->change();
            $table->timestamp('updated_at')->change();
            $table->timestamp('published_at')->nullable()->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add navbar background and text colors for SuperHero CrossFit dark navbar styling
     */
    public function up(): void
    {
        Schema::table('template_theme_colors', function (Blueprint $table) {
            // Add navbar colors if they don't exist
            if (!Schema::hasColumn('template_theme_colors', 'bg_navbar')) {
                $table->string('bg_navbar')->default('#212529')->after('bg_footer'); // Bootstrap bg-dark color
            }
            if (!Schema::hasColumn('template_theme_colors', 'text_navbar')) {
                $table->string('text_navbar')->default('#ffffff')->after('bg_navbar'); // White text for dark navbar
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_theme_colors', function (Blueprint $table) {
            if (Schema::hasColumn('template_theme_colors', 'bg_navbar')) {
                $table->dropColumn('bg_navbar');
            }
            if (Schema::hasColumn('template_theme_colors', 'text_navbar')) {
                $table->dropColumn('text_navbar');
            }
        });
    }
};

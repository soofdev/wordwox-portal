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
        Schema::table('template_theme_colors', function (Blueprint $table) {
            // Button Colors
            $table->string('button_bg_color')->nullable()->after('secondary_hover'); // Button background color
            $table->string('button_text_color')->nullable()->after('button_bg_color'); // Button text color
        });
        
        // Set default values for existing records
        \DB::table('template_theme_colors')->update([
            'button_bg_color' => '#4285F4', // Default to primary color
            'button_text_color' => '#ffffff', // Default to white text
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_theme_colors', function (Blueprint $table) {
            $table->dropColumn(['button_bg_color', 'button_text_color']);
        });
    }
};

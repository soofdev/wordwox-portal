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
        if (Schema::hasTable('template_theme_colors')) {
            Schema::dropIfExists('template_theme_colors');
        }
        
        Schema::create('template_theme_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id');
            $table->string('template')->default('fitness'); // template name (fitness, modern, etc.)
            
            // Primary Brand Colors (SuperHero CrossFit colors from packages page)
            $table->string('primary_color')->default('#4285F4');        // Google blue for Buy buttons (from packages page CSS)
            $table->string('secondary_color')->default('#e03e2d');      // Red accent color (from homepage)
            
            // Text Colors
            $table->string('text_dark')->default('#212529');           // Dark gray/black (from CSS border colors)
            $table->string('text_gray')->default('#6c757d');
            $table->string('text_base')->default('#212529');           // Base text color (dark gray/black)
            $table->string('text_light')->default('#ffffff');
            
            // Background Colors
            $table->string('bg_white')->default('#ffffff');
            $table->string('bg_light')->default('#f8f9fa');
            $table->string('bg_lighter')->default('#e9ecef');
            $table->string('bg_packages')->default('#f5f5f5');         // Light gray (from packages page CSS)
            $table->string('bg_footer')->default('#f5f5f5');             // Light gray footer (from packages page)
            
            // Interactive Colors
            $table->string('primary_hover')->default('#357ABD');      // Darker blue for hover (from CSS)
            $table->string('secondary_hover')->default('#c02d1f');       // Darker red for hover
            
            $table->timestamps();
            
            // Unique constraint: one theme per org per template
            $table->unique(['org_id', 'template']);
            
            // Foreign key - Note: org table uses bigint unsigned, so we match that
            // Using unsignedBigInteger to match org.id type
            
            // Indexes
            $table->index('org_id');
            $table->index('template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_theme_colors');
    }
};

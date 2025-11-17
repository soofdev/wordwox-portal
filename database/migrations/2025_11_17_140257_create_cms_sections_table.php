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
        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('cms_page_id');
            
            // Section identification
            $table->string('name');
            $table->string('type'); // hero, content, gallery, testimonials, contact_form, etc.
            
            // Content
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->json('settings')->nullable(); // For section-specific settings
            $table->json('data')->nullable(); // For dynamic content (images, links, etc.)
            
            // Layout and styling
            $table->string('template')->default('default');
            $table->string('css_classes')->nullable();
            $table->json('styles')->nullable(); // Custom CSS properties
            
            // Position and visibility
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            
            // Responsive settings
            $table->json('responsive_settings')->nullable();
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['cms_page_id', 'sort_order']);
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
            
            // Foreign keys
            $table->foreign('cms_page_id')->references('id')->on('cms_pages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_sections');
    }
};

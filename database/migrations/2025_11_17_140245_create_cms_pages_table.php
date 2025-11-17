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
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('org_id');
            $table->integer('orgPortal_id');
            
            // Page identification
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Content
            $table->longText('content')->nullable();
            $table->json('meta_data')->nullable(); // For SEO and custom fields
            
            // Page settings
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->enum('type', ['page', 'post', 'home', 'about', 'contact', 'custom'])->default('page');
            $table->boolean('is_homepage')->default(false);
            $table->boolean('show_in_navigation')->default(true);
            $table->integer('sort_order')->default(0);
            
            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            
            // Template and layout
            $table->string('template')->default('default');
            $table->string('layout')->default('default');
            
            // Publishing
            $table->timestamp('published_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['org_id', 'orgPortal_id']);
            $table->index(['slug', 'org_id']);
            $table->index(['status', 'published_at']);
            $table->index('sort_order');
            
            // Foreign keys
            $table->foreign('org_id')->references('id')->on('org');
            $table->foreign('orgPortal_id')->references('id')->on('orgPortal');
            $table->foreign('created_by')->references('id')->on('user');
            $table->foreign('updated_by')->references('id')->on('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};

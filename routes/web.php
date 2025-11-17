<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Debugbar routes for Laravel 11 compatibility
if (app()->environment('local') && config('debugbar.enabled')) {
    Route::get('_debugbar/open', ['as' => 'debugbar.openhandler', 'uses' => '\Barryvdh\Debugbar\Controllers\OpenHandlerController@handle']);
    Route::get('_debugbar/assets/stylesheets', ['as' => 'debugbar.assets.css', 'uses' => '\Barryvdh\Debugbar\Controllers\AssetController@css']);
    Route::get('_debugbar/assets/javascript', ['as' => 'debugbar.assets.js', 'uses' => '\Barryvdh\Debugbar\Controllers\AssetController@js']);
}

// Dashboard route
Route::get('dashboard', \App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified', \App\Http\Middleware\SetTenantTimezone::class])
    ->name('dashboard');

// CMS Public Routes with /cms/ prefix (for admin reference)
Route::get('cms/{slug?}', \App\Livewire\CmsPageViewer::class)
    ->name('cms.page');

// CMS Home Page
Route::get('portal', \App\Livewire\CmsPageViewer::class)
    ->name('portal.home');

// Main website routes (clean URLs without /cms/)
Route::get('/', \App\Livewire\CmsPageViewer::class)
    ->name('home');

Route::get('{slug}', \App\Livewire\CmsPageViewer::class)
    ->where('slug', '^(?!dashboard|login|register|cms-admin|portal|cms).*$')
    ->name('page.view');

// CMS Admin Routes (Protected)
Route::middleware(['auth', 'verified', \App\Http\Middleware\SetTenantTimezone::class])->prefix('cms-admin')->name('cms.')->group(function () {
    Route::get('/', \App\Livewire\CmsAdminDashboard::class)->name('dashboard');
    
    // Pages
    Route::get('pages', \App\Livewire\CmsPagesIndex::class)->name('pages.index');
    Route::get('pages/create', \App\Livewire\CmsPagesCreate::class)->name('pages.create');
    Route::get('pages/{page}/edit', \App\Livewire\CmsPagesEdit::class)->name('pages.edit');
    
    // Templates
    Route::get('templates', \App\Livewire\TemplatePreview::class)->name('templates');
    
    // Sections - Temporarily commented out - components missing
    // Route::get('sections', \App\Livewire\CmsSectionsIndex::class)->name('sections.index');
    // Route::get('sections/create', \App\Livewire\CmsSectionsCreate::class)->name('sections.create');
    // Route::get('sections/{section}/edit', \App\Livewire\CmsSectionsEdit::class)->name('sections.edit');
    
    // Media - Temporarily commented out - components missing
    // Route::get('media', \App\Livewire\CmsMediaIndex::class)->name('media.index');
    
    // Settings - Temporarily commented out - components missing
    // Route::get('settings/general', \App\Livewire\CmsSettingsGeneral::class)->name('settings.general');
    // Route::get('settings/seo', \App\Livewire\CmsSettingsSeo::class)->name('settings.seo');
    // Route::get('settings/appearance', \App\Livewire\CmsSettingsAppearance::class)->name('settings.appearance');
});

// Authentication routes are included from routes/auth.php
require __DIR__.'/auth.php';

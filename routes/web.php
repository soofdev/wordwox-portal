<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Debugbar routes for Laravel 11 compatibility
if (app()->environment('local') && config('debugbar.enabled')) {
    Route::get('_debugbar/open', ['as' => 'debugbar.openhandler', 'uses' => '\Barryvdh\Debugbar\Controllers\OpenHandlerController@handle']);
    Route::get('_debugbar/assets/stylesheets', ['as' => 'debugbar.assets.css', 'uses' => '\Barryvdh\Debugbar\Controllers\AssetController@css']);
    Route::get('_debugbar/assets/javascript', ['as' => 'debugbar.assets.js', 'uses' => '\Barryvdh\Debugbar\Controllers\AssetController@js']);
}

// Redirect dashboard to CMS admin dashboard
Route::get('dashboard', function () {
    return redirect()->route('cms.dashboard');
})->middleware(['auth', 'verified', \App\Http\Middleware\SetTenantTimezone::class])
    ->name('dashboard');

// CMS Public Routes with /cms/ prefix (for admin reference)
Route::get('cms/{slug?}', \App\Livewire\CmsPageViewer::class)
    ->name('cms.page');

// CMS Home Page
Route::get('portal', \App\Livewire\CmsPageViewer::class)
    ->name('portal.home');

// Contact form submission route (POST only)
// Note: GET requests to /contact or /contact-us are handled by the catch-all route below
Route::post('contact', [\App\Http\Controllers\ContactController::class, 'submit'])->name('contact.submit');

// Main website routes (clean URLs without /cms/)
Route::get('/', \App\Livewire\CmsPageViewer::class)
    ->name('home');

// Coach Profile Route (Public - must be BEFORE catch-all to take priority)
Route::get('coaches/view', \App\Livewire\Customer\CoachProfile::class)->name('coach.view');

// Customer Login Routes (OTP-based, separate from CMS login)
// Main /login route is for customers
Route::middleware('guest')->group(function () {
    Route::get('login', \App\Livewire\Customer\CustomerLogin::class)->name('login');
});

// Customer routes with prefix
Route::middleware('guest')->prefix('customer')->name('customer.')->group(function () {
    Route::get('signup', \App\Livewire\Customer\CustomerSignup::class)->name('signup');
    Route::get('verify-otp', \App\Livewire\Customer\VerifyOtp::class)->name('verify-otp');
});

// Customer Portal Routes (Protected - require authentication)
Route::middleware(['auth'])->prefix('org-plan')->name('customer.')->group(function () {
    Route::get('index', \App\Livewire\Customer\PurchasePlan::class)->name('purchase-plan');
});

// Catch-all route for CMS pages (GET requests) - now catches /coaches too
Route::get('{slug}', \App\Livewire\CmsPageViewer::class)
    ->where('slug', '^(?!dashboard|register|cms-admin|portal|cms|org-plan|customer|forgot-password|reset-password|verify-email|confirm-password).*$')
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

    // Image Upload for CKEditor
    Route::post('upload-image', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'upload' => 'required|image|max:10240', // 10MB max
        ]);

        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $path = $file->store('cms/images', 'public');
            $url = \Illuminate\Support\Facades\Storage::url($path);

            return response()->json([
                'url' => $url
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    })->name('upload-image');

    // Sections - Temporarily commented out - components missing
    // Route::get('sections', \App\Livewire\CmsSectionsIndex::class)->name('sections.index');
    // Route::get('sections/create', \App\Livewire\CmsSectionsCreate::class)->name('sections.create');
    // Route::get('sections/{section}/edit', \App\Livewire\CmsSectionsEdit::class)->name('sections.edit');

    // Media - Temporarily commented out - components missing
    // Route::get('media', \App\Livewire\CmsMediaIndex::class)->name('media.index');

    // Settings
    Route::get('settings/footer', \App\Livewire\CmsFooterSettings::class)->name('settings.footer');

    // Temporarily commented out - components missing
    // Route::get('settings/general', \App\Livewire\CmsSettingsGeneral::class)->name('settings.general');
    // Route::get('settings/seo', \App\Livewire\CmsSettingsSeo::class)->name('settings.seo');
    // Route::get('settings/appearance', \App\Livewire\CmsSettingsAppearance::class)->name('settings.appearance');
});

// Authentication routes are included from routes/auth.php
require __DIR__.'/auth.php';

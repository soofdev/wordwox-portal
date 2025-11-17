<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Horizon authorization
        Horizon::auth(function ($request) {
            // Only allow authenticated users with admin permissions
            return $request->user() && 
                   $request->user()->orgUser && 
                   optional($request->user()->orgUser)->safeHasPermissionTo('admin access');
        });
    }
}

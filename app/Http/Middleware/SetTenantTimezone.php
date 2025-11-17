<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next)
    {
        // Skip timezone setting for public signature routes
        if ($request->is('public/signature/*')) {
            return $next($request);
        }
        
        try {
            // Only set timezone if user is authenticated
            if (auth()->check()) {
                $org = auth()->user()->orgUser->org;
                
                if ($org && $org->timezone) {
                    config(['app.timezone' => $org->timezone]);
                    date_default_timezone_set($org->timezone);
                }
            }
        } catch (\Exception $e) {
            // Fallback to default timezone
            config(['app.timezone' => config('app.timezone', 'UTC')]);
            date_default_timezone_set(config('app.timezone', 'UTC'));
        }

        return $next($request);
    }
}
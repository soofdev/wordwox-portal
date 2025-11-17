<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to handle permission-related exceptions gracefully
 * 
 * This middleware catches PermissionDoesNotExist exceptions and redirects
 * users to a friendly error page instead of showing a 500 error.
 */
class HandlePermissionExceptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (PermissionDoesNotExist $e) {
            // Log the missing permission for debugging
            Log::warning('Permission system not configured', [
                'permission' => $e->getMessage(),
                'user_id' => auth()->id(),
                'org_user_id' => auth()->user()?->orgUser?->id,
                'org_id' => auth()->user()?->orgUser?->org_id,
                'url' => $request->url(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'message' => 'Consider running: php artisan db:seed --class=FohPermissionSeeder'
            ]);

            // Check if this is an AJAX/API request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Permission system not configured',
                    'message' => 'The required permissions have not been set up. Please contact technical support.',
                    'code' => 'PERMISSION_NOT_CONFIGURED'
                ], 503);
            }

            // For Livewire requests, show a flash message and redirect
            if ($request->header('X-Livewire')) {
                session()->flash('error', __('gym.Permission system not configured. Please contact technical support.'));
                return redirect()->route('dashboard');
            }

            // For regular web requests, show the error page
            return response()->view('errors.missing-permissions', [
                'exception' => $e
            ], 503);
        }
    }
}

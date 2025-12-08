<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureFohAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('🛡️ FOH ACCESS MIDDLEWARE STARTED', [
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Step 1: Check if FOH check should be skipped
        $shouldSkip = $this->shouldSkipFohCheck($request);
        Log::info('🔍 Checking if FOH check should be skipped', [
            'should_skip' => $shouldSkip,
            'route_name' => $request->route()?->getName(),
            'url_path' => $request->path()
        ]);

        if ($shouldSkip) {
            Log::info('⏭️ FOH check skipped - allowing request to proceed');
            return $next($request);
        }

        // Step 2: Check authentication using CMS guard
        $isAuthenticated = Auth::guard('cms')->check();
        Log::info('🔐 Checking authentication status', [
            'is_authenticated' => $isAuthenticated,
            'session_id' => session()->getId(),
            'guard' => 'cms'
        ]);

        if (!$isAuthenticated) {
            Log::warning('❌ User not authenticated - redirecting to CMS login', [
                'redirect_to' => route('cms.login')
            ]);
            return redirect()->route('cms.login');
        }

        // Step 3: Get authenticated user details from CMS guard
        $user = Auth::guard('cms')->user();
        Log::info('👤 Authenticated user details', [
            'user_id' => $user->id,
            'user_name' => $user->fullName,
            'email' => $user->email,
            'current_orgUser_id' => $user->orgUser_id
        ]);

        // Step 4: Check current orgUser FOH access
        $hasCurrentOrgUser = !is_null($user->orgUser);
        Log::info('🏢 Checking current orgUser FOH access', [
            'has_current_orgUser' => $hasCurrentOrgUser,
            'current_orgUser_id' => $user->orgUser_id
        ]);

        if ($hasCurrentOrgUser) {
            $currentFohAccess = (bool)$user->orgUser->isFohUser;
            Log::info('🏢 Current orgUser FOH details', [
                'orgUser_id' => $user->orgUser->id,
                'org_id' => $user->orgUser->org_id,
                'org_name' => $user->orgUser->org->name ?? 'Unknown',
                'isFohUser' => $user->orgUser->isFohUser,
                'has_foh_access' => $currentFohAccess
            ]);

            if ($currentFohAccess) {
                Log::info('✅ FOH ACCESS GRANTED - User has FOH access in current organization');
                return $next($request);
            }
        }

        // Step 5: Check FOH access in other organizations
        Log::info('🌐 Current org has no FOH access - checking other organizations');
        $hasAnyFohAccess = $user->hasAnyFohAccess();
        $fohOrgCount = $user->fohOrgUsers()->count();
        
        Log::info('🌐 FOH access in other organizations', [
            'has_any_foh_access' => $hasAnyFohAccess,
            'total_foh_orgs' => $fohOrgCount
        ]);

        if ($hasAnyFohAccess) {
            $orgSelectRoute = route('org-user.select');
            Log::info('🔄 REDIRECTING TO ORG SELECTION - User has FOH access in other orgs', [
                'redirect_to' => $orgSelectRoute,
                'available_foh_orgs' => $fohOrgCount
            ]);
            return redirect()->route('org-user.select');
        }

        // Step 6: No FOH access anywhere - logout and redirect
        Log::error('🚫 NO FOH ACCESS - User has no FOH permissions anywhere', [
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'logout_and_redirect'
        ]);

        Auth::guard('cms')->logout();
        Log::info('🚪 User logged out due to lack of FOH access');
        
        return redirect()->route('cms.login')->withErrors([
            'email' => 'You do not have permission to access the Front of House interface. Please contact your administrator to get access.'
        ]);
    }

    /**
     * Determine if FOH check should be skipped for this request
     */
    private function shouldSkipFohCheck(Request $request): bool
    {
        // Routes that should skip FOH check
        $skipRoutes = [
            'cms.login',
            'login', // Customer login
            'logout', 
            'org-user.select',
            'org-user.set',
            'public.signature.*', // Public signature routes
        ];

        // Check if current route matches any skip patterns
        foreach ($skipRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        // Skip for public signature URLs
        if ($request->is('public/signature/*')) {
            return true;
        }

        return false;
    }
}

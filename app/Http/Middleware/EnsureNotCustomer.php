<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent customers from accessing CMS/admin routes
 * 
 * Customers should only access customer portal routes, not CMS admin routes
 */
class EnsureNotCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If not authenticated, let other middleware handle it
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Check if user is a customer-only (not staff/admin/FOH)
        if ($this->isCustomerOnly($user)) {
            Log::warning('Customer attempted to access CMS route', [
                'user_id' => $user->id,
                'org_user_id' => $user->orgUser_id,
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
            ]);
            
            // Logout customer and redirect to customer login
            Auth::logout();
            
            return redirect()->route('login')->withErrors([
                'email' => 'You do not have permission to access the CMS. Please use the customer portal.'
            ]);
        }

        return $next($request);
    }

    /**
     * Check if user is a customer-only (not staff/admin/FOH user)
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    private function isCustomerOnly($user): bool
    {
        // Use the User model's helper method
        return $user->isCustomerOnly();
    }
}


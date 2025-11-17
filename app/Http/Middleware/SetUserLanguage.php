<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetUserLanguage
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
        // Skip for public signature routes
        if ($request->is('public/signature/*')) {
            return $next($request);
        }

        try {
            if (Auth::check() && Auth::user()->orgUser) {
                $effectiveLanguage = Auth::user()->getEffectiveLanguage();
                
                // Validate that we got a string
                if (!is_string($effectiveLanguage)) {
                    \Log::warning('getEffectiveLanguage returned non-string value', [
                        'value' => $effectiveLanguage,
                        'type' => gettype($effectiveLanguage),
                        'user_id' => Auth::id(),
                    ]);
                    $effectiveLanguage = 'en-US';
                }
                
                // Convert to simple language code for Laravel locale (en-US -> en, ar-SA -> ar)
                $localeCode = explode('-', $effectiveLanguage)[0];
                
                // Set the application locale
                App::setLocale($localeCode);
                
                // Store in session for consistency
                session(['locale' => $localeCode, 'effective_language' => $effectiveLanguage]);
            }
        } catch (\Exception $e) {
            // Fallback to default locale if something goes wrong
            App::setLocale(config('app.locale', 'en'));
            \Log::warning('Failed to set user language, using default', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $next($request);
    }
}
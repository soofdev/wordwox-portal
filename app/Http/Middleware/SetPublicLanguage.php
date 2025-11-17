<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLanguage
{
    /**
     * Available languages for public pages
     */
    private $availableLanguages = ['en', 'ar'];

    /**
     * Handle an incoming request for public pages
     *
     * This middleware sets the application locale for anonymous users
     * based on session storage, cookies, or browser preferences.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $language = $this->detectLanguage($request);
        
        // Set the application locale
        App::setLocale($language);
        
        // Store in session for consistency
        session(['public_locale' => $language]);
        
        // Add language info to view data
        view()->share([
            'currentPublicLanguage' => $language,
            'isRtlLanguage' => $this->isRtlLanguage($language),
            'availablePublicLanguages' => $this->getAvailableLanguageData(),
        ]);

        return $next($request);
    }

    /**
     * Detect the appropriate language for the request
     */
    private function detectLanguage(Request $request): string
    {
        // Priority: URL Parameter → Session → Cookie → Browser Language → Default

        // 1. Check URL parameter (for future URL-based switching)
        if ($request->has('lang')) {
            $urlLang = $request->get('lang');
            if (in_array($urlLang, $this->availableLanguages)) {
                return $urlLang;
            }
        }

        // 2. Check session
        if (session()->has('public_locale')) {
            $sessionLang = session('public_locale');
            if (in_array($sessionLang, $this->availableLanguages)) {
                return $sessionLang;
            }
        }

        // 3. Check cookie
        if ($request->hasCookie('public_language_preference')) {
            $cookieLang = $request->cookie('public_language_preference');
            if (in_array($cookieLang, $this->availableLanguages)) {
                return $cookieLang;
            }
        }

        // 4. Check browser language
        $browserLang = $this->getBrowserLanguage($request);
        if ($browserLang && in_array($browserLang, $this->availableLanguages)) {
            return $browserLang;
        }

        // 5. Default to English
        return 'en';
    }

    /**
     * Get browser's preferred language from Accept-Language header
     */
    private function getBrowserLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', trim($lang));
            $code = explode('-', $parts[0])[0]; // Get primary language code (en from en-US)
            $quality = isset($parts[1]) ? (float)$parts[1] : 1.0;
            $languages[$code] = $quality;
        }

        // Sort by quality (preference)
        arsort($languages);

        // Return first available language
        foreach (array_keys($languages) as $code) {
            if (in_array($code, $this->availableLanguages)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Check if a language is RTL (Right-to-Left)
     */
    private function isRtlLanguage(string $language): bool
    {
        $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
        return in_array($language, $rtlLanguages);
    }

    /**
     * Get available language data for views
     */
    private function getAvailableLanguageData(): array
    {
        return [
            'en' => [
                'name' => 'English',
                'native' => 'English',
                'flag' => '🇺🇸',
                'rtl' => false,
            ],
            'ar' => [
                'name' => 'Arabic',
                'native' => 'العربية',
                'flag' => '🇸🇦',
                'rtl' => true,
            ],
        ];
    }
}

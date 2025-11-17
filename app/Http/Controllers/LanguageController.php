<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class LanguageController extends Controller
{
    /**
     * Switch user language
     */
    public function switch(Request $request)
    {
        $language = $request->input('language');

        try {
            $user = Auth::user();

            // Get available languages for validation
            $availableLanguages = $user->getAvailableLanguages();

            // Validate language is available
            if (!array_key_exists($language, $availableLanguages)) {
                return redirect()->back()->with('error', __('gym.language_not_available'));
            }

            // Set user preference
            $user->setLanguagePreference($language);

            // Convert to simple language code for Laravel locale (en-US -> en, ar-SA -> ar)
            $localeCode = explode('-', $language)[0];

            // Set app locale immediately
            App::setLocale($localeCode);
            session(['locale' => $localeCode, 'effective_language' => $language]);

            // Log the change
            Log::info('Language switched via controller', [
                'user_id' => Auth::id(),
                'language' => $language,
                'locale' => $localeCode,
                'url' => $request->input('return_url', url()->previous())
            ]);

            // Show success message
            session()->flash('success', __('gym.language_changed_successfully'));

            // Redirect back to the same page to refresh and apply RTL/LTR styles
            $returnUrl = $request->input('return_url', url()->previous());
            return redirect()->to($returnUrl);

        } catch (\Exception $e) {
            Log::error('Language switch failed in controller', [
                'user_id' => Auth::id(),
                'language' => $language,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', __('gym.language_change_failed'));
        }
    }
}

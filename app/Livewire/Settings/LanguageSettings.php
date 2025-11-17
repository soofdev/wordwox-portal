<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\OrgSettingsFeatures;
use Illuminate\Support\Facades\Auth;
use App\Services\LanguageService;

class LanguageSettings extends Component
{
    public $userLanguagePreference = null;
    public $availableLanguages = []; // Only languages enabled for this org

    public $orgFeatures;

    public function mount()
    {
        $this->loadSettings();
        
        // Redirect to settings if language features are not enabled
        if (!$this->canUseLanguageFeatures()) {
            return redirect()->route('settings.appearance')->with('error', __('gym.language_features_not_enabled'));
        }
    }

    /**
     * Load organization features and language settings
     */
    public function loadSettings()
    {
        $orgId = Auth::user()->orgUser->org_id;
        $this->orgFeatures = OrgSettingsFeatures::where('org_id', $orgId)->first();

        if ($this->orgFeatures && $this->orgFeatures->isLanguageFeatureEnabled()) {
            $this->availableLanguages = $this->orgFeatures->getEnabledLanguagesWithNames();
        } else {
            $this->availableLanguages = ['en-US' => 'English'];
        }

        $this->userLanguagePreference = Auth::user()->language_preference;
    }

    /**
     * Set user's personal language preference
     */
    public function setUserLanguagePreference($languageCode)
    {
        try {
            if ($languageCode === 'null' || $languageCode === '') {
                // Clear user preference (use org default)
                $this->userLanguagePreference = null;
                Auth::user()->language_preference = null;
                Auth::user()->save();

                // Get org default language for session
                $effectiveLanguage = Auth::user()->getEffectiveLanguage();
                $localeCode = explode('-', $effectiveLanguage)[0];
                
                session()->flash('success', __('gym.user_language_cleared'));
            } else {
                // Set user preference
                Auth::user()->setLanguagePreference($languageCode);
                $this->userLanguagePreference = $languageCode;

                // Convert to simple language code for Laravel locale
                $localeCode = explode('-', $languageCode)[0];
                
                session()->flash('success', __('gym.user_language_updated'));
            }

            // Set app locale and session data immediately
            \Illuminate\Support\Facades\App::setLocale($localeCode);
            session(['locale' => $localeCode, 'effective_language' => $languageCode ?: $effectiveLanguage]);

            // Refresh the component data to reflect changes
            $this->loadSettings();

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Handle radio group model updates
     */
    public function updatedUserLanguagePreference($value)
    {
        $this->setUserLanguagePreference($value);
        
        // Force page refresh to apply language changes and RTL/LTR styles
        $this->dispatch('force-page-refresh');
    }

    /**
     * Get language name for display using LanguageService
     */
    public function getLanguageName($languageCode)
    {
        return LanguageService::getLanguageDisplayName($languageCode);
    }

    /**
     * Get user's effective language (preference or org default)
     */
    public function getUserEffectiveLanguage()
    {
        return Auth::user()->getEffectiveLanguage();
    }

    /**
     * Check if user has a personal language preference set
     */
    public function hasUserLanguagePreference()
    {
        return !empty($this->userLanguagePreference);
    }

    /**
     * Check if the organization can use language features
     *
     * @return bool
     */
    public function canUseLanguageFeatures(): bool
    {
        return $this->orgFeatures && $this->orgFeatures->isLanguageFeatureEnabled();
    }

    public function render()
    {
        return view('livewire.settings.language-settings');
    }
}

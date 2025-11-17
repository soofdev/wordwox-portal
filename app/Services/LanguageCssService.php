<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Service to manage language-specific CSS and RTL/LTR direction
 */
class LanguageCssService
{
    /**
     * Get the CSS files that should be loaded for the current language
     *
     * @return array
     */
    public static function getLanguageCssFiles(): array
    {
        $currentLanguage = self::getCurrentLanguage();
        $cssFiles = [];

        // Always include the base app.css
        $cssFiles[] = 'app.css';

        // Add language-specific CSS files
        if (self::isRtlLanguage($currentLanguage)) {
            $cssFiles[] = 'arabic.css';
        }

        return $cssFiles;
    }

    /**
     * Get the current user's effective language
     *
     * @return string
     */
    public static function getCurrentLanguage(): string
    {
        if (Auth::check() && Auth::user()->orgUser) {
            return Auth::user()->getEffectiveLanguage();
        }

        return 'en-US'; // Default fallback
    }

    /**
     * Check if a language is RTL (Right-to-Left)
     *
     * @param string $languageCode
     * @return bool
     */
    public static function isRtlLanguage(string $languageCode): bool
    {
        $rtlLanguages = [
            'ar-SA', 'ar', 'he-IL', 'he', 'fa-IR', 'fa', 'ur-PK', 'ur'
        ];

        return in_array($languageCode, $rtlLanguages);
    }

    /**
     * Get the text direction for the current language
     *
     * @return string 'rtl' or 'ltr'
     */
    public static function getTextDirection(): string
    {
        $currentLanguage = self::getCurrentLanguage();
        return self::isRtlLanguage($currentLanguage) ? 'rtl' : 'ltr';
    }

    /**
     * Get HTML attributes for the current language
     *
     * @return array
     */
    public static function getHtmlAttributes(): array
    {
        $currentLanguage = self::getCurrentLanguage();
        $direction = self::getTextDirection();
        
        // Convert language code to simple format for HTML lang attribute
        $langCode = self::getSimpleLanguageCode($currentLanguage);

        return [
            'lang' => $langCode,
            'dir' => $direction
        ];
    }

    /**
     * Convert full language code to simple format
     *
     * @param string $languageCode
     * @return string
     */
    public static function getSimpleLanguageCode(string $languageCode): string
    {
        // Convert 'en-US' to 'en', 'ar-SA' to 'ar', etc.
        return explode('-', $languageCode)[0];
    }

    /**
     * Get CSS class names for the current language
     *
     * @return string
     */
    public static function getLanguageCssClasses(): string
    {
        $currentLanguage = self::getCurrentLanguage();
        $classes = [];

        // Add direction class
        $classes[] = self::getTextDirection();

        // Add language-specific class
        $classes[] = 'lang-' . self::getSimpleLanguageCode($currentLanguage);

        // Add RTL class if needed
        if (self::isRtlLanguage($currentLanguage)) {
            $classes[] = 'rtl-layout';
        }

        return implode(' ', $classes);
    }

    /**
     * Generate CSS link tags for the current language
     *
     * @return string
     */
    public static function generateCssLinks(): string
    {
        $cssFiles = self::getLanguageCssFiles();
        $links = [];

        foreach ($cssFiles as $cssFile) {
            $links[] = sprintf(
                '<link rel="stylesheet" href="%s">',
                asset("css/{$cssFile}")
            );
        }

        return implode("\n", $links);
    }

    /**
     * Check if the current language needs special font loading
     *
     * @return bool
     */
    public static function needsSpecialFonts(): bool
    {
        $currentLanguage = self::getCurrentLanguage();
        
        // Languages that might need special font support
        $specialFontLanguages = ['ar-SA', 'ar', 'he-IL', 'he', 'fa-IR', 'fa', 'ur-PK', 'ur'];
        
        return in_array($currentLanguage, $specialFontLanguages);
    }

    /**
     * Get font family CSS for the current language
     *
     * @return string
     */
    public static function getLanguageFontFamily(): string
    {
        $currentLanguage = self::getCurrentLanguage();

        switch (self::getSimpleLanguageCode($currentLanguage)) {
            case 'ar':
                return "'Segoe UI', 'Tahoma', 'Arial Unicode MS', sans-serif";
            case 'he':
                return "'Segoe UI', 'Tahoma', 'Arial Unicode MS', sans-serif";
            case 'fa':
                return "'Segoe UI', 'Tahoma', 'Arial Unicode MS', sans-serif";
            default:
                return "'Inter', 'Segoe UI', 'Roboto', sans-serif";
        }
    }

    /**
     * Generate inline CSS for language-specific styles
     *
     * @return string
     */
    public static function generateInlineCss(): string
    {
        $fontFamily = self::getLanguageFontFamily();
        $direction = self::getTextDirection();

        return "
        <style>
            html, body {
                font-family: {$fontFamily};
                direction: {$direction};
            }
        </style>
        ";
    }

    /**
     * Get all supported languages with their RTL status
     *
     * @return array
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'en-US' => [
                'name' => 'English',
                'native_name' => 'English',
                'rtl' => false,
                'css_file' => null
            ],
            'ar-SA' => [
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'rtl' => true,
                'css_file' => 'arabic.css'
            ]
        ];
    }
}

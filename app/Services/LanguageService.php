<?php

namespace App\Services;

class LanguageService
{
    /**
     * Get display name for a language code
     *
     * @param string $languageCode
     * @return string
     */
    public static function getLanguageDisplayName(string $languageCode): string
    {
        $languageNames = self::getLanguageNames();

        return $languageNames[$languageCode] ?? ucfirst($languageCode);
    }

    /**
     * Get all supported language names
     *
     * @return array
     */
    public static function getLanguageNames(): array
    {
        return [
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'ar-SA' => 'العربية (Saudi Arabia)',
            'ar-EG' => 'العربية (Egypt)',
            'ar-AE' => 'العربية (UAE)',
            'es-ES' => 'Español (Spain)',
            'es-MX' => 'Español (Mexico)',
            'fr-FR' => 'Français (France)',
            'fr-CA' => 'Français (Canada)',
            'de-DE' => 'Deutsch (Germany)',
            'de-AT' => 'Deutsch (Austria)',
            'zh-CN' => '中文 (China)',
            'zh-TW' => '中文 (Taiwan)',
            'ja-JP' => '日本語 (Japan)',
            'ko-KR' => '한국어 (Korea)',
            'it-IT' => 'Italiano (Italy)',
            'pt-BR' => 'Português (Brazil)',
            'pt-PT' => 'Português (Portugal)',
            'ru-RU' => 'Русский (Russia)',
            'tr-TR' => 'Türkçe (Turkey)',
            'nl-NL' => 'Nederlands (Netherlands)',
            'sv-SE' => 'Svenska (Sweden)',
            'da-DK' => 'Dansk (Denmark)',
            'no-NO' => 'Norsk (Norway)',
            'fi-FI' => 'Suomi (Finland)',
            'pl-PL' => 'Polski (Poland)',
            'cs-CZ' => 'Čeština (Czech Republic)',
            'hu-HU' => 'Magyar (Hungary)',
            'ro-RO' => 'Română (Romania)',
            'bg-BG' => 'Български (Bulgaria)',
            'hr-HR' => 'Hrvatski (Croatia)',
            'sk-SK' => 'Slovenčina (Slovakia)',
            'sl-SI' => 'Slovenščina (Slovenia)',
            'et-EE' => 'Eesti (Estonia)',
            'lv-LV' => 'Latviešu (Latvia)',
            'lt-LT' => 'Lietuvių (Lithuania)',
            'uk-UA' => 'Українська (Ukraine)',
            'he-IL' => 'עברית (Israel)',
            'hi-IN' => 'हिन्दी (India)',
            'th-TH' => 'ไทย (Thailand)',
            'vi-VN' => 'Tiếng Việt (Vietnam)',
            'id-ID' => 'Bahasa Indonesia (Indonesia)',
            'ms-MY' => 'Bahasa Melayu (Malaysia)',
            'tl-PH' => 'Filipino (Philippines)',
        ];
    }

    /**
     * Get base language code from full locale code
     *
     * @param string $languageCode
     * @return string
     */
    public static function getBaseLanguageCode(string $languageCode): string
    {
        return explode('-', $languageCode)[0];
    }

    /**
     * Check if a language code is supported
     *
     * @param string $languageCode
     * @return bool
     */
    public static function isSupported(string $languageCode): bool
    {
        return array_key_exists($languageCode, self::getLanguageNames());
    }

    /**
     * Get available language codes
     *
     * @return array
     */
    public static function getAvailableLanguageCodes(): array
    {
        return array_keys(self::getLanguageNames());
    }

    /**
     * Get language direction (RTL or LTR)
     *
     * @param string $languageCode
     * @return string
     */
    public static function getLanguageDirection(string $languageCode): string
    {
        $rtlLanguages = ['ar', 'he', 'fa', 'ur', 'yi'];
        $baseLanguage = self::getBaseLanguageCode($languageCode);

        return in_array($baseLanguage, $rtlLanguages) ? 'rtl' : 'ltr';
    }

    /**
     * Get language flag emoji or icon identifier
     *
     * @param string $languageCode
     * @return string
     */
    public static function getLanguageFlag(string $languageCode): string
    {
        $flags = [
            'en-US' => '🇺🇸',
            'en-GB' => '🇬🇧',
            'ar-SA' => '🇸🇦',
            'ar-EG' => '🇪🇬',
            'ar-AE' => '🇦🇪',
            'es-ES' => '🇪🇸',
            'es-MX' => '🇲🇽',
            'fr-FR' => '🇫🇷',
            'fr-CA' => '🇨🇦',
            'de-DE' => '🇩🇪',
            'de-AT' => '🇦🇹',
            'zh-CN' => '🇨🇳',
            'zh-TW' => '🇹🇼',
            'ja-JP' => '🇯🇵',
            'ko-KR' => '🇰🇷',
            'it-IT' => '🇮🇹',
            'pt-BR' => '🇧🇷',
            'pt-PT' => '🇵🇹',
            'ru-RU' => '🇷🇺',
            'tr-TR' => '🇹🇷',
            'nl-NL' => '🇳🇱',
            'sv-SE' => '🇸🇪',
            'da-DK' => '🇩🇰',
            'no-NO' => '🇳🇴',
            'fi-FI' => '🇫🇮',
            'pl-PL' => '🇵🇱',
            'cs-CZ' => '🇨🇿',
            'hu-HU' => '🇭🇺',
            'ro-RO' => '🇷🇴',
            'bg-BG' => '🇧🇬',
            'hr-HR' => '🇭🇷',
            'sk-SK' => '🇸🇰',
            'sl-SI' => '🇸🇮',
            'et-EE' => '🇪🇪',
            'lv-LV' => '🇱🇻',
            'lt-LT' => '🇱🇹',
            'uk-UA' => '🇺🇦',
            'he-IL' => '🇮🇱',
            'hi-IN' => '🇮🇳',
            'th-TH' => '🇹🇭',
            'vi-VN' => '🇻🇳',
            'id-ID' => '🇮🇩',
            'ms-MY' => '🇲🇾',
            'tl-PH' => '🇵🇭',
        ];

        return $flags[$languageCode] ?? '🌐';
    }
}

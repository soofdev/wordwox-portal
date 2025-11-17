<?php

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;

class PhoneNumberService
{
    private PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Format phone number for storage (E.164 format)
     */
    public function formatForStorage(string $phoneNumber, string $countryCode = null): ?string
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException $e) {
            // Return null for invalid numbers
        }
        
        return null;
    }

    /**
     * Format phone number for display (National format)
     */
    public function formatForDisplay(string $phoneNumber, string $countryCode = null): ?string
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::NATIONAL);
            }
        } catch (NumberParseException $e) {
            // Return original number if parsing fails
        }
        
        return $phoneNumber;
    }

    /**
     * Format phone number for international display
     */
    public function formatForInternational(string $phoneNumber, string $countryCode = null): ?string
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->format($parsedNumber, PhoneNumberFormat::INTERNATIONAL);
            }
        } catch (NumberParseException $e) {
            // Return original number if parsing fails
        }
        
        return $phoneNumber;
    }

    /**
     * Get the country code from a phone number
     */
    public function getCountryCode(string $phoneNumber): ?string
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, null);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->getRegionCodeForNumber($parsedNumber);
            }
        } catch (NumberParseException $e) {
            // Return null for invalid numbers
        }
        
        return null;
    }

    /**
     * Parse and validate a phone number
     */
    public function parseAndValidate(string $phoneNumber, string $countryCode = null): ?PhoneNumber
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $parsedNumber;
            }
        } catch (NumberParseException $e) {
            // Return null for invalid numbers
        }
        
        return null;
    }

    /**
     * Check if a phone number is mobile
     */
    public function isMobile(string $phoneNumber, string $countryCode = null): bool
    {
        try {
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                $numberType = $this->phoneUtil->getNumberType($parsedNumber);
                return in_array($numberType, [
                    \libphonenumber\PhoneNumberType::MOBILE,
                    \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE
                ]);
            }
        } catch (NumberParseException $e) {
            // Return false for invalid numbers
        }
        
        return false;
    }

    /**
     * Get supported countries with their data
     */
    public function getSupportedCountries(): array
    {
        $supportedRegions = $this->phoneUtil->getSupportedRegions();
        $countries = [];
        
        // Priority countries (commonly used in gyms)
        $priorityCountries = ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'ES', 'IT', 'JP', 'KR', 'AE', 'SA', 'QA', 'JO'];
        
        // Add priority countries first
        foreach ($priorityCountries as $regionCode) {
            if (in_array($regionCode, $supportedRegions)) {
                $countries[$regionCode] = $this->getCountryData($regionCode);
            }
        }
        
        // Add remaining countries
        foreach ($supportedRegions as $regionCode) {
            if (!in_array($regionCode, $priorityCountries)) {
                $countries[$regionCode] = $this->getCountryData($regionCode);
            }
        }
        
        return $countries;
    }

    /**
     * Get country data for a specific region
     */
    private function getCountryData(string $regionCode): array
    {
        $countryCode = $this->phoneUtil->getCountryCodeForRegion($regionCode);
        
        // Country names mapping (you might want to use a proper localization package)
        $countryNames = [
            'US' => ['name' => 'United States', 'flag' => '🇺🇸'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦'],
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪'],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷'],
            'ES' => ['name' => 'Spain', 'flag' => '🇪🇸'],
            'IT' => ['name' => 'Italy', 'flag' => '🇮🇹'],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵'],
            'KR' => ['name' => 'South Korea', 'flag' => '🇰🇷'],
            'AE' => ['name' => 'United Arab Emirates', 'flag' => '🇦🇪'],
            'SA' => ['name' => 'Saudi Arabia', 'flag' => '🇸🇦'],
            'QA' => ['name' => 'Qatar', 'flag' => '🇶🇦'],
            'JO' => ['name' => 'Jordan', 'flag' => '🇯🇴'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳'],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳'],
            'BR' => ['name' => 'Brazil', 'flag' => '🇧🇷'],
            'MX' => ['name' => 'Mexico', 'flag' => '🇲🇽'],
            'RU' => ['name' => 'Russia', 'flag' => '🇷🇺'],
            'ZA' => ['name' => 'South Africa', 'flag' => '🇿🇦'],
            'NG' => ['name' => 'Nigeria', 'flag' => '🇳🇬'],
        ];
        
        $countryInfo = $countryNames[$regionCode] ?? [
            'name' => $regionCode,
            'flag' => '🌍'
        ];
        
        return [
            'code' => (string) $countryCode,
            'name' => $countryInfo['name'],
            'flag' => $countryInfo['flag'],
            'region' => $regionCode
        ];
    }

    /**
     * Auto-detect country from phone number
     */
    public function detectCountry(string $phoneNumber): ?string
    {
        try {
            // Try parsing without country code first
            $parsedNumber = $this->phoneUtil->parse($phoneNumber, null);
            
            if ($this->phoneUtil->isValidNumber($parsedNumber)) {
                return $this->phoneUtil->getRegionCodeForNumber($parsedNumber);
            }
        } catch (NumberParseException $e) {
            // Could not detect country
        }
        
        return null;
    }
}
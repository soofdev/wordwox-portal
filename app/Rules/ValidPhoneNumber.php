<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberType;

class ValidPhoneNumber implements ValidationRule
{
    protected $countryCode;
    protected $allowedTypes;
    protected $strict;

    public function __construct(string $countryCode = null, array $allowedTypes = null, bool $strict = true)
    {
        $this->countryCode = $countryCode;
        $this->allowedTypes = $allowedTypes ?? [
            PhoneNumberType::MOBILE,
            PhoneNumberType::FIXED_LINE,
            PhoneNumberType::FIXED_LINE_OR_MOBILE
        ];
        $this->strict = $strict;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        
        try {
            // Parse the phone number
            $phoneNumber = $phoneUtil->parse($value, $this->countryCode);
            
            // Check if the number is valid
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $fail('The phone number is not valid.');
                return;
            }
            
            // Check if strict validation is enabled
            if ($this->strict) {
                // Verify the number type is allowed
                $numberType = $phoneUtil->getNumberType($phoneNumber);
                
                if (!in_array($numberType, $this->allowedTypes)) {
                    $this->failWithTypeMessage($fail, $numberType);
                    return;
                }
                
                // Additional validation: check if it's possible number
                if (!$phoneUtil->isPossibleNumber($phoneNumber)) {
                    $fail('The phone number format is not valid for the selected country.');
                    return;
                }
            }
            
        } catch (NumberParseException $e) {
            $this->failWithParseError($fail, $e);
        }
    }

    /**
     * Handle parse errors with specific messages
     */
    private function failWithParseError(Closure $fail, NumberParseException $e): void
    {
        switch ($e->getErrorType()) {
            case NumberParseException::INVALID_COUNTRY_CODE:
                $fail('The country code is invalid.');
                break;
            case NumberParseException::NOT_A_NUMBER:
                $fail('The phone number contains invalid characters.');
                break;
            case NumberParseException::TOO_SHORT_NSN:
                $fail('The phone number is too short.');
                break;
            case NumberParseException::TOO_LONG:
                $fail('The phone number is too long.');
                break;
            case NumberParseException::TOO_SHORT_AFTER_IDD:
                $fail('The phone number is too short after the country code.');
                break;
            default:
                $fail('The phone number format is invalid.');
        }
    }

    /**
     * Handle number type validation failures
     */
    private function failWithTypeMessage(Closure $fail, int $numberType): void
    {
        $typeNames = [
            PhoneNumberType::FIXED_LINE => 'landline',
            PhoneNumberType::MOBILE => 'mobile',
            PhoneNumberType::FIXED_LINE_OR_MOBILE => 'phone',
            PhoneNumberType::TOLL_FREE => 'toll-free',
            PhoneNumberType::PREMIUM_RATE => 'premium rate',
            PhoneNumberType::SHARED_COST => 'shared cost',
            PhoneNumberType::VOIP => 'VoIP',
            PhoneNumberType::PERSONAL_NUMBER => 'personal',
            PhoneNumberType::PAGER => 'pager',
            PhoneNumberType::UAN => 'UAN',
            PhoneNumberType::VOICEMAIL => 'voicemail',
        ];

        $typeName = $typeNames[$numberType] ?? 'phone';
        
        if (in_array(PhoneNumberType::MOBILE, $this->allowedTypes) && 
            !in_array(PhoneNumberType::FIXED_LINE, $this->allowedTypes)) {
            $fail("Please enter a mobile phone number. {$typeName} numbers are not allowed.");
        } elseif (in_array(PhoneNumberType::FIXED_LINE, $this->allowedTypes) && 
                  !in_array(PhoneNumberType::MOBILE, $this->allowedTypes)) {
            $fail("Please enter a landline phone number. {$typeName} numbers are not allowed.");
        } else {
            $fail("This type of phone number ({$typeName}) is not allowed.");
        }
    }
}
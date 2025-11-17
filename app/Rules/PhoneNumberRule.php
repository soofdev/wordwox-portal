<?php

namespace App\Rules;

use Closure;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    protected $formattedNumber = "";
    protected $phoneCountry;

    public function __construct($phoneCountry)
    {
        $this->phoneCountry = $phoneCountry;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fullNumber = '+'.$this->phoneCountry.$value;
        
        if (!$this->isValidAndFormattedPhoneNumber($fullNumber)) {
            $fail('Invalid phone number.');
        }
    }

    private function isValidAndFormattedPhoneNumber($phoneNumber): bool 
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $numberProto = $phoneUtil->parse($phoneNumber, null);

            if($phoneUtil->isValidNumber($numberProto)) {
                $this->formattedNumber = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
                return true;
            }
            return false;
        } catch (NumberParseException $e) {
            return false;
        }
    }
}

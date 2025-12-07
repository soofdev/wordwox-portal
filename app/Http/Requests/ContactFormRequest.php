<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Contact form is public
    }

    /**
     * Get the validation rules that apply to the request.
     * Similar to Yii's ContactForm rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
            'verification_code' => 'required|string',
            'org_id' => 'nullable|integer',
        ];
    }
    
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $captchaCode = session('captcha_code');
            $userCode = strtolower($this->input('verification_code'));
            
            if (empty($captchaCode) || $captchaCode !== $userCode) {
                $validator->errors()->add('verification_code', 'The verification code is incorrect.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name cannot be blank.',
            'email.required' => 'Email cannot be blank.',
            'email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Subject cannot be blank.',
            'body.required' => 'Body cannot be blank.',
        ];
    }
}


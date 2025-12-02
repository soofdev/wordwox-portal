<?php

namespace App\Livewire\Customer;

use App\Models\Org;
use App\Rules\PhoneNumberRule;
use App\Rules\UniqueOrgUserEmail;
use App\Rules\UniqueOrgUserFullName;
use App\Rules\UniqueOrgUserPhone;
use App\Services\CustomerRegistrationService;
use App\Services\PhoneNumberService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Validate;

/**
 * Customer Signup Component
 * 
 * Allows customers to register using email or phone
 * Similar to Yii project customer registration
 */
class CustomerSignup extends Component
{
    #[Validate('required|string|min:2|max:255')]
    public string $fullName = '';
    
    public string $loginMethod = 'email'; // 'email' or 'phone'
    
    #[Validate('nullable|email|max:255')]
    public string $email = '';
    
    public string $phoneCountry = 'US';
    #[Validate('nullable|string')]
    public string $phoneNumber = '';
    
    #[Validate('nullable|date|before:today')]
    public string $dob = '';
    
    #[Validate('nullable|in:1,2')]
    public ?int $gender = null;
    
    #[Validate('nullable|string|max:500')]
    public string $address = '';
    
    public string $message = '';
    public bool $registrationSuccess = false;
    public $orgId;
    
    protected CustomerRegistrationService $registrationService;
    protected PhoneNumberService $phoneService;
    
    public function boot(CustomerRegistrationService $registrationService, PhoneNumberService $phoneService)
    {
        $this->registrationService = $registrationService;
        $this->phoneService = $phoneService;
        $this->orgId = env('CMS_DEFAULT_ORG_ID', 8);
    }
    
    /**
     * Register customer
     */
    public function register()
    {
        // Validate based on login method
        $rules = [
            'fullName' => ['required', 'string', 'min:2', 'max:255', new UniqueOrgUserFullName($this->orgId)],
            'dob' => 'nullable|date|before:today',
            'gender' => 'nullable|in:1,2',
            'address' => 'nullable|string|max:500',
        ];
        
        if ($this->loginMethod === 'email') {
            $rules['email'] = ['required', 'email', 'max:255', new UniqueOrgUserEmail($this->orgId)];
            $rules['phoneCountry'] = 'nullable|string|min:1|max:4';
            // Only validate phone if provided
            if (!empty($this->phoneNumber)) {
                $rules['phoneNumber'] = ['string', new PhoneNumberRule($this->phoneCountry ?: 'US')];
            } else {
                $rules['phoneNumber'] = 'nullable|string';
            }
        } else {
            $rules['phoneCountry'] = 'required|string|min:1|max:4';
            $rules['phoneNumber'] = ['required', 'string', new PhoneNumberRule($this->phoneCountry), new UniqueOrgUserPhone($this->orgId, $this->phoneCountry)];
            $rules['email'] = 'nullable|email|max:255';
        }
        
        $this->validate($rules);
        
        try {
            // Prepare registration data
            $data = [
                'fullName' => trim($this->fullName),
                'email' => $this->email ?: null,
                'phoneCountry' => $this->phoneCountry,
                'phoneNumber' => $this->phoneNumber ?: null,
                'dob' => $this->dob ?: null,
                'gender' => $this->gender,
                'address' => $this->address ?: null,
                'loginMethod' => $this->loginMethod,
            ];
            
            // Create customer registration
            $orgUser = $this->registrationService->createIndividualRegistration($data, $this->orgId);
            
            Log::info('Customer registration successful', [
                'org_user_id' => $orgUser->id,
                'org_id' => $this->orgId,
                'full_name' => $orgUser->fullName,
                'login_method' => $this->loginMethod
            ]);
            
            $this->registrationSuccess = true;
            $this->message = 'Registration successful! Please check your ' . ($this->loginMethod === 'email' ? 'email' : 'phone') . ' for verification.';
            
            // Redirect to login after a short delay
            session()->flash('registration_success', true);
            return $this->redirect(route('login'), navigate: false);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->message = 'Validation failed. Please check your input.';
            throw $e;
        } catch (\Exception $e) {
            Log::error('Customer registration failed', [
                'error' => $e->getMessage(),
                'org_id' => $this->orgId,
                'data' => $data ?? []
            ]);
            $this->message = 'Registration failed. Please try again.';
        }
    }
    
    /**
     * Get supported countries for phone input
     */
    public function getSupportedCountries()
    {
        return $this->phoneService->getSupportedCountries();
    }
    
    public function render()
    {
        return view('livewire.customer.customer-signup')
            ->layout('components.layouts.templates.fitness');
    }
}


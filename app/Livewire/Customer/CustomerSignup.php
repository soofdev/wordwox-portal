<?php

namespace App\Livewire\Customer;

use App\Models\CmsPage;
use App\Models\Org;
use App\Rules\UniqueOrgUserEmail;
use App\Rules\UniqueOrgUserFullName;
use App\Rules\UniqueOrgUserPhone;
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
    public $navigationPages;
    
    public function boot()
    {
        $this->orgId = env('CMS_DEFAULT_ORG_ID', env('DEFAULT_ORG_ID', 8));
        $this->loadNavigationPages();
    }
    
    /**
     * Load navigation pages for the navbar
     */
    protected function loadNavigationPages()
    {
        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
        $this->navigationPages = CmsPage::where('org_id', $orgId)
            ->where('status', 'published')
            ->where('show_in_navigation', true)
            ->where('is_homepage', false)
            ->where('slug', '!=', 'home')
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    
    /**
     * Register customer
     * Uses same validation and creation logic as /members/create
     */
    public function register()
    {
        // Validate based on login method (same as member creation)
        $rules = [
            'fullName' => ['required', 'string', 'min:2', 'max:255', new UniqueOrgUserFullName($this->orgId)],
            'dob' => 'nullable|date|before:today',
            'gender' => 'nullable|in:1,2',
            'address' => 'nullable|string|max:500',
        ];
        
        if ($this->loginMethod === 'email') {
            // For email login: email required and unique, phone optional (no uniqueness check)
            $rules['email'] = ['required', 'email', 'max:255', new UniqueOrgUserEmail($this->orgId)];
            $rules['phoneCountry'] = 'nullable|string|min:1|max:4';
            $rules['phoneNumber'] = ['nullable', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15'];
        } else {
            // For phone login: phone required and unique, email optional (no uniqueness check)
            $rules['phoneCountry'] = 'required|string|min:1|max:4';
            $rules['phoneNumber'] = ['required', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15', new UniqueOrgUserPhone($this->orgId, $this->phoneCountry)];
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }
        
        $this->validate($rules);
        
        try {
            // Convert ISO country code to dialing code (same as member creation)
            $phoneCountryCode = $this->convertIsoToDialingCode($this->phoneCountry);
            
            // Prepare user data (same structure as member creation)
            // Ensure phone data is only stored when phone signup, email data when email signup
            $userData = [
                'org_id' => $this->orgId,
                'fullName' => trim($this->fullName),
                'phoneCountry' => ($this->loginMethod === 'phone' && $phoneCountryCode) ? $phoneCountryCode : null, // Store dialing code only for phone signup
                'phoneNumber' => ($this->loginMethod === 'phone' && $this->phoneNumber) ? $this->phoneNumber : null, // Store phone only for phone signup
                'email' => ($this->loginMethod === 'email' && $this->email) ? $this->email : null, // Store email only for email signup
                'dob' => $this->dob ?: null,
                'gender' => $this->gender ? (int)$this->gender : null,
                'address' => $this->address ?: null,
                'isCustomer' => true,
                'addMemberInviteOption' => $this->loginMethod === 'phone' ? 2 : 1, // 1=Email, 2=SMS (matching Yii2 constants)
            ];
            
            // Log what will be stored
            Log::info('Creating OrgUser with signup data', [
                'login_method' => $this->loginMethod,
                'email' => $userData['email'],
                'phone_country' => $userData['phoneCountry'],
                'phone_number' => $userData['phoneNumber'],
                'add_member_invite_option' => $userData['addMemberInviteOption'],
            ]);
            
            // Create OrgUser directly (same as member creation)
            $orgUser = \App\Models\OrgUser::create($userData);
            
            Log::info('Customer registration successful', [
                'org_user_id' => $orgUser->id,
                'org_id' => $this->orgId,
                'full_name' => $orgUser->fullName,
                'login_method' => $this->loginMethod,
                'stored_email' => $orgUser->email,
                'stored_phone_country' => $orgUser->phoneCountry,
                'stored_phone_number' => $orgUser->phoneNumber,
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
                'trace' => $e->getTraceAsString()
            ]);
            $this->message = 'Registration failed: ' . $e->getMessage();
        }
    }
    
    /**
     * Convert ISO country code to dialing code for database storage
     * Same logic as member creation form
     */
    private function convertIsoToDialingCode(?string $isoCode): ?string
    {
        if (!$isoCode) {
            return null;
        }
        
        $countries = $this->getSupportedCountries();
        
        if (!isset($countries[$isoCode])) {
            return null;
        }
        
        return $countries[$isoCode]['code'] ?? null;
    }
    
    /**
     * Get supported countries for phone input
     * Returns hardcoded list matching member creation form
     */
    public function getSupportedCountries(): array
    {
        return [
            'US' => ['code' => '1', 'name' => 'United States', 'flag' => '🇺🇸'],
            'CA' => ['code' => '1', 'name' => 'Canada', 'flag' => '🇨🇦'],
            'GB' => ['code' => '44', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'AU' => ['code' => '61', 'name' => 'Australia', 'flag' => '🇦🇺'],
            'DE' => ['code' => '49', 'name' => 'Germany', 'flag' => '🇩🇪'],
            'FR' => ['code' => '33', 'name' => 'France', 'flag' => '🇫🇷'],
            'ES' => ['code' => '34', 'name' => 'Spain', 'flag' => '🇪🇸'],
            'IT' => ['code' => '39', 'name' => 'Italy', 'flag' => '🇮🇹'],
            'JP' => ['code' => '81', 'name' => 'Japan', 'flag' => '🇯🇵'],
            'KR' => ['code' => '82', 'name' => 'South Korea', 'flag' => '🇰🇷'],
            'AE' => ['code' => '971', 'name' => 'United Arab Emirates', 'flag' => '🇦🇪'],
            'SA' => ['code' => '966', 'name' => 'Saudi Arabia', 'flag' => '🇸🇦'],
            'QA' => ['code' => '974', 'name' => 'Qatar', 'flag' => '🇶🇦'],
            'JO' => ['code' => '962', 'name' => 'Jordan', 'flag' => '🇯🇴'],
        ];
    }
    
    public function render()
    {
        return view('livewire.customer.customer-signup')
            ->layout('components.layouts.templates.fitness', [
                'navigationPages' => $this->navigationPages ?? collect(),
            ]);
    }
}


<?php

namespace App\Services;

use App\Models\OrgUser;
use App\Models\OrgFamily;
use App\Models\OrgFamilyUser;
use App\Models\Org;
use App\Rules\UniqueOrgUserEmail;
use App\Rules\UniqueOrgUserEmailOptional;
use App\Rules\UniqueOrgUserFullName;
use App\Rules\UniqueOrgUserPhone;
use App\Rules\UniqueOrgUserPhoneOptional;
use App\Rules\ValidChildAge;
use App\Rules\ValidSchoolGrade;
use App\Services\Yii2QueueDispatcher;
use App\Services\ConsentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerRegistrationService
{
    // Constants matching Yii2 OrgSettings exactly for addMemberInviteOption
    const ADD_MEMBER_INVITE_OPTION_NONE = 0;
    const ADD_MEMBER_INVITE_OPTION_EMAIL = 1;
    const ADD_MEMBER_INVITE_OPTION_SMS = 2;
    const ADD_MEMBER_INVITE_OPTION_ALL = 3;
    const ADD_MEMBER_INVITE_OPTION_CREATE_PASS = 4;

    // Login method constants for user preference
    const LOGIN_METHOD_EMAIL = 'email';
    const LOGIN_METHOD_SMS = 'sms';

    protected Yii2QueueDispatcher $yii2QueueDispatcher;
    protected ConsentService $consentService;

    public function __construct(Yii2QueueDispatcher $yii2QueueDispatcher, ConsentService $consentService)
    {
        $this->yii2QueueDispatcher = $yii2QueueDispatcher;
        $this->consentService = $consentService;
    }

    /**
     * Create individual customer registration
     */
    public function createIndividualRegistration(array $data, int $orgId): OrgUser
    {
        DB::beginTransaction();
        
        try {
            // Validate individual registration data
            $this->validateIndividualData($data, $orgId);
            
            // Prepare user data with login method preference
            $userData = $this->prepareIndividualUserData($data, $orgId);
            
            // Create the user
            $orgUser = $this->createOrgUser($userData);
            
            // Store consent records if provided
            if (isset($data['consents']) && isset($data['consents']['primary'])) {
                $this->consentService->storeUserConsents(
                    $orgUser,
                    $data['consents']['primary'],
                    'registration'
                );
            }
            
            // Dispatch post-creation jobs
            $this->dispatchPostCreationJobs($orgUser);
            
            DB::commit();
            
            Log::info('Individual registration completed', [
                'org_user_id' => $orgUser->id,
                'org_id' => $orgId,
                'full_name' => $orgUser->fullName,
            ]);
            
            return $orgUser;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Individual registration failed', [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Create family registration (primary user + spouse + children)
     */
    public function createFamilyRegistration(array $data, int $orgId): array
    {
        DB::beginTransaction();
        
        try {
            // Validate family registration data
            $this->validateFamilyData($data, $orgId);
            
            $createdMembers = [];
            
            // 1. Create primary user (required)
            $primaryData = $this->preparePrimaryUserData($data, $orgId);
            $primaryUser = $this->createOrgUser($primaryData);
            $createdMembers['primary'] = $primaryUser;
            
            // 2. Create spouse (optional)
            if ($this->hasSpouseData($data)) {
                $spouseData = $this->prepareSpouseUserData($data, $orgId, $primaryUser->id);
                $spouse = $this->createOrgUser($spouseData);
                $createdMembers['spouse'] = $spouse;
            }
            
            // 3. Create children (optional)
            $children = [];
            for ($i = 1; $i <= 2; $i++) {
                if ($this->hasChildData($data, $i)) {
                    $childData = $this->prepareChildUserData($data, $i, $orgId, $primaryUser->id);
                    $child = $this->createOrgUser($childData);
                    $children[] = $child;
                }
            }
            $createdMembers['children'] = $children;
            
            // 4. Create family group and relationships
            $family = $this->createFamilyGroup($orgId);
            $createdMembers['family'] = $family;
            
            // Link primary user to family as parent
            $this->linkFamilyMember($family->id, $primaryUser->id, 'parent', $orgId);
            
            // Link spouse to family as parent (if exists)
            if (isset($createdMembers['spouse'])) {
                $this->linkFamilyMember($family->id, $createdMembers['spouse']->id, 'parent', $orgId);
            }
            
            // Link children to family (if any)
            foreach ($children as $child) {
                $this->linkFamilyMember($family->id, $child->id, 'child', $orgId);
            }
            
            // 5. Store consent records if provided
            if (isset($data['consents'])) {
                $familyMembers = [
                    'primary' => $primaryUser,
                    'spouse' => $createdMembers['spouse'] ?? null,
                    'child_1' => $children[0] ?? null,
                    'child_2' => $children[1] ?? null,
                ];
                
                $this->consentService->storeFamilyConsents(
                    $familyMembers,
                    $data['consents'],
                    'registration'
                );
            }
            
            // 6. Dispatch post-creation jobs for all family members
            foreach ($createdMembers as $key => $member) {
                if ($key === 'children') {
                    foreach ($member as $child) {
                        $this->dispatchPostCreationJobs($child);
                    }
                } elseif ($key !== 'family') {
                    // Skip the family object, only dispatch jobs for OrgUser objects
                    $this->dispatchPostCreationJobs($member);
                }
            }
            
            DB::commit();
            
            Log::info('Family registration completed', [
                'primary_user_id' => $primaryUser->id,
                'org_id' => $orgId,
                'family_size' => count($createdMembers['children']) + 1 + (isset($createdMembers['spouse']) ? 1 : 0),
            ]);
            
            return $createdMembers;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Family registration failed', [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Generate secure registration token for public access
     */
    public function generateRegistrationToken(int $orgId, string $type = 'individual', int $expirationHours = 48): string
    {
        // Create a secure token with metadata
        $payload = [
            'org_id' => $orgId,
            'type' => $type, // 'individual' or 'family'
            'expires_at' => now()->addHours($expirationHours)->timestamp,
            'created_at' => now()->timestamp,
        ];
        
        // Create a secure token (you might want to use JWT or encrypt this)
        $token = base64_encode(json_encode($payload)) . '.' . Str::random(32);
        
        return $token;
    }

    /**
     * Validate and decode registration token
     */
    public function validateRegistrationToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return null;
            }
            
            $payload = json_decode(base64_decode($parts[0]), true);
            
            if (!$payload || !isset($payload['expires_at'], $payload['org_id'])) {
                return null;
            }
            
            // Check if token is expired
            if ($payload['expires_at'] < now()->timestamp) {
                return null;
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            Log::warning('Invalid registration token', ['token' => $token, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validate individual registration data
     */
    protected function validateIndividualData(array $data, int $orgId): void
    {
        // Determine login method preference to set appropriate validation rules
        $loginMethod = $data['loginMethod'] ?? self::LOGIN_METHOD_EMAIL;
        
        $rules = [
            'fullName' => ['required', 'string', 'max:255', 'min:2', new UniqueOrgUserFullName($orgId)],
            'nationality_country' => 'nullable|string|max:255',
            'nationalID' => 'nullable|string|max:50',
            'dob' => 'nullable|date|before:today',
            'gender' => 'nullable|in:1,2',
            'employer_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ];

        // Add conditional validation rules based on login method preference
        if ($loginMethod === self::LOGIN_METHOD_SMS) {
            // For SMS login, phone is required and email is optional (NO uniqueness validation)
            $rules['phoneCountry'] = 'required|string|min:1|max:4';
            $rules['phoneNumber'] = ['required', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15', new UniqueOrgUserPhone($orgId, $data['phoneCountry'] ?? 'US')];
            $rules['email'] = ['nullable', 'email', 'max:255']; // No uniqueness check for SMS login
        } else {
            // For email login (default), email is required and phone is optional (NO uniqueness validation)
            $rules['email'] = ['required', 'email', 'max:255', new UniqueOrgUserEmail($orgId)];
            $rules['phoneCountry'] = 'nullable|string|min:1|max:4';
            $rules['phoneNumber'] = ['nullable', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15']; // No uniqueness check for email login
        }

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Validate family registration data
     */
    protected function validateFamilyData(array $data, int $orgId): void
    {
        // Validate primary user data (required)
        $this->validateIndividualData($data, $orgId);
        
        // Validate spouse data (optional but if provided, must be valid)
        if ($this->hasSpouseData($data)) {
            $this->validateSpouseData($data, $orgId);
        }
        
        // Validate children data (optional but if provided, must be valid)
        for ($i = 1; $i <= 2; $i++) {
            if ($this->hasChildData($data, $i)) {
                $this->validateChildData($data, $i, $orgId);
            }
        }
    }

    /**
     * Validate spouse data
     */
    protected function validateSpouseData(array $data, int $orgId): void
    {
        // Determine spouse login method preference to set appropriate validation rules
        $spouseLoginMethod = $data['spouse_loginMethod'] ?? self::LOGIN_METHOD_EMAIL;
        
        $rules = [
            'spouse_fullName' => ['required', 'string', 'max:255', 'min:2', new UniqueOrgUserFullName($orgId)],
            'spouse_nationality_country' => 'nullable|string|max:255',
            'spouse_dob' => 'nullable|date|before:today',
            'spouse_employer_name' => 'nullable|string|max:255',
        ];

        // Add conditional validation rules based on spouse's login method preference
        if ($spouseLoginMethod === self::LOGIN_METHOD_SMS) {
            // For SMS login, phone is required and email is optional (NO uniqueness validation)
            $rules['spouse_phoneCountry'] = 'required|string|min:1|max:4';
            $rules['spouse_phoneNumber'] = ['required', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15', new UniqueOrgUserPhone($orgId, $data['spouse_phoneCountry'] ?? 'US')];
            $rules['spouse_email'] = ['nullable', 'email', 'max:255']; // No uniqueness check for SMS login
        } else {
            // For email login (default), email is required and phone is optional (NO uniqueness validation)
            $rules['spouse_email'] = ['required', 'email', 'max:255', new UniqueOrgUserEmail($orgId)];
            $rules['spouse_phoneCountry'] = 'nullable|string|min:1|max:4';
            $rules['spouse_phoneNumber'] = ['nullable', 'string', 'regex:/^[0-9\-\+\(\)\s]+$/', 'min:7', 'max:15']; // No uniqueness check for email login
        }

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Validate child data
     */
    protected function validateChildData(array $data, int $childIndex, int $orgId): void
    {
        $prefix = "child_{$childIndex}_";
        
        $rules = [
            "{$prefix}name" => ['required', 'string', 'max:255', 'min:2', new UniqueOrgUserFullName($orgId)],
            "{$prefix}gender" => 'required|in:1,2',
            "{$prefix}dob" => ['required', 'date', 'before:today', new ValidChildAge(0, 18)],
            "{$prefix}school_name" => 'required|string|max:255',
            "{$prefix}school_level" => ['required', 'string', 'max:50', new ValidSchoolGrade()],
            "{$prefix}activities" => 'nullable|string|max:500',
            "{$prefix}medical_conditions" => 'nullable|string|max:500',
            "{$prefix}allergies" => 'nullable|string|max:500',
            "{$prefix}medications" => 'nullable|string|max:500',
            "{$prefix}special_needs" => 'nullable|string|max:500',
            "{$prefix}past_injuries" => 'nullable|string|max:500',
        ];

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Check if spouse data is provided
     */
    protected function hasSpouseData(array $data): bool
    {
        return !empty($data['spouse_fullName']);
    }

    /**
     * Check if child data is provided
     */
    protected function hasChildData(array $data, int $childIndex): bool
    {
        return !empty($data["child_{$childIndex}_name"]);
    }

    /**
     * Prepare individual user data for creation
     */
    protected function prepareIndividualUserData(array $data, int $orgId): array
    {
        return [
            'org_id' => $orgId,
            'fullName' => $data['fullName'],
            'phoneCountry' => $data['phoneCountry'] ?? null,
            'phoneNumber' => $data['phoneNumber'] ?? null,
            'email' => $data['email'] ?? null,
            'nationality_country' => $data['nationality_country'] ?? null,
            'nationalID' => $data['nationalID'] ?? null,
            'dob' => $data['dob'] ? Carbon::parse($data['dob'])->format('Y-m-d') : null,
            'gender' => !empty($data['gender']) ? (int)$data['gender'] : null,
            'address' => $data['address'] ?? null,
            'emergencyFullName' => $data['emergencyFullName'] ?? null,
            'emergencyEmail' => $data['emergencyEmail'] ?? null,
            'emergencyPhoneNumber' => $data['emergencyPhoneNumber'] ?? null,
            'emergencyRelation' => $data['emergencyRelation'] ?? null,
            'isCustomer' => true,
            'addMemberInviteOption' => $this->determineInviteOptionFromLoginMethod($orgId, $data),
        ];
    }

    /**
     * Prepare primary user data for family registration
     */
    protected function preparePrimaryUserData(array $data, int $orgId): array
    {
        return $this->prepareIndividualUserData($data, $orgId);
    }

    /**
     * Prepare spouse user data
     */
    protected function prepareSpouseUserData(array $data, int $orgId, int $primaryUserId): array
    {
        // Prepare spouse data for invite option determination
        $spouseData = [
            'email' => $data['spouse_email'] ?? null,
            'phoneNumber' => $data['spouse_phoneNumber'] ?? null,
            'loginMethod' => $data['spouse_loginMethod'] ?? self::LOGIN_METHOD_EMAIL,
        ];
        
        return [
            'org_id' => $orgId,
            'fullName' => $data['spouse_fullName'],
            'phoneCountry' => $data['spouse_phoneCountry'] ?? null,
            'phoneNumber' => $data['spouse_phoneNumber'] ?? null,
            'email' => $data['spouse_email'] ?? null,
            'nationality_country' => $data['spouse_nationality_country'] ?? null,
            'dob' => $data['spouse_dob'] ? Carbon::parse($data['spouse_dob'])->format('Y-m-d') : null,
            'address' => $data['address'] ?? null, // Use same address as primary
            'isCustomer' => true,
            'created_by' => $primaryUserId, // Link to primary user
            'addMemberInviteOption' => $this->determineInviteOptionFromLoginMethod($orgId, $spouseData),
        ];
    }

    /**
     * Prepare child user data
     */
    protected function prepareChildUserData(array $data, int $childIndex, int $orgId, int $primaryUserId): array
    {
        $prefix = "child_{$childIndex}_";
        
        // Combine medical information into a single notes field
        $medicalNotes = [];
        if (!empty($data["{$prefix}medical_conditions"])) {
            $medicalNotes[] = "Medical Conditions: " . $data["{$prefix}medical_conditions"];
        }
        if (!empty($data["{$prefix}allergies"])) {
            $medicalNotes[] = "Allergies: " . $data["{$prefix}allergies"];
        }
        if (!empty($data["{$prefix}medications"])) {
            $medicalNotes[] = "Medications: " . $data["{$prefix}medications"];
        }
        if (!empty($data["{$prefix}special_needs"])) {
            $medicalNotes[] = "Special Needs: " . $data["{$prefix}special_needs"];
        }
        if (!empty($data["{$prefix}past_injuries"])) {
            $medicalNotes[] = "Past Injuries: " . $data["{$prefix}past_injuries"];
        }
        
        return [
            'org_id' => $orgId,
            'fullName' => $data["{$prefix}name"],
            'gender' => !empty($data["{$prefix}gender"]) ? (int)$data["{$prefix}gender"] : null,
            'dob' => Carbon::parse($data["{$prefix}dob"])->format('Y-m-d'),
            'address' => $data['address'] ?? null, // Use same address as primary
            'isCustomer' => true,
            'created_by' => $primaryUserId, // Link to primary user
            // Store child-specific information in available fields
            'nationalID' => $data["{$prefix}school_level"] ?? null, // Repurpose for school grade
            // Medical notes could be stored in a custom field or notes system
        ];
    }

    /**
     * Create OrgUser record
     */
    protected function createOrgUser(array $userData): OrgUser
    {
        return OrgUser::create($userData);
    }

    /**
     * Dispatch post-creation jobs
     */
    protected function dispatchPostCreationJobs(OrgUser $orgUser): void
    {
        // The OrgUser model already handles this in its boot method
        // But we can add additional jobs here if needed
        Log::info('Post-creation jobs dispatched for user', ['org_user_id' => $orgUser->id]);
    }

    /**
     * Create a family group
     */
    protected function createFamilyGroup(int $orgId): OrgFamily
    {
        return OrgFamily::create([
            'org_id' => $orgId,
        ]);
    }

    /**
     * Link a family member to a family group
     */
    protected function linkFamilyMember(int $familyId, int $userId, string $level, int $orgId): OrgFamilyUser
    {
        return OrgFamilyUser::create([
            'org_id' => $orgId,
            'orgFamily_id' => $familyId,
            'orgUser_id' => $userId,
            'level' => $level, // 'parent' or 'child'
        ]);
    }

    /**
     * Determine the appropriate addMemberInviteOption based on user's selected login method
     * 
     * This method aligns with the Yii2 OrgUserCreateCompleteJob which checks:
     * - addMemberInviteOption == OrgSettings::ADD_MEMBER_INVITE_OPTION_EMAIL (1) for email invites
     * - addMemberInviteOption == OrgSettings::ADD_MEMBER_INVITE_OPTION_SMS (2) for SMS invites
     * 
     * @param int $orgId Organization ID
     * @param array $userData User data containing loginMethod, email and phoneNumber
     * @return int Invite option constant matching Yii2 OrgSettings constants
     */
    protected function determineInviteOptionFromLoginMethod(int $orgId, array $userData): int
    {
        try {
            // Get organization with settings features
            $org = Org::with('orgSettingsFeatures')->find($orgId);
            
            if (!$org || !$org->orgSettingsFeatures) {
                Log::warning('Organization or settings features not found for invite option determination', [
                    'org_id' => $orgId
                ]);
                return self::ADD_MEMBER_INVITE_OPTION_NONE;
            }

            $features = $org->orgSettingsFeatures;
            $loginMethod = $userData['loginMethod'] ?? self::LOGIN_METHOD_EMAIL;
            $hasEmail = !empty($userData['email']);
            $hasPhone = !empty($userData['phoneNumber']);

            // Check if SMS verification is enabled for the organization
            $smsEnabled = $features->isMarketingSMSEnabled || $features->smsVerificationEnabled;
            
            // Use user's selected login method preference
            if ($loginMethod === self::LOGIN_METHOD_SMS && $smsEnabled && $hasPhone) {
                Log::info('Setting SMS invite option based on user login method preference', [
                    'org_id' => $orgId,
                    'login_method' => $loginMethod,
                    'has_phone' => $hasPhone,
                    'sms_enabled' => $smsEnabled,
                    'invite_option' => self::ADD_MEMBER_INVITE_OPTION_SMS
                ]);
                return self::ADD_MEMBER_INVITE_OPTION_SMS;
            }
            
            // Default to email for all other cases
            if ($hasEmail) {
                Log::info('Setting email invite option based on user login method preference', [
                    'org_id' => $orgId,
                    'login_method' => $loginMethod,
                    'has_email' => $hasEmail,
                    'invite_option' => self::ADD_MEMBER_INVITE_OPTION_EMAIL
                ]);
                return self::ADD_MEMBER_INVITE_OPTION_EMAIL;
            }

            // No invite option available
            Log::warning('No invite option available for user registration', [
                'org_id' => $orgId,
                'login_method' => $loginMethod,
                'has_email' => $hasEmail,
                'has_phone' => $hasPhone,
                'sms_enabled' => $smsEnabled,
                'invite_option' => self::ADD_MEMBER_INVITE_OPTION_NONE
            ]);
            return self::ADD_MEMBER_INVITE_OPTION_NONE;

        } catch (\Exception $e) {
            Log::error('Error determining invite option for user registration', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
                'invite_option' => self::ADD_MEMBER_INVITE_OPTION_NONE
            ]);
            return self::ADD_MEMBER_INVITE_OPTION_NONE;
        }
    }
}

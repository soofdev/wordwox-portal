# Customer Registration Wizard System

## Overview

The Customer Registration Wizard is a comprehensive multi-step registration system that allows customers to register for gym memberships either individually or as families. The system provides two access methods: universal organization URLs and staff-generated secure links with advanced step navigation, real-time validation, and family relationship management.

## 🚀 Features

### **Registration Types**
- **Individual Registration**: Single member registration with personal details and optional emergency contact
- **Family Registration**: Primary member + optional spouse + up to 2 children with comprehensive details and family relationship tracking

### **Access Methods**
- **Universal Org URLs**: Permanent public URLs using organization UUID (`/register/org/{orgUuid}`)
- **Token-based Links**: Staff-generated expiring links for targeted outreach (`/register/{token}`)

### **Multi-step Wizard Flow**

#### **Individual Registration (3 Steps)**
1. **Step 0**: Registration type selection (Individual vs Family) - *Not counted in progress*
2. **Step 1**: Primary user information (Your Information)
3. **Step 2**: Emergency contact information (Optional - all fields)
4. **Step 6**: Review and submit

#### **Family Registration (5 Steps)**
1. **Step 0**: Registration type selection (Individual vs Family) - *Not counted in progress*
2. **Step 1**: Primary user information (Your Information)
3. **Step 2**: Spouse information (Optional)
4. **Step 3**: Child 1 information (Optional)
5. **Step 4**: Child 2 information (Optional)
6. **Step 5**: Review and submit

### **Advanced Features**
- **Smart Step Navigation**: Automatic step skipping for individual registration
- **Real-time Validation**: Field-level validation with immediate feedback
- **Login Method Preference**: Users can choose between email or SMS-based login, affecting field requirements
- **Dynamic Field Requirements**: Required fields change based on selected login method (email vs SMS)
- **Phone Number Normalization**: Automatic handling of leading zeros and format consistency
- **Family Relationship Management**: Proper linking of family members via `OrgFamily` and `OrgFamilyUser` models
- **Step-specific URLs**: Each step has unique URL parameters for direct navigation
- **Dynamic Progress Indicators**: Visual step indicators that adapt to registration type
- **Sticky Navigation**: Persistent form controls for better UX

### **Staff Management Interface**
- Generate secure registration links with configurable expiration
- Send links via SMS or Email with customizable messages
- Universal URL management and sharing
- Link tracking and management

## 🏗️ Technical Architecture

### **Core Components**

#### **Livewire Components**
- `CustomerRegistrationWizard` - Unified registration wizard handling both access methods
- `RegistrationLinkManager` - FOH staff link management interface

#### **Services**
- `CustomerRegistrationService` - Handles registration logic, validation, and family relationship creation
- `SmsService` - SMS delivery via BluNet provider
- `PhoneNumberService` - Phone number validation and formatting
- `Yii2QueueDispatcher` - Background job processing

#### **Models**
- `OrgUser` - Customer records with emergency contact fields
- `OrgFamily` - Family group management (new)
- `OrgFamilyUser` - Family member relationships with levels (parent/child) (new)
- `Org` - Organization with UUID support

#### **Validation Rules**
- `UniqueOrgUserFullName` - Ensures unique names per organization
- `UniqueOrgUserEmail` / `UniqueOrgUserEmailOptional` - Email uniqueness validation
- `UniqueOrgUserPhone` / `UniqueOrgUserPhoneOptional` - Phone uniqueness with normalization
- `ValidPhoneNumber` - International phone number format validation
- `ValidChildAge` - Age validation for children (0-18 years)
- `ValidSchoolGrade` - School grade validation

## 📋 Data Structure

### **Individual Registration Fields**

#### **Required Fields**

**Note**: Field requirements depend on user's selected login method preference:

**For Email Login (Default)**:
```
'fullName' => 'string|required|min:2|max:255'
'email' => 'email|required|max:255'
'loginMethod' => 'string|nullable|in:email,sms' (defaults to 'email')
'phoneCountry' => 'string|nullable|size:2' (ISO code, converted to dialing code)
'phoneNumber' => 'string|nullable|min:7|max:15' (normalized, leading zeros removed)
```

**For SMS Login**:
```
'fullName' => 'string|required|min:2|max:255'
'phoneCountry' => 'string|required|size:2' (ISO code, converted to dialing code)
'phoneNumber' => 'string|required|min:7|max:15' (normalized, leading zeros removed)
'loginMethod' => 'string|required|value:sms'
'email' => 'email|nullable|max:255'
```

#### **Optional Fields**
```
'nationality_country' => 'string|nullable|max:255' (country name as string)
'nationalID' => 'string|nullable|max:50'
'dob' => 'date|nullable|before:today'
'gender' => 'integer|nullable|in:1,2' (1=Male, 2=Female)
'employer_name' => 'string|nullable|max:255'
'address' => 'string|nullable|max:500'
```

#### **Emergency Contact Fields** (All Optional)
```
'emergencyFullName' => 'string|nullable|max:255'
'emergencyEmail' => 'email|nullable|max:255'
'emergencyPhoneNumber' => 'string|nullable|max:20'
'emergencyRelation' => 'string|nullable|max:100'
```

### **Family Registration Additional Fields**

#### **Spouse Fields**

**Note**: Spouse field requirements also depend on login method preference:

**For Email Login (Default)**:
```
'spouse_fullName' => 'string|required_if_present|min:2|max:255'
'spouse_email' => 'email|required_if_login_method_email|max:255'
'spouse_loginMethod' => 'string|nullable|in:email,sms' (defaults to 'email')
'spouse_phoneCountry' => 'string|nullable|size:2'
'spouse_phoneNumber' => 'string|nullable|min:7|max:15' (must differ from primary)
'spouse_nationality_country' => 'string|nullable|max:255'
'spouse_dob' => 'date|nullable|before:today'
'spouse_employer_name' => 'string|nullable|max:255'
```

**For SMS Login**:
```
'spouse_fullName' => 'string|required_if_present|min:2|max:255'
'spouse_phoneCountry' => 'string|required_if_login_method_sms|size:2'
'spouse_phoneNumber' => 'string|required_if_login_method_sms|min:7|max:15' (must differ from primary)
'spouse_loginMethod' => 'string|required_if_present|value:sms'
'spouse_email' => 'email|nullable|max:255'
'spouse_nationality_country' => 'string|nullable|max:255'
'spouse_dob' => 'date|nullable|before:today'
'spouse_employer_name' => 'string|nullable|max:255'
```

#### **Child Fields** (Optional, up to 2 children)
```
'child_{n}_name' => 'string|required_if_present|min:2|max:255'
'child_{n}_gender' => 'integer|required_if_present|in:1,2'
'child_{n}_dob' => 'date|required_if_present|before:today|age:0-18'
'child_{n}_school_name' => 'string|required_if_present|max:255'
'child_{n}_school_level' => 'string|required_if_present|valid_grade'
'child_{n}_activities' => 'string|nullable|max:500'
'child_{n}_medical_conditions' => 'string|nullable|max:500'
'child_{n}_allergies' => 'string|nullable|max:500'
'child_{n}_medications' => 'string|nullable|max:500'
'child_{n}_special_needs' => 'string|nullable|max:500'
'child_{n}_past_injuries' => 'string|nullable|max:500'
```

## 🌐 URL Structure & Navigation

### **Universal Organization URLs**
```
Format: /register/org/{orgUuid}?step={stepNumber}
Example: http://wodworx-foh.test/register/org/c6a244394b0e11e984999600000a0cbd?step=2

- Permanent URLs that never expire
- Uses organization UUID for identification
- Step-specific navigation via query parameters
- Automatic step validation and access control
```

### **Token-based URLs**
```
Format: /register/{token}?step={stepNumber}
Example: http://wodworx-foh.test/register/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...?step=1

- Temporary URLs with configurable expiration (24-168 hours)
- Generated by FOH staff for specific customers
- Contains encrypted organization and type information
- Step-specific navigation supported
```

### **Step Navigation Logic**

#### **Individual Registration Navigation**
- **Step 0** → **Step 1** → **Step 2** → **Step 6** (Review)
- Automatically skips steps 3, 4, 5 (spouse/children)
- Backward navigation: Step 6 → Step 2 → Step 1 → Step 0

#### **Family Registration Navigation**
- **Step 0** → **Step 1** → **Step 2** → **Step 3** → **Step 4** → **Step 5** (Review)
- All steps accessible in sequence
- Standard forward/backward navigation

## 🔧 Implementation Details

### **Phone Number Handling**
- **Input**: ISO country codes (e.g., 'JO', 'US') from dropdown
- **Conversion**: Automatic conversion to dialing codes (e.g., '962', '1') for database storage
- **Normalization**: Leading zeros removed, non-digit characters stripped
- **Validation**: International format validation via `libphonenumber`
- **Uniqueness**: Database queries use normalized phone numbers with `TRIM(LEADING "0" FROM phoneNumber)`

### **Real-time Validation**
- **Field-level**: Validation on `wire:blur` and `wire:change` events
- **Step-level**: Comprehensive validation before step navigation
- **Final**: Complete validation before submission via `validateAllSteps()`
- **Error Display**: Immediate feedback with specific error messages

### **Family Relationship Management**
- **OrgFamily**: Created for each family registration
- **OrgFamilyUser**: Links each family member to the family group
- **Levels**: 'parent' (primary user + spouse) and 'child' (children)
- **Database Transaction**: Atomic creation of all family members and relationships

### **Step Access Control**
The `validateStepAccess()` method enforces proper navigation:
- Token-based access skips type selection if predetermined
- Individual registration blocks access to spouse/children steps (2-5)
- URL parameters validated against registration type
- Invalid steps redirect to appropriate valid steps

## 🎨 UI/UX Features

### **Progress Indicators**
- **Individual**: 3-step visual progress (Your Information → Emergency Contact → Review)
- **Family**: 5-step visual progress (Your Information → Spouse → Child 1 → Child 2 → Review)
- **Visual Design**: Circular numbered steps with checkmarks for completed steps
- **Color Coding**: Blue for current step, green for completed, gray for pending

### **Responsive Design**
- Mobile-first approach with Tailwind CSS
- Touch-friendly interface for tablets
- Sticky navigation footer for easy access to controls
- Loading states with targeted spinners

### **User Experience**
- Dynamic page titles based on current step
- Clear step-by-step progress indication
- Smart step skipping for streamlined individual registration
- Organization branding throughout the process
- Real-time validation with helpful error messages

## 🔐 Security & Validation

### **Data Protection**
- All form data validated server-side via `CustomerRegistrationService`
- SQL injection prevention through Eloquent ORM
- XSS protection via Laravel's built-in escaping
- CSRF protection on all forms

### **Validation Strategy**
- **Livewire Layer**: Real-time field validation with `rules()` method
- **Service Layer**: Comprehensive data validation before database operations with login method-aware validation
- **Database Layer**: Foreign key constraints and data integrity
- **Phone Validation**: International format validation with country-specific rules
- **Login Method Validation**: Dynamic field requirements based on user's preferred login method (email vs SMS)

### **Access Control**
- Token-based authentication for secure links with expiration
- Organization isolation through multi-tenancy (`TenantScope`)
- No authentication required for customers (by design)
- Staff interface requires FOH authentication

### **Phone Number Security**
- Normalization prevents duplicate detection bypass
- Country code validation ensures proper international format
- Uniqueness checks account for various input formats
- Database storage uses consistent dialing code format

## 📊 Database Schema

### **Enhanced Tables**

#### **orgUser** (Enhanced)
```sql
-- New emergency contact fields added:
emergencyFullName VARCHAR(255) NULL
emergencyEmail VARCHAR(255) NULL  
emergencyPhoneNumber VARCHAR(20) NULL
emergencyRelation VARCHAR(100) NULL
```

#### **orgFamily** (New)
```sql
id INT PRIMARY KEY AUTO_INCREMENT
org_id INT NOT NULL
name VARCHAR(255) NULL
uuid VARCHAR(36) NOT NULL
created_at INT NOT NULL
updated_at INT NOT NULL
deleted_at INT NULL
```

#### **orgFamilyUser** (New)
```sql
id INT PRIMARY KEY AUTO_INCREMENT
org_id INT NOT NULL
orgFamily_id INT NOT NULL
orgUser_id INT NOT NULL
level ENUM('parent', 'child') NOT NULL
uuid VARCHAR(36) NOT NULL
created_at INT NOT NULL
updated_at INT NOT NULL
deleted_at INT NULL
```

### **Data Flow**
```
Registration Form → Livewire Validation → Service Validation → Database Transaction:
  1. Create OrgUser records (primary, spouse, children)
  2. Create OrgFamily record (family registration only)
  3. Create OrgFamilyUser relationships (family registration only)
  4. Dispatch Yii2 background jobs
  5. Commit transaction
```

## 🚀 Deployment & Configuration

### **Environment Requirements**
- SMS service configured (BluNet provider)
- Email service configured (SMTP/SES)
- Queue workers running for background jobs
- Organization UUIDs generated for existing organizations

### **Route Configuration**
```php
// Public registration routes
Route::get('/register/org/{identifier}', CustomerRegistrationWizard::class)->name('register.org');
Route::get('/register/{identifier}', CustomerRegistrationWizard::class)->name('register.wizard');

// Staff management routes (auth required)
Route::get('/registration-links', RegistrationLinkManager::class)->name('registration.links.index');
```

### **Key Configuration Files**
- `config/sms.php` - SMS provider settings
- `config/queue.php` - Background job configuration
- `config/mail.php` - Email delivery settings

## 📈 Usage & Analytics

### **Registration Completion Rates**
- Individual registration: Typically 3-step completion
- Family registration: Variable completion based on family size
- Step abandonment tracking available via URL parameters

### **Validation Error Patterns**
- Phone number format issues (most common)
- Duplicate name/email/phone conflicts
- Child age validation failures
- School grade input errors

### **Performance Metrics**
- Real-time validation response times
- SMS/Email delivery success rates
- Background job processing times
- Database transaction completion rates

## 🆘 Troubleshooting

### **Common Issues**

#### **Phone Number Validation Errors**
- **Issue**: "Phone number already exists" for valid new numbers
- **Cause**: Leading zero normalization or country code mismatch
- **Solution**: Check `convertIsoToDialingCode()` and `normalizePhoneNumber()` methods

#### **Step Navigation Problems**
- **Issue**: User stuck on wrong step for registration type
- **Cause**: URL parameter manipulation or invalid step access
- **Solution**: `validateStepAccess()` method handles automatic correction

#### **Family Registration Failures**
- **Issue**: Transaction rollback during family creation
- **Cause**: Validation failure or relationship creation error
- **Solution**: Check `createFamilyRegistration()` method and database constraints

#### **Spouse Phone Validation**
- **Issue**: Spouse phone same as primary not caught
- **Cause**: Normalization not applied in validation closure
- **Solution**: Both numbers normalized before comparison in `getSpouseRules()`

#### **Login Method Validation Errors**
- **Issue**: "The phone number field is required" error when user selects email login
- **Cause**: Validation rules not respecting user's login method preference
- **Solution**: `validateIndividualData()` and `validateSpouseData()` methods now check `loginMethod` field to determine required fields dynamically

### **Debug Methods**
```php
// Enable detailed logging
Log::info('Registration step validation', [
    'step' => $currentStep,
    'type' => $registrationType,
    'org_id' => $orgId,
    'validation_rules' => $rules
]);

// Check phone normalization
$normalized = $this->normalizePhoneNumber($phoneNumber);
$dialingCode = $this->convertIsoToDialingCode($countryCode);
```

## 📚 API Reference

### **CustomerRegistrationWizard Methods**

#### **Navigation**
- `nextStep()` - Advance to next step with validation
- `previousStep()` - Go back to previous step
- `selectRegistrationType($type)` - Set registration type and advance
- `skipOptionalSteps()` / `skipOptionalStepsBackward()` - Handle individual registration navigation

#### **Validation**
- `rules()` - Return all validation rules for Livewire
- `validateCurrentStep()` - Validate current step before navigation
- `validateAllSteps()` - Comprehensive validation before submission
- `validateField($field)` - Real-time field validation

#### **Utility**
- `normalizePhoneNumber($phone)` - Remove leading zeros and format
- `convertIsoToDialingCode($iso)` - Convert ISO to dialing code
- `getLogicalStepNumber()` - Map actual steps to display steps
- `getProgressPercentage()` - Calculate progress for UI

### **CustomerRegistrationService Methods**

#### **Registration**
- `createIndividualRegistration(array $data, int $orgId): OrgUser`
- `createFamilyRegistration(array $data, int $orgId): array`

#### **Validation**
- `validateIndividualData(array $data, int $orgId): void`
- `validateFamilyData(array $data, int $orgId): void`
- `validateSpouseData(array $data, int $orgId): void`
- `validateChildData(array $data, int $childIndex, int $orgId): void`

#### **Family Management**
- `createFamilyGroup(int $orgId): OrgFamily`
- `linkFamilyMember(int $familyId, int $userId, string $level, int $orgId): OrgFamilyUser`

### **Org Model Methods**
- `getPublicRegistrationUrl(): string` - Get universal registration URL
- `generateUuidIfMissing(): void` - Generate UUID if not exists
- `findByUuid(string $uuid): ?self` - Find organization by UUID

---

*Last Updated: 2025-01-07*
*Version: 2.0 - Complete Implementation Analysis*
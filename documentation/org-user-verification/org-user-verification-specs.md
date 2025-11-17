# OrgUser Verification

## Overview
This document describes how we verify a (customer) OrgUser after they are added either by the admin or through self-registration. 

## OrgUser vs User Model
The OrgUser represent the profile in the Org so it does not yet represent a login account. The user has to verify using either the "token" field (for email verification) or "token_sms" field (for SMS verification) that gets generated and sent to the user's email and/or mobile number depending on what has been provided upon creation. 

## Token Usage
**DUAL TOKEN SYSTEM**: The system uses **separate tokens** for email and SMS verification with different formats and database fields:

### Email Tokens
- **Field**: `orgUser.token`
- **Format**: `{32_char_random_string}_{timestamp}`
- **Example**: `rG9NbVptzCJC43HHDu3Us4rkLyHvFdJN_1757446050`
- **Expiration**: 24 hours from timestamp

### SMS Tokens  
- **Field**: `orgUser.token_sms`
- **Format**: `{5_char_random_string}_{timestamp}`
- **Example**: `RMUMI_1757359650`
- **Expiration**: 24 hours from timestamp

**Token Generation**: Both tokens are automatically generated when an OrgUser is created:
```php
// Email token generation
public function generateToken()
{
    $this->token = Str::random(32) . '_' . time();
}

// SMS token generation  
public function generateTokenSMS()
{
    $this->token_sms = Str::random(5) . '_' . time();
}
```

## Controller Implementation
**Single Controller**: `VerificationController` handles both email and SMS verification by **auto-detecting token format** and querying the appropriate database field:

### Token Format Detection
- **Email Token Detection**: `count($tokenParts) == 2 && strlen($tokenParts[0]) == 32` (32-char random string)
- **SMS Token Detection**: `count($tokenParts) == 2 && strlen($tokenParts[0]) == 5` (5-char random string)

### Database Lookup Strategy
```php
if ($isEmailToken) {
    // Email verification: Query by 'token' field
    $orgUser = OrgUser::where('token', $token)->first();
    $verificationType = 'email';
} elseif ($isSmsToken) {
    // SMS verification: Query by 'token_sms' field  
    $orgUser = OrgUser::where('token_sms', $token)->first();
    $verificationType = 'sms';
}
```

### Verification Type Detection
- **By Token Format**: Email tokens → 'email', SMS tokens → 'sms'
- **By Contact Info**: Falls back to OrgUser's available contact information if needed

## Verification Process
Once the OrgUser is created the link will be sent by email and/or sms. The user will click the link and be taken to the verification URL. The verification flow includes:

### Step 1: Landing Page
- Shows gym branding and loading state
- Validates token format and expiration
- Auto-redirects to processing step

### Step 2: Token Validation & Routing
The controller validates that:

1. **Token Format**: Must match either format:
   - **Email**: `{32_chars}_{timestamp}` (2 parts, 32-char random string)
   - **SMS**: `{5_chars}_{timestamp}` (2 parts, 5-char random string)
2. **Token Expiration**: Must be within 24 hours of creation timestamp
   - **Both Email and SMS**: Extract timestamp from `tokenParts[1]`
3. **OrgUser Exists**: Token must match existing record in appropriate field:
   - **Email**: `orgUser.token` field
   - **SMS**: `orgUser.token_sms` field
4. **Not Already Verified**: OrgUser must not have `user_id` set (verified OrgUser is one where `user_id is not null`)

### Step 3: User Detection & Routing
Based on the OrgUser's contact information, check for existing User accounts using **ONLY the verified contact method**:
   - **Email verification**: Check if User exists with same email address (ONLY email, never phone)
   - **SMS verification**: Check if User exists with same phone number (ONLY phone, never email)

**🔐 CRITICAL SECURITY REQUIREMENT**: We can ONLY trust the contact method that was actually verified by the user clicking the verification link. Cross-method checking would create security vulnerabilities where attackers could gain access to accounts by providing someone else's unverified contact information.

### Step 4A: New User Path (No Existing Account)
**Route**: `/verify/{token}` → `verification.new-user` view

**Context-Aware Form Display:**
- **Email Verification**: Shows only email field (phone field hidden)
- **SMS Verification**: Shows only phone field (email field hidden)
- Password creation fields required for both

**Context-Aware User Creation:**
- **Email Verification**: Creates User with:
  - `email` = verified email address
  - `verifiedEmail = true` (boolean field, properly cast)
  - `email_verified_at` = Carbon timestamp (MySQL datetime format)
  - `phoneNumber = null` (not set)
  - `verifiedPhoneNumber = false` (boolean field, properly cast)
- **SMS Verification**: Creates User with:
  - `phoneNumber` = verified phone number
  - `phoneCountry` = verified country code
  - `verifiedPhoneNumber = true` (boolean field, properly cast)
  - `email = null` (not set)
  - `verifiedEmail = false` (boolean field, properly cast)
- **Common Fields** (both verification types):
  - `password_hash` (bcrypted password)
  - `auth_key` (32 char random string)
  - `uuid` (generated without dashes)
  - `status = 10` (active/verified)
  - `orgUser_id = orgUser.id` (sets tenant context)

**Completion:**
- Link OrgUser: `orgUser.user_id = user.id`, clear both tokens (`token = null`, `token_sms = null`)
- Redirect to success page: `/verify/{orgUser.uuid}/success`

### Step 4B: Account Linking Path (Existing User Found)
**Route**: `/verify/{token}` → `verification.link-account` view
- Show existing user details and current gym memberships
- **MANDATORY ACCOUNT LINKING**: When an existing User is found with the same verified contact method, the system will ONLY offer account linking
- **NO "Create New Account" Option**: This scenario does not provide an option to create a separate account (may be added as future enhancement)
- Account linking process: Update `orgUser.user_id = existingUser.id`, `user.orgUser_id = orgUser.id`
- Clear both verification tokens (`token = null`, `token_sms = null`)
- Redirect to success page: `/verify/{orgUser.uuid}/success`

**🚨 IMPORTANT**: The system assumes that if a User exists with the same verified contact method, it represents the same person and should be linked automatically. Creating duplicate accounts with the same verified contact method is not supported in the current implementation.

### Step 5: Success Page
**Route**: `/verify/{uuid}/success`
- **Security**: Uses OrgUser UUID in URL path (not token)
- Validates that OrgUser exists and is verified (`user_id is not null`)
- Shows gym branding, user details, and existing memberships
- Provides next steps (mobile app, profile completion, etc.) 

## Routes & URLs

### Implemented Routes
```php
// Main verification entry point
Route::get('/verify/{token}', [VerificationController::class, 'verify'])
    ->name('orguser.verification.verify');

// Processing redirect (adds ?process=1 parameter)  
Route::get('/verify/{token}/process', function($token) {
    return redirect()->route('orguser.verification.verify', ['token' => $token, 'process' => 1]);
})->name('orguser.verification.process');

// Form submissions
Route::post('/verify/create-account', [VerificationController::class, 'createAccount'])
    ->name('orguser.verification.create-account');
    
Route::post('/verify/link-account', [VerificationController::class, 'linkAccount'])
    ->name('orguser.verification.link-account');

// Success page (secure UUID-based)
Route::get('/verify/{uuid}/success', [VerificationController::class, 'success'])
    ->name('orguser.verification.success');
```

**Note**: Routes are prefixed with `orguser.verification.*` to avoid conflicts with Laravel's built-in email verification routes.

## User Model Configuration

### Field Casting
The User model includes proper casting for verification-related fields to ensure data type consistency:

```php
protected function casts(): array
{
    return [
        'password_hash' => 'hashed',
        'verifiedEmail' => 'boolean',           // Ensures true/false values
        'verifiedPhoneNumber' => 'boolean',     // Ensures true/false values  
        'email_verified_at' => 'timestamp',     // Carbon timestamp handling
    ];
}
```

**Benefits**:
- **Boolean Fields**: `verifiedEmail` and `verifiedPhoneNumber` are automatically cast to proper boolean values (true/false) when retrieved from database
- **Timestamp Handling**: `email_verified_at` uses Laravel's timestamp casting for proper Carbon/datetime conversion
- **Database Compatibility**: Resolves MySQL timestamp column format issues by using Carbon timestamps instead of raw Unix timestamps

### Fillable Fields
Verification-related fields are included in the User model's `$fillable` array:
```php
protected $fillable = [
    // ... other fields ...
    'verifiedEmail',
    'verifiedPhoneNumber', 
    'email_verified_at',
    'orgUser_id',  // For tenant context switching
    // ... other fields ...
];
```

## Services & Architecture

### VerificationService
**Location**: `app/Services/VerificationService.php`

**Key Methods**:
- `validateToken(string $token): array` - Validates token format, expiration, and finds OrgUser
- `checkExistingUser(OrgUser $orgUser, string $verificationType): ?User` - **SECURITY CRITICAL**: Finds existing users ONLY by the verified contact method (email OR phone, never both)
- `createUserAccount(array $userData, OrgUser $orgUser, string $verificationType): User` - Creates context-aware User and links to OrgUser
- `linkExistingUser(User $existingUser, OrgUser $orgUser): bool` - Links existing User to OrgUser
- `isAlreadyVerified(OrgUser $orgUser): bool` - Checks if OrgUser has user_id set
- `getUserMemberships(User $user)` - Gets user's existing gym memberships

**Context-Aware User Creation:**
The `createUserAccount()` method now accepts a `$verificationType` parameter and creates User accounts with only the verified contact information:
- **Email verification**: Sets `email`, `verifiedEmail = true` (boolean), `email_verified_at` (Carbon timestamp)
- **SMS verification**: Sets `phoneNumber`, `phoneCountry`, `verifiedPhoneNumber = true` (boolean)

### VerificationController  
**Location**: `app/Http/Controllers/VerificationController.php`

**Key Methods**:
- `verify(Request $request, string $token)` - Main entry point, handles routing logic
- `showLandingPage(string $token)` - Shows gym-branded loading page
- `createAccount(Request $request)` - Handles new user account creation
- `linkAccount(Request $request)` - Handles account linking for existing users
- `success(Request $request, string $uuid)` - Shows success page (UUID-based security)

## Tenant Context Management

**Critical Feature**: When verification completes (either new user creation or account linking), the system sets `user.orgUser_id = orgUser.id`. This ensures that when the user logs in next time, they automatically switch to the correct tenant context (gym) they just verified for.

**Implementation**:
- **New User**: `orgUser_id` set during User creation
- **Existing User**: `orgUser_id` updated during account linking
- **Benefit**: Seamless multi-gym experience with automatic tenant switching

## Views & UI Implementation

### Implemented Views
- `resources/views/verification/landing.blade.php` - Landing page with gym branding
- `resources/views/verification/new-user.blade.php` - Password creation form for new users
- `resources/views/verification/link-account.blade.php` - Account linking page for existing users
- `resources/views/verification/already-verified.blade.php` - Shown if already verified
- `resources/views/verification/success.blade.php` - Success page with gym branding
- `resources/views/verification/error.blade.php` - Error page for invalid/expired tokens

### UI Features
- **Gym Branding**: All pages show organization logo and name
- **Mobile-First**: Responsive design optimized for mobile devices
- **Progressive Disclosure**: Information revealed step-by-step
- **Security Indicators**: Trust badges and secure verification messaging
- **Multi-Gym Context**: Shows existing memberships when linking accounts
- **Context-Aware Forms**: Forms display only relevant fields based on verification type
  - **Email verification**: Shows only email field
  - **SMS verification**: Shows only phone field

## Testing & Development

### Artisan Command
**Command**: `php artisan verification:setup-test {orgUserId} [options]`

**Options**:
- `--type=both` - Verification type (email, sms, both)
- `--hours=24` - Token expiration hours  
- `--clear-user` - Delete existing User account to test new user path

**Features**:
- Generates **both** email and SMS verification tokens
- Resets OrgUser status (`user_id = null`, `token = null`, `token_sms = null`, `status = 0`)
- Provides **separate** verification URLs for email and SMS testing
- Supports both new user and account linking test scenarios

**Token Output**:
```bash
Email Token: rG9NbVptzCJC43HHDu3Us4rkLyHvFdJN_1757446050
SMS Token: RMUMI_1757359650

📧 Email Verification:
   http://wodworx-foh.test/verify/rG9NbVptzCJC43HHDu3Us4rkLyHvFdJN_1757446050

📱 SMS Verification:
   http://wodworx-foh.test/verify/RMUMI_1757359650
```

## Duplicate User Detection and Account Linking Flows

### Current Implementation (Secure)
The current implementation uses a **security-first approach**:

**🔐 Verification-Method-Specific Detection**: 
- **Email verification**: Checks for existing User with same email address ONLY
- **SMS verification**: Checks for existing User with same phone number ONLY
- **NEVER cross-checks**: Email verification never checks phone, SMS verification never checks email
- If found: Shows account linking page
- If not found: Shows new user creation page

**Security Rationale**: This prevents account takeover attacks where an attacker could gain access to someone else's account by providing their unverified contact information.

### Future Enhancement Areas
The following features are **not yet implemented** and may be added in future versions:

#### **Create New Account Option for Existing Users**
- **Current**: When existing User found with same verified contact method → mandatory account linking only
- **Future**: Could add option for user to choose between "Link Account" or "Create New Account"
- **Use Case**: User wants separate accounts for different purposes (personal vs business, etc.)
- **Implementation**: Would require additional UI flow and duplicate account management

#### **Complex SMS Duplicate Handling**
- Reactivating deleted OrgUsers
- Handling active duplicate OrgUsers  
- Complex SMS-specific duplicate detection scenarios

**Current Status**: Basic duplicate detection is implemented with mandatory linking. Advanced scenarios are placeholders for future development.

## Multiple Verification Paths

### Path 1: New User (No existing User account)  
1. User clicks verification link
2. Landing page → Token validation → New user form
3. User sets password and accepts terms
4. System creates User account with tenant context
5. Links OrgUser to new User account
6. Redirects to success page

### Path 2: Existing User (Mandatory Account Linking)
1. User clicks verification link  
2. Landing page → Token validation → Account linking page
3. Shows existing user details and current gym memberships
4. **AUTOMATIC LINKING**: System presents account linking as the only option (no choice to create new account)
5. User confirms account linking
6. System links OrgUser to existing User account
7. Updates tenant context to new gym
8. Redirects to success page

**Note**: Creating a new account when an existing User has the same verified contact method is not currently supported.

### Path 3: Already Verified
1. User clicks verification link
2. System detects OrgUser already has user_id
3. Shows "already verified" page with account details

## Security & Error Handling

### Verification Security Model

**🔐 FUNDAMENTAL SECURITY PRINCIPLE**: Only trust verified contact methods.

#### **Why Verification-Specific User Detection is Required**

The system uses **verification-method-specific user detection** as a critical security measure:

**Email Verification:**
- User receives link at email address → Email ownership is verified
- System checks for existing Users with **same verified email only**
- Never checks phone numbers during email verification

**SMS Verification:**
- User receives link at phone number → Phone ownership is verified  
- System checks for existing Users with **same verified phone only**
- Never checks email addresses during SMS verification

#### **Security Vulnerability Prevention**

**Attack Scenario Prevented:**
```
1. Attacker knows victim's email: victim@email.com
2. Attacker registers at gym with:
   - Name: "Victim Name"
   - Email: victim@email.com (victim's real email)
   - Phone: +1-555-FAKE (attacker's phone)
3. Attacker requests SMS verification
4. If system checked ALL contact methods, it would find victim's existing email-based account
5. Attacker would gain access to victim's gym memberships!
```

**Our Security Model Prevents This:**
- SMS verification ONLY checks phone numbers
- Attacker's fake phone number won't match victim's verified email-based account
- System creates separate account, protecting victim's data

#### **Multiple User Accounts are Acceptable**

**By Design:** The same person can legitimately have multiple User accounts:
- **Account #1:** email-based (verified email, no phone)
- **Account #2:** phone-based (verified phone, no email)

This is **secure and intentional** because:
- Each account represents a verified contact method
- User controls both contact methods
- User can choose which identity to use at different gyms
- No security risk since both contact methods are genuinely verified

### Token Security
- **Format Validation**: Strict validation for both email and SMS token formats using random string length detection
- **Auto-Detection**: System automatically detects token type by random string length (32 chars = email, 5 chars = SMS)
- **Expiration Enforcement**: 24-hour hard expiration for both token types
- **Secure Database Lookup**: Email tokens query `orgUser.token`, SMS tokens query `orgUser.token_sms`
- **Complete Token Clearing**: Both `token` and `token_sms` fields cleared after successful verification

### URL Security
- **UUID-based Success URLs**: Success page uses OrgUser UUID in path (`/verify/{uuid}/success`) instead of tokens
- **Verification Status Check**: Success page validates that OrgUser is actually verified before displaying
- **No Session Dependencies**: Success page doesn't rely on session data for security

### Error Handling
- **Invalid Token Format**: Clear error messages for malformed tokens
- **Expired Tokens**: Specific messaging for expired verification links  
- **Missing OrgUser**: Handles cases where token doesn't match any OrgUser
- **Already Verified**: Graceful handling when user clicks old verification links
- **Database Errors**: Transaction rollbacks ensure data consistency during account creation/linking

### Route Naming
- **Conflict Avoidance**: Routes use `orguser.verification.*` prefix to avoid conflicts with Laravel's built-in email verification
- **RESTful Design**: Clean URL structure with proper HTTP methods

## Recent Updates & Fixes

### Version History

**Latest Updates (January 2025)**:
1. **Dual Token System**: Implemented separate `token` (email) and `token_sms` (SMS) fields with format-specific validation
2. **Smart Token Detection**: Auto-detects token format and queries appropriate database field
3. **Context-Aware Verification**: Forms and user creation based on verification type
   - Email verification: Shows only email field, creates User with email only
   - SMS verification: Shows only phone field, creates User with phone only
4. **Verification Status Tracking**: Proper `verifiedEmail`/`verifiedPhoneNumber` flags set based on verification method
5. **Tenant Context Setting**: Added automatic `user.orgUser_id` setting during verification for seamless tenant switching  
6. **UUID-based Success URLs**: Changed from token-based to UUID-based success page URLs for better security
7. **User Model Field Fixes**: Fixed `password_hash`, `auth_key`, `uuid`, and `status` field handling during User creation
8. **Route Naming Conflicts**: Resolved conflicts with Laravel's built-in email verification routes
9. **Security Hardening**: Removed session dependencies from success page validation
10. **Enhanced Testing**: Console command generates both email and SMS tokens for comprehensive testing
11. **Boolean Field Casting**: Added proper boolean casting for `verifiedEmail` and `verifiedPhoneNumber` fields in User model
12. **Timestamp Handling Fix**: Fixed `email_verified_at` field by using Carbon timestamps instead of Unix timestamps for MySQL compatibility

**Key Improvements**:
- ✅ **Multi-tenant Support**: Users automatically switch to correct gym context after verification
- ✅ **Security**: UUID-based success URLs prevent unauthorized access  
- ✅ **Error Handling**: Comprehensive error handling with user-friendly messages
- ✅ **Testing**: Artisan command for easy verification testing scenarios
- ✅ **UI/UX**: Mobile-first design with gym branding throughout the flow

**Testing Status**: 
- ✅ **New User Creation**: Verified working with proper tenant context setting
- ✅ **Account Linking**: Verified working with tenant context updates
- ✅ **Email Token Validation**: Long format tokens work correctly
- ✅ **SMS Token Validation**: Short format tokens work correctly  
- ✅ **Dual Token Support**: Both email and SMS verification flows functional
- ✅ **Context-Aware Forms**: Email verification shows only email field, SMS shows only phone field
- ✅ **Context-Aware User Creation**: Email verification creates User with email only, SMS with phone only
- ✅ **Verification Status Tracking**: `verifiedEmail`/`verifiedPhoneNumber` flags properly set
- ✅ **Boolean Field Casting**: `verifiedEmail` and `verifiedPhoneNumber` properly cast as boolean values (true/false)
- ✅ **Timestamp Handling**: `email_verified_at` field uses Carbon timestamps for MySQL compatibility
- ✅ **Database Compatibility**: Resolved "Invalid datetime format" errors with proper timestamp casting
- ✅ **Security**: UUID-based success page validation implemented
- ✅ **Error Scenarios**: Invalid/expired tokens handled gracefully
- ✅ **Testing Tools**: Console command generates both token types for testing


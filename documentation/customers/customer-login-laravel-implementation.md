# Customer Login & OTP Verification - Laravel Portal Implementation

## Overview

This document explains how customer login and OTP verification work in the Laravel portal. Unlike the Yii2 customer portal which uses API endpoints, the Laravel implementation performs **direct database operations**.

## Login Flow

### Step 1: User Submits Login Form

**Component**: `app/Livewire/Customer/CustomerLogin.php`  
**Route**: `/login`  
**View**: `resources/views/livewire/customer/customer-login.blade.php`

**User Actions**:
1. Selects login method: **Email** or **Phone**
2. Enters email address OR phone number (with country code)
3. Clicks "Send OTP" button

**Form Fields**:
- `loginMethod`: Radio button selection ('email' or 'phone')
- `identifier`: Email address (if email login)
- `phoneCountry`: Country code dropdown (if phone login)
- `phoneNumber`: Phone number (if phone login)

### Step 2: Send OTP (`sendOtp()` Method)

**File**: `app/Livewire/Customer/CustomerLogin.php` (lines 66-401)

#### 2.1 Validation

```php
if ($this->loginMethod === 'email') {
    $this->validate([
        'identifier' => 'required|email',
    ]);
} else {
    $this->validate([
        'phoneCountry' => 'required|string|min:1|max:4',
        'phoneNumber' => 'required|string',
    ]);
}
```

#### 2.2 Find OrgUser

**Email Login**:
```php
$orgUser = OrgUser::where('email', $this->identifier)
    ->where('isCustomer', true)
    ->where(function($query) {
        $query->where('isDeleted', false)
              ->orWhereNull('isDeleted');
    })
    ->first();
```

**Phone Login**:
```php
// Convert ISO country code to dialing code
$dialingCode = $this->convertIsoToDialingCode($this->phoneCountry);

// Clean phone number
$cleanPhone = preg_replace('/[\s\+\-\(\)]/', '', $this->phoneNumber);
$cleanPhone = ltrim($cleanPhone, '0'); // Remove leading zeros

// Primary lookup: exact match
$orgUser = OrgUser::where('phoneCountry', $dialingCode)
    ->where('phoneNumber', $cleanPhone)
    ->where('isCustomer', true)
    ->where('isDeleted', false)
    ->whereNull('deleted_at')
    ->first();

// If not found, try alternative formats (concatenated, with +, etc.)
```

**Phone Number Normalization**:
- Removes spaces, dashes, parentheses, plus signs
- Removes leading zeros
- Converts ISO country code (e.g., "US") to dialing code (e.g., "1")

#### 2.3 Find or Create User Account

```php
// First, try to find by orgUser_id (including soft-deleted)
$user = User::withTrashed()->where('orgUser_id', $orgUser->id)->first();

if ($user && $user->trashed()) {
    // Restore soft-deleted user
    $user->restore();
}

if (!$user) {
    // Check if User exists with same phone number (handles unique constraints)
    if ($orgUser->phoneNumber && $orgUser->phoneCountry) {
        $user = User::withTrashed()
            ->where('phoneNumber', $orgUser->phoneNumber)
            ->where('phoneCountry', $orgUser->phoneCountry)
            ->first();
        
        if ($user) {
            // Restore and update orgUser_id if different
            if ($user->trashed()) {
                $user->restore();
            }
            if ($user->orgUser_id != $orgUser->id) {
                $user->orgUser_id = $orgUser->id;
                $user->fullName = $orgUser->fullName;
                $user->email = $orgUser->email;
                $user->save();
            }
        }
    }
    
    // If still no user found, create new one
    if (!$user) {
        $user = new User();
        $user->orgUser_id = $orgUser->id;
        $user->fullName = $orgUser->fullName;
        $user->email = $orgUser->email;
        $user->phoneNumber = $orgUser->phoneNumber;
        $user->phoneCountry = $orgUser->phoneCountry;
        $user->uuid = \Illuminate\Support\Str::uuid();
        $user->auth_key = \Illuminate\Support\Str::random(32);
        $user->password_hash = Hash::make(\Illuminate\Support\Str::random(32)); // Required but not used
        $user->status = 10; // Active/Verified
        $user->save();
        
        // Dispatch Yii2 queue job
        $dispatcher->dispatch('common\jobs\user\UserCreateCompleteJob', ['id' => $user->id]);
    }
}
```

**Key Points**:
- Handles soft-deleted users (restores them)
- Handles race conditions (unique constraint violations)
- Creates User account if it doesn't exist
- Updates existing User if orgUser_id differs

#### 2.4 Generate OTP

**File**: `app/Models/User.php` (lines 83-91)

```php
public function generateOTP() {
    $this->timestamps = false;
    
    $this->otp = rand(1111, 9999); // 4-digit code
    $this->otp_token = md5($this->otp);
    $this->otp_expire = now()->addMinutes(15)->timestamp; // 15 minutes expiration
    $this->save();
}
```

**OTP Details**:
- **Format**: 4-digit numeric code (1111-9999)
- **Expiration**: 15 minutes
- **Storage**: `user.otp`, `user.otp_token`, `user.otp_expire`

#### 2.5 Send OTP

**Email OTP**:
```php
Mail::raw("Your OTP code is: {$user->otp}\n\nThis code will expire in 15 minutes.", function ($message) use ($orgUser) {
    $message->to($orgUser->email)
            ->subject('Your Login OTP Code');
});
```

**SMS OTP**:
```php
$smsService = new SmsService();
$fullPhone = $orgUser->phoneCountry . $orgUser->phoneNumber;
$message = "Your OTP code is: {$user->otp}. This code will expire in 15 minutes.";

$smsService->send(
    $fullPhone,
    $message,
    $orgUser->org_id,
    $orgUser->id
);
```

#### 2.6 Store Session Data

```php
session([
    'customer_otp_user_id' => $user->id,
    'customer_otp_method' => $this->loginMethod, // 'email' or 'phone'
    'customer_otp_sent_at' => now()->timestamp
]);

$this->otpSent = true;
$this->resendCooldown = 60; // 60 second cooldown for resend
```

### Step 3: User Enters OTP Code

**UI Changes**:
- Login form is hidden
- OTP input form is shown
- Large 4-digit input field with auto-focus
- Resend button with countdown timer (60 seconds)

**OTP Input**:
- Max length: 4 digits
- Pattern: `[0-9]{4}` (numbers only)
- Auto-formats to remove non-numeric characters
- Large font size for better visibility

### Step 4: Verify OTP (`verifyOtp()` Method)

**File**: `app/Livewire/Customer/CustomerLogin.php` (lines 406-475)

#### 4.1 Validation

```php
$this->validate([
    'otp' => 'required|string|size:4',
]);
```

#### 4.2 Retrieve User from Session

```php
$userId = session('customer_otp_user_id');
if (!$userId) {
    $this->message = 'Session expired. Please request a new OTP.';
    return;
}

$user = User::find($userId);
if (!$user) {
    $this->message = 'Invalid session. Please request a new OTP.';
    return;
}
```

#### 4.3 Check OTP Expiration

```php
if ($user->otp_expire && $user->otp_expire < now()->timestamp) {
    $this->message = 'OTP has expired. Please request a new one.';
    $user->clearOTP();
    session()->forget('customer_otp_user_id');
    $this->otpSent = false;
    return;
}
```

**Expiration Check**:
- Compares `otp_expire` (Unix timestamp) with current timestamp
- If expired, clears OTP and resets form

#### 4.4 Verify OTP Code

```php
if ($user->otp != $this->otp) {
    $this->message = 'Invalid OTP code. Please try again.';
    $this->otp = ''; // Clear the input
    return;
}
```

**Verification**:
- Direct comparison: `$user->otp == $this->otp`
- Case-sensitive numeric comparison
- Allows retry if invalid (OTP remains valid until expiration)

#### 4.5 Login User

```php
// OTP is valid - login the user
\Illuminate\Support\Facades\Auth::login($user);

// Clear OTP and session data
$user->clearOTP();
session()->forget('customer_otp_user_id');
session()->forget('customer_otp_method');
session()->forget('customer_otp_sent_at');
```

**Clear OTP Method** (`app/Models/User.php` lines 93-101):
```php
public function clearOTP() {
    $this->timestamps = false;
    
    $this->otp = null;
    $this->otp_token = null;
    $this->otp_expire = null;
    $this->save();
}
```

#### 4.6 Dispatch Yii2 Queue Job

```php
$dispatcher = new Yii2QueueDispatcher();
$dispatcher->dispatch('common\jobs\user\UserLoginCompleteJob', [
    'id' => $user->id,
    'orgUser_id' => $user->orgUser_id
]);
```

#### 4.7 Redirect

```php
return $this->redirect(route('home'), navigate: false);
```

### Step 5: Resend OTP (`resendOtp()` Method)

**File**: `app/Livewire/Customer/CustomerLogin.php` (lines 481-502)

```php
public function resendOtp()
{
    // Check if cooldown period has passed
    $otpSentAt = session('customer_otp_sent_at');
    if ($otpSentAt) {
        $elapsed = now()->timestamp - $otpSentAt;
        $remaining = 60 - $elapsed;
        
        if ($remaining > 0) {
            $this->resendCooldown = $remaining;
            $this->message = "Please wait {$remaining} seconds before requesting a new OTP.";
            return;
        }
    }
    
    // Clear previous OTP and message
    $this->otp = '';
    $this->message = '';
    
    // Resend OTP by calling sendOtp again
    $this->sendOtp();
}
```

**Resend Features**:
- **Cooldown**: 60 seconds between resend requests
- **Countdown Timer**: Visual countdown in UI
- **Auto-enable**: Button enables automatically after cooldown

## Complete Login Flow Diagram

```
User visits /login
    ↓
User selects Email/Phone and enters identifier
    ↓
User clicks "Send OTP"
    ↓
sendOtp() method:
    ├─ Validates input
    ├─ Finds OrgUser (direct DB query)
    ├─ Finds or creates User account (direct DB operation)
    ├─ Generates OTP (rand(1111, 9999))
    ├─ Sends OTP via Email or SMS
    └─ Stores user_id in session
    ↓
OTP input form displayed
    ↓
User enters 4-digit OTP code
    ↓
User clicks "Verify & Login"
    ↓
verifyOtp() method:
    ├─ Validates OTP format
    ├─ Retrieves User from session
    ├─ Checks OTP expiration (15 minutes)
    ├─ Verifies OTP code matches
    ├─ Logs user in (Auth::login)
    ├─ Clears OTP from database
    ├─ Clears session data
    ├─ Dispatches Yii2 queue job
    └─ Redirects to home page
    ↓
User is logged in ✅
```

## Key Differences from Yii2

| Feature | Yii2 | Laravel |
|---------|------|---------|
| **Login Method** | ✅ API Call (`/org-user/login`) | ❌ Direct Database Query |
| **OTP Verification** | ✅ API Call (`/org-user/verify-otp`) | ❌ Direct Database Query |
| **OrgUser Lookup** | ✅ Handled by API | ❌ `OrgUser::where()` query |
| **User Creation** | ✅ Handled by API | ❌ `User::create()` |
| **OTP Generation** | ✅ Handled by API | ❌ `$user->generateOTP()` |
| **OTP Verification** | ✅ API validates | ❌ Direct comparison in code |
| **Session Storage** | ✅ `customer_otp_user_id` | ✅ `customer_otp_user_id` |
| **OTP Format** | ✅ 4-digit (1111-9999) | ✅ 4-digit (1111-9999) |
| **OTP Expiration** | ✅ 15 minutes | ✅ 15 minutes |

## Database Operations

### Tables Used

1. **`orgUser` table**:
   - Find OrgUser by email or phone
   - Fields: `email`, `phoneCountry`, `phoneNumber`, `isCustomer`, `isDeleted`

2. **`user` table**:
   - Find or create User account
   - Store OTP: `otp`, `otp_token`, `otp_expire`
   - Link to OrgUser: `orgUser_id`

### Direct Queries (No API)

**OrgUser Lookup**:
```php
OrgUser::where('email', $identifier)
    ->where('isCustomer', true)
    ->where('isDeleted', false)
    ->first();
```

**User Lookup/Creation**:
```php
User::withTrashed()->where('orgUser_id', $orgUser->id)->first();
// or
User::create([...]);
```

**OTP Storage**:
```php
$user->otp = rand(1111, 9999);
$user->otp_token = md5($user->otp);
$user->otp_expire = now()->addMinutes(15)->timestamp;
$user->save();
```

## Session Management

### Session Keys Used

- `customer_otp_user_id`: User ID for OTP verification
- `customer_otp_method`: Login method ('email' or 'phone')
- `customer_otp_sent_at`: Timestamp when OTP was sent (for cooldown)

### Session Lifecycle

1. **OTP Sent**: Session keys are set
2. **OTP Verified**: Session keys are cleared
3. **OTP Expired**: Session keys are cleared
4. **User Logged In**: Session keys are cleared

## Error Handling

### Common Errors

1. **No Account Found**:
   - Message: "No account found with this email/phone number."
   - Action: User can try again with different identifier

2. **OTP Expired**:
   - Message: "OTP has expired. Please request a new one."
   - Action: OTP is cleared, user must request new OTP

3. **Invalid OTP**:
   - Message: "Invalid OTP code. Please try again."
   - Action: User can retry (OTP remains valid until expiration)

4. **Session Expired**:
   - Message: "Session expired. Please request a new OTP."
   - Action: User must start login process again

5. **Resend Cooldown**:
   - Message: "Please wait X seconds before requesting a new OTP."
   - Action: Button disabled until cooldown expires

## Security Features

1. **OTP Expiration**: 15-minute validity
2. **Resend Cooldown**: 60-second cooldown between resend requests
3. **Session Validation**: User ID stored in session, validated on verification
4. **OTP Clearing**: OTP cleared after successful login or expiration
5. **Soft Delete Handling**: Restores soft-deleted users automatically
6. **Race Condition Handling**: Handles unique constraint violations

## UI Features

### Login Form
- Email/Phone toggle buttons
- Conditional field display based on login method
- Country code dropdown for phone login
- "Send OTP" button

### OTP Verification Form
- Large 4-digit input field
- Auto-focus on OTP input
- Auto-format (numbers only)
- Resend button with countdown timer
- "Back" button to return to login form

### JavaScript Enhancements
- Auto-focus OTP input when OTP is sent
- Countdown timer for resend button
- Real-time cooldown updates
- Input formatting (numbers only)

## Comparison with Yii2

### Similarities

1. **OTP Format**: Both use 4-digit codes (1111-9999)
2. **OTP Expiration**: Both use 15-minute expiration
3. **Login Methods**: Both support email and phone
4. **User Creation**: Both create User account on first login
5. **Session Storage**: Both use `customer_otp_user_id` in session

### Differences

1. **API Usage**: Yii2 uses API, Laravel uses direct DB queries
2. **Phone Normalization**: Laravel has more comprehensive phone lookup
3. **Error Handling**: Laravel has more detailed error messages
4. **Resend Cooldown**: Laravel has visual countdown timer
5. **UI/UX**: Laravel has better UX with auto-focus and formatting

## Code References

### Main Files

1. **Login Component**: `app/Livewire/Customer/CustomerLogin.php`
   - `sendOtp()`: Lines 66-401
   - `verifyOtp()`: Lines 406-475
   - `resendOtp()`: Lines 481-502

2. **OTP Verification Component**: `app/Livewire/Customer/VerifyOtp.php`
   - `verifyOtp()`: Lines 54-119

3. **User Model**: `app/Models/User.php`
   - `generateOTP()`: Lines 83-91
   - `clearOTP()`: Lines 93-101

4. **Views**:
   - `resources/views/livewire/customer/customer-login.blade.php`
   - `resources/views/livewire/customer/verify-otp.blade.php`

## Summary

The Laravel customer login implementation:

1. ✅ **No API calls** - All operations use direct database queries
2. ✅ **Comprehensive phone lookup** - Handles various phone formats
3. ✅ **Automatic User creation** - Creates User account on first login
4. ✅ **OTP generation** - 4-digit code with 15-minute expiration
5. ✅ **Email/SMS delivery** - Sends OTP via configured services
6. ✅ **Session management** - Stores user ID for OTP verification
7. ✅ **Security features** - Expiration, cooldown, validation
8. ✅ **Error handling** - Clear error messages for all scenarios
9. ✅ **UI enhancements** - Auto-focus, countdown, formatting

The implementation is **independent** and **self-contained**, not requiring any external API services.


# Customer Login - Yii Project Reference

This document describes how customer login works in the Yii project, which our Laravel implementation follows.

## Overview

The Yii project implements an OTP-based customer login system that allows customers to authenticate using either email or phone number. This is separate from the CMS admin login system.

## Login Flow

### 1. Customer Login Request

**Route:** `/login` (public route, no authentication required)

**Process:**
1. Customer selects login method: **Email** or **Phone**
2. Customer enters their email address or phone number
3. System finds the `OrgUser` record matching the identifier
4. System finds or creates a `User` account linked to the `OrgUser`
5. System generates a 4-digit OTP code
6. System sends OTP via email or SMS
7. Customer receives OTP and enters it on verification page

### 2. Finding OrgUser

#### Email Login
```php
// Yii Pattern (translated to Laravel)
OrgUser::find()
    ->where(['email' => $identifier])
    ->andWhere(['isCustomer' => true])
    ->andWhere(['isDeleted' => false])
    ->one();
```

#### Phone Login
```php
// Yii Pattern - Phone number lookup handles various formats:
// 1. Remove spaces, +, -, (), and 00 prefix
$phone = preg_replace('/[\s\+\-\(\)]/', '', $identifier);
$phone = preg_replace('/^00/', '', $phone);

// 2. Try exact match first
OrgUser::find()
    ->where(['phoneNumber' => $phone])
    ->andWhere(['isCustomer' => true])
    ->andWhere(['isDeleted' => false])
    ->one();

// 3. If not found, try with country code concatenated
OrgUser::find()
    ->where(['isCustomer' => true])
    ->andWhere(['isDeleted' => false])
    ->andWhere(['or',
        ['like', "CONCAT(phoneCountry, phoneNumber)", $phone],
        ['like', "CONCAT('+', phoneCountry, phoneNumber)", $phone]
    ])
    ->one();
```

### 3. User Account Creation

In Yii, when a customer logs in for the first time, a `User` account is automatically created if it doesn't exist:

```php
// Yii Pattern
$user = User::find()
    ->where(['orgUser_id' => $orgUser->id])
    ->one();

if (!$user) {
    $user = new User();
    $user->orgUser_id = $orgUser->id;
    $user->fullName = $orgUser->fullName;
    $user->email = $orgUser->email;
    $user->phoneNumber = $orgUser->phoneNumber;
    $user->phoneCountry = $orgUser->phoneCountry;
    $user->uuid = Yii::$app->security->generateRandomString(32);
    $user->auth_key = Yii::$app->security->generateRandomString(32);
    $user->password_hash = Yii::$app->security->generatePasswordHash(
        Yii::$app->security->generateRandomString(32)
    ); // Not used for OTP login, but required field
    $user->status = 10; // Active/Verified
    $user->save();
}
```

### 4. OTP Generation

Yii generates a 4-digit OTP code (1111-9999) with 15-minute expiration:

```php
// Yii Pattern
$user->otp = rand(1111, 9999);
$user->otp_token = md5($user->otp);
$user->otp_expire = time() + (15 * 60); // 15 minutes from now
$user->save();
```

### 5. Sending OTP

#### Email OTP
```php
// Yii Pattern
Yii::$app->mailer->compose()
    ->setTo($orgUser->email)
    ->setSubject('Your Login OTP Code')
    ->setTextBody("Your OTP code is: {$user->otp}\n\nThis code will expire in 15 minutes.")
    ->send();
```

#### SMS OTP
```php
// Yii Pattern - Uses SMS service
$fullPhone = $orgUser->phoneCountry . $orgUser->phoneNumber;
$message = "Your OTP code is: {$user->otp}. This code will expire in 15 minutes.";

// Send via SMS service (BluNet or configured provider)
Yii::$app->sms->send($fullPhone, $message, $orgUser->org_id, $orgUser->id);
```

### 6. OTP Verification

**Route:** `/customer/verify-otp`

**Process:**
1. Customer enters 4-digit OTP code
2. System retrieves `User` from session (`customer_otp_user_id`)
3. System validates:
   - OTP code matches stored code
   - OTP not expired (15 minutes)
4. If valid:
   - Log in customer using `Yii::$app->user->login($user)`
   - Clear OTP from database
   - Redirect to purchase plan page

```php
// Yii Pattern
$user = User::findOne($userId);

// Check expiration
if ($user->otp_expire < time()) {
    // OTP expired
    $user->otp = null;
    $user->otp_token = null;
    $user->otp_expire = null;
    $user->save();
    return ['error' => 'OTP has expired'];
}

// Verify OTP
if ($user->otp != $otpCode) {
    return ['error' => 'Invalid OTP code'];
}

// Login successful
$user->otp = null;
$user->otp_token = null;
$user->otp_expire = null;
$user->save();

Yii::$app->user->login($user);
return $this->redirect(['customer/purchase-plan']);
```

## Key Differences from CMS Admin Login

1. **Authentication Method:**
   - **Customer Login:** OTP-based (email or SMS)
   - **CMS Admin Login:** Password-based

2. **User Account:**
   - **Customer Login:** Auto-creates `User` account if needed
   - **CMS Admin Login:** `User` account must exist with password

3. **Routes:**
   - **Customer Login:** `/login` (public)
   - **CMS Admin Login:** `/cms-admin/login` (public, but different flow)

4. **Session Storage:**
   - **Customer Login:** Stores `customer_otp_user_id` in session
   - **CMS Admin Login:** Standard Laravel authentication session

## Database Schema

### User Table Fields Used
- `id` - Primary key
- `orgUser_id` - Links to OrgUser
- `fullName` - User's full name
- `email` - Email address
- `phoneNumber` - Phone number
- `phoneCountry` - Country code
- `uuid` - Unique identifier
- `auth_key` - Authentication key (required)
- `password_hash` - Password hash (required but not used for OTP)
- `otp` - 4-digit OTP code
- `otp_token` - MD5 hash of OTP
- `otp_expire` - Unix timestamp expiration
- `status` - User status (10 = active/verified)

### OrgUser Table Fields Used
- `id` - Primary key
- `email` - Email address (for email login)
- `phoneNumber` - Phone number (for phone login)
- `phoneCountry` - Country code
- `fullName` - Customer's full name
- `isCustomer` - Must be true
- `isDeleted` - Must be false
- `org_id` - Organization ID

## Security Considerations

1. **OTP Expiration:** 15 minutes maximum validity
2. **OTP Format:** 4-digit numeric code (1111-9999)
3. **Rate Limiting:** Should be implemented to prevent OTP spam
4. **Session Security:** OTP user ID stored in session, cleared after login
5. **Phone Number Normalization:** Handles various input formats
6. **Soft Deleted Users:** Excluded from login (isDeleted = false)

## Error Handling

1. **No Account Found:**
   - Message: "No account found with this email/phone number"
   - Action: Return to login form

2. **OTP Expired:**
   - Message: "OTP has expired. Please request a new one"
   - Action: Clear OTP, redirect to login

3. **Invalid OTP:**
   - Message: "Invalid OTP code. Please try again"
   - Action: Allow retry (OTP remains valid)

4. **Send Failure:**
   - Message: "Failed to send OTP. Please try again"
   - Action: Allow retry

## Integration Points

1. **SMS Service:** Uses `SmsService` for sending SMS OTP
2. **Email Service:** Uses Laravel Mail for sending email OTP
3. **Queue System:** OTP sending can be queued via Yii2QueueDispatcher
4. **Logging:** All login attempts and OTP sends are logged

## Laravel Implementation

Our Laravel implementation follows this Yii pattern exactly:

- **File:** `app/Livewire/Customer/CustomerLogin.php`
- **File:** `app/Livewire/Customer/VerifyOtp.php`
- **File:** `app/Models/User.php` (generateOTP, clearOTP methods)

The implementation maintains compatibility with the Yii backend while using Laravel/Livewire for the frontend.


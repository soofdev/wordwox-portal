# Customer Login API - Yii vs Laravel Implementation

## Overview

This document explains how customer login works in the Yii2 customer portal project and identifies the API integration that exists in Yii2 but is **NOT** used in the Laravel implementation.

## Key Finding: Yii2 Uses API for Login

**Important Discovery**: The Yii2 customer portal **DOES use an API** for login, but the Laravel implementation does **NOT** use this API - it performs direct database queries instead.

## Yii2 Login Implementation

### Login Flow with API

**File**: `/Users/macbook1993/wodworx-customer-portal-yii/common/models/OTPLoginForm.php`

```php
public function validateUser()
{
    // Detect if the username provided is an email or a phone number 
    if (filter_var($this->username, FILTER_VALIDATE_EMAIL)) {
        $this->loginOption = 'email';
    } else {
        $this->loginOption = 'phone';
    }

    // Call the login endpoint
    $response = Yii::$app->apiClient->request('/org-user/login', 'POST', ['username' => $this->username]);

    if($response->statusCode == 200) {
        $data = $response->data['data'];
        if($data['status'] == 200) {
            $this->_orgUser = $this->findOrgUser($data['user']['uuid']);
            return true;
        } else {
            if(!empty($data['errors'])) {
                $this->addErrors($data['errors']);
            } else {
                $this->addError('username', 'Could not log in.');
            }
        }
    }

    return false;
}
```

### API Endpoint Details

**Endpoint**: `/org-user/login`  
**Method**: `POST`  
**Request Body**: 
```json
{
    "username": "user@example.com" // or phone number
}
```

**Response Format** (Expected):
```json
{
    "status": 200,
    "data": {
        "user": {
            "uuid": "org-user-uuid-here"
        }
    }
}
```

### API Client Configuration

**File**: `/Users/macbook1993/wodworx-customer-portal-yii/common/components/APIClient.php`

```php
public function request($action, $method, $data) {        
    $client = new Client();
    return $client->createRequest()
        ->setMethod($method)
        ->addHeaders(['Content-Type' => 'application/json'])
        ->addHeaders(['Accept' => 'application/json'])
        ->addHeaders(['HTTP_X_ORG_API_KEY' => $this->apiKey])
        ->addHeaders(['HTTP_X_ORGUSER' => (Yii::$app->user->identity ? Yii::$app->user->identity->orgUser_id : '')])
        ->addHeaders(['Authorization' => 'Bearer '.(Yii::$app->user->identity ? Yii::$app->user->identity->authKey : '')])
        ->setUrl($this->url.$action)
        ->setData($data)
        ->send();
}
```

**API Headers**:
- `Content-Type: application/json`
- `Accept: application/json`
- `HTTP_X_ORG_API_KEY`: Organization API key
- `HTTP_X_ORGUSER`: Current user's orgUser_id (if authenticated)
- `Authorization: Bearer {authKey}`: User's auth key (if authenticated)

**Note**: For login, the user is not authenticated yet, so `HTTP_X_ORGUSER` and `Authorization` headers may be empty.

### SiteController Login Action

**File**: `/Users/macbook1993/wodworx-customer-portal-yii/frontend/controllers/SiteController.php`

```php
public function actionLogin()
{
    if (!Yii::$app->user->isGuest) {
        return $this->goHome();
    }

    $model = new OTPLoginForm(['org_id' => $this->org->id]);
    
    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        // Verify that orgUser is set before accessing its properties
        if ($model->orgUser === null) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Unable to process login request. Please try again.'));
            return $this->render('otp-login', [
                'model' => $model,
            ]);
        }
        
        // redirect based on the loginOption. Use switch case
        switch ($model->loginOption) {
            case 'email':
                return $this->redirect(['verify-otp-email', 'i' => $model->orgUser->uuid]);
                break;
            
            case 'phone':
                return $this->redirect(['verify-otp-phone', 'i' => $model->orgUser->uuid]);
                break;
        }
    } else {
        return $this->render('otp-login', [
            'model' => $model,
        ]);
    }
}
```

## Laravel Login Implementation

### Current Implementation: Direct Database Queries

**File**: `app/Livewire/Customer/CustomerLogin.php`

The Laravel implementation **does NOT use the API**. Instead, it performs direct database queries:

```php
public function sendOtp()
{
    // Validate based on login method
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
    
    // Find OrgUser by email or phone - DIRECT DATABASE QUERY
    $orgUser = null;
    
    if ($this->loginMethod === 'email') {
        $orgUser = OrgUser::where('email', $this->identifier)
            ->where('isCustomer', true)
            ->where(function($query) {
                $query->where('isDeleted', false)
                      ->orWhereNull('isDeleted');
            })
            ->first();
    } else {
        // Phone login - direct database query
        // ... phone lookup logic ...
    }
    
    // Find or create User account - DIRECT DATABASE OPERATION
    $user = User::withTrashed()->where('orgUser_id', $orgUser->id)->first();
    // ... create user if not exists ...
    
    // Generate OTP - DIRECT DATABASE OPERATION
    $user->generateOTP();
    // ... send OTP ...
}
```

## Comparison: Yii2 vs Laravel

| Aspect | Yii2 | Laravel |
|--------|------|---------|
| **Login Method** | ✅ API Call (`/org-user/login`) | ❌ Direct Database Query |
| **API Integration** | ✅ Yes - Uses `APIClient` | ❌ No - Direct database access |
| **OrgUser Lookup** | ✅ Handled by API | ❌ Direct `OrgUser::where()` query |
| **User Creation** | ✅ Handled by API | ❌ Direct `User::create()` |
| **OTP Generation** | ✅ Handled by API | ❌ Direct `$user->generateOTP()` |
| **API Endpoint** | ✅ `/org-user/login` | ❌ N/A |
| **API Headers** | ✅ `HTTP_X_ORG_API_KEY`, `Authorization` | ❌ N/A |

## All Login-Related API Endpoints

### 1. Endpoint: `/org-user/login` (Initial Login Request)

**File**: `common/models/OTPLoginForm.php` (line 46)

**Base URL**: Configured in Yii2's `APIClient` component (likely in `common/config/main.php`)

**Full URL**: `{API_BASE_URL}/org-user/login`

**Method**: `POST`

**Request**:
```json
{
    "username": "user@example.com"
}
```
or
```json
{
    "username": "+1234567890"
}
```

**Response (Success)**:
```json
{
    "status": 200,
    "data": {
        "status": 200,
        "user": {
            "uuid": "org-user-uuid-here"
        }
    }
}
```

**Response (Error)**:
```json
{
    "status": 200,
    "data": {
        "status": 400,
        "errors": {
            "username": ["No account found with this email/phone"]
        }
    }
}
```

**Usage**:
- Called when user submits login form with email or phone
- API detects if username is email or phone automatically
- Returns OrgUser UUID if found
- API handles OrgUser lookup, User creation, and OTP generation

### 2. Endpoint: `/org-user/verify-otp` (OTP Verification)

**File**: `common/models/OTPVerifyForm.php` (line 37)

**Full URL**: `{API_BASE_URL}/org-user/verify-otp`

**Method**: `GET`

**Request Parameters**:
```
uuid: org-user-uuid (from login response)
otp: 4-digit OTP code
to: 'email' or 'phone' (login method)
```

**Request Example**:
```
GET /org-user/verify-otp?uuid=org-user-uuid-here&otp=1234&to=email
```

**Response (Success)**:
```json
{
    "status": 200,
    "data": {
        "token": "authentication-token-here",
        "orgUser_uuid": "org-user-uuid-here"
    }
}
```

**Response (Error)**:
```json
{
    "status": 200,
    "data": {
        "errors": {
            "otp": ["Invalid or expired OTP code"]
        }
    }
}
```

**Usage**:
- Called when user submits OTP code for verification
- Validates OTP code and expiration
- Returns authentication token if valid
- Used to complete login process

## What the APIs Do

### `/org-user/login` API Endpoint

Based on the Yii2 code, this API endpoint:

1. **Validates the username** (email or phone)
2. **Finds the OrgUser** record matching the identifier
3. **Finds or creates a User account** linked to the OrgUser
4. **Generates an OTP code** (4-digit, 1111-9999)
5. **Sends OTP** via email or SMS
6. **Returns the OrgUser UUID** for OTP verification

### `/org-user/verify-otp` API Endpoint

This API endpoint:

1. **Validates the OTP code** against stored OTP
2. **Checks OTP expiration** (15 minutes)
3. **Verifies login method** (email or phone)
4. **Returns authentication token** if valid
5. **Clears OTP** from database after successful verification

## Why Laravel Doesn't Use the API

The Laravel implementation was designed to:
1. **Work independently** - Not depend on external API availability
2. **Use direct database access** - Faster performance, no HTTP overhead
3. **Maintain consistency** - Same database, same data, no sync issues
4. **Simplify architecture** - No API authentication, rate limiting, or error handling needed

## Should Laravel Use the API?

### Current Approach (Recommended)

**Pros**:
- ✅ Faster (no HTTP overhead)
- ✅ Simpler (no API authentication)
- ✅ More reliable (no network dependency)
- ✅ Immediate data consistency

**Cons**:
- ❌ Duplicates logic (both systems have login code)
- ❌ Potential inconsistency if logic differs

### API Approach (Alternative)

**Pros**:
- ✅ Single source of truth (API handles all login logic)
- ✅ Consistent behavior across all clients
- ✅ Centralized OTP generation and sending

**Cons**:
- ❌ Network dependency (API must be available)
- ❌ Slower (HTTP request overhead)
- ❌ More complex (API authentication, error handling)
- ❌ Potential failure point

## API Configuration in Yii2

The API client is configured in Yii2's configuration files. To find the API base URL:

1. Check `common/config/main.php` or environment-specific configs
2. Look for `apiClient` component configuration
3. Check for `API_URL` or similar environment variables

**Example Configuration** (inferred):
```php
'components' => [
    'apiClient' => [
        'class' => 'common\components\APIClient',
        'url' => getenv('API_BASE_URL') ?: 'https://api.wodworx.com',
        'apiKey' => getenv('ORG_API_KEY'),
    ],
],
```

## Complete Login Flow with APIs

### Step 1: User Submits Login Form
```
POST /org-user/login
Body: { "username": "user@example.com" }
```

**API Response**:
- Finds OrgUser
- Creates User account if needed
- Generates OTP
- Sends OTP via email/SMS
- Returns: `{ "user": { "uuid": "..." } }`

### Step 2: User Submits OTP Code
```
GET /org-user/verify-otp?uuid=...&otp=1234&to=email
```

**API Response**:
- Validates OTP
- Checks expiration
- Returns: `{ "token": "...", "orgUser_uuid": "..." }`

### Step 3: Yii2 Logs User In
```php
Yii::$app->user->login($orgUser->user, 3600 * 24 * 30);
```

## Related API Endpoints

Based on the `ApiController.php`, other API endpoints used in Yii2 (require authentication):

- `/event` - GET - Get events
- `/event/view` - GET - Get event details
- `/event/subscribe` - GET - Subscribe to event
- `/event/waitlist` - GET - Join waitlist
- `/event/unsubscribe` - GET - Unsubscribe from event
- `/event/signin` - GET - Sign in to event
- `/org-plan` - GET - Get plans
- `/org-plan/view` - GET - Get plan details
- `/program` - GET - Get programs

**Note**: All these endpoints require authentication (user must be logged in).

## Recommendations

### If You Want to Use the API in Laravel

1. **Create API Client Service**:
   ```php
   // app/Services/WodworxApiClient.php
   class WodworxApiClient {
       public function login(string $username): array {
           // Make HTTP request to /org-user/login
       }
   }
   ```

2. **Update CustomerLogin Component**:
   ```php
   public function sendOtp() {
       $apiClient = new WodworxApiClient();
       $response = $apiClient->login($this->identifier);
       // Handle response
   }
   ```

3. **Add Configuration**:
   ```php
   // config/services.php
   'wodworx_api' => [
       'base_url' => env('WODWORX_API_URL'),
       'api_key' => env('ORG_API_KEY'),
   ],
   ```

### Current Approach (Recommended)

Keep the current direct database approach because:
- It's faster and more reliable
- No external dependencies
- Works even if API is down
- Simpler error handling

## Conclusion

**Yii2 Customer Portal**:
- ✅ Uses API endpoint `/org-user/login` for login
- ✅ API handles OrgUser lookup, User creation, and OTP generation
- ✅ Returns OrgUser UUID for OTP verification

**Laravel Customer Portal**:
- ❌ Does NOT use the API
- ✅ Performs direct database queries
- ✅ Handles OrgUser lookup, User creation, and OTP generation directly

Both approaches work, but they use different methods. The Laravel approach is more independent and faster, while the Yii2 approach centralizes logic in the API.


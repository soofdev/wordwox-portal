# Buy Functionality & MyFatoorah Payment Flow - Complete Guide

## Overview

This document explains the complete flow of the "Buy" button functionality and how MyFatoorah payment integration works in the Laravel customer portal.

---

## Table of Contents

1. [Buy Button Flow](#buy-button-flow)
2. [MyFatoorah Payment Creation](#myfatoorah-payment-creation)
3. [Payment Processing](#payment-processing)
4. [Callback & Record Creation](#callback--record-creation)
5. [API Endpoint Details](#api-endpoint-details)
6. [Database Records Created](#database-records-created)

---

## Buy Button Flow

### 1. User Clicks "Buy" Button

**Location**: Package listing pages (`/packages`, home page packages section)

**Button Code**:
```php
<a href="/org-plan/index?plan={{ $planUuid }}" class="btn btn-dark">
    Buy
</a>
```

**Behavior**:
- If user is **not authenticated**: Redirects to `/login?redirect=/org-plan/index?plan={planUuid}`
- If user is **authenticated**: Redirects to `/org-plan/index?plan={planUuid}` (purchase page)

### 2. Purchase Page Loading

**Component**: `App\Livewire\Customer\PurchasePlan`

**Process**:
1. Loads plan details from database using UUID
2. Checks if plan is **free** (price = 0) or **paid** (price > 0)
3. For **paid plans**: Checks for existing active memberships
4. For **paid plans**: Creates payment via API

### 3. Plan Type Detection

#### Free Plans (Price = 0)
- Shows "Complete order" confirmation page
- No payment gateway integration
- Creates records immediately when user clicks "Complete order"

#### Paid Plans (Price > 0)
- Shows package details + payment iframe
- Integrates with MyFatoorah via `wodworx-pay` API
- Creates payment URL and displays it in iframe

---

## MyFatoorah Payment Creation

### Step 1: Prepare Payment Data

**Location**: `PurchasePlan::createPayment()`

**Payment Data Structure**:
```php
$paymentData = [
    'org_id' => 8,
    'payment_method_id' => 2, // Default: Visa/Mastercard
    'invoice_value' => 25.50, // Plan price
    'customer_name' => 'John Doe', // From orgUser.fullName
    'customer_email' => 'john.doe@example.com', // From orgUser.email
    'currency_iso' => 'KWD', // From orgSettingsPaymentGateway
    'language' => 'en',
    'org_plan_id' => 123,
    'org_user_id' => 456,
    'plan_name' => 'Premium Plan',
    'callback_url' => 'https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}',
    'error_url' => 'https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}',
    'membership_data' => [
        'org_id' => 8,
        'orgUser_id' => 456,
        'orgPlan_id' => 123,
        'invoiceStatus' => 1, // PAID
        'invoiceMethod' => 'online',
        'status' => 2, // ACTIVE
        'created_by' => 456,
        'sold_by' => 456,
        'note' => 'Purchased online via customer portal',
        'startDateLoc' => '2025-12-07',
    ],
];
```

### Step 2: Call wodworx-pay API

**Service**: `App\Services\MyFatoorahPaymentApiService`

**Endpoint**: `POST {MYFATOORAH_PAYMENT_SERVICE_URL}/api/myfatoorah/create-payment`

**Base URL Configuration**:
```php
// config/services.php
'myfatoorah' => [
    'base_url' => env('MYFATOORAH_PAYMENT_SERVICE_URL', 'https://781c91b1c7e5.ngrok-free.app'),
    'api_token' => env('MYFATOORAH_API_TOKEN'),
    'timeout' => 30,
],
```

**Request Headers**:
```php
[
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'X-API-Token' => env('MYFATOORAH_API_TOKEN'), // Optional
]
```

**Example cURL Request**:
```bash
curl --location 'https://781c91b1c7e5.ngrok-free.app/api/myfatoorah/create-payment' \
--header 'Content-Type: application/json' \
--header 'X-API-Token: your-api-token' \
--data-raw '{
    "org_id": 8,
    "payment_method_id": 2,
    "invoice_value": 25.50,
    "customer_name": "John Doe",
    "customer_email": "john.doe@example.com",
    "customer_mobile": "1234567890",
    "mobile_country_code": "+965",
    "currency_iso": "KWD",
    "language": "en",
    "customer_reference": "ORDER-12345",
    "org_plan_id": 123,
    "org_user_id": 456,
    "plan_name": "Premium Plan",
    "callback_url": "https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}",
    "error_url": "https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}",
    "membership_data": {
        "org_id": 8,
        "orgUser_id": 456,
        "orgPlan_id": 123,
        "invoiceStatus": 1,
        "invoiceMethod": "online",
        "status": 2,
        "created_by": 456,
        "sold_by": 456,
        "note": "Purchased online via customer portal",
        "startDateLoc": "2025-12-07"
    }
}'
```

**Response**:
```json
{
    "success": true,
    "data": {
        "payment_url": "https://myfatoorah.com/payment/...",
        "payment_id": "07076346864319697374",
        "invoice_id": "07076346864319697374"
    }
}
```

### Step 3: Store Payment Data in Session

**Purpose**: Store payment details for callback processing

**Session Key**: `payment_pending_{invoice_id}`

**Session Data**:
```php
[
    'org_id' => 8,
    'org_user_id' => 456,
    'org_plan_id' => 123,
    'plan_uuid' => 'plan-uuid-here',
    'plan_name' => 'Premium Plan',
    'plan_price' => 25.50,
    'payment_id' => '07076346864319697374',
    'invoice_id' => '07076346864319697374',
    'payment_url' => 'https://myfatoorah.com/payment/...',
    'membership_data' => [...],
    'payment_data' => [...],
    'created_at' => '2025-12-07T10:00:00Z',
    'expires_at' => '2025-12-08T10:00:00Z', // 24 hour expiration
]
```

### Step 4: Display Payment URL

**Location**: `resources/views/livewire/customer/purchase-plan.blade.php`

**Display**: Payment URL is embedded in an iframe for user to complete payment

**Important**: **NO database records are created yet** - records are created only after successful payment callback

---

## Payment Processing

### User Completes Payment

1. User is redirected to MyFatoorah payment page (via iframe or redirect)
2. User enters payment details (card number, CVV, etc.)
3. User submits payment
4. MyFatoorah processes payment with bank/payment gateway
5. Payment is either **successful** or **failed**

### MyFatoorah Callback

**When**: Immediately after payment processing completes

**Method**: GET request

**URL**: The `callback_url` provided during payment creation

**Query Parameters**:
```
?paymentId=07076346864319697374&Id=07076346864319697374&org_id=8
```

**Note**: MyFatoorah may call the callback **multiple times** (retries)

---

## Callback & Record Creation

### Step 1: Receive Callback

**Controller**: `App\Http\Controllers\PaymentCallbackController::handleCallback()`

**Route**: `GET /payment/callback`

**Process**:
1. Extract `paymentId` and `Id` (invoiceId) from query parameters
2. Extract `org_id` from query parameters
3. Log callback received

### Step 2: Verify Payment Status

**Service**: `MyFatoorahPaymentApiService::verifyPayment()`

**Endpoint**: `POST {MYFATOORAH_PAYMENT_SERVICE_URL}/api/myfatoorah/verify-payment`

**Request**:
```php
[
    'payment_id' => '07076346864319697374',
    'verification_type' => 'PaymentId', // or 'InvoiceId'
]
```

**Response**:
```json
{
    "success": true,
    "data": {
        "InvoiceStatus": "Paid",
        "InvoiceValue": 25.50,
        "PaymentId": "07076346864319697374",
        "Id": "07076346864319697374"
    }
}
```

**Status Check**:
- If `InvoiceStatus === "Paid"`: Proceed to create records
- If `InvoiceStatus !== "Paid"`: Redirect to error page

### Step 3: Retrieve Session Data

**Session Key**: `payment_pending_{invoice_id}`

**Purpose**: Retrieve membership data and payment details stored during payment creation

### Step 4: Dispatch Queue Job

**Job**: `App\Jobs\CreatePaymentRecordsJob`

**Purpose**: Create database records asynchronously (prevents timeout)

**Data Passed to Job**:
```php
CreatePaymentRecordsJob::dispatch(
    $membershipData,      // From session
    $paymentData,         // From session
    $planPrice,           // From session
    $orgId,               // From session
    $orgUserId,           // From session
    $orgPlanId,           // From session
    $paymentId,           // From callback
    $invoiceId,           // From callback
    $sessionKey           // For cleanup
);
```

### Step 5: Clear Session

**Action**: `Session::forget($sessionKey)`

**Purpose**: Prevent duplicate processing

### Step 6: Redirect to Success Page

**Route**: `GET /pay/org-plan-success?ref=plan_{plan_uuid}_{org_user_id}`

**Purpose**: Show success message to user

---

## Database Records Created

### After Successful Payment (via Queue Job)

#### 1. `orgUserPlan` (Membership)

**Table**: `orgUserPlan`

**Fields**:
```php
[
    'org_id' => 8,
    'orgUser_id' => 456,
    'orgPlan_id' => 123,
    'invoiceStatus' => 1, // PAID
    'invoiceMethod' => 'online',
    'status' => 2, // ACTIVE
    'created_by' => 456,
    'sold_by' => 456,
    'note' => 'Purchased online via customer portal',
    'startDateLoc' => '2025-12-07',
    'isCanceled' => 0,
    'isDeleted' => 0,
]
```

**Created via**: `OrgUserPlanService::create()`

#### 2. `orgInvoice`

**Table**: `orgInvoice`

**Fields**:
```php
[
    'uuid' => 'invoice-uuid',
    'org_id' => 8,
    'orgUserPlan_id' => 789, // ID from orgUserPlan created above
    'orgUser_id' => 456,
    'total' => 25.50,
    'totalPaid' => 25.50,
    'currency' => 'KWD',
    'status' => 'paid', // InvoiceStatus::PAID
    'pp' => 'myfatoorah', // Payment provider
    'isDeleted' => 0,
]
```

#### 3. `orgInvoicePayment`

**Table**: `orgInvoicePayment`

**Fields**:
```php
[
    'uuid' => 'payment-uuid',
    'org_id' => 8,
    'orgInvoice_id' => 101, // ID from orgInvoice created above
    'amount' => 25.50,
    'currency' => 'KWD',
    'method' => 'online',
    'status' => 'paid', // STATUS_PAID
    'gateway' => 'myfatoorah',
    'pp' => 'myfatoorah',
    'gateway_payment_id' => '07076346864319697374',
    'gateway_invoice_id' => '07076346864319697374',
    'paid_at' => time(),
    'created_by' => 456,
    'isCanceled' => 0,
    'isDeleted' => 0,
]
```

---

## API Endpoint Details

### Create Payment Endpoint

**URL**: `POST {MYFATOORAH_PAYMENT_SERVICE_URL}/api/myfatoorah/create-payment`

**Service**: External `wodworx-pay` service (separate Laravel project)

**Required Fields**:
- `org_id` (int): Organization ID
- `payment_method_id` (int): Payment method ID (2 = Visa/Mastercard)
- `invoice_value` (float): Payment amount
- `customer_name` (string): Customer full name
- `currency_iso` (string): Currency code (e.g., "KWD")
- `language` (string): Language code (e.g., "en")

**Optional Fields**:
- `customer_email` (string): Customer email
- `customer_mobile` (string): Customer phone number
- `mobile_country_code` (string): Country code (e.g., "+965")
- `customer_reference` (string): Custom reference
- `org_plan_id` (int): Plan ID
- `org_user_id` (int): User ID
- `plan_name` (string): Plan name
- `callback_url` (string): Callback URL after payment
- `error_url` (string): Error URL if payment fails
- `membership_data` (array): Membership data for record creation

**Response Format**:
```json
{
    "success": true,
    "data": {
        "payment_url": "https://myfatoorah.com/payment/...",
        "payment_id": "07076346864319697374",
        "invoice_id": "07076346864319697374"
    }
}
```

### Verify Payment Endpoint

**URL**: `POST {MYFATOORAH_PAYMENT_SERVICE_URL}/api/myfatoorah/verify-payment`

**Required Fields**:
- `payment_id` (string): Payment ID or Invoice ID
- `verification_type` (string): "PaymentId" or "InvoiceId"

**Response Format**:
```json
{
    "success": true,
    "data": {
        "InvoiceStatus": "Paid",
        "InvoiceValue": 25.50,
        "PaymentId": "07076346864319697374",
        "Id": "07076346864319697374"
    }
}
```

---

## Important Notes

### 1. No Records Created Before Payment

**Key Point**: Database records (`orgUserPlan`, `orgInvoice`, `orgInvoicePayment`) are **NOT created** when payment is initiated. They are created **only after successful payment** via the callback.

### 2. Session Storage

Payment details are stored in session with 24-hour expiration to allow callback processing.

### 3. Queue Job Processing

Records are created via a queue job (`CreatePaymentRecordsJob`) to prevent timeout issues and ensure reliable processing.

### 4. Callback URL Configuration

The callback URL must be:
- **Absolute URL** (not relative)
- **HTTPS in production** (HTTP allowed in local development)
- **Publicly accessible** (no authentication required)

### 5. Error Handling

- If payment verification fails: User redirected to error page
- If session data missing: User redirected to error page with support contact info
- If queue job fails: Error logged, user still sees success (job can be retried)

---

## Flow Diagram

```
User clicks "Buy"
    ↓
Purchase Page Loads
    ↓
Check if Free or Paid
    ↓
[If Paid]
    ↓
Create Payment via API
    ↓
Store Payment Data in Session
    ↓
Display Payment URL (iframe)
    ↓
User Completes Payment on MyFatoorah
    ↓
MyFatoorah Processes Payment
    ↓
MyFatoorah Calls Callback URL
    ↓
Verify Payment Status
    ↓
Retrieve Session Data
    ↓
Dispatch Queue Job
    ↓
Create Database Records:
  - orgUserPlan
  - orgInvoice
  - orgInvoicePayment
    ↓
Redirect to Success Page
```

---

## Testing

### Automated Test Command

Laravel includes a comprehensive test command that validates the entire buy and payment flow:

```bash
# Test without making actual API calls (recommended for initial testing)
php artisan test:buy-payment-flow --org-id=8 --skip-api

# Test with actual API calls (requires wodworx-pay service to be running)
php artisan test:buy-payment-flow --org-id=8

# Test with specific plan and user
php artisan test:buy-payment-flow --org-id=8 --plan-id=123 --user-id=456 --skip-api

# Show detailed output
php artisan test:buy-payment-flow --org-id=8 --skip-api --detailed
```

**Test Coverage**:
1. ✅ **Buy Button Flow**: Plan loading, user validation, membership checks
2. ✅ **Payment Creation**: Payment data preparation, API configuration
3. ✅ **Session Storage**: Session data storage and retrieval
4. ✅ **Callback Handling**: Callback route and controller validation
5. ✅ **Database Records**: Table structure and required columns validation

**Example Output**:
```
🧪 Testing Buy and Payment Flow
================================

📦 Test 1: Buy Button Flow
✅ Plan loaded: UNLIMITED MONTHLY (ID: 9)
✅ Plan is PAID - will use payment gateway
✅ OrgUser loaded: User 365 (ID: 365)
✅ No active membership found - payment can proceed
✅ MyFatoorah configuration found

💳 Test 2: Payment Creation
✅ Payment data prepared
✅ API Configuration
✅ Payment data structure is valid

💾 Test 3: Session Storage
✅ Session data stored and retrieved successfully

🔄 Test 4: Callback Handling
✅ Callback route exists
✅ Callback controller method exists
✅ Callback URL generated

🗄️  Test 5: Database Records
✅ All required tables exist
✅ All required columns exist
✅ Queue job class exists
✅ Service classes exist

📊 Test Summary
✅ All tests passed!
```

### Manual API Testing

#### Test Payment Creation

```bash
curl --location 'https://781c91b1c7e5.ngrok-free.app/api/myfatoorah/create-payment' \
--header 'Content-Type: application/json' \
--header 'X-API-Token: your-api-token' \
--data-raw '{
    "org_id": 8,
    "payment_method_id": 2,
    "invoice_value": 25.50,
    "customer_name": "John Doe",
    "customer_email": "john.doe@example.com",
    "currency_iso": "KWD",
    "language": "en",
    "callback_url": "https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}",
    "error_url": "https://your-domain.com/payment/callback?org_id=8&plan={plan_uuid}"
}'
```

#### Test Payment Verification

```bash
curl --location 'https://781c91b1c7e5.ngrok-free.app/api/myfatoorah/verify-payment' \
--header 'Content-Type: application/json' \
--header 'X-API-Token: your-api-token' \
--data-raw '{
    "payment_id": "07076346864319697374",
    "verification_type": "PaymentId"
}'
```

---

## Configuration

### Environment Variables

```env
MYFATOORAH_PAYMENT_SERVICE_URL=https://781c91b1c7e5.ngrok-free.app
MYFATOORAH_API_TOKEN=your-api-token-here
```

### Config File

```php
// config/services.php
'myfatoorah' => [
    'base_url' => env('MYFATOORAH_PAYMENT_SERVICE_URL'),
    'api_token' => env('MYFATOORAH_API_TOKEN'),
    'timeout' => 30,
],
```

---

## Troubleshooting

### Payment URL Not Generated

- Check `MYFATOORAH_PAYMENT_SERVICE_URL` is correct
- Check `wodworx-pay` service is running
- Check API token is valid (if required)
- Check logs: `storage/logs/laravel.log`

### Callback Not Received

- Check callback URL is publicly accessible
- Check callback URL uses HTTPS in production
- Check MyFatoorah dashboard for callback logs
- Check `wodworx-pay` service logs

### Records Not Created

- Check queue is running: `php artisan queue:work`
- Check queue logs: `storage/logs/laravel.log`
- Check session data exists: `Session::get('payment_pending_{invoice_id}')`
- Check payment verification response

---

## Related Documentation

- [Buy Functionality Flow](./buy-functionality-flow.md)
- [MyFatoorah Callback Execution](./myfatoorah-callback-execution.md)
- [Payment Callback Configuration](./callback-url-configuration.md)
- [Create Payment Records Job](./create-payment-records-job.md)


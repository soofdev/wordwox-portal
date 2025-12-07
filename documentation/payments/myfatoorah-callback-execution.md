# MyFatoorah Payment Callback Execution

## When the Callback is Executed

The MyFatoorah payment callback is executed **after payment processing** in the following sequence:

### Payment Flow Timeline

1. **User Initiates Payment**
   - User clicks "Buy" button in Laravel portal
   - Laravel creates records with `PENDING` status
   - Laravel calls `wodworx-pay` API: `POST /api/myfatoorah/create-payment`
   - `wodworx-pay` calls MyFatoorah: `POST /v2/ExecutePayment`
   - MyFatoorah returns payment URL

2. **User Completes Payment**
   - User is redirected to MyFatoorah payment page
   - User enters payment details (card number, etc.)
   - User submits payment

3. **MyFatoorah Processes Payment**
   - MyFatoorah validates payment details
   - MyFatoorah processes payment with bank/payment gateway
   - Payment is either successful or failed

4. **Callback Execution** ⚡
   - **MyFatoorah sends GET request to `CallBackUrl`**
   - Callback includes query parameters:
     - `paymentId` (or `PaymentId`): MyFatoorah Payment ID
     - `Id`: MyFatoorah Invoice ID
   - Callback happens **immediately after payment processing**
   - Callback may be called **multiple times** (MyFatoorah retries)

5. **Laravel Processes Callback**
   - `PaymentCallbackController::handleCallback()` receives request
   - Verifies payment status with MyFatoorah API
   - Updates records from `PENDING` to `ACTIVE`/`PAID`
   - Redirects user to success page

## Callback URL Configuration

The callback URL is set in the `executePayment` method in `wodworx-pay` service:

```php
$postFields = [
    'CallBackUrl' => $paymentData['callback_url'] ?? $this->config['callback_url'],
    // ... other fields
];
```

The Laravel portal sends the callback URL in `PurchasePlan::createPayment()`:

```php
$callbackUrl = route('payment.callback', [
    'org_id' => $orgId,
    'plan' => $this->plan->uuid,
], true);

$paymentData['callback_url'] = $callbackUrl;
```

## Callback Request Format

### Request Method
- **GET** (MyFatoorah sends GET request by default)
- Route accepts both GET and POST for flexibility

### Query Parameters

MyFatoorah sends the following parameters in the query string:

```
GET /payment/callback?paymentId=07076345426319602672&Id=6345426&org_id=8
```

**Parameters:**
- `paymentId` or `PaymentId`: MyFatoorah Payment ID
- `Id` or `InvoiceId`: MyFatoorah Invoice ID
- `org_id`: Organization ID (if included in callback URL)

### Example Callback Request

```
GET https://your-domain.com/payment/callback?paymentId=07076345426319602672&Id=6345426&org_id=8
```

## Callback Handler Implementation

The callback handler (`PaymentCallbackController::handleCallback()`) performs:

1. **Extract Parameters**
   - Gets `paymentId` and `invoiceId` from query string
   - Logs all parameters for debugging

2. **Verify Payment Status**
   - Calls MyFatoorah API to verify payment status
   - Ensures payment is actually "Paid" before updating records

3. **Find Existing Records**
   - Searches for existing payment record by `pp_id` or `pp_number`
   - Checks if payment already processed (idempotent)

4. **Update Records**
   - Updates `orgUserPlan`: `STATUS_PENDING` → `STATUS_ACTIVE`, `INVOICE_STATUS_PENDING` → `INVOICE_STATUS_PAID`
   - Updates `orgInvoice`: `PENDING` → `PAID`, `totalPaid` = `total`
   - Updates `orgInvoicePayment`: `STATUS_PENDING` → `STATUS_PAID`, sets `paid_at`

5. **Redirect User**
   - Redirects to success page with membership reference

## Idempotency

The callback handler is **idempotent** - it's safe to call multiple times:

- If payment already processed, it just redirects to success page
- If payment is pending, it updates records
- Prevents duplicate record creation

## Error Handling

If callback fails:
- Payment records remain in `PENDING` status
- User can manually verify payment later
- Admin can check pending payments and update manually

## Testing Callbacks

### Local Development

For local development, use ngrok or similar tool to expose local server:

```bash
ngrok http 8000
```

Update callback URL in payment data:
```php
$callbackUrl = 'https://your-ngrok-url.ngrok.io/payment/callback';
```

### Production

Ensure callback URL is:
- Accessible from internet (not behind firewall)
- Uses HTTPS (MyFatoorah requires HTTPS for callbacks)
- Returns HTTP 200 status code

## Important Notes

1. **Callback Timing**: Callback is executed **asynchronously** after payment processing. It may take a few seconds to minutes.

2. **Multiple Callbacks**: MyFatoorah may call the callback URL multiple times. The handler must be idempotent.

3. **Payment Status**: Always verify payment status via MyFatoorah API before updating records. Don't trust query parameters alone.

4. **HTTPS Required**: MyFatoorah requires HTTPS for callbacks in production. Use HTTPS URLs only.

5. **Timeout**: If callback doesn't receive response within timeout, MyFatoorah may retry. Ensure handler responds quickly.

## Related Files

- `app/Http/Controllers/PaymentCallbackController.php` - Callback handler
- `app/Livewire/Customer/PurchasePlan.php` - Payment initiation
- `routes/web.php` - Callback route definition
- `app/Services/MyFatoorahPaymentApiService.php` - Payment verification




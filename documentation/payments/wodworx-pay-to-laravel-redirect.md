# wodworx-pay to Laravel Portal Redirect

## Problem

MyFatoorah is calling the `wodworx-pay` service callback URL instead of the Laravel portal:
```
https://781c91b1c7e5.ngrok-free.app/api/myfatoorah/payment-callback?org_id=8&paymentId=07076346864319697374&Id=07076346864319697374
```

## Solution

The `wodworx-pay` service should redirect to the Laravel portal callback after processing, so Laravel can create the records.

## Implementation in wodworx-pay Service

Update the `wodworx-pay` payment callback handler to redirect to Laravel portal:

```php
// In wodworx-pay: app/Http/Controllers/MyFatoorahController.php or similar

public function paymentCallback(Request $request)
{
    $paymentId = $request->input('paymentId') ?? $request->input('PaymentId');
    $invoiceId = $request->input('Id') ?? $request->input('invoiceId');
    $orgId = $request->input('org_id');
    
    // Log the callback received
    Log::info('wodworx-pay: Payment callback received from MyFatoorah', [
        'payment_id' => $paymentId,
        'invoice_id' => $invoiceId,
        'org_id' => $orgId,
        'all_params' => $request->all(),
    ]);
    
    // Get Laravel portal callback URL from config or request
    // Option 1: From config
    $laravelCallbackUrl = config('laravel_portal.callback_url');
    
    // Option 2: From request (if passed during payment creation)
    // This would need to be stored when payment was created
    
    // Option 3: Construct from org_id
    $laravelCallbackUrl = 'http://127.0.0.1:8000/payment/callback'; // Local
    // Or: $laravelCallbackUrl = 'https://your-laravel-domain.com/payment/callback'; // Production
    
    // Build redirect URL with all payment parameters
    $redirectUrl = $laravelCallbackUrl . '?' . http_build_query([
        'paymentId' => $paymentId,
        'Id' => $invoiceId,
        'org_id' => $orgId,
    ]);
    
    Log::info('wodworx-pay: Redirecting to Laravel portal', [
        'redirect_url' => $redirectUrl,
        'payment_id' => $paymentId,
        'invoice_id' => $invoiceId,
    ]);
    
    // Redirect to Laravel portal - Laravel will verify payment and create records
    return redirect($redirectUrl);
}
```

## Alternative: Store Laravel Callback URL During Payment Creation

If you want to store the Laravel callback URL when payment is created:

```php
// In wodworx-pay executePayment method
public function executePayment($paymentData)
{
    // ... existing code ...
    
    // Store Laravel callback URL for later redirect
    if (isset($paymentData['callback_url'])) {
        // Store in database or cache with payment ID
        Cache::put('laravel_callback_' . $invoiceId, $paymentData['callback_url'], 3600);
    }
    
    // ... rest of method ...
}

// In callback handler
public function paymentCallback(Request $request)
{
    $invoiceId = $request->input('Id');
    $laravelCallbackUrl = Cache::get('laravel_callback_' . $invoiceId);
    
    if ($laravelCallbackUrl) {
        $redirectUrl = $laravelCallbackUrl . '?' . http_build_query([
            'paymentId' => $request->input('paymentId'),
            'Id' => $invoiceId,
            'org_id' => $request->input('org_id'),
        ]);
        
        return redirect($redirectUrl);
    }
    
    // Fallback to default Laravel URL
    // ...
}
```

## Laravel Portal Callback Handler

The Laravel portal callback handler (`PaymentCallbackController::handleCallback`) will:

1. ✅ Receive callback with `paymentId` and `Id`
2. ✅ Verify payment status with MyFatoorah API
3. ✅ Check if payment is "Paid"
4. ✅ Find session data by payment/invoice ID
5. ✅ Dispatch `CreatePaymentRecordsJob` to create:
   - `orgUserPlan` (membership) with `STATUS_ACTIVE` and `INVOICE_STATUS_PAID`
   - `orgInvoice` with `PAID` status
   - `orgInvoicePayment` with `STATUS_PAID`
6. ✅ Redirect to success page

## Flow After Redirect

```
MyFatoorah → wodworx-pay callback
  ↓
wodworx-pay shows success page
  ↓
wodworx-pay redirects to Laravel portal
  ↓
Laravel portal callback handler:
  1. Verifies payment with MyFatoorah API
  2. Finds session data
  3. Dispatches CreatePaymentRecordsJob
  4. Creates orgUserPlan, orgInvoice, orgInvoicePayment
  5. Redirects to success page
```

## Testing

1. Make a test payment
2. Verify MyFatoorah calls `wodworx-pay` callback
3. Verify `wodworx-pay` redirects to Laravel portal
4. Check Laravel logs for:
   - Payment verification
   - Session data found
   - Job dispatched
   - Records created
5. Verify records exist in database:
   - `orgUserPlan` with `status = 2` (ACTIVE)
   - `orgInvoice` with `status = 2` (PAID)
   - `orgInvoicePayment` with `status = 2` (PAID)




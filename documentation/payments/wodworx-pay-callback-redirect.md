# wodworx-pay Callback Redirect Issue

## Problem

MyFatoorah is calling the `wodworx-pay` service callback URL (`https://781c91b1c7e5.ngrok-free.app/api/myfatoorah/payment-callback`) instead of the Laravel portal callback URL.

## Root Cause

The `wodworx-pay` service's `executePayment` method may be using its default callback URL from config instead of the `callback_url` parameter sent from Laravel.

## Solution

### 1. Ensure Callback URL is Passed Correctly

The Laravel portal sends the callback URL in the payment data:

```php
$callbackUrl = route('payment.callback', [
    'org_id' => $orgId,
    'plan' => $this->plan->uuid,
], true);

$paymentData['callback_url'] = $callbackUrl;
$paymentData['return_url'] = $callbackUrl;
```

### 2. Update wodworx-pay executePayment Method

The `wodworx-pay` service's `executePayment` method should:

1. **Prioritize callback_url from request** over default config
2. **Log the callback URL being sent to MyFatoorah**
3. **Use the callback_url parameter** when calling MyFatoorah API

```php
public function executePayment($paymentData)
{
    // ... existing code ...
    
    $postFields = [
        'PaymentMethodId' => $paymentData['payment_method_id'],
        'InvoiceValue' => $paymentData['invoice_value'],
        'CustomerName' => $paymentData['customer_name'],
        'DisplayCurrencyIso' => $paymentData['currency_iso'] ?? $this->getCurrencyForCountry(...),
        
        // IMPORTANT: Use callback_url from request, fallback to config
        'CallBackUrl' => $paymentData['callback_url'] ?? $this->config['callback_url'],
        'ErrorUrl' => $paymentData['error_url'] ?? $this->config['error_url'],
    ];
    
    // Log the callback URL being sent to MyFatoorah
    Log::info('wodworx-pay executePayment: Sending to MyFatoorah', [
        'callback_url' => $postFields['CallBackUrl'],
        'error_url' => $postFields['ErrorUrl'],
        'invoice_value' => $postFields['InvoiceValue'],
        'customer_name' => $postFields['CustomerName'],
        'payment_method_id' => $postFields['PaymentMethodId'],
        'full_post_fields' => $postFields, // Log all data
    ]);
    
    // ... rest of the method ...
}
```

### 3. Verify MyFatoorah Receives Correct Callback URL

After updating `wodworx-pay`, verify in logs that:
- The callback URL sent to MyFatoorah is the Laravel portal URL
- Not the `wodworx-pay` service URL

### 4. Alternative: Redirect in wodworx-pay Callback

If you cannot modify `wodworx-pay` immediately, you can add a redirect in the `wodworx-pay` callback handler:

```php
// In wodworx-pay payment-callback handler
public function handleCallback(Request $request)
{
    // ... process payment ...
    
    // After processing, redirect to Laravel portal
    $laravelCallbackUrl = $request->input('laravel_callback_url') 
        ?? $request->input('callback_url')
        ?? config('laravel_portal.callback_url');
    
    if ($laravelCallbackUrl) {
        $paymentId = $request->input('paymentId') ?? $request->input('PaymentId');
        $invoiceId = $request->input('Id') ?? $request->input('invoiceId');
        $orgId = $request->input('org_id');
        
        $redirectUrl = $laravelCallbackUrl . '?' . http_build_query([
            'paymentId' => $paymentId,
            'Id' => $invoiceId,
            'org_id' => $orgId,
        ]);
        
        return redirect($redirectUrl);
    }
    
    // ... show success page ...
}
```

## Logging Requirements

### In Laravel Portal

The `MyFatoorahPaymentApiService` now logs:
- All payment data being sent to `wodworx-pay`
- Callback URL being sent
- Response from `wodworx-pay`

### In wodworx-pay Service

The `executePayment` method should log:
- Callback URL received from Laravel
- Callback URL being sent to MyFatoorah
- All data sent to MyFatoorah API
- Response from MyFatoorah

## Testing

1. **Check Laravel logs** for callback URL being sent:
   ```
   MyFatoorah API: Creating payment - Request Data
   callback_url: https://your-laravel-domain.com/payment/callback?org_id=8&plan=...
   ```

2. **Check wodworx-pay logs** for callback URL being sent to MyFatoorah:
   ```
   wodworx-pay executePayment: Sending to MyFatoorah
   callback_url: https://your-laravel-domain.com/payment/callback?org_id=8&plan=...
   ```

3. **Verify MyFatoorah callback** goes to Laravel portal, not wodworx-pay

## Current Status

- ✅ Laravel portal sends `callback_url` in payment data
- ✅ Laravel logs all payment data being sent
- ⚠️ Need to verify `wodworx-pay` uses the `callback_url` parameter
- ⚠️ Need to add logging in `wodworx-pay` executePayment method




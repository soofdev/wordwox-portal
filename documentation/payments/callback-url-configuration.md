# Callback URL Configuration for MyFatoorah Payments

## Overview

The Laravel portal sends `callback_url` and `error_url` in the payment data to the `wodworx-pay` service. The `wodworx-pay` service must use these URLs when calling MyFatoorah's `ExecutePayment` API.

## Payment Data Sent from Laravel Portal

When creating a payment, the Laravel portal sends:

```php
$paymentData = [
    'org_id' => $orgId,
    'payment_method_id' => 2,
    'invoice_value' => 250.00,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'currency_iso' => 'KWD',
    'language' => 'en',
    'callback_url' => 'https://laravel-portal.com/payment/callback?org_id=8&plan=...',
    'error_url' => 'https://laravel-portal.com/payment/callback?org_id=8&plan=...',
    'return_url' => 'https://laravel-portal.com/payment/callback?org_id=8&plan=...',
    'membership_data' => [...],
    // ... other fields
];
```

## wodworx-pay Service Implementation

The `wodworx-pay` service's `executePayment` method should use these URLs:

```php
public function executePayment($paymentData)
{
    try {
        $baseUrl = $this->config['test_mode'] ? $this->config['test_url'] : $this->config['live_url'];
        $apiKey = $this->mfConfig['apiKey'];
        
        $postFields = [
            'PaymentMethodId' => $paymentData['payment_method_id'],
            'InvoiceValue' => $paymentData['invoice_value'],
            'CustomerName' => $paymentData['customer_name'],
            'DisplayCurrencyIso' => $paymentData['currency_iso'] ?? $this->getCurrencyForCountry(...),
            
            // IMPORTANT: Use callback_url from request, fallback to config
            'CallBackUrl' => $paymentData['callback_url'] ?? $this->config['callback_url'],
            'ErrorUrl' => $paymentData['error_url'] ?? $this->config['error_url'],
        ];
        
        // Add optional fields
        if (isset($paymentData['customer_email'])) {
            $postFields['CustomerEmail'] = $paymentData['customer_email'];
        }
        
        if (isset($paymentData['customer_mobile'])) {
            $postFields['CustomerMobile'] = $paymentData['customer_mobile'];
        }
        
        if (isset($paymentData['mobile_country_code'])) {
            $postFields['MobileCountryCode'] = $paymentData['mobile_country_code'];
        }
        
        if (isset($paymentData['language'])) {
            $postFields['Language'] = $paymentData['language'];
        }
        
        if (isset($paymentData['customer_reference'])) {
            $postFields['CustomerReference'] = $paymentData['customer_reference'];
        }
        
        // Add UserDefinedField for custom data
        if (isset($paymentData['user_defined_field'])) {
            $postFields['UserDefinedField'] = is_string($paymentData['user_defined_field']) 
                ? $paymentData['user_defined_field'] 
                : json_encode($paymentData['user_defined_field']);
        }
        
        // Log the data being sent to MyFatoorah
        Log::info('wodworx-pay executePayment: Sending to MyFatoorah ExecutePayment API', [
            'endpoint' => $baseUrl . '/v2/ExecutePayment',
            'callback_url' => $postFields['CallBackUrl'],
            'error_url' => $postFields['ErrorUrl'],
            'invoice_value' => $postFields['InvoiceValue'],
            'customer_name' => $postFields['CustomerName'],
            'payment_method_id' => $postFields['PaymentMethodId'],
            'currency_iso' => $postFields['DisplayCurrencyIso'],
            'full_post_fields' => $postFields, // Log all data sent to MyFatoorah
        ]);
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($baseUrl . '/v2/ExecutePayment', $postFields);
        
        if ($response->failed()) {
            throw new Exception('MyFatoorah ExecutePayment failed: ' . $response->body());
        }
        
        $data = $response->json();
        
        // Extract payment URL and invoice ID
        $paymentUrl = $data['Data']['PaymentURL'] ?? null;
        $invoiceId = $data['Data']['InvoiceId'] ?? null;
        
        if (!$paymentUrl || !$invoiceId) {
            throw new Exception('Invalid response from MyFatoorah ExecutePayment - missing PaymentURL or InvoiceId');
        }
        
        // Log the response from MyFatoorah
        Log::info('wodworx-pay executePayment: MyFatoorah API Response', [
            'org_id' => $paymentData['org_id'] ?? null,
            'invoice_id' => $invoiceId,
            'payment_url' => $paymentUrl,
            'payment_method_id' => $paymentData['payment_method_id'],
            'invoice_value' => $paymentData['invoice_value'],
            'callback_url_sent' => $postFields['CallBackUrl'],
            'error_url_sent' => $postFields['ErrorUrl'],
            'full_response' => $data,
        ]);
        
        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'invoice_id' => $invoiceId,
            'data' => $data,
        ];
        
    } catch (Exception $e) {
        Log::error('wodworx-pay executePayment: MyFatoorah ExecutePayment API Failed', [
            'org_id' => $paymentData['org_id'] ?? null,
            'error' => $e->getMessage(),
            'payment_data' => $paymentData,
            'callback_url_received' => $paymentData['callback_url'] ?? null,
            'error_url_received' => $paymentData['error_url'] ?? null,
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

## Key Points

1. **Priority**: Always use `callback_url` and `error_url` from `$paymentData` if provided
2. **Fallback**: Only use config defaults if not provided in request
3. **Logging**: Log both the received URLs and the URLs sent to MyFatoorah
4. **Verification**: Verify in logs that MyFatoorah receives the Laravel portal URLs, not wodworx-pay URLs

## Expected Behavior

### When Laravel sends callback_url:
- `wodworx-pay` should use: `$paymentData['callback_url']`
- MyFatoorah will call: `https://laravel-portal.com/payment/callback?...`
- ✅ Correct behavior

### When Laravel doesn't send callback_url:
- `wodworx-pay` should use: `$this->config['callback_url']`
- MyFatoorah will call: `https://wodworx-pay-service.com/api/myfatoorah/payment-callback?...`
- ⚠️ This should redirect to Laravel portal

## Logging Requirements

The `wodworx-pay` service should log:

1. **Received URLs**:
   ```php
   Log::info('wodworx-pay: Received payment request', [
       'callback_url_received' => $paymentData['callback_url'] ?? null,
       'error_url_received' => $paymentData['error_url'] ?? null,
   ]);
   ```

2. **URLs sent to MyFatoorah**:
   ```php
   Log::info('wodworx-pay: Sending to MyFatoorah', [
       'callback_url_sent' => $postFields['CallBackUrl'],
       'error_url_sent' => $postFields['ErrorUrl'],
   ]);
   ```

## Testing

1. Check Laravel logs for URLs being sent:
   ```
   MyFatoorah API: Creating payment - Request Data
   callback_url: https://laravel-portal.com/payment/callback?...
   error_url: https://laravel-portal.com/payment/callback?...
   ```

2. Check wodworx-pay logs for URLs being sent to MyFatoorah:
   ```
   wodworx-pay executePayment: Sending to MyFatoorah ExecutePayment API
   callback_url: https://laravel-portal.com/payment/callback?...
   error_url: https://laravel-portal.com/payment/callback?...
   ```

3. Verify MyFatoorah callback goes to Laravel portal, not wodworx-pay




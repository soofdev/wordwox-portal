<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Service to interact with MyFatoorah Payment API from external payment service
 * 
 * This service calls the MyFatoorah payment gateway API hosted on a separate service
 * (wodworx-pay project) to initiate payments for memberships.
 */
class MyFatoorahPaymentApiService
{
    /**
     * Base URL for the payment service API
     */
    protected string $baseUrl;

    /**
     * API token for authentication
     */
    protected string $apiToken;

    /**
     * Timeout for API requests in seconds
     */
    protected int $timeout;

    /**
     * Initialize the service with configuration
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.myfatoorah.base_url', ''), '/');
        $this->apiToken = config('services.myfatoorah.api_token', '');
        $this->timeout = config('services.myfatoorah.timeout', 30);


        if (empty($this->baseUrl)) {
            Log::error('MyFatoorahPaymentApiService: Base URL not configured', [
                'config_value' => config('services.myfatoorah.base_url'),
                'env_value' => env('MYFATOORAH_PAYMENT_SERVICE_URL'),
            ]);
            throw new \RuntimeException('MyFatoorah payment service base URL is not configured');
        }

        // API token is optional - frontend endpoints don't require it
        // Only verify-payment endpoint uses it
    }

    /**
     * Get available payment methods
     * 
     * @param int $orgId Organization ID
     * @param float $invoiceAmount Invoice amount
     * @param string|null $currencyIso Currency ISO code (optional)
     * @return array Response data from the API
     * @throws \Exception If the API request fails
     */
    public function getPaymentMethods(int $orgId, float $invoiceAmount, ?string $currencyIso = null): array
    {
        $endpoint = $this->baseUrl . '/api/myfatoorah/get-payment-methods';

        $payload = [
            'org_id' => $orgId,
            'invoice_amount' => $invoiceAmount,
        ];

        if ($currencyIso) {
            $payload['currency_iso'] = $currencyIso;
        }

        Log::info('MyFatoorah API: Getting payment methods', [
            'endpoint' => $endpoint,
            'org_id' => $orgId,
            'invoice_amount' => $invoiceAmount,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $statusCode,
                ];
            }

            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            throw new \Exception("Failed to get payment methods: {$errorMessage}", $statusCode);

        } catch (\Exception $e) {
            Log::error('MyFatoorah API: Error getting payment methods', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create payment and get payment URL (NO database records created)
     * 
     * @param array $paymentData Payment data (org_id, payment_method_id, invoice_value, customer_name, etc.)
     * @return array Response with payment_url
     * @throws \Exception If the API request fails
     */
    public function createPayment(array $paymentData): array
    {
        $endpoint = $this->baseUrl . '/api/myfatoorah/create-payment';

        // Log all payment data being sent (excluding sensitive data)
        Log::info('MyFatoorah API: Creating payment - Request Data', [
            'endpoint' => $endpoint,
            'org_id' => $paymentData['org_id'] ?? null,
            'payment_method_id' => $paymentData['payment_method_id'] ?? null,
            'invoice_value' => $paymentData['invoice_value'] ?? null,
            'customer_name' => $paymentData['customer_name'] ?? null,
            'customer_email' => $paymentData['customer_email'] ?? null,
            'currency_iso' => $paymentData['currency_iso'] ?? null,
            'language' => $paymentData['language'] ?? null,
            'callback_url' => $paymentData['callback_url'] ?? null,
            'error_url' => $paymentData['error_url'] ?? null,
            'return_url' => $paymentData['return_url'] ?? null,
            'org_plan_id' => $paymentData['org_plan_id'] ?? null,
            'org_user_id' => $paymentData['org_user_id'] ?? null,
            'plan_name' => $paymentData['plan_name'] ?? null,
            'has_membership_data' => isset($paymentData['membership_data']),
            'has_api_token' => !empty($this->apiToken),
            'token_preview' => $this->apiToken ? substr($this->apiToken, 0, 10) . '...' : 'empty',
            'full_payment_data_keys' => array_keys($paymentData),
            'callback_url_provided' => !empty($paymentData['callback_url']),
            'error_url_provided' => !empty($paymentData['error_url']),
        ]);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            
            // Add API token from env/config if available
            if ($this->apiToken) {
                $headers['X-API-Token'] = $this->apiToken;
                Log::info('MyFatoorah API: X-API-Token header added', [
                    'token_length' => strlen($this->apiToken),
                    'token_preview' => substr($this->apiToken, 0, 10) . '...',
                ]);
            } else {
                Log::warning('MyFatoorah API: X-API-Token header NOT added - token is empty');
            }
            
            // Log the exact payload being sent to wodworx-pay service
            Log::info('MyFatoorah API: Sending payment request to wodworx-pay', [
                'endpoint' => $endpoint,
                'payload' => $paymentData,
                'headers' => $headers,
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($endpoint, $paymentData);

            $statusCode = $response->status();
            $responseData = $response->json();
            $responseBody = $response->body();

            Log::info('MyFatoorah API: Payment creation response from wodworx-pay', [
                'status_code' => $statusCode,
                'response' => $responseData,
                'response_body' => $responseBody,
                'endpoint' => $endpoint,
                'payment_url' => $responseData['data']['payment_url'] ?? $responseData['payment_url'] ?? null,
                'payment_id' => $responseData['data']['payment_id'] ?? $responseData['data']['PaymentId'] ?? null,
                'invoice_id' => $responseData['data']['invoice_id'] ?? $responseData['data']['Id'] ?? null,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                    'status_code' => $statusCode,
                ];
            }

            // Better error handling for different status codes
            if ($statusCode === 404) {
                $errorMessage = 'Payment service endpoint not found. Please check the service URL configuration.';
                Log::error('MyFatoorah API: Endpoint not found (404)', [
                    'endpoint' => $endpoint,
                    'base_url' => $this->baseUrl,
                ]);
            } elseif ($statusCode === 401 || $statusCode === 403) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Authentication failed. Please check API token.';
            } elseif ($statusCode >= 500) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Payment service error. Please try again later.';
            } else {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? $responseBody ?? 'Unknown error';
            }
            
            throw new \Exception("Failed to create payment: {$errorMessage}", $statusCode);

        } catch (\Exception $e) {
            Log::error('MyFatoorah API: Error creating payment', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify payment status
     * 
     * @param string $paymentId Payment ID or Invoice ID
     * @param string $paymentType Payment type (PaymentId or InvoiceId)
     * @param int|null $orgId Organization ID (required by API)
     * @return array Payment status information
     * @throws \Exception If the API request fails
     */
    public function verifyPayment(string $paymentId, string $paymentType = 'PaymentId', ?int $orgId = null): array
    {
        $endpoint = $this->baseUrl . '/api/myfatoorah/verify-payment';

        // The API expects 'verification_type' not 'payment_type' and requires 'org_id'
        $payload = [
            'payment_id' => $paymentId,
            'verification_type' => $paymentType,
        ];
        
        // Add org_id if provided (required by API)
        if ($orgId) {
            $payload['org_id'] = $orgId;
        }

        Log::info('MyFatoorah API: Verifying payment', [
            'endpoint' => $endpoint,
            'payment_id' => $paymentId,
            'verification_type' => $paymentType,
            'has_api_token' => !empty($this->apiToken),
            'token_preview' => $this->apiToken ? substr($this->apiToken, 0, 10) . '...' : 'empty',
        ]);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            
            // Add API token from env/config if available
            if ($this->apiToken) {
                $headers['X-API-Token'] = $this->apiToken;
                Log::info('MyFatoorah API: X-API-Token header added', [
                    'token_length' => strlen($this->apiToken),
                    'token_preview' => substr($this->apiToken, 0, 10) . '...',
                ]);
            } else {
                Log::warning('MyFatoorah API: X-API-Token header NOT added - token is empty');
            }
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($endpoint, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();
            $responseBody = $response->body();

            Log::info('MyFatoorah API: Verify payment response', [
                'status_code' => $statusCode,
                'response_data' => $responseData,
                'response_body' => $responseBody,
                'payload_sent' => $payload,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                    'status_code' => $statusCode,
                ];
            }

            // Extract detailed error message
            $errorMessage = $responseData['message'] ?? 
                          $responseData['error'] ?? 
                          ($responseData['errors'] ?? null) ? json_encode($responseData['errors']) : null;
            
            if (!$errorMessage) {
                $errorMessage = $responseBody ?? 'Unknown error';
            }
            
            Log::error('MyFatoorah API: Verify payment failed', [
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
                'response_data' => $responseData,
                'response_body' => $responseBody,
                'payload_sent' => $payload,
            ]);
            
            throw new \Exception("Failed to verify payment: {$errorMessage}", $statusCode);

        } catch (\Exception $e) {
            Log::error('MyFatoorah API: Error verifying payment', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check payment status for a UUID
     * 
     * @param string $uuid Invoice or membership UUID
     * @return array Payment status information
     * @throws \Exception If the API request fails
     */
    public function checkPaymentStatus(string $uuid): array
    {
        $endpoint = $this->baseUrl . '/api/myfatoorah/payment-status';


        Log::info('MyFatoorah API: Checking payment status', [
            'endpoint' => $endpoint,
            'uuid' => $uuid,
            'has_api_token' => !empty($this->apiToken),
            'token_preview' => $this->apiToken ? substr($this->apiToken, 0, 10) . '...' : 'empty',
        ]);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            
            // Add API token from env/config if available
            if ($this->apiToken) {
                $headers['X-API-Token'] = $this->apiToken;
                Log::info('MyFatoorah API: X-API-Token header added', [
                    'token_length' => strlen($this->apiToken),
                    'token_preview' => substr($this->apiToken, 0, 10) . '...',
                ]);
            } else {
                Log::warning('MyFatoorah API: X-API-Token header NOT added - token is empty');
            }
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->get($endpoint, [
                    'uuid' => $uuid,
                ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $statusCode,
                ];
            }

            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            
            Log::error('MyFatoorah API: Payment status check failed', [
                'status_code' => $statusCode,
                'uuid' => $uuid,
                'error' => $errorMessage,
            ]);

            throw new \Exception("MyFatoorah payment status check failed: {$errorMessage}", $statusCode);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('MyFatoorah API: Connection error during status check', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to MyFatoorah payment service: {$e->getMessage()}", 0, $e);

        } catch (\Exception $e) {
            Log::error('MyFatoorah API: Unexpected error during status check', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    
}


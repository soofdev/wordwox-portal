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

        if (empty($this->apiToken)) {
            Log::error('MyFatoorahPaymentApiService: API token not configured');
            throw new \RuntimeException('MyFatoorah payment service API token is not configured');
        }
    }

    /**
     * Initiate a MyFatoorah payment for a membership/invoice
     * 
     * @param string $uuid Invoice or membership UUID
     * @param array $additionalData Optional additional data to send
     * @return array Response data from the API
     * @throws \Exception If the API request fails
     */
    public function initiatePayment(string $uuid, array $additionalData = []): array
    {
        $endpoint = $this->baseUrl . '/api/myfatoorah/initiate-payment';

        $payload = array_merge([
            'uuid' => $uuid,
        ], $additionalData);

        Log::info('MyFatoorah API: Initiating payment', [
            'endpoint' => $endpoint,
            'uuid' => $uuid,
            'has_additional_data' => !empty($additionalData),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Token' => $this->apiToken,
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

            // Handle error response
            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            
          

            throw new \Exception("MyFatoorah payment initiation failed: {$errorMessage}", $statusCode);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('MyFatoorah API: Connection error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to MyFatoorah payment service: {$e->getMessage()}", 0, $e);

        } catch (\Exception $e) {
            Log::error('MyFatoorah API: Unexpected error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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


        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Token' => $this->apiToken,
                ])
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


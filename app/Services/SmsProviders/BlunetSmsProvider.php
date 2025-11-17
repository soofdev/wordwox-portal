<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use App\DataObjects\SmsResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlunetSmsProvider implements SmsProviderInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Send an SMS message via BluNet API.
     */
    public function send(string $from, string $to, string $message, array $options = []): SmsResult
    {
        try {
            // Clean phone number (remove + and spaces, same as Yii2)
            $to = ltrim($to, '+');
            $to = str_replace(' ', '', $to);

            // Build query parameters (matches Yii2 BlunetMessageSendJob)
            $queryParams = [
                'sid' => $from,
                'mno' => $to,
                'text' => $message,
                'type' => $this->config['type'],
                'accesskey' => $this->config['access_key'],
            ];

            $url = $this->config['endpoint'] . '?' . http_build_query($queryParams);

            Log::info('BluNet SMS Request', [
                'url' => $url,
                'to' => $to,
                'from' => $from,
                'message_length' => strlen($message),
            ]);

            // Make GET request (same as Yii2 implementation)
            $response = Http::timeout(30)->get($this->config['endpoint'], $queryParams);

            $httpCode = $response->status();
            $responseBody = $response->body();

            Log::info('BluNet SMS Response', [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'to' => $to,
            ]);

            // Check if successful (matches Yii2 logic: 200 or 201)
            if (in_array($httpCode, [200, 201])) {
                return SmsResult::success(
                    message: 'SMS sent successfully via BluNet',
                    messageId: null, // BluNet doesn't return message ID
                    cost: 40, // Same cost as Yii2 implementation
                    rawResponse: [
                        'body' => $responseBody,
                        'query_params' => $queryParams,
                    ],
                    provider: 'blunet',
                    httpCode: $httpCode
                );
            } else {
                Log::warning('BluNet SMS Failed', [
                    'http_code' => $httpCode,
                    'response' => $responseBody,
                    'to' => $to,
                    'params' => $queryParams,
                ]);

                return SmsResult::failure(
                    message: "BluNet SMS failed with HTTP {$httpCode}: {$responseBody}",
                    rawResponse: [
                        'body' => $responseBody,
                        'query_params' => $queryParams,
                    ],
                    provider: 'blunet',
                    httpCode: $httpCode
                );
            }

        } catch (\Exception $e) {
            Log::error('BluNet SMS Exception', [
                'error' => $e->getMessage(),
                'to' => $to,
                'from' => $from,
            ]);

            return SmsResult::failure(
                message: "BluNet SMS exception: {$e->getMessage()}",
                rawResponse: ['exception' => $e->getMessage()],
                provider: 'blunet'
            );
        }
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return 'blunet';
    }

    /**
     * Validate provider credentials/configuration.
     */
    public function validateCredentials(): bool
    {
        return !empty($this->config['endpoint']) && 
               !empty($this->config['access_key']) && 
               !empty($this->config['type']);
    }

    /**
     * Get sender name for specific organization.
     */
    public function getSenderForOrg(?int $orgId): string
    {
        if ($orgId && isset($this->config['sender_map'][(string)$orgId])) {
            return $this->config['sender_map'][(string)$orgId];
        }

        return $this->config['sender_map']['default'] ?? $this->config['default_sender'];
    }
}
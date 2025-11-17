<?php

namespace App\DataObjects;

class SmsResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $messageId = null,
        public int $cost = 0,
        public array $rawResponse = [],
        public ?string $provider = null,
        public ?int $httpCode = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $message = 'SMS sent successfully',
        ?string $messageId = null,
        int $cost = 0,
        array $rawResponse = [],
        ?string $provider = null,
        ?int $httpCode = null
    ): self {
        return new self(
            success: true,
            message: $message,
            messageId: $messageId,
            cost: $cost,
            rawResponse: $rawResponse,
            provider: $provider,
            httpCode: $httpCode
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        string $message,
        array $rawResponse = [],
        ?string $provider = null,
        ?int $httpCode = null
    ): self {
        return new self(
            success: false,
            message: $message,
            messageId: null,
            cost: 0,
            rawResponse: $rawResponse,
            provider: $provider,
            httpCode: $httpCode
        );
    }

    /**
     * Check if the result indicates success.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the result indicates failure.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the result as an array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'message_id' => $this->messageId,
            'cost' => $this->cost,
            'provider' => $this->provider,
            'http_code' => $this->httpCode,
            'raw_response' => $this->rawResponse,
        ];
    }
}
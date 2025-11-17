<?php

namespace App\Contracts;

use App\DataObjects\SmsResult;

interface SmsProviderInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $from The sender identifier
     * @param string $to The recipient phone number
     * @param string $message The message content
     * @param array $options Additional options for the provider
     * @return SmsResult
     */
    public function send(string $from, string $to, string $message, array $options = []): SmsResult;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Validate provider credentials/configuration.
     *
     * @return bool
     */
    public function validateCredentials(): bool;
}
<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\DataObjects\SmsResult;
use App\Jobs\SendSmsJob;
use App\Models\LogSms;
use App\Models\OrgMsgItem;
use App\Models\OrgUser;
use App\Services\SmsProviders\BlunetSmsProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class SmsService
{
    protected array $config;
    protected array $providers = [];

    public function __construct()
    {
        $this->config = config('sms');
        $this->initializeProviders();
    }

    /**
     * Send an SMS message (queued).
     */
    public function send(
        string $to,
        string $message,
        ?int $orgId = null,
        ?int $orgUserId = null,
        array $options = []
    ): bool {
        try {
            // Check if SMS is globally disabled
            if (!$this->config['service']['enabled']) {
                Log::info('SMS skipped - globally disabled', [
                    'to' => $to,
                    'message' => substr($message, 0, 100),
                    'org_id' => $orgId,
                ]);
                return true;
            }

            // Skip in development if configured
            if (app()->environment('local', 'development') && $this->config['service']['skip_in_dev']) {
                Log::info('SMS skipped in development environment', [
                    'to' => $to,
                    'message' => substr($message, 0, 100),
                    'org_id' => $orgId,
                ]);
                return true;
            }

            // Validate phone number if configured
            if ($this->config['service']['validate_before_send']) {
                if (!$this->validatePhoneNumber($to, $formattedNumber)) {
                    Log::warning('Invalid phone number for SMS', [
                        'phone' => $to,
                        'org_id' => $orgId,
                    ]);
                    return false;
                }
                $to = $formattedNumber;
            }

            // Check country blacklist
            if ($this->isCountryBlacklisted($to)) {
                $countryCode = $this->extractCountryCode($to);
                Log::warning('SMS blocked - country blacklisted', [
                    'phone' => $to,
                    'country_code' => $countryCode,
                    'org_id' => $orgId,
                ]);
                return false;
            }

            // Get preferred provider
            $providerName = $this->getPreferredProvider($to, $orgId);
            
            // Get sender name
            $from = $this->getSenderName($providerName, $orgId);

            // Create message item for tracking (optional)
            $orgMsgItemId = null;
            if (isset($options['create_msg_item']) && $options['create_msg_item']) {
                $orgMsgItem = OrgMsgItem::create([
                    'org_id' => $orgId,
                    'orgUser_id' => $orgUserId,
                    'channel' => 'sms',
                    'subject' => $options['subject'] ?? 'SMS Message',
                    'body' => $message,
                    'status' => 'queued', // Use valid ENUM value
                    'created_by' => auth()->id() ?: 1, // Use default user if not authenticated
                ]);
                $orgMsgItemId = $orgMsgItem->id;
            }

            // Queue the SMS job
            Queue::connection($this->config['queue']['connection'])
                ->pushOn($this->config['queue']['queue'], new SendSmsJob(
                    provider: $providerName,
                    from: $from,
                    to: $to,
                    message: $message,
                    orgId: $orgId,
                    orgUserId: $orgUserId,
                    orgMsgItemId: $orgMsgItemId,
                    options: $options
                ));

            Log::info('SMS queued successfully', [
                'provider' => $providerName,
                'to' => $to,
                'from' => $from,
                'org_id' => $orgId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('SMS service error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'org_id' => $orgId,
            ]);
            return false;
        }
    }

    /**
     * Send SMS immediately (synchronous).
     */
    public function sendNow(
        string $to,
        string $message,
        ?int $orgId = null,
        ?int $orgUserId = null,
        array $options = []
    ): SmsResult {
        try {
            // Check if SMS is globally disabled
            if (!$this->config['service']['enabled']) {
                Log::info('SMS sendNow skipped - globally disabled', [
                    'to' => $to,
                    'message' => substr($message, 0, 100),
                    'org_id' => $orgId,
                ]);
                return SmsResult::success('SMS disabled globally', 0, []);
            }

            // Get preferred provider
            $providerName = $this->getPreferredProvider($to, $orgId);
            $provider = $this->getProvider($providerName);
            
            // Get sender name
            $from = $this->getSenderName($providerName, $orgId);

            // Send SMS
            $result = $provider->send($from, $to, $message, $options);

            // Log the result
            $this->logSmsResult($result, $providerName, $orgId, $orgUserId, $from, $to, $message, $options);

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS send now error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'org_id' => $orgId,
            ]);

            return SmsResult::failure("SMS service error: {$e->getMessage()}");
        }
    }

    /**
     * Get the preferred SMS provider for a phone number and organization.
     */
    public function getPreferredProvider(string $phoneNumber, ?int $orgId = null): string
    {
        $countryCode = $this->extractCountryCode($phoneNumber);
        
        // Check for country-specific routing exceptions
        $exceptions = $this->config['service']['country_routing']['exceptions'] ?? [];
        if ($countryCode && isset($exceptions[$countryCode])) {
            return $exceptions[$countryCode];
        }

        // Return default provider
        return $this->config['service']['country_routing']['default'] ?? $this->config['default'];
    }

    /**
     * Validate and format phone number.
     */
    public function validatePhoneNumber(string $phoneNumber, &$formattedNumber = null): bool
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($phoneNumber, null);

            if ($phoneUtil->isValidNumber($numberProto)) {
                $formattedNumber = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
                return true;
            }

            return false;
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * Check if country is blacklisted.
     */
    public function isCountryBlacklisted(string $phoneNumber): bool
    {
        $countryCode = $this->extractCountryCode($phoneNumber);
        $blacklist = $this->config['service']['blacklisted_countries'] ?? [];
        
        return $countryCode && in_array($countryCode, $blacklist);
    }

    /**
     * Extract country code from phone number.
     */
    protected function extractCountryCode(string $phoneNumber): ?string
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($phoneNumber, null);
            return (string) $numberProto->getCountryCode();
        } catch (NumberParseException $e) {
            return null;
        }
    }

    /**
     * Get sender name for provider and organization.
     */
    protected function getSenderName(string $providerName, ?int $orgId): string
    {
        $provider = $this->getProvider($providerName);
        
        if (method_exists($provider, 'getSenderForOrg')) {
            return $provider->getSenderForOrg($orgId);
        }

        return $this->config['providers'][$providerName]['default_sender'] ?? 'Wodworx';
    }

    /**
     * Get provider instance.
     */
    protected function getProvider(string $providerName): SmsProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new \InvalidArgumentException("SMS provider '{$providerName}' not found");
        }

        return $this->providers[$providerName];
    }

    /**
     * Initialize SMS providers.
     */
    protected function initializeProviders(): void
    {
        foreach ($this->config['providers'] as $name => $config) {
            $this->providers[$name] = match ($config['driver']) {
                'blunet' => new BlunetSmsProvider($config),
                default => throw new \InvalidArgumentException("Unsupported SMS driver: {$config['driver']}")
            };
        }
    }

    /**
     * Log SMS result to database.
     */
    protected function logSmsResult(
        SmsResult $result,
        string $provider,
        ?int $orgId,
        ?int $orgUserId,
        string $from,
        string $to,
        string $message,
        array $options = []
    ): void {
        try {
            LogSms::createFromSmsResult(
                gateway: $provider,
                orgId: $orgId ?? 0,
                orgUserId: $orgUserId,
                from: $from,
                to: $to,
                message: $message,
                status: $result->success ? 'sent' : 'error',
                responseData: $result->rawResponse,
                orgMsgItemId: $options['orgMsgItemId'] ?? null,
                visible: $options['visible'] ?? 'all'
            );
        } catch (\Exception $e) {
            Log::error('Failed to log SMS result', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'to' => $to,
            ]);
        }
    }
}
<?php

namespace App\Services;

use App\Models\OrgUser;
use App\Models\SignatureRequest;
use App\Services\SmsService;
use App\Mail\SignatureRequestMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SignatureRequestService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Create a new signature request.
     */
    public function createRequest(
        OrgUser $orgUser,
        string $method = 'sms',
        int $expirationHours = 48
    ): SignatureRequest {
        // Check if there's already an active request for this user
        $existingRequest = SignatureRequest::where('org_user_id', $orgUser->id)
            ->active()
            ->first();

        if ($existingRequest) {
            // Mark existing request as expired to create a new one
            $existingRequest->markAsExpired();
        }

        // Create new signature request
        $signatureRequest = SignatureRequest::create([
            'org_user_id' => $orgUser->id,
            'org_id' => $orgUser->org_id,
            'method' => $method,
            'status' => 'pending',
            'expires_at' => now()->addHours($expirationHours),
            'created_by' => auth()->id(),
        ]);

        Log::info('Signature request created', [
            'request_id' => $signatureRequest->id,
            'org_user_id' => $orgUser->id,
            'method' => $method,
            'expires_at' => $signatureRequest->expires_at,
        ]);

        return $signatureRequest;
    }

    /**
     * Send signature request via SMS.
     */
    public function sendViaSms(SignatureRequest $signatureRequest): bool
    {
        try {
            $orgUser = $signatureRequest->orgUser;
            
            if (empty($orgUser->fullPhone)) {
                Log::warning('Cannot send SMS - no phone number', [
                    'request_id' => $signatureRequest->id,
                    'org_user_id' => $orgUser->id,
                ]);
                $signatureRequest->markAsFailed();
                return false;
            }

            // Check if SMS is enabled for this organization
            if (!($orgUser->org->orgSettingsFeatures->smsVerificationEnabled ?? false)) {
                Log::warning('Cannot send SMS - SMS not enabled for organization', [
                    'request_id' => $signatureRequest->id,
                    'org_user_id' => $orgUser->id,
                    'org_id' => $orgUser->org_id,
                ]);
                $signatureRequest->markAsFailed();
                return false;
            }

            // Generate public signature URL
            $signatureUrl = $signatureRequest->getPublicUrl();
            
            // Create SMS message
            $message = $this->createSmsMessage($orgUser, $signatureUrl);

            // Send SMS
            $success = $this->smsService->send(
                to: $orgUser->fullPhone,
                message: $message,
                orgId: $orgUser->org_id,
                orgUserId: $orgUser->id,
                options: [
                    'subject' => 'Membership Agreement Signature Request',
                    'create_msg_item' => true,
                    'signature_request_id' => $signatureRequest->id,
                ]
            );

            if ($success) {
                $signatureRequest->markAsSent();
                
                Log::info('Signature request SMS sent', [
                    'request_id' => $signatureRequest->id,
                    'org_user_id' => $orgUser->id,
                    'phone' => $orgUser->fullPhone,
                ]);
            } else {
                $signatureRequest->markAsFailed();
                
                Log::error('Failed to send signature request SMS', [
                    'request_id' => $signatureRequest->id,
                    'org_user_id' => $orgUser->id,
                    'phone' => $orgUser->fullPhone,
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Signature request SMS exception', [
                'request_id' => $signatureRequest->id,
                'error' => $e->getMessage(),
            ]);

            $signatureRequest->markAsFailed();
            return false;
        }
    }

    /**
     * Send signature request via email.
     */
    public function sendViaEmail(SignatureRequest $signatureRequest): bool
    {
        try {
            $orgUser = $signatureRequest->orgUser;
            
            if (empty($orgUser->email)) {
                Log::warning('Cannot send email - no email address', [
                    'request_id' => $signatureRequest->id,
                    'org_user_id' => $orgUser->id,
                ]);
                $signatureRequest->markAsFailed();
                return false;
            }

            // Generate public signature URL
            $signatureUrl = $signatureRequest->getPublicUrl();
            
            // Send email
            Mail::to($orgUser->email)->send(
                new SignatureRequestMail($signatureRequest, $orgUser, $signatureUrl)
            );

            $signatureRequest->markAsSent();
            
            Log::info('Signature request email sent', [
                'request_id' => $signatureRequest->id,
                'org_user_id' => $orgUser->id,
                'email' => $orgUser->email,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Signature request email exception', [
                'request_id' => $signatureRequest->id,
                'error' => $e->getMessage(),
            ]);

            $signatureRequest->markAsFailed();
            return false;
        }
    }

    /**
     * Create and send signature request.
     */
    public function createAndSend(
        OrgUser $orgUser,
        string $method = 'sms',
        int $expirationHours = 48
    ): ?SignatureRequest {
        $signatureRequest = $this->createRequest($orgUser, $method, $expirationHours);

        $success = match ($method) {
            'sms' => $this->sendViaSms($signatureRequest),
            'email' => $this->sendViaEmail($signatureRequest),
            default => false,
        };

        return $success ? $signatureRequest : null;
    }

    /**
     * Validate and retrieve signature request by token.
     */
    public function getByToken(string $token): ?SignatureRequest
    {
        $signatureRequest = SignatureRequest::with(['orgUser.org'])
            ->where('token', $token)
            ->first();

        if (!$signatureRequest) {
            return null;
        }

        // Check if expired - TEMPORARILY DISABLED FOR TESTING
        // if ($signatureRequest->isExpired()) {
        //     $signatureRequest->markAsExpired();
        //     return null;
        // }

        // Mark as viewed if first time accessing
        if ($signatureRequest->status === 'sent') {
            $signatureRequest->markAsViewed();
        }

        return $signatureRequest;
    }

    /**
     * Complete signature request (mark as signed).
     */
    public function completeRequest(SignatureRequest $signatureRequest): void
    {
        $signatureRequest->markAsSigned();

        Log::info('Signature request completed', [
            'request_id' => $signatureRequest->id,
            'org_user_id' => $signatureRequest->org_user_id,
        ]);
    }

    /**
     * Create SMS message for signature request.
     */
    protected function createSmsMessage(OrgUser $orgUser, string $signatureUrl): string
    {
        $gymName = $orgUser->org->name ?? 'Wodworx';
        
        return "Hi {$orgUser->fullName}! Please review and sign your {$gymName} membership agreement: {$signatureUrl} (Link expires in 48 hours)";
    }

    /**
     * Clean up expired signature requests.
     */
    public function cleanupExpiredRequests(): int
    {
        $expiredCount = SignatureRequest::expired()->count();
        
        SignatureRequest::expired()->update(['status' => 'expired']);
        
        Log::info('Cleaned up expired signature requests', [
            'count' => $expiredCount,
        ]);

        return $expiredCount;
    }
}
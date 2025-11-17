<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\OrgUser;
use App\Models\Org;

/**
 * SendPushNotificationJob - Send push notifications using various services
 * 
 * Supports multiple push notification services:
 * - Firebase Cloud Messaging (FCM)
 * - OneSignal
 * - Custom webhook endpoints
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $pushData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $pushData)
    {
        $this->pushData = $pushData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendPushNotificationJob: Processing push notification', [
                'user_id' => $this->pushData['user_id'],
                'headline' => $this->pushData['headline'],
                'template_id' => $this->pushData['template_id'] ?? null,
                'org_id' => $this->pushData['org_id'] ?? null
            ]);

            // Get user and org data
            $orgUser = OrgUser::find($this->pushData['user_id']);
            if (!$orgUser) {
                throw new \Exception('OrgUser not found: ' . $this->pushData['user_id']);
            }

            $org = null;
            if ($this->pushData['org_id']) {
                $org = Org::find($this->pushData['org_id']);
            }

            // Determine push service to use
            $pushService = $this->determinePushService($org);
            
            // Send notification based on service
            switch ($pushService) {
                case 'firebase':
                    $this->sendFirebaseNotification($orgUser, $org);
                    break;
                    
                case 'onesignal':
                    $this->sendOneSignalNotification($orgUser, $org);
                    break;
                    
                case 'webhook':
                    $this->sendWebhookNotification($orgUser, $org);
                    break;
                    
                default:
                    $this->logPushNotification($orgUser, $org);
                    break;
            }

        } catch (\Exception $e) {
            Log::error('SendPushNotificationJob: Failed to send push notification', [
                'user_id' => $this->pushData['user_id'] ?? 'unknown',
                'template_id' => $this->pushData['template_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Determine which push service to use
     */
    private function determinePushService(?Org $org): string
    {
        // Check org-specific push service configuration
        if ($org && !empty($org->push_service)) {
            return $org->push_service;
        }

        // Check global configuration
        $globalService = config('services.push.default_service', 'log');
        
        return $globalService;
    }

    /**
     * Send notification via Firebase Cloud Messaging
     */
    private function sendFirebaseNotification(OrgUser $orgUser, ?Org $org): void
    {
        $fcmServerKey = config('services.firebase.server_key') ?? $org?->fcm_server_key;
        
        if (!$fcmServerKey) {
            throw new \Exception('Firebase server key not configured');
        }

        // Get user's FCM tokens (you'd need to store these in your user model)
        $fcmTokens = $this->getUserFcmTokens($orgUser);
        
        if (empty($fcmTokens)) {
            Log::warning('SendPushNotificationJob: No FCM tokens found for user', [
                'user_id' => $orgUser->id
            ]);
            return;
        }

        $payload = [
            'registration_ids' => $fcmTokens,
            'notification' => [
                'title' => $this->pushData['headline'],
                'body' => $this->pushData['body'],
                'sound' => 'default',
            ],
            'data' => array_merge([
                'template_id' => $this->pushData['template_id'] ?? null,
                'org_id' => $this->pushData['org_id'] ?? null,
            ], $this->pushData['data'] ?? [])
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $fcmServerKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->successful()) {
            Log::info('SendPushNotificationJob: Firebase notification sent successfully', [
                'user_id' => $orgUser->id,
                'tokens_count' => count($fcmTokens),
                'response' => $response->json()
            ]);
        } else {
            throw new \Exception('Firebase API error: ' . $response->body());
        }
    }

    /**
     * Send notification via OneSignal
     */
    private function sendOneSignalNotification(OrgUser $orgUser, ?Org $org): void
    {
        $oneSignalAppId = config('services.onesignal.app_id') ?? $org?->onesignal_app_id;
        $oneSignalApiKey = config('services.onesignal.api_key') ?? $org?->onesignal_api_key;
        
        if (!$oneSignalAppId || !$oneSignalApiKey) {
            throw new \Exception('OneSignal configuration not found');
        }

        // Get user's OneSignal player IDs
        $playerIds = $this->getUserOneSignalIds($orgUser);
        
        if (empty($playerIds)) {
            Log::warning('SendPushNotificationJob: No OneSignal player IDs found for user', [
                'user_id' => $orgUser->id
            ]);
            return;
        }

        $payload = [
            'app_id' => $oneSignalAppId,
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $this->pushData['headline']],
            'contents' => ['en' => $this->pushData['body']],
            'data' => array_merge([
                'template_id' => $this->pushData['template_id'] ?? null,
                'org_id' => $this->pushData['org_id'] ?? null,
            ], $this->pushData['data'] ?? [])
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $oneSignalApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', $payload);

        if ($response->successful()) {
            Log::info('SendPushNotificationJob: OneSignal notification sent successfully', [
                'user_id' => $orgUser->id,
                'player_ids_count' => count($playerIds),
                'response' => $response->json()
            ]);
        } else {
            throw new \Exception('OneSignal API error: ' . $response->body());
        }
    }

    /**
     * Send notification via custom webhook
     */
    private function sendWebhookNotification(OrgUser $orgUser, ?Org $org): void
    {
        $webhookUrl = config('services.push.webhook_url') ?? $org?->push_webhook_url;
        
        if (!$webhookUrl) {
            throw new \Exception('Push webhook URL not configured');
        }

        $payload = [
            'user_id' => $orgUser->id,
            'user_email' => $orgUser->email,
            'user_name' => $orgUser->fullName,
            'org_id' => $this->pushData['org_id'],
            'notification' => [
                'headline' => $this->pushData['headline'],
                'subtitle' => $this->pushData['subtitle'] ?? '',
                'body' => $this->pushData['body'],
            ],
            'data' => $this->pushData['data'] ?? [],
            'template_id' => $this->pushData['template_id'] ?? null,
            'timestamp' => now()->toISOString()
        ];

        $response = Http::timeout(30)->post($webhookUrl, $payload);

        if ($response->successful()) {
            Log::info('SendPushNotificationJob: Webhook notification sent successfully', [
                'user_id' => $orgUser->id,
                'webhook_url' => $webhookUrl,
                'response_status' => $response->status()
            ]);
        } else {
            throw new \Exception('Webhook error: ' . $response->status() . ' - ' . $response->body());
        }
    }

    /**
     * Log push notification (fallback when no service is configured)
     */
    private function logPushNotification(OrgUser $orgUser, ?Org $org): void
    {
        Log::info('SendPushNotificationJob: Push notification prepared (logging only)', [
            'user_id' => $orgUser->id,
            'user_name' => $orgUser->fullName,
            'user_email' => $orgUser->email,
            'org_id' => $this->pushData['org_id'],
            'headline' => $this->pushData['headline'],
            'subtitle' => $this->pushData['subtitle'] ?? '',
            'body' => $this->pushData['body'],
            'data' => $this->pushData['data'] ?? [],
            'template_id' => $this->pushData['template_id'] ?? null,
            'note' => 'Push notification service not configured - logged only'
        ]);
    }

    /**
     * Get user's FCM tokens (placeholder - implement based on your user model)
     */
    private function getUserFcmTokens(OrgUser $orgUser): array
    {
        // TODO: Implement based on your user model structure
        // This might be stored in a separate table or as JSON in user model
        
        // Example implementation:
        // return $orgUser->fcm_tokens ?? [];
        
        // For now, return empty array
        return [];
    }

    /**
     * Get user's OneSignal player IDs (placeholder - implement based on your user model)
     */
    private function getUserOneSignalIds(OrgUser $orgUser): array
    {
        // TODO: Implement based on your user model structure
        // This might be stored in a separate table or as JSON in user model
        
        // Example implementation:
        // return $orgUser->onesignal_player_ids ?? [];
        
        // For now, return empty array
        return [];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushNotificationJob: Job failed permanently', [
            'user_id' => $this->pushData['user_id'] ?? 'unknown',
            'template_id' => $this->pushData['template_id'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}







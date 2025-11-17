<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\OrgUserPlanHold;
use App\Models\OrgUserPlan;
use App\Models\OrgUser;
use App\Mail\HoldNotificationMail;

class SendHoldNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $holdId;
    public $notificationType; // 'created', 'ended', 'cancelled'
    public $sendEmail;
    public $sendPush;

    /**
     * Create a new job instance.
     */
    public function __construct($holdId, $notificationType = 'created', $sendEmail = false, $sendPush = false)
    {
        $this->holdId = $holdId;
        $this->notificationType = $notificationType;
        $this->sendEmail = $sendEmail;
        $this->sendPush = $sendPush;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find the hold record
            $hold = OrgUserPlanHold::find($this->holdId);

            if (!$hold) {
                Log::warning('SendHoldNotificationJob: Hold not found', [
                    'hold_id' => $this->holdId
                ]);
                return;
            }

            $membership = $hold->orgUserPlan;
            $orgUser = $membership->orgUser ?? null;

            if (!$orgUser) {
                Log::warning('SendHoldNotificationJob: OrgUser not found', [
                    'hold_id' => $this->holdId,
                    'membership_id' => $membership->id
                ]);
                return;
            }

            $org = $orgUser->org;

            Log::info('SendHoldNotificationJob: Processing notification', [
                'hold_id' => $this->holdId,
                'member_name' => $orgUser->fullName,
                'notification_type' => $this->notificationType,
                'send_email' => $this->sendEmail,
                'send_push' => $this->sendPush
            ]);

            // Send email notification using HoldNotificationMail
            if ($this->sendEmail && $orgUser->email) {
                $this->sendEmailNotification($hold, $membership, $orgUser, $org);
            }

            // Send push notification (simplified version)
            if ($this->sendPush) {
                $this->sendPushNotification($hold, $membership, $orgUser, $org);
            }

        } catch (\Exception $e) {
            Log::error('SendHoldNotificationJob: Exception occurred', [
                'hold_id' => $this->holdId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Send email notification using HoldNotificationMail
     */
    private function sendEmailNotification($hold, $membership, $orgUser, $org): void
    {
        try {
            $emailData = [
                'member_name' => $orgUser->fullName,
                'plan_name' => $membership->orgPlan->name ?? 'Unknown Plan',
                'start_date' => is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('M d, Y') : $hold->startDateTime->format('M d, Y'),
                'end_date' => is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('M d, Y') : $hold->endDateTime->format('M d, Y'),
                'notification_type' => $this->notificationType,
                'hold_note' => $hold->note ?? '',
                'org_name' => $org->name ?? 'Your Gym'
            ];

            Mail::to($orgUser->email)->send(new HoldNotificationMail($emailData));

            Log::info('SendHoldNotificationJob: Email sent successfully', [
                'hold_id' => $this->holdId,
                'email' => $orgUser->email,
                'notification_type' => $this->notificationType
            ]);

        } catch (\Exception $e) {
            Log::error('SendHoldNotificationJob: Failed to send email', [
                'hold_id' => $this->holdId,
                'email' => $orgUser->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send push notification (simplified version)
     */
    private function sendPushNotification($hold, $membership, $orgUser, $org): void
    {
        try {
            // For now, just log the push notification
            // In a real implementation, you would integrate with a push notification service
            Log::info('SendHoldNotificationJob: Push notification logged', [
                'hold_id' => $this->holdId,
                'user_id' => $orgUser->id,
                'member_name' => $orgUser->fullName,
                'notification_type' => $this->notificationType,
                'message' => $this->getPushNotificationMessage($hold, $membership, $orgUser, $org)
            ]);

        } catch (\Exception $e) {
            Log::error('SendHoldNotificationJob: Failed to send push notification', [
                'hold_id' => $this->holdId,
                'user_id' => $orgUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get push notification message
     */
    private function getPushNotificationMessage($hold, $membership, $orgUser, $org): string
    {
        $orgName = $org->name ?? 'Your Gym';
        $planName = $membership->orgPlan->name ?? 'Unknown Plan';
        $startDate = is_string($hold->startDateTime) ? \Carbon\Carbon::parse($hold->startDateTime)->format('M d, Y') : $hold->startDateTime->format('M d, Y');
        $endDate = is_string($hold->endDateTime) ? \Carbon\Carbon::parse($hold->endDateTime)->format('M d, Y') : $hold->endDateTime->format('M d, Y');

        return match($this->notificationType) {
            'created' => "Your {$planName} membership has been put on hold from {$startDate} to {$endDate}.",
            'ended' => "Your {$planName} membership hold has ended. Your membership is now active.",
            'cancelled' => "Your {$planName} membership hold has been cancelled.",
            'modified' => "Your {$planName} membership hold has been updated.",
            default => "Your membership hold has been updated."
        };
    }

}

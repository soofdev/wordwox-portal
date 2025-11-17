<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Org;
use App\Models\NotificationTemplate;
use App\Jobs\SendTemplateEmailJob;
use App\Jobs\SendTemplateSmsJob;
use App\Jobs\SendPushNotificationJob;

/**
 * NotificationsHelper - Handles template-based notifications
 * 
 * Based on the Box project's notification system, adapted for Laravel.
 * Supports email, SMS, and push notifications with template placeholders.
 */
class NotificationsHelper
{
    /**
     * @var Org|null
     */
    private $org;

    /**
     * @var NotificationTemplate|null
     */
    public $template;

    /**
     * Default email templates
     */
    public $htmlTemplate = "emails.custom-template-html";
    public $textTemplate = "emails.custom-template-text";

    /**
     * Email sender information
     */
    public $fromEmail;
    public $fromName;

    /**
     * NotificationsHelper constructor
     *
     * @param Org|null $org
     */
    public function __construct(Org $org = null)
    {
        $this->fromEmail = config('mail.from.address');
        $this->fromName = config('mail.from.name');
        $this->org = $org;

        // Override with org-specific settings if available
        if ($this->org) {
            $this->fromEmail = $this->org->email ?? $this->fromEmail;
            $this->fromName = $this->org->nameOnApp ?? $this->org->name ?? $this->fromName;
        }
    }

    /**
     * Find and load a notification template
     *
     * @param string $slug Template slug identifier
     * @return NotificationTemplate|null
     */
    public function checkTemplate(string $slug): ?NotificationTemplate
    {
        $query = NotificationTemplate::where('slug', $slug);

        // First try to find org-specific template
        if ($this->org) {
            $template = (clone $query)->where('org_id', $this->org->id)->first();
            if ($template) {
                return $this->template = $template;
            }
        }

        // Fall back to global template
        $template = $query->whereNull('org_id')->first();
        return $this->template = $template;
    }

    /**
     * Send email notification using template
     *
     * @param string $to Recipient email address
     * @param array $placeholders Key-value pairs for template replacement
     * @param string|null $templateSlug Optional template slug (if not already loaded)
     * @return bool
     */
    public function sendEmail(string $to, array $placeholders, string $templateSlug = null): bool
    {
        // Load template if slug provided
        if ($templateSlug) {
            $this->checkTemplate($templateSlug);
        }

        if (!$this->template) {
            Log::warning('NotificationsHelper: No template found for email', [
                'to' => $to,
                'template_slug' => $templateSlug,
                'org_id' => $this->org?->id
            ]);
            return false;
        }

        // Check if email template content exists
        if (empty($this->template->emailSubject) || (empty($this->template->emailBodyHtml) && empty($this->template->emailBodyText))) {
            Log::warning('NotificationsHelper: Template has no email content', [
                'template_id' => $this->template->id,
                'slug' => $this->template->slug
            ]);
            return false;
        }

        try {
            // Prepare template content with placeholders
            $subject = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->emailSubject
            );
            
            $htmlData = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->emailBodyHtml
            );
            
            $textData = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->emailBodyText
            );

            // Dispatch email job
            SendTemplateEmailJob::dispatch([
                'org_id' => $this->org?->id,
                'template_html' => $this->htmlTemplate,
                'template_text' => $this->textTemplate,
                'subject' => $subject,
                'fromEmail' => $this->fromEmail,
                'fromName' => $this->fromName,
                'to' => $to,
                'htmlData' => $htmlData,
                'textData' => $textData,
                'template_id' => $this->template->id
            ]);

            Log::info('NotificationsHelper: Email notification dispatched', [
                'to' => $to,
                'subject' => $subject,
                'template_id' => $this->template->id,
                'org_id' => $this->org?->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('NotificationsHelper: Failed to send email', [
                'to' => $to,
                'template_id' => $this->template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification using template
     *
     * @param string $to Recipient phone number
     * @param array $placeholders Key-value pairs for template replacement
     * @param string|null $templateSlug Optional template slug (if not already loaded)
     * @return bool
     */
    public function sendSMS(string $to, array $placeholders, string $templateSlug = null): bool
    {
        // Load template if slug provided
        if ($templateSlug) {
            $this->checkTemplate($templateSlug);
        }

        if (!$this->template) {
            Log::warning('NotificationsHelper: No template found for SMS', [
                'to' => $to,
                'template_slug' => $templateSlug,
                'org_id' => $this->org?->id
            ]);
            return false;
        }

        // Check if SMS template content exists
        if (empty($this->template->smsBody)) {
            Log::warning('NotificationsHelper: Template has no SMS content', [
                'template_id' => $this->template->id,
                'slug' => $this->template->slug
            ]);
            return false;
        }

        try {
            // Prepare SMS message with placeholders
            $message = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->smsBody
            );

            // Dispatch SMS job
            SendTemplateSmsJob::dispatch([
                'from' => config('sms.from_name', 'Wodworx'),
                'to' => $to,
                'message' => $message,
                'template_id' => $this->template->id,
                'org_id' => $this->org?->id
            ]);

            Log::info('NotificationsHelper: SMS notification dispatched', [
                'to' => $to,
                'template_id' => $this->template->id,
                'org_id' => $this->org?->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('NotificationsHelper: Failed to send SMS', [
                'to' => $to,
                'template_id' => $this->template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send push notification using template
     *
     * @param int $userId User ID to send push notification to
     * @param array $placeholders Key-value pairs for template replacement
     * @param string|null $templateSlug Optional template slug (if not already loaded)
     * @param array $additionalData Additional data to include in push payload
     * @return bool
     */
    public function sendPushNotification(int $userId, array $placeholders, string $templateSlug = null, array $additionalData = []): bool
    {
        // Load template if slug provided
        if ($templateSlug) {
            $this->checkTemplate($templateSlug);
        }

        if (!$this->template) {
            Log::warning('NotificationsHelper: No template found for push notification', [
                'user_id' => $userId,
                'template_slug' => $templateSlug,
                'org_id' => $this->org?->id
            ]);
            return false;
        }

        // Check if push template content exists
        if (empty($this->template->pushHeadline) && empty($this->template->pushBody)) {
            Log::warning('NotificationsHelper: Template has no push content', [
                'template_id' => $this->template->id,
                'slug' => $this->template->slug
            ]);
            return false;
        }

        try {
            // Prepare push notification content with placeholders
            $headline = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->pushHeadline ?? ''
            );
            
            $subtitle = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->pushSubtitle ?? ''
            );
            
            $body = $this->prepareTemplateContent(
                $this->template->placeholder, 
                $placeholders, 
                $this->template->pushBody ?? ''
            );

            // Dispatch push notification job
            SendPushNotificationJob::dispatch([
                'user_id' => $userId,
                'org_id' => $this->org?->id,
                'headline' => $headline,
                'subtitle' => $subtitle,
                'body' => $body,
                'data' => $additionalData,
                'template_id' => $this->template->id
            ]);

            Log::info('NotificationsHelper: Push notification dispatched', [
                'user_id' => $userId,
                'headline' => $headline,
                'template_id' => $this->template->id,
                'org_id' => $this->org?->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('NotificationsHelper: Failed to send push notification', [
                'user_id' => $userId,
                'template_id' => $this->template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Prepare template content by replacing placeholders with actual values
     *
     * @param string|array $templatePlaceholders JSON string or array of allowed placeholders
     * @param array $placeholderValues Actual values to replace placeholders with
     * @param string $text Template text content
     * @return string
     */
    public function prepareTemplateContent($templatePlaceholders, array $placeholderValues, string $text): string
    {
        // Decode JSON if it's a string
        if (is_string($templatePlaceholders)) {
            $templatePlaceholders = json_decode($templatePlaceholders, true) ?? [];
        }

        // Validate placeholders against allowed ones
        $validatedPlaceholders = $this->validatePlaceholders($templatePlaceholders, $placeholderValues);
        
        // Replace placeholders in text
        return $this->replacePlaceholders($text, $validatedPlaceholders);
    }

    /**
     * Replace placeholders with values in text
     *
     * @param string $text Text containing placeholders
     * @param array $placeholders Key-value pairs for replacement
     * @return string
     */
    public function replacePlaceholders(string $text, array $placeholders): string
    {
        if (empty($placeholders)) {
            return $text;
        }

        foreach ($placeholders as $search => $replace) {
            // Case-insensitive replacement
            $text = str_ireplace($search, $replace ?? '', $text);
        }

        return $text;
    }

    /**
     * Validate placeholders against allowed template placeholders
     *
     * @param array $allowedPlaceholders Array of allowed placeholder definitions
     * @param array $providedPlaceholders Key-value pairs provided for replacement
     * @return array Filtered placeholders that are allowed
     */
    public function validatePlaceholders(array $allowedPlaceholders, array $providedPlaceholders): array
    {
        if (empty($allowedPlaceholders)) {
            return [];
        }

        // Extract allowed placeholder names
        $allowedNames = [];
        foreach ($allowedPlaceholders as $placeholder) {
            if (is_array($placeholder) && isset($placeholder['name'])) {
                $allowedNames[$placeholder['name']] = true;
            } elseif (is_string($placeholder)) {
                $allowedNames[$placeholder] = true;
            }
        }

        // Filter provided placeholders to only include allowed ones
        $validatedPlaceholders = [];
        foreach ($providedPlaceholders as $key => $value) {
            if (isset($allowedNames[$key])) {
                $validatedPlaceholders[$key] = $value;
            }
        }

        return $validatedPlaceholders;
    }

    /**
     * Send notification using multiple channels
     *
     * @param array $channels Array of channels: ['email', 'sms', 'push']
     * @param string $templateSlug Template slug to use
     * @param array $recipients Recipients data
     * @param array $placeholders Placeholder values
     * @return array Results of each channel
     */
    public function sendMultiChannelNotification(array $channels, string $templateSlug, array $recipients, array $placeholders): array
    {
        $this->checkTemplate($templateSlug);
        $results = [];

        if (!$this->template) {
            return ['error' => 'Template not found'];
        }

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    if (!empty($recipients['email'])) {
                        $results['email'] = $this->sendEmail($recipients['email'], $placeholders);
                    }
                    break;

                case 'sms':
                    if (!empty($recipients['phone'])) {
                        $results['sms'] = $this->sendSMS($recipients['phone'], $placeholders);
                    }
                    break;

                case 'push':
                    if (!empty($recipients['user_id'])) {
                        $results['push'] = $this->sendPushNotification(
                            $recipients['user_id'], 
                            $placeholders, 
                            null, 
                            $recipients['push_data'] ?? []
                        );
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Get available templates for the current organization
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableTemplates()
    {
        $query = NotificationTemplate::query();

        if ($this->org) {
            // Get org-specific templates and global templates
            $query->where(function($q) {
                $q->where('org_id', $this->org->id)
                  ->orWhereNull('org_id');
            });
        } else {
            // Only global templates
            $query->whereNull('org_id');
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Test notification system with sample data
     *
     * @param string $channel Channel to test: 'email', 'sms', or 'push'
     * @param string $templateSlug Template slug to test
     * @param array $testRecipients Test recipient data
     * @return bool
     */
    public function testNotification(string $channel, string $templateSlug, array $testRecipients): bool
    {
        $testPlaceholders = [
            '[USER FULL NAME]' => 'Test User',
            '[ORG NAME]' => $this->org?->name ?? 'Test Gym',
            '[SUPPORT EMAIL]' => $this->org?->email ?? 'support@example.com',
            '[PLAN NAME]' => 'Test Plan',
            '[START DATE]' => now()->addDays(7)->format('M d, Y'),
            '[END DATE]' => now()->addDays(12)->format('M d, Y'),
        ];

        switch ($channel) {
            case 'email':
                return $this->sendEmail($testRecipients['email'] ?? 'test@example.com', $testPlaceholders, $templateSlug);
            
            case 'sms':
                return $this->sendSMS($testRecipients['phone'] ?? '+1234567890', $testPlaceholders, $templateSlug);
            
            case 'push':
                return $this->sendPushNotification($testRecipients['user_id'] ?? 1, $testPlaceholders, $templateSlug);
            
            default:
                return false;
        }
    }
}








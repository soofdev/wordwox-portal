<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NotificationTemplate Model
 * 
 * Stores notification templates for email, SMS, and push notifications
 * with support for placeholder replacement and multi-channel delivery.
 */
class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notificationTemplate';

    protected $fillable = [
        'uuid',
        'slug',
        'org_id',
        'name',
        'emailSubject',
        'emailBodyHtml',
        'emailBodyText',
        'pushHeadline',
        'pushSubtitle',
        'pushBody',
        'smsBody',
        'placeholder'
    ];

    protected $casts = [
        'placeholder' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'placeholder' => '[]'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID on creation
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Str::uuid();
            }
        });

        // Auto-generate text version from HTML before saving
        static::saving(function ($model) {
            if ($model->emailBodyHtml && !$model->emailBodyText) {
                $model->emailBodyText = $model->convertHtmlToText($model->emailBodyHtml);
            }
        });
    }

    /**
     * Get the organization that owns this template
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Scope to get global templates (no org_id)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('org_id');
    }

    /**
     * Scope to get templates for specific organization
     */
    public function scopeForOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Check if template has email content
     */
    public function hasEmailContent(): bool
    {
        return !empty($this->emailSubject) && (!empty($this->emailBodyHtml) || !empty($this->emailBodyText));
    }

    /**
     * Check if template has SMS content
     */
    public function hasSmsContent(): bool
    {
        return !empty($this->smsBody);
    }

    /**
     * Check if template has push notification content
     */
    public function hasPushContent(): bool
    {
        return !empty($this->pushHeadline) || !empty($this->pushBody);
    }

    /**
     * Get available channels for this template
     */
    public function getAvailableChannels(): array
    {
        $channels = [];
        
        if ($this->hasEmailContent()) {
            $channels[] = 'email';
        }
        
        if ($this->hasSmsContent()) {
            $channels[] = 'sms';
        }
        
        if ($this->hasPushContent()) {
            $channels[] = 'push';
        }
        
        return $channels;
    }

    /**
     * Validate that required placeholders are present in content
     */
    public function validateRequiredPlaceholders(): array
    {
        $missingPlaceholders = [];
        
        if (!is_array($this->placeholder)) {
            return $missingPlaceholders;
        }

        foreach ($this->placeholder as $placeholder) {
            if (!is_array($placeholder) || !isset($placeholder['required']) || !$placeholder['required']) {
                continue;
            }

            $placeholderName = $placeholder['name'] ?? '';
            if (empty($placeholderName)) {
                continue;
            }

            // Check email content
            if ($this->hasEmailContent()) {
                $emailContent = $this->emailBodyHtml . ' ' . $this->emailSubject;
                if (stripos($emailContent, $placeholderName) === false) {
                    $missingPlaceholders['email'][] = $placeholderName;
                }
            }

            // Check SMS content
            if ($this->hasSmsContent()) {
                if (stripos($this->smsBody, $placeholderName) === false) {
                    $missingPlaceholders['sms'][] = $placeholderName;
                }
            }

            // Check push content
            if ($this->hasPushContent()) {
                $pushContent = $this->pushHeadline . ' ' . $this->pushSubtitle . ' ' . $this->pushBody;
                if (stripos($pushContent, $placeholderName) === false) {
                    $missingPlaceholders['push'][] = $placeholderName;
                }
            }
        }

        return $missingPlaceholders;
    }

    /**
     * Convert HTML content to plain text
     */
    public function convertHtmlToText(string $htmlContent): string
    {
        // Extract URLs from links and append them after the link text
        $withUrls = preg_replace_callback('/<a\s+[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', function($matches) {
            $url = $matches[1];
            $text = trim($matches[2]);
            return $text . " (" . $url . ")";
        }, $htmlContent);

        // Replace <br> tags and <div> tags with newline characters
        $withLineBreaks = preg_replace('/<br\s*\/?>|<\/div>/i', "\n", $withUrls);

        // Strip all other HTML tags
        $strippedHtml = strip_tags($withLineBreaks);

        // Decode HTML entities
        $decodedHtml = html_entity_decode($strippedHtml);

        // Split into lines, trim each line, and collapse multiple empty lines
        $lines = explode("\n", $decodedHtml);
        $formattedLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '' && end($formattedLines) === '') {
                // Skip consecutive empty lines
                continue;
            }
            $formattedLines[] = $trimmedLine;
        }

        // Re-combine the lines
        return implode("\n", $formattedLines);
    }

    /**
     * Get default placeholders for hold notifications
     */
    public static function getHoldNotificationPlaceholders(): array
    {
        return [
            [
                'name' => '[USER FULL NAME]',
                'description' => 'Full name of the member',
                'required' => true,
                'example' => 'John Doe'
            ],
            [
                'name' => '[ORG NAME]',
                'description' => 'Name of the organization/gym',
                'required' => true,
                'example' => 'SuperHero CrossFit'
            ],
            [
                'name' => '[PLAN NAME]',
                'description' => 'Name of the membership plan',
                'required' => true,
                'example' => 'Unlimited Monthly'
            ],
            [
                'name' => '[START DATE]',
                'description' => 'Hold start date',
                'required' => true,
                'example' => 'Dec 20, 2025'
            ],
            [
                'name' => '[END DATE]',
                'description' => 'Hold end date',
                'required' => true,
                'example' => 'Dec 25, 2025'
            ],
            [
                'name' => '[HOLD REASON]',
                'description' => 'Reason for the hold',
                'required' => false,
                'example' => 'Vacation hold'
            ],
            [
                'name' => '[SUPPORT EMAIL]',
                'description' => 'Support email address',
                'required' => false,
                'example' => 'support@superhero.com'
            ],
            [
                'name' => '[HOLD DURATION]',
                'description' => 'Duration of the hold in days',
                'required' => false,
                'example' => '5 days'
            ]
        ];
    }

    /**
     * Create default hold notification templates
     */
    public static function createDefaultHoldTemplates(?int $orgId = null): array
    {
        $placeholders = self::getHoldNotificationPlaceholders();
        $templates = [];

        // Hold Created Template
        $templates[] = self::create([
            'slug' => 'hold_created',
            'org_id' => $orgId,
            'name' => 'Membership Hold Created',
            'emailSubject' => 'Your [PLAN NAME] Membership Has Been Put On Hold',
            'emailBodyHtml' => '
                <h2>Hello [USER FULL NAME],</h2>
                <p>This email confirms that your <strong>[PLAN NAME]</strong> membership has been put on hold.</p>
                <p><strong>Hold Details:</strong></p>
                <ul>
                    <li>Start Date: [START DATE]</li>
                    <li>End Date: [END DATE]</li>
                    <li>Duration: [HOLD DURATION]</li>
                </ul>
                <p><strong>Reason:</strong> [HOLD REASON]</p>
                <p>During this period, your membership will be paused and the hold duration will be added to your membership end date.</p>
                <p>If you have any questions, please contact us at [SUPPORT EMAIL].</p>
                <p>Thank you,<br>The [ORG NAME] Team</p>
            ',
            'pushHeadline' => 'Membership Hold Created',
            'pushBody' => 'Your [PLAN NAME] has been put on hold from [START DATE] to [END DATE].',
            'smsBody' => 'Hi [USER FULL NAME], your [PLAN NAME] membership has been put on hold from [START DATE] to [END DATE]. Contact [SUPPORT EMAIL] for questions.',
            'placeholder' => $placeholders
        ]);

        // Hold Ended Template
        $templates[] = self::create([
            'slug' => 'hold_ended',
            'org_id' => $orgId,
            'name' => 'Membership Hold Ended',
            'emailSubject' => 'Your [PLAN NAME] Hold Has Been Ended',
            'emailBodyHtml' => '
                <h2>Hello [USER FULL NAME],</h2>
                <p>This email confirms that your <strong>[PLAN NAME]</strong> membership hold has been ended.</p>
                <p>Your membership is now <strong>active</strong> again and you can resume using all services.</p>
                <p>The hold duration has been added to your membership end date.</p>
                <p>If you have any questions, please contact us at [SUPPORT EMAIL].</p>
                <p>Thank you,<br>The [ORG NAME] Team</p>
            ',
            'pushHeadline' => 'Membership Hold Ended',
            'pushBody' => 'Your [PLAN NAME] hold has been ended. Your membership is now active.',
            'smsBody' => 'Hi [USER FULL NAME], your [PLAN NAME] hold has been ended. Your membership is now active. Contact [SUPPORT EMAIL] for questions.',
            'placeholder' => $placeholders
        ]);

        // Hold Cancelled Template
        $templates[] = self::create([
            'slug' => 'hold_cancelled',
            'org_id' => $orgId,
            'name' => 'Membership Hold Cancelled',
            'emailSubject' => 'Your [PLAN NAME] Hold Has Been Cancelled',
            'emailBodyHtml' => '
                <h2>Hello [USER FULL NAME],</h2>
                <p>This email confirms that your <strong>[PLAN NAME]</strong> membership hold scheduled from [START DATE] to [END DATE] has been cancelled.</p>
                <p>Your membership remains <strong>active</strong> and you can continue using all services.</p>
                <p>If you have any questions, please contact us at [SUPPORT EMAIL].</p>
                <p>Thank you,<br>The [ORG NAME] Team</p>
            ',
            'pushHeadline' => 'Membership Hold Cancelled',
            'pushBody' => 'Your [PLAN NAME] hold from [START DATE] to [END DATE] has been cancelled.',
            'smsBody' => 'Hi [USER FULL NAME], your [PLAN NAME] hold from [START DATE] to [END DATE] has been cancelled. Contact [SUPPORT EMAIL] for questions.',
            'placeholder' => $placeholders
        ]);

        // Hold Modified Template
        $templates[] = self::create([
            'slug' => 'hold_modified',
            'org_id' => $orgId,
            'name' => 'Membership Hold Modified',
            'emailSubject' => 'Your [PLAN NAME] Hold Dates Have Been Updated',
            'emailBodyHtml' => '
                <h2>Hello [USER FULL NAME],</h2>
                <p>This email confirms that your <strong>[PLAN NAME]</strong> membership hold dates have been updated.</p>
                <p><strong>New Hold Details:</strong></p>
                <ul>
                    <li>Start Date: [START DATE]</li>
                    <li>End Date: [END DATE]</li>
                    <li>Duration: [HOLD DURATION]</li>
                </ul>
                <p><strong>Reason:</strong> [HOLD REASON]</p>
                <p>Please review the new dates. Your membership will be paused during this updated period.</p>
                <p>If you have any questions, please contact us at [SUPPORT EMAIL].</p>
                <p>Thank you,<br>The [ORG NAME] Team</p>
            ',
            'pushHeadline' => 'Membership Hold Updated',
            'pushBody' => 'Your [PLAN NAME] hold dates have been updated to [START DATE] - [END DATE].',
            'smsBody' => 'Hi [USER FULL NAME], your [PLAN NAME] hold dates have been updated to [START DATE] - [END DATE]. Contact [SUPPORT EMAIL] for questions.',
            'placeholder' => $placeholders
        ]);

        return $templates;
    }
}




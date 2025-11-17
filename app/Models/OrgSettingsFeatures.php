<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgSettingsFeatures extends BaseWWModel
{
    use HasFactory;

    protected $table = 'orgSettingsFeatures';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id',
        'freeClassesEnabled',
        'quotaModeEnabled',
        'isMultiLocationEnabled',
        'groupsEnabled',
        'genresEnabled',
        'roomsEnabled',
        'coachesEnabled',
        'payrollEnabled',
        'payrollRatesAdvanced',
        'paymentsEnabled',
        'workoutsEnabled',
        'workoutPlansEnabled',
        'workoutProgramsEnabled',
        'workoutBuilderEnabled',
        'isHoldEnabled',
        'portalEnabled',
        'portalAccessSchedules',
        'portalAccessPlans',
        'smsVerificationEnabled',
        'smsGatewayPreferred',
        'smsBlacklistCountry',
        'sharedPlansEnabled',
        'upchargePlansEnabled',
        'accessControlEnabled',
        'accessControlMultiDoorEnabled',
        'accessControlGroupsEnabled',
        'orgPlanTimeSlotsEnabled',
        'orgPlanRatesEnabled',
        'orgPlanRevShareDropInEnabled',
        'orgPlanRevShareWODEnabled',
        'orgPlanRevSharePTEnabled',
        'orgPlanRevShareGXEnabled',
        'isOrgPlanCategoryCrudEnabled',
        'notificationTemplatesEnabled',
        'isCallReminderEnabled',
        'isPtScheduleEnabled',
        'isMarketingEnabled',
        'isMarketingMsgEnabled',
        'isMarketingSMSEnabled',
        'isOrgAdsEnabled',
        'isMarketingPromoEnabled',
        'isInvoiceRefundsEnabled',
        'isWhiteLabelAppEnabled',
        'orgUserPlanPreferencesEnabled',
        'isInvoicingEnabled',
        'isZoomEnabled',
        'isWaitlistEnabled',
        'isLateCancelEnabled',
        'isNoShowsEnabled',
        'isAppsEnabled',
        'isSubscriberSelfSignInEnabled',
        'isAssignmentSelfSignInEnabled',
        'isPTEventEnabled',
        'isEventCapacityEnabled',
        'isEventEnabled',
        'isSchedulePreReserveEnabled',
        'isScheduleTimeUpdateEnabled',
        'isScheduleOverlapEnabled',
        'isScheduleEnabled',
        'isReportsEnabled',
        'isInsightsEnabled',
        'isUserPlanUpgradeEnabled',
        'isUserPlanTransferEnabled',
        'isUserPlanDowngradeEnabled',
        'isPlanDiscountEnabled',
        'isPlanDiscountPermissionsEnabled',
        'isPlanPermissionsEnabled',
        'isFamiliesEnabled',
        'AppleAppStoreUrl',
        'GooglePlayStoreUrl',
        'orgNetworksEnabled',
        'orgLevelsEnabled',
        'crmEnabled',
        'crmMaxPipelines',
        'limitSleep',
        'lockStatus',
        'lockRbacTask',
        'enabled_languages',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'smsVerificationEnabled' => 'boolean',
        'isMarketingEnabled' => 'boolean',
        'isMarketingSMSEnabled' => 'boolean',
        'orgNetworksEnabled' => 'boolean',
        'orgLevelsEnabled' => 'boolean',
        'crmEnabled' => 'boolean',
        'isPlanDiscountPermissionsEnabled' => 'boolean',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'smsGatewayPreferred' => 'array',
        'smsBlacklistCountry' => 'array',
        'enabled_languages' => 'array',
    ];



        /**
     * Check if language feature is enabled for this organization
     *
     * @return bool
     */
    public function isLanguageFeatureEnabled(): bool
    {
        $enabledLanguages = $this->getEnabledLanguages(); // Use the method that handles JSON decoding
        return !empty($enabledLanguages);
    }

    /**
     * Check if multi-language is enabled
     *
     * @return bool
     */
    public function isMultiLanguageEnabled(): bool
    {
        return count($this->getEnabledLanguages()) > 1;
    }

    public function getEnabledLanguages(): array
    {
        $enabledLanguages = $this->enabled_languages ?? [];

        // If it's a string (JSON), decode it
        if (is_string($enabledLanguages)) {
            $decoded = json_decode($enabledLanguages, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::warning('Failed to decode enabled_languages JSON', [
                    'value' => $enabledLanguages,
                    'error' => json_last_error_msg(),
                    'org_id' => $this->org_id ?? null,
                ]);
                return [];
            }
            return is_array($decoded) ? $decoded : [];
        }

        // If it's already an array, return it
        if (is_array($enabledLanguages)) {
            return $enabledLanguages;
        }

        // Log unexpected type
        \Log::warning('enabled_languages has unexpected type', [
            'value' => $enabledLanguages,
            'type' => gettype($enabledLanguages),
            'org_id' => $this->org_id ?? null,
        ]);

        // Fallback to empty array
        return [];
    }

    /**
     * Relationship to the organization.
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }
    public function getEnabledLanguagesWithNames(): array
    {
        $enabledLanguages = $this->getEnabledLanguages();

        // Use stored names for languages
        $storedLanguageNames = [
            'en-US' => 'English (US)',
            'ar-SA' => 'العربية (Saudi Arabia)', // Arabic
            'es-ES' => 'Español (Spain)',       // Spanish
            'fr-FR' => 'Français (France)',     // French
            'de-DE' => 'Deutsch (Germany)',     // German
            'zh-CN' => '中文 (China)',           // Chinese
            'ja-JP' => '日本語 (Japan)',         // Japanese
        ];

        // Return associative array with language codes as keys
        $result = [];
        foreach ($enabledLanguages as $code) {
            $result[$code] = $storedLanguageNames[$code] ?? ucfirst($code);
        }

        return $result;
    }
}

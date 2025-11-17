<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Org;
use App\Models\OrgSettingsFeatures;
use Illuminate\Support\Facades\Log;

class EnableLanguageFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'language:enable {--org-id= : Specific organization ID} {--all : Enable for all organizations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable language features for organizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌐 Enabling Language Features');
        $this->info('============================');

        $orgId = $this->option('org-id');
        $all = $this->option('all');

        if (!$orgId && !$all) {
            $this->error('Please specify either --org-id=X or --all');
            return 1;
        }

        if ($orgId) {
            $this->enableForOrganization($orgId);
        } else {
            $this->enableForAllOrganizations();
        }

        return 0;
    }

    /**
     * Enable language features for specific organization
     */
    private function enableForOrganization($orgId)
    {
        $org = Org::find($orgId);
        
        if (!$org) {
            $this->error("Organization with ID {$orgId} not found");
            return;
        }

        $this->info("Enabling language features for: {$org->name} (ID: {$org->id})");
        
        $this->enableLanguagesForOrg($org);
        
        $this->info("✅ Language features enabled for organization {$org->id}");
    }

    /**
     * Enable language features for all organizations
     */
    private function enableForAllOrganizations()
    {
        $organizations = Org::all();
        
        $this->info("Enabling language features for {$organizations->count()} organizations");
        
        $bar = $this->output->createProgressBar($organizations->count());
        $bar->start();

        foreach ($organizations as $org) {
            $this->enableLanguagesForOrg($org);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✅ Language features enabled for all organizations");
    }

    /**
     * Enable languages for a specific organization
     */
    private function enableLanguagesForOrg($org)
    {
        Log::info('EnableLanguageFeatures: Processing organization', [
            'org_id' => $org->id,
            'org_name' => $org->name
        ]);

        // Find or create OrgSettingsFeatures
        $orgFeatures = OrgSettingsFeatures::firstOrCreate(
            ['org_id' => $org->id],
            [
                'enabled_languages' => ['en-US', 'ar-SA'], // Enable English and Arabic
                'freeClassesEnabled' => false,
                'quotaModeEnabled' => false,
                'isMultiLocationEnabled' => false,
                'groupsEnabled' => false,
                'genresEnabled' => false,
                'roomsEnabled' => false,
                'coachesEnabled' => false,
                'payrollEnabled' => false,
                'payrollRatesAdvanced' => false,
                'paymentsEnabled' => false,
                'workoutsEnabled' => false,
                'workoutPlansEnabled' => false,
                'workoutProgramsEnabled' => false,
                'workoutBuilderEnabled' => false,
                'isHoldEnabled' => false,
                'portalEnabled' => false,
                'portalAccessSchedules' => false,
                'portalAccessPlans' => false,
                'smsVerificationEnabled' => false,
                'smsGatewayPreferred' => null,
                'smsBlacklistCountry' => null,
                'sharedPlansEnabled' => false,
                'upchargePlansEnabled' => false,
                'accessControlEnabled' => false,
                'accessControlMultiDoorEnabled' => false,
                'accessControlGroupsEnabled' => false,
                'orgPlanTimeSlotsEnabled' => false,
                'orgPlanRatesEnabled' => false,
                'orgPlanRevShareDropInEnabled' => false,
                'orgPlanRevShareWODEnabled' => false,
                'orgPlanRevSharePTEnabled' => false,
                'orgPlanRevShareGXEnabled' => false,
                'isOrgPlanCategoryCrudEnabled' => false,
                'notificationTemplatesEnabled' => false,
                'isCallReminderEnabled' => false,
                'isPtScheduleEnabled' => false,
                'isMarketingEnabled' => false,
                'isMarketingMsgEnabled' => false,
                'isMarketingSMSEnabled' => false,
                'isOrgAdsEnabled' => false,
                'isMarketingPromoEnabled' => false,
                'isInvoiceRefundsEnabled' => false,
                'isWhiteLabelAppEnabled' => false,
                'orgUserPlanPreferencesEnabled' => false,
                'isInvoicingEnabled' => false,
                'isZoomEnabled' => false,
                'isWaitlistEnabled' => false,
                'isLateCancelEnabled' => false,
                'isNoShowsEnabled' => false,
                'isAppsEnabled' => false,
                'isSubscriberSelfSignInEnabled' => false,
                'isAssignmentSelfSignInEnabled' => false,
                'isPTEventEnabled' => false,
                'isEventCapacityEnabled' => false,
                'isEventEnabled' => false,
                'isSchedulePreReserveEnabled' => false,
                'isScheduleTimeUpdateEnabled' => false,
                'isScheduleOverlapEnabled' => false,
                'isScheduleEnabled' => false,
                'isReportsEnabled' => false,
                'isInsightsEnabled' => false,
                'isUserPlanUpgradeEnabled' => false,
                'isUserPlanTransferEnabled' => false,
                'isUserPlanDowngradeEnabled' => false,
                'isPlanDiscountEnabled' => false,
                'isPlanDiscountPermissionsEnabled' => false,
                'isPlanPermissionsEnabled' => false,
                'isFamiliesEnabled' => false,
                'AppleAppStoreUrl' => null,
                'GooglePlayStoreUrl' => null,
                'orgNetworksEnabled' => false,
                'orgLevelsEnabled' => false,
                'crmEnabled' => false,
                'crmMaxPipelines' => 0,
                'limitSleep' => 0,
                'lockStatus' => 0,
                'lockRbacTask' => 0,
            ]
        );

        // If it already exists, just update the languages
        if (!$orgFeatures->wasRecentlyCreated) {
            $orgFeatures->enabled_languages = ['en-US', 'ar-SA'];
            $orgFeatures->save();
        }

        // Log the result
        Log::info('EnableLanguageFeatures: Language features enabled', [
            'org_id' => $org->id,
            'was_created' => $orgFeatures->wasRecentlyCreated,
            'enabled_languages' => $orgFeatures->enabled_languages,
            'is_language_feature_enabled' => $orgFeatures->isLanguageFeatureEnabled(),
            'languages_with_names' => $orgFeatures->getEnabledLanguagesWithNames()
        ]);
    }
}


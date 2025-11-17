<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrgSettingsFeatures;
use App\Models\Org;
use Illuminate\Support\Facades\Log;

class LanguageFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds to enable language features
     */
    public function run(): void
    {
        Log::info('LanguageFeatureSeeder: Starting language feature enablement');

        // Get all organizations
        $organizations = Org::all();
        
        Log::info('LanguageFeatureSeeder: Found organizations', ['count' => $organizations->count()]);

        foreach ($organizations as $org) {
            Log::info('LanguageFeatureSeeder: Processing organization', [
                'org_id' => $org->id,
                'org_name' => $org->name ?? 'Unknown'
            ]);

            // Find or create OrgSettingsFeatures
            $orgFeatures = OrgSettingsFeatures::firstOrCreate(
                ['org_id' => $org->id],
                [
                    'enabled_languages' => ['en-US', 'ar-SA'], // Enable English and Arabic
                    'groupsEnabled' => false,
                    'genresEnabled' => false,
                    'roomsEnabled' => false,
                    'coachesEnabled' => false,
                    'payrollEnabled' => false,
                    'paymentsEnabled' => false,
                    'smsVerificationEnabled' => false,
                    'isMarketingEnabled' => false,
                    'isMarketingSMSEnabled' => false,
                    'orgNetworksEnabled' => false,
                    'orgLevelsEnabled' => false,
                    'crmEnabled' => false,
                ]
            );

            // If it already exists, just update the languages
            if (!$orgFeatures->wasRecentlyCreated) {
                $orgFeatures->enabled_languages = ['en-US', 'ar-SA'];
                $orgFeatures->save();
                
                Log::info('LanguageFeatureSeeder: Updated existing org features', [
                    'org_id' => $org->id,
                    'enabled_languages' => $orgFeatures->enabled_languages
                ]);
            } else {
                Log::info('LanguageFeatureSeeder: Created new org features', [
                    'org_id' => $org->id,
                    'enabled_languages' => $orgFeatures->enabled_languages
                ]);
            }

            // Verify the language feature is enabled
            $isEnabled = $orgFeatures->isLanguageFeatureEnabled();
            $enabledLanguages = $orgFeatures->getEnabledLanguages();
            $languagesWithNames = $orgFeatures->getEnabledLanguagesWithNames();

            Log::info('LanguageFeatureSeeder: Language feature status', [
                'org_id' => $org->id,
                'is_language_feature_enabled' => $isEnabled,
                'enabled_languages_raw' => $orgFeatures->enabled_languages,
                'enabled_languages_processed' => $enabledLanguages,
                'languages_with_names' => $languagesWithNames
            ]);
        }

        Log::info('LanguageFeatureSeeder: Completed language feature enablement');
        
        // Output summary
        $this->command->info('Language features enabled for ' . $organizations->count() . ' organizations');
        $this->command->info('Enabled languages: English (en-US), Arabic (ar-SA)');
        $this->command->info('Check the logs for detailed information');
    }
}


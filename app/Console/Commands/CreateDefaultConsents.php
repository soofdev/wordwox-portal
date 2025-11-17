<?php

namespace App\Console\Commands;

use App\Models\Org;
use App\Models\OrgConsent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDefaultConsents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consents:create-defaults 
                            {--org-id= : Create defaults for specific organization ID}
                            {--force : Force recreate existing consents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default consent types for organizations';

    /**
     * Default consent definitions
     */
    protected array $defaultConsents = [
        // [
        //     'consent_key' => 'terms',
        //     'consent_name' => 'Terms of Service',
        //     'description' => 'I agree to the Terms of Service and membership rules.',
        //     'is_active' => true,
        //     'is_required' => false,
        //     'display_contexts' => ['registration'],
        //     'order' => 10,
        // ],
        // [
        //     'consent_key' => 'privacy',
        //     'consent_name' => 'Privacy Policy',
        //     'description' => 'I agree to the Privacy Policy and data handling practices.',
        //     'is_active' => true,
        //     'is_required' => false,
        //     'display_contexts' => ['registration'],
        //     'order' => 20,
        // ],
        // [
        //     'consent_key' => 'liability',
        //     'consent_name' => 'Liability Waiver',
        //     'description' => 'I understand and accept the risks associated with physical activities and waive liability claims.',
        //     'is_active' => true,
        //     'is_required' => false,
        //     'display_contexts' => ['registration'],
        //     'order' => 30,
        // ],
        // [
        //     'consent_key' => 'participation',
        //     'consent_name' => 'Participation Consent',
        //     'description' => 'I consent (as parent/guardian) for my child to participate in gym activities and programs.',
        //     'is_active' => true,
        //     'is_required' => false,
        //     'display_contexts' => ['registration'],
        //     'order' => 40,
        // ],
        // [
        //     'consent_key' => 'medical_emergency',
        //     'consent_name' => 'Medical Emergency Authorization',
        //     'description' => 'I authorize gym staff to seek emergency medical treatment for my child if needed.',
        //     'is_active' => true,
        //     'is_required' => false,
        //     'display_contexts' => ['registration'],
        //     'order' => 50,
        // ],
        [
            'consent_key' => 'marketing_email',
            'consent_name' => 'Email Marketing',
            'description' => 'I consent to receiving promotional emails, newsletters, and updates.',
            'is_active' => true,
            'is_required' => false,
            'display_contexts' => ['registration', 'verification'],
            'order' => 60,
        ],
        [
            'consent_key' => 'marketing_sms',
            'consent_name' => 'SMS Marketing',
            'description' => 'I consent to receiving promotional text messages and notifications.',
            'is_active' => true,
            'is_required' => false,
            'display_contexts' => ['registration', 'verification'],
            'order' => 70,
        ],
        [
            'consent_key' => 'media',
            'consent_name' => 'Media Consent',
            'description' => 'I consent to photos/videos being taken and used for promotional purposes.',
            'is_active' => true,
            'is_required' => false,
            'display_contexts' => ['registration'],
            'order' => 80,
        ],
        // [
        //     'consent_key' => 'data_sharing',
        //     'consent_name' => 'Third-Party Data Sharing',
        //     'description' => 'I consent to sharing my data with trusted third-party partners for enhanced services.',
        //     'is_active' => false, // Inactive by default
        //     'is_required' => false,
        //     'display_contexts' => ['registration', 'verification'],
        //     'order' => 90,
        // ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orgId = $this->option('org-id');
        $force = $this->option('force');

        if ($orgId) {
            $org = Org::find($orgId);
            if (!$org) {
                $this->error("Organization with ID {$orgId} not found.");
                return 1;
            }
            $this->createConsentsForOrg($org, $force);
        } else {
            $this->createConsentsForAllOrgs($force);
        }

        return 0;
    }

    /**
     * Create default consents for all organizations
     */
    protected function createConsentsForAllOrgs(bool $force): void
    {
        $this->info('Creating default consents for all organizations...');

        $orgCount = 0;
        $consentCount = 0;

        Org::chunk(100, function ($orgs) use (&$orgCount, &$consentCount, $force) {
            foreach ($orgs as $org) {
                $created = $this->createConsentsForOrg($org, $force, false);
                $orgCount++;
                $consentCount += $created;
            }
        });

        $this->info("✅ Processed {$orgCount} organizations.");
        $this->info("✅ Created/updated {$consentCount} consent records.");
    }

    /**
     * Create default consents for a specific organization
     */
    protected function createConsentsForOrg(Org $org, bool $force, bool $verbose = true): int
    {
        if ($verbose) {
            $this->info("Creating default consents for organization: {$org->name} (ID: {$org->id})");
        }

        $createdCount = 0;

        DB::transaction(function () use ($org, $force, &$createdCount, $verbose) {
            foreach ($this->defaultConsents as $consentData) {
                $existing = OrgConsent::where('org_id', $org->id)
                    ->where('consent_key', $consentData['consent_key'])
                    ->first();

                if ($existing && !$force) {
                    if ($verbose) {
                        $this->warn("  ⚠️  Consent '{$consentData['consent_key']}' already exists. Use --force to update.");
                    }
                    continue;
                }

                if ($existing && $force) {
                    // Update existing consent
                    $existing->update(array_merge($consentData, ['org_id' => $org->id]));
                    if ($verbose) {
                        $this->info("  ✅ Updated consent: {$consentData['consent_name']}");
                    }
                } else {
                    // Create new consent
                    OrgConsent::create(array_merge($consentData, ['org_id' => $org->id]));
                    if ($verbose) {
                        $this->info("  ✅ Created consent: {$consentData['consent_name']}");
                    }
                }

                $createdCount++;
            }
        });

        if ($verbose) {
            $this->info("✅ Processed {$createdCount} consents for {$org->name}");
        }

        return $createdCount;
    }
}
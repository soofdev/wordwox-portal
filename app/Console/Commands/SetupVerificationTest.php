<?php

namespace App\Console\Commands;

use App\Models\OrgUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupVerificationTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:setup-test 
                            {orgUserId : The OrgUser ID to setup for testing}
                            {--type=both : Type of verification (email, sms, both)}
                            {--hours=24 : Token expiration in hours}
                            {--clear-user : Clear existing User account to test new user path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup an OrgUser for verification testing by generating tokens and resetting status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orgUserId = $this->argument('orgUserId');
        $type = $this->option('type');
        $hours = $this->option('hours');

        // Find the OrgUser
        $orgUser = OrgUser::find($orgUserId);
        
        if (!$orgUser) {
            $this->error("OrgUser with ID {$orgUserId} not found.");
            return 1;
        }

        $this->info("Setting up verification test for OrgUser ID: {$orgUserId}");
        $this->info("Name: {$orgUser->fullName}");
        $this->info("Email: {$orgUser->email}");
        $this->info("Phone: {$orgUser->fullPhone}");
        $this->info("Org: {$orgUser->org->name}");
        
        // Reset the OrgUser status
        $this->info("\n🔄 Resetting OrgUser status...");
        $orgUser->update([
            'user_id' => null,    // Clear user link (unverified state)
            'token' => null,      // Clear existing token
            'token_sms' => null,  // Clear existing SMS token
            'status' => 0,        // Reset status to unverified state (OrgUserStatus::None)
        ]);

        // Clear existing User if requested (for new user path testing)
        if ($this->option('clear-user')) {
            $this->info("🗑️ Clearing existing User accounts...");
            $existingUser = null;
            
            if ($orgUser->email) {
                $existingUser = \App\Models\User::where('email', $orgUser->email)->first();
            }
            
            if (!$existingUser && $orgUser->phoneNumber) {
                $existingUser = \App\Models\User::where('phoneNumber', $orgUser->phoneNumber)
                                              ->where('phoneCountry', $orgUser->phoneCountry)
                                              ->first();
            }
            
            if ($existingUser) {
                $this->info("   Deleting User: {$existingUser->fullName} ({$existingUser->email})");
                $existingUser->forceDelete();
            } else {
                $this->info("   No existing User found to delete");
            }
        }

        // Generate verification tokens
        $this->info("🔑 Generating verification tokens...");
        $timestamp = now()->addHours((int)$hours)->timestamp;
        $token = Str::random(32) . '_' . $timestamp . '_' . $orgUser->org_id;
        $tokenSms = Str::random(5) . '_' . time();
        
        $orgUser->update([
            'token' => $token,
            'token_sms' => $tokenSms,
        ]);

        // Generate URLs based on type
        $baseUrl = config('app.url');
        $verificationUrl = "{$baseUrl}/verify/{$token}";
        $smsVerificationUrl = "{$baseUrl}/verify/{$tokenSms}";

        $this->info("\n✅ Setup complete!");
        $this->info("Token expires in: {$hours} hours");
        $this->info("Email Token: {$token}");
        $this->info("SMS Token: {$tokenSms}");
        
        // Display verification links
        $this->info("\n🔗 Verification Links:");
        
        if ($type === 'email' || $type === 'both') {
            $this->line("📧 Email Verification:");
            $this->line("   {$verificationUrl}");
            
            if ($orgUser->email) {
                $this->info("   ✓ Email available: {$orgUser->email}");
            } else {
                $this->warn("   ⚠ No email set for this OrgUser");
            }
        }

        if ($type === 'sms' || $type === 'both') {
            $this->line("\n📱 SMS Verification:");
            $this->line("   {$smsVerificationUrl}");
            
            if ($orgUser->phoneNumber) {
                $this->info("   ✓ Phone available: {$orgUser->fullPhone}");
            } else {
                $this->warn("   ⚠ No phone number set for this OrgUser");
            }
        }

        if ($type === 'both') {
            $this->line("\n🔄 Universal Link (Auto-detects email/SMS):");
            $this->line("   {$verificationUrl}");
        }

        // Display test scenarios
        $this->info("\n🧪 Test Scenarios:");
        $this->line("1. New User Path: Visit the link above (no existing User account)");
        $this->line("2. Account Linking: Create a User with same email/phone first");
        $this->line("3. Already Verified: Run this command again after completing verification");

        // Display quick test commands
        $this->info("\n⚡ Quick Test Commands:");
        $this->line("# Test in browser:");
        $this->line("open {$verificationUrl}");
        $this->line("");
        $this->line("# Create duplicate user for linking test:");
        $this->line("php artisan tinker --execute=\"App\\Models\\User::create(['email' => '{$orgUser->email}', 'fullName' => 'Test User', 'password' => bcrypt('password')]);\"");
        $this->line("");
        $this->line("# Check current status:");
        $this->line("php artisan tinker --execute=\"\\\$ou = App\\Models\\OrgUser::find({$orgUserId}); echo 'Verified: ' . (\\\$ou->user_id ? 'Yes' : 'No') . PHP_EOL;\"");

        return 0;
    }
}

<?php

namespace App\Services;

use App\Models\OrgUser;
use App\Models\User;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * VerificationService handles all verification-related operations
 * including token validation, user detection, and account creation
 */
class VerificationService
{
    /**
     * Validate a verification token and return orgUser with metadata
     * 
     * @param string $token The verification token (email or SMS format)
     * @return array Contains: valid, orgUser, verificationType, error
     */
    public function validateToken(string $token): array
    {
        $tokenParts = explode('_', $token);
        
        // Determine token type by format
        $isEmailToken = count($tokenParts) >= 3; // Email: {32_chars}_{timestamp}_{org_id}
        $isSmsToken = count($tokenParts) == 2;   // SMS: {5_chars}_{timestamp}
        
        if (!$isEmailToken && !$isSmsToken) {
            return [
                'valid' => false,
                'error' => 'Invalid token format',
                'orgUser' => null,
                'verificationType' => null
            ];
        }

        // Extract timestamp for expiration check
        if ($isEmailToken) {
            // Email token: {32_chars}_{timestamp}_{org_id}
            $timestamp = $tokenParts[count($tokenParts) - 2];
        } else {
            // SMS token: {5_chars}_{timestamp}
            $timestamp = $tokenParts[1];
        }

        // Check if token is expired (24 hours)
        $tokenTime = Carbon::createFromTimestamp($timestamp);
        if ($tokenTime->addHours(24)->isPast()) {
            return [
                'valid' => false,
                'error' => 'Token has expired',
                'orgUser' => null,
                'verificationType' => null
            ];
        }

        // Find OrgUser by appropriate token field
        $orgUser = null;
        $verificationType = null;

        if ($isEmailToken) {
            // Look up by email token field
            $orgUser = OrgUser::where('token', $token)
                             ->with(['org', 'user'])
                             ->first();
            $verificationType = 'email';
        } elseif ($isSmsToken) {
            // Look up by SMS token field
            $orgUser = OrgUser::where('token_sms', $token)
                             ->with(['org', 'user'])
                             ->first();
            $verificationType = 'sms';
        }

        if (!$orgUser) {
            return [
                'valid' => false,
                'error' => 'Invalid or expired token',
                'orgUser' => null,
                'verificationType' => null
            ];
        }

        // Auto-detect verification type based on available contact info if both are available
        if ($orgUser->phoneNumber && $orgUser->email) {
            $verificationType = $isEmailToken ? 'email' : 'sms';
        } elseif ($orgUser->phoneNumber && !$orgUser->email) {
            $verificationType = 'sms';
        } elseif (!$orgUser->phoneNumber && $orgUser->email) {
            $verificationType = 'email';
        }

        return [
            'valid' => true,
            'error' => null,
            'orgUser' => $orgUser,
            'verificationType' => $verificationType
        ];
    }

    /**
     * Check if there's an existing user with the same email or phone
     * 
     * @param OrgUser $orgUser The OrgUser to check against
     * @param string $verificationType The type of verification (email, sms, both)
     * @return User|null Existing user if found
     */
    public function checkExistingUser(OrgUser $orgUser, string $verificationType): ?User
    {
        $query = User::query();

        if ($verificationType === 'email' || $verificationType === 'both') {
            if ($orgUser->email) {
                $query->where('email', $orgUser->email);
            }
        }

        if ($verificationType === 'sms' || $verificationType === 'both') {
            if ($orgUser->phoneNumber && $orgUser->phoneCountry) {
                $query->orWhere(function($q) use ($orgUser) {
                    $q->where('phoneNumber', $orgUser->phoneNumber)
                      ->where('phoneCountry', $orgUser->phoneCountry);
                });
            }
        }

        return $query->first();
    }

    /**
     * Create a new User account and link it to the OrgUser
     * 
     * @param array $userData User data for creation
     * @param OrgUser $orgUser The OrgUser to link to
     * @param string $verificationType The type of verification (email, sms, both)
     * @return User The created user
     */
    public function createUserAccount(array $userData, OrgUser $orgUser, string $verificationType): User
    {
        return DB::transaction(function () use ($userData, $orgUser, $verificationType) {
            // Prepare user creation data
            $userCreateData = [
                'password_hash' => $userData['password'], // Use password_hash field
                'fullName' => $userData['fullName'],
                'auth_key' => Str::random(32), // Required auth key
                'uuid' => Str::uuid()->getHex(), // Generate UUID without dashes
                'status' => 10, // Set status to 10 (active/verified)
                'orgUser_id' => $orgUser->id, // Set current tenant context
            ];

            // Add contact information and verification status based on verification type
            if ($verificationType === 'email') {
                $userCreateData['email'] = $userData['email'];
                $userCreateData['verifiedEmail'] = true;
                $userCreateData['email_verified_at'] = now(); // Carbon timestamp
            } elseif ($verificationType === 'sms') {
                $userCreateData['phoneNumber'] = $userData['phoneNumber'];
                $userCreateData['phoneCountry'] = $userData['phoneCountry'];
                $userCreateData['verifiedPhoneNumber'] = true;
            } elseif ($verificationType === 'both') {
                // Handle both email and phone verification
                $userCreateData['email'] = $userData['email'] ?? null;
                $userCreateData['phoneNumber'] = $userData['phoneNumber'] ?? null;
                $userCreateData['phoneCountry'] = $userData['phoneCountry'] ?? null;
                
                if (isset($userData['email'])) {
                    $userCreateData['verifiedEmail'] = true;
                    $userCreateData['email_verified_at'] = now(); // Carbon timestamp
                }
                if (isset($userData['phoneNumber'])) {
                    $userCreateData['verifiedPhoneNumber'] = true;
                }
            }

            // Create the User account
            $user = User::create($userCreateData);

            // Link OrgUser to the new User and mark as verified
            $orgUser->update([
                'user_id' => $user->id,
                'token' => null,     // Clear the email verification token
                'token_sms' => null, // Clear the SMS verification token
                'status' => 10,      // Mark OrgUser as verified
            ]);

            return $user;
        });
    }

    /**
     * Link an existing User to an OrgUser
     * 
     * @param User $existingUser The existing user to link
     * @param OrgUser $orgUser The OrgUser to link to
     * @return bool Success status
     */
    public function linkExistingUser(User $existingUser, OrgUser $orgUser): bool
    {
        return DB::transaction(function () use ($existingUser, $orgUser) {
            // Update OrgUser to link to existing user and mark as verified
            $orgUser->update([
                'user_id' => $existingUser->id,
                'token' => null,     // Clear the email verification token
                'token_sms' => null, // Clear the SMS verification token
                'status' => 10,      // Mark OrgUser as verified
            ]);

            // Update user verification status and set tenant context
            $userUpdates = [
                'orgUser_id' => $orgUser->id, // Set current tenant context
            ];

            if ($orgUser->email && !$existingUser->verifiedEmail) {
                $userUpdates['verifiedEmail'] = true;
                $userUpdates['email_verified_at'] = now(); // Carbon timestamp
            }

            if ($orgUser->phoneNumber && !$existingUser->verifiedPhoneNumber) {
                $userUpdates['verifiedPhoneNumber'] = true;
            }

            $existingUser->update($userUpdates);

            return true;
        });
    }

    /**
     * Clean up expired verification tokens
     * 
     * @return int Number of cleaned up tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $expiredTime = Carbon::now()->subHours(24);
        
        return OrgUser::whereNotNull('token')
                     ->where('created_at', '<', $expiredTime)
                     ->whereNull('user_id')
                     ->update(['token' => null]);
    }

    /**
     * Check if the OrgUser has already been verified
     * 
     * @param OrgUser $orgUser
     * @return bool
     */
    public function isAlreadyVerified(OrgUser $orgUser): bool
    {
        return !is_null($orgUser->user_id);
    }

    /**
     * Get user's existing gym memberships for display
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserMemberships(User $user)
    {
        return $user->orgUsers()
                   ->with('org')
                   ->whereNotNull('user_id')
                   ->get();
    }
}

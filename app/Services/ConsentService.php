<?php

namespace App\Services;

use App\Models\OrgConsent;
use App\Models\OrgUserConsent;
use App\Models\OrgUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ConsentService
{
    /**
     * Get active consents for an organization in a specific context
     */
    public function getConsentsForContext(int $orgId, string $context): Collection
    {
        return OrgConsent::where('org_id', $orgId)
            ->forContext($context)
            ->ordered()
            ->get();
    }

    /**
     * Get active consents for registration
     */
    public function getRegistrationConsents(int $orgId): Collection
    {
        return $this->getConsentsForContext($orgId, 'registration');
    }

    /**
     * Get active consents for verification
     */
    public function getVerificationConsents(int $orgId): Collection
    {
        return $this->getConsentsForContext($orgId, 'verification');
    }

    /**
     * Get required consents for a specific context
     */
    public function getRequiredConsentsForContext(int $orgId, string $context): Collection
    {
        return OrgConsent::where('org_id', $orgId)
            ->active()
            ->required()
            ->forContext($context)
            ->ordered()
            ->get();
    }

    /**
     * Store consent records for a user
     */
    public function storeUserConsents(
        OrgUser $orgUser, 
        array $consents, 
        string $context = 'registration',
        ?OrgUser $consentedBy = null,
        ?string $sessionId = null
    ): void {
        $sessionId = $sessionId ?: (string) Str::uuid();
        $timestamp = now();

        foreach ($consents as $consentKey => $consented) {
            $orgConsent = OrgConsent::where('org_id', $orgUser->org_id)
                ->where('consent_key', $consentKey)
                ->first();

            if ($orgConsent) {
                OrgUserConsent::updateOrCreate(
                    [
                        'org_user_id' => $orgUser->id,
                        'org_consent_id' => $orgConsent->id,
                    ],
                    [
                        'consented' => (bool) $consented,
                        'consented_at' => $timestamp,
                        'consented_by_org_user_id' => $consentedBy?->id,
                        'registration_session_id' => $sessionId,
                        'context' => $context,
                    ]
                );
            }
        }
    }

    /**
     * Store consents for multiple family members
     */
    public function storeFamilyConsents(
        array $familyMembers,
        array $consentsData,
        string $context = 'registration',
        ?string $sessionId = null
    ): void {
        $sessionId = $sessionId ?: (string) Str::uuid();

        foreach ($consentsData as $memberType => $memberConsents) {
            $member = $familyMembers[$memberType] ?? null;
            if (!$member) continue;

            // Determine who is providing consent
            $consentedBy = null;
            if ($memberType === 'child_1' || $memberType === 'child_2') {
                // Parent provides consent for children
                $consentedBy = $familyMembers['primary'] ?? null;
            }

            $this->storeUserConsents(
                $member,
                $memberConsents,
                $context,
                $consentedBy,
                $sessionId
            );
        }
    }

    /**
     * Check if user has consented to a specific consent type
     */
    public function hasUserConsented(OrgUser $orgUser, string $consentKey): bool
    {
        return OrgUserConsent::whereHas('orgConsent', function ($query) use ($consentKey, $orgUser) {
                $query->where('consent_key', $consentKey)
                      ->where('org_id', $orgUser->org_id);
            })
            ->where('org_user_id', $orgUser->id)
            ->where('consented', true)
            ->exists();
    }

    /**
     * Get user's consent record for a specific consent type
     */
    public function getUserConsent(OrgUser $orgUser, string $consentKey): ?OrgUserConsent
    {
        return OrgUserConsent::whereHas('orgConsent', function ($query) use ($consentKey, $orgUser) {
                $query->where('consent_key', $consentKey)
                      ->where('org_id', $orgUser->org_id);
            })
            ->where('org_user_id', $orgUser->id)
            ->first();
    }

    /**
     * Get all consent records for a user
     */
    public function getUserConsents(OrgUser $orgUser): Collection
    {
        return OrgUserConsent::with(['orgConsent', 'consentedBy'])
            ->where('org_user_id', $orgUser->id)
            ->get();
    }

    /**
     * Check if user has all required consents for a context
     */
    public function hasRequiredConsentsForContext(OrgUser $orgUser, string $context): bool
    {
        $requiredConsents = $this->getRequiredConsentsForContext($orgUser->org_id, $context);
        
        foreach ($requiredConsents as $consent) {
            if (!$this->hasUserConsented($orgUser, $consent->consent_key)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get missing required consents for a user in a context
     */
    public function getMissingRequiredConsents(OrgUser $orgUser, string $context): Collection
    {
        $requiredConsents = $this->getRequiredConsentsForContext($orgUser->org_id, $context);
        
        return $requiredConsents->filter(function ($consent) use ($orgUser) {
            return !$this->hasUserConsented($orgUser, $consent->consent_key);
        });
    }

    /**
     * Validate consent data against required consents
     */
    public function validateConsents(array $consents, int $orgId, string $context): array
    {
        $requiredConsents = $this->getRequiredConsentsForContext($orgId, $context);
        $errors = [];

        foreach ($requiredConsents as $consent) {
            $consentValue = $consents[$consent->consent_key] ?? false;
            
            if (!$consentValue) {
                $errors[$consent->consent_key] = "You must agree to the {$consent->consent_name} to continue.";
            }
        }

        return $errors;
    }

    /**
     * Validate family consents
     */
    public function validateFamilyConsents(array $consentsData, int $orgId, string $context = 'registration'): array
    {
        $errors = [];

        foreach ($consentsData as $memberType => $memberConsents) {
            if (empty($memberConsents)) continue;

            $memberErrors = $this->validateConsents($memberConsents, $orgId, $context);
            
            foreach ($memberErrors as $consentKey => $error) {
                $errors["{$memberType}.{$consentKey}"] = $error;
            }
        }

        return $errors;
    }

    /**
     * Get consent statistics for an organization
     */
    public function getConsentStatistics(int $orgId, string $context = null): array
    {
        $query = OrgUserConsent::whereHas('orgConsent', function ($q) use ($orgId) {
            $q->where('org_id', $orgId);
        });

        if ($context) {
            $query->where('context', $context);
        }

        $total = $query->count();
        $consented = $query->where('consented', true)->count();
        $declined = $query->where('consented', false)->count();

        return [
            'total' => $total,
            'consented' => $consented,
            'declined' => $declined,
            'consent_rate' => $total > 0 ? round(($consented / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get consent statistics by type
     */
    public function getConsentStatisticsByType(int $orgId, string $context = null): array
    {
        $consents = OrgConsent::where('org_id', $orgId)
            ->when($context, fn($q) => $q->forContext($context))
            ->with(['userConsents' => function ($q) use ($context) {
                if ($context) {
                    $q->where('context', $context);
                }
            }])
            ->get();

        return $consents->map(function ($consent) {
            $total = $consent->userConsents->count();
            $consented = $consent->userConsents->where('consented', true)->count();
            $declined = $consent->userConsents->where('consented', false)->count();

            return [
                'consent_name' => $consent->consent_name,
                'consent_key' => $consent->consent_key,
                'total' => $total,
                'consented' => $consented,
                'declined' => $declined,
                'consent_rate' => $total > 0 ? round(($consented / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Withdraw consent for a user
     */
    public function withdrawConsent(OrgUser $orgUser, string $consentKey, string $context = 'withdrawal'): bool
    {
        $consent = $this->getUserConsent($orgUser, $consentKey);
        
        if ($consent) {
            $consent->update([
                'consented' => false,
                'consented_at' => now(),
                'context' => $context,
            ]);
            return true;
        }

        return false;
    }
}

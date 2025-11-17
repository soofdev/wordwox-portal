<?php

namespace App\Services;

use App\Models\OrgUserPlan;
use App\Models\Discount;
use Illuminate\Support\Facades\Auth;

class DiscountService
{
    /**
     * Determine member type (new vs renewing) based on membership history
     */
    public static function getMemberType(int $customerId): string
    {
        $hasActivePlans = OrgUserPlan::where('orgUser_id', $customerId)
            ->where('isDeleted', false)
            ->where('isCanceled', false)
            ->whereNotIn('status', ['canceled', 'deleted'])
            ->exists();
            
        return $hasActivePlans ? 'renewing_member' : 'new_member';
    }
    
    /**
     * Get filtered discount options based on staff permissions and member type
     */
    public static function getFilteredDiscountOptions(int $staffId, string $memberType): array
    {
        $query = Discount::query()
            ->where('org_id', Auth::user()->orgUser->org_id)
            ->where('status', 'active');
            
        // Filter by member type
        if ($memberType === 'new_member') {
            $query->where('can_be_used_for_new_memberships', true);
        } else {
            $query->where('can_be_used_for_renewal_memberships', true);
        }
        
        // Apply staff permissions if feature is enabled
        if (Auth::user()->orgUser->org->orgSettingsFeatures->isPlanDiscountPermissionsEnabled ?? false) {
            $query->where(function ($q) use ($staffId, $memberType) {
                $q->where('available_to_all_staff', true)
                  ->orWhereHas('discountPermissions', function ($permQuery) use ($staffId, $memberType) {
                      $permQuery->where('orgUser_id', $staffId)
                               ->where('operation_type', $memberType === 'new_member' ? 'new_member' : 'renewing_member');
                  });
            });
        }
        
        return $query->get()->map(function ($discount) {
            return [
                'id' => $discount->id,
                'name' => $discount->name,
                'type' => $discount->unit === 'percent' ? 'percentage' : 'fixed',
                'value' => $discount->value,
                'unit' => $discount->unit,
                'formatted' => self::formatDiscountOption($discount)
            ];
        })->toArray();
    }

    /**
     * Format discount option for display
     */
    public static function formatDiscountOption(Discount $discount): string
    {
        $value = $discount->unit === 'percent' 
            ? $discount->value . '%' 
            : number_format($discount->value, 2) . ' ' . (Auth::user()->orgUser->org->sysCountry->currencyCode ?? 'USD');
            
        return "{$discount->name} - {$value}";
    }

    /**
     * Calculate discount amount based on discount type and base price
     */
    public static function calculateDiscountAmount(Discount $discount, float $basePrice): float
    {
        if ($discount->unit === 'percent') {
            return $basePrice * ($discount->value / 100);
        }
        
        return min($discount->value, $basePrice); // Don't exceed base price
    }

    /**
     * Validate discount eligibility for a customer
     */
    public static function validateDiscountEligibility(int $discountId, int $customerId): bool
    {
        $discount = Discount::find($discountId);
        if (!$discount) {
            return false;
        }

        $memberType = self::getMemberType($customerId);
        
        // Check if discount is applicable for member type
        if ($memberType === 'new_member' && !$discount->can_be_used_for_new_memberships) {
            return false;
        }
        
        if ($memberType === 'renewing_member' && !$discount->can_be_used_for_renewal_memberships) {
            return false;
        }

        // Check staff permissions if enabled
        if (Auth::user()->orgUser->org->orgSettingsFeatures->isPlanDiscountPermissionsEnabled ?? false) {
            $staffId = Auth::user()->orgUser->id;
            
            if (!$discount->available_to_all_staff) {
                $hasPermission = $discount->discountPermissions()
                    ->where('orgUser_id', $staffId)
                    ->where('operation_type', $memberType === 'new_member' ? 'new_member' : 'renewing_member')
                    ->exists();
                    
                if (!$hasPermission) {
                    return false;
                }
            }
        }

        return true;
    }
}
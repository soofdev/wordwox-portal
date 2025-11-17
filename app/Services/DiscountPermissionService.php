<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\OrgUser;
use Illuminate\Support\Facades\Auth;

class DiscountPermissionService
{
    const OPERATION_NEW_MEMBER = 'new_member';
    const OPERATION_RENEWING_MEMBER = 'renewing_member';

    /**
     * Check if staff member has permission to use a discount
     */
    public static function hasDiscountPermission(int $staffId, int $discountId, string $operationType): bool
    {
        $discount = Discount::find($discountId);
        if (!$discount) {
            return false;
        }

        // If discount is available to all staff, permission granted
        if ($discount->available_to_all_staff) {
            return true;
        }

        // Check specific permission
        return $discount->discountPermissions()
            ->where('orgUser_id', $staffId)
            ->where('operation_type', $operationType)
            ->exists();
    }

    /**
     * Get all discounts a staff member can use for a specific operation
     */
    public static function getAuthorizedDiscounts(int $staffId, string $operationType): array
    {
        $orgId = Auth::user()->orgUser->org_id;

        $query = Discount::query()
            ->where('org_id', $orgId)
            ->where('status', 'active')
            ->where(function ($q) use ($staffId, $operationType) {
                $q->where('available_to_all_staff', true)
                  ->orWhereHas('discountPermissions', function ($permQuery) use ($staffId, $operationType) {
                      $permQuery->where('orgUser_id', $staffId)
                               ->where('operation_type', $operationType);
                  });
            });

        // Filter by operation type compatibility
        if ($operationType === self::OPERATION_NEW_MEMBER) {
            $query->where('can_be_used_for_new_memberships', true);
        } else {
            $query->where('can_be_used_for_renewal_memberships', true);
        }

        return $query->get()->toArray();
    }

    /**
     * Grant discount permission to a staff member
     */
    public static function grantPermission(int $staffId, int $discountId, string $operationType): bool
    {
        $discount = Discount::find($discountId);
        $staff = OrgUser::find($staffId);

        if (!$discount || !$staff) {
            return false;
        }

        // Check if permission already exists
        $exists = $discount->discountPermissions()
            ->where('orgUser_id', $staffId)
            ->where('operation_type', $operationType)
            ->exists();

        if (!$exists) {
            $discount->discountPermissions()->create([
                'orgUser_id' => $staffId,
                'operation_type' => $operationType,
            ]);
        }

        return true;
    }

    /**
     * Revoke discount permission from a staff member
     */
    public static function revokePermission(int $staffId, int $discountId, string $operationType): bool
    {
        $discount = Discount::find($discountId);
        if (!$discount) {
            return false;
        }

        $discount->discountPermissions()
            ->where('orgUser_id', $staffId)
            ->where('operation_type', $operationType)
            ->delete();

        return true;
    }

    /**
     * Get staff members with permissions for a specific discount
     */
    public static function getStaffWithPermissions(int $discountId): array
    {
        $discount = Discount::with(['discountPermissions.staff'])->find($discountId);
        if (!$discount) {
            return [];
        }

        return $discount->discountPermissions->map(function ($permission) {
            return [
                'staff_id' => $permission->orgUser_id,
                'staff_name' => $permission->staff->fullName ?? 'Unknown',
                'operation_type' => $permission->operation_type,
            ];
        })->toArray();
    }

    /**
     * Validate operation type
     */
    public static function isValidOperationType(string $operationType): bool
    {
        return in_array($operationType, [
            self::OPERATION_NEW_MEMBER,
            self::OPERATION_RENEWING_MEMBER,
        ]);
    }
}
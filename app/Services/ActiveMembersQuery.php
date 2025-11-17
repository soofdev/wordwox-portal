<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\OrgUserPlan;

class ActiveMembersQuery
{
    /**
     * Build the complete active members query
     * This query ensures exactly one record per user by using MySQL 5.7 compatible
     * MAX() aggregation to handle users with multiple active memberships
     * 
     * IMPORTANT: This query includes tenant filtering to ensure users only see
     * members from their own organization (org_id).
     */
    public static function build()
    {
        // Get current user's org_id for tenant filtering
        $orgId = auth()->user()->orgUser->org_id ?? null;
        
        if (!$orgId) {
            // Return empty query if no org_id available
            return DB::table('orgUser')->whereRaw('1 = 0');
        }
        
        return DB::table('orgUser')
            ->select([
                'orgUser.id',
                'orgUser.uuid', 
                'orgUser.number',
                'orgUser.fullName',
                'orgUser.org_id',
                'orgUser.isOwner',
                'orgUser.isAdmin', 
                'orgUser.isStaff',
                'orgUser.isOnRoster',
                'orgUser.isCustomer',
                'orgUser.isDeleted',
                'orgUser.phoneCountry',
                'orgUser.phoneNumber',
                DB::raw("CONCAT('+(', orgUser.phoneCountry, ') ', orgUser.phoneNumber) AS fullPhoneNumber"),
                'orgUser.email',
                'orgUser.gender',
                'orgUser.dob',
                // Age calculation in days until next birthday
                DB::raw("CASE WHEN orgUser.dob IS NOT NULL THEN
                    CASE WHEN DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d') <= CURDATE() THEN
                      DATEDIFF(DATE_FORMAT(CONCAT(YEAR(CURDATE()) + 1, '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d'), CURDATE())
                    ELSE
                      DATEDIFF(DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d'), CURDATE())
                    END
                  ELSE NULL END AS dobDays"),
                'orgUser.created_at',
                // Latest plan data from JOIN - GUARANTEED ONE RECORD PER USER
                'latest_plan.id as plan_id',
                'latest_plan.name as plan_name', 
                'latest_plan.status as plan_status',
                'latest_plan.endDate as plan_end_date',
                'latest_plan.startDate as plan_start_date',
                // Days until expiration
                DB::raw("DATEDIFF(latest_plan.endDate, CURDATE()) AS expires_in_days"),
                // Days since expiration (for expired plans)
                DB::raw("CASE WHEN latest_plan.endDate < CURDATE() THEN DATEDIFF(CURDATE(), latest_plan.endDate) ELSE NULL END AS inactive_days")
            ])
            ->leftJoin('user', 'orgUser.user_id', '=', 'user.id')
            // CRITICAL: This subquery ensures exactly ONE record per orgUser_id
            // Uses MySQL 5.7 compatible MAX() aggregation instead of window functions
            ->leftJoin(DB::raw('(
                SELECT oup1.orgUser_id, oup1.id, oup1.name, oup1.status, oup1.endDate, oup1.startDate
                FROM orgUserPlan oup1
                INNER JOIN (
                    SELECT orgUser_id, MAX(startDate) as max_start_date, MAX(endDate) as max_end_date, MAX(id) as max_id
                    FROM orgUserPlan 
                    WHERE status = ' . OrgUserPlan::STATUS_ACTIVE . ' AND isDeleted != true AND org_id = ' . $orgId . '
                    GROUP BY orgUser_id
                ) oup2 ON oup1.orgUser_id = oup2.orgUser_id 
                    AND oup1.startDate = oup2.max_start_date 
                    AND oup1.endDate = oup2.max_end_date
                    AND oup1.id = oup2.max_id
                WHERE oup1.status = ' . OrgUserPlan::STATUS_ACTIVE . ' AND oup1.isDeleted != true AND oup1.org_id = ' . $orgId . '
            ) as latest_plan'), 'orgUser.id', '=', 'latest_plan.orgUser_id')
            ->where('orgUser.org_id', $orgId)
            ->where('orgUser.isArchived', false)
            ->whereNull('orgUser.deleted_at')
            ->whereNotNull('latest_plan.id')
            ->where('latest_plan.status', OrgUserPlan::STATUS_ACTIVE);
    }

    /**
     * Build query with search functionality
     */
    public static function buildWithSearch(?string $search = null)
    {
        $query = self::build();
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('orgUser.fullName', 'like', '%' . $search . '%')
                  ->orWhere('orgUser.email', 'like', '%' . $search . '%')
                  ->orWhere('orgUser.phoneNumber', 'like', '%' . $search . '%')
                  ->orWhere('orgUser.number', 'like', '%' . $search . '%');
            });
        }
        
        return $query;
    }

    /**
     * Get active members with pagination
     */
    public static function paginated(?string $search = null, int $perPage = 25)
    {
        return self::buildWithSearch($search)
            ->orderBy('latest_plan.endDate', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get count of active members
     */
    public static function count(?string $search = null): int
    {
        return self::buildWithSearch($search)->count();
    }

    /**
     * Get active members expiring soon (within specified days)
     */
    public static function expiringSoon(int $days = 7, ?string $search = null)
    {
        return self::buildWithSearch($search)
            ->whereRaw('DATEDIFF(latest_plan.endDate, CURDATE()) <= ?', [$days])
            ->whereRaw('DATEDIFF(latest_plan.endDate, CURDATE()) >= 0')
            ->orderBy('latest_plan.endDate', 'asc');
    }
}
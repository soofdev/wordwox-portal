<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\OrgUserPlan;

class AllMembersQuery
{
    /**
     * Build the complete members query showing ALL members with their active status
     * This query includes all members and determines their active status based on
     * whether they have an active membership or not.
     * 
     * IMPORTANT: This query includes tenant filtering to ensure users only see
     * members from their own organization (org_id).
     */
    public static function build()
    {
        // Get current user's org_id for tenant filtering
        $orgId = null;
        
        try {
            $orgId = auth()->user()->orgUser->org_id ?? null;
        } catch (\Exception $e) {
            // Handle case where user is not authenticated or orgUser is null
            $orgId = null;
        }
        
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
                'orgUser.photoFilePath',
                'orgUser.portraitFilePath',
                'orgUser.created_at',
                'orgUser.updated_at',
                // Active plan data from LEFT JOIN - may be NULL for inactive members
                'active_plan.id as plan_id',
                'active_plan.name as plan_name', 
                'active_plan.status as plan_status',
                'active_plan.endDateLoc as plan_end_date',
                'active_plan.startDateLoc as plan_start_date',
                // Days until expiration (NULL if no active plan)
                DB::raw("CASE 
                    WHEN active_plan.endDate IS NOT NULL 
                    THEN DATEDIFF(active_plan.endDate, CURDATE()) 
                    ELSE NULL 
                END AS expires_in_days"),
                // Determine if member is active based on having an active plan
                DB::raw("CASE 
                    WHEN active_plan.id IS NOT NULL 
                    THEN 'active' 
                    ELSE 'inactive' 
                END AS membership_status"),
                // Add a numeric sort field to ensure active members come first
                DB::raw("CASE 
                    WHEN active_plan.id IS NOT NULL 
                    THEN 1 
                    ELSE 0 
                END AS is_active_sort")
            ])
            ->leftJoin('user', 'orgUser.user_id', '=', 'user.id')
            // LEFT JOIN to get active plan (if any) - using same deduplication logic
            ->leftJoin(DB::raw('(
                SELECT oup1.orgUser_id, oup1.id, oup1.name, oup1.status, oup1.endDate, oup1.startDate, oup1.endDateLoc, oup1.startDateLoc
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
            ) as active_plan'), 'orgUser.id', '=', 'active_plan.orgUser_id')
            ->where('orgUser.org_id', $orgId)
            ->where('orgUser.isArchived', false)
            ->whereNull('orgUser.deleted_at');
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
     * Get all members with pagination
     */
    public static function paginated(?string $search = null, int $perPage = 25, ?string $statusFilter = null)
    {
        $query = self::buildWithSearch($search);
        
        // Add status filter if provided
        if ($statusFilter === 'active') {
            $query->whereNotNull('active_plan.id');
        } elseif ($statusFilter === 'inactive') {
            $query->whereNull('active_plan.id');
        }
        
        return $query
            ->orderBy('is_active_sort', 'desc')    // Active members first (1 before 0)
            ->orderBy('orgUser.fullName', 'asc')   // Then alphabetical by name
            ->paginate($perPage);
    }

    /**
     * Get members for infinite scroll (with offset/limit instead of pagination)
     */
    public static function getMembers(?string $search = null, int $limit = 25, int $offset = 0, ?string $statusFilter = null)
    {
        $query = self::buildWithSearch($search);
        
        // Add status filter if provided
        if ($statusFilter === 'active') {
            $query->whereNotNull('active_plan.id');
        } elseif ($statusFilter === 'inactive') {
            $query->whereNull('active_plan.id');
        }
        
        return $query
            ->orderBy('is_active_sort', 'desc')    // Active members first (1 before 0)
            ->orderBy('orgUser.fullName', 'asc')   // Then alphabetical by name
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of all members
     */
    public static function count(?string $search = null, ?string $statusFilter = null): int
    {
        $query = self::buildWithSearch($search);
        
        // Add status filter if provided
        if ($statusFilter === 'active') {
            $query->whereNotNull('active_plan.id');
        } elseif ($statusFilter === 'inactive') {
            $query->whereNull('active_plan.id');
        }
        
        return $query->count();
    }

    /**
     * Get count of active members only
     */
    public static function activeCount(?string $search = null): int
    {
        return self::count($search, 'active');
    }

    /**
     * Get count of inactive members only
     */
    public static function inactiveCount(?string $search = null): int
    {
        return self::count($search, 'inactive');
    }

    /**
     * Get members expiring soon (within specified days)
     */
    public static function expiringSoon(int $days = 7, ?string $search = null)
    {
        return self::buildWithSearch($search)
            ->whereNotNull('active_plan.id')
            ->whereRaw('DATEDIFF(active_plan.endDate, CURDATE()) <= ?', [$days])
            ->whereRaw('DATEDIFF(active_plan.endDate, CURDATE()) >= 0')
            ->orderBy('active_plan.endDate', 'asc');
    }

    /**
     * Get recently created members (ordered by created_at desc)
     */
    public static function recentMembers(int $limit = 5)
    {
        return self::build()
            ->orderBy('orgUser.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get members created today
     */
    public static function todaysNewMembers()
    {
        // Convert today to timestamp range since created_at is stored as Unix timestamp
        $todayStart = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        return self::build()
            ->where('orgUser.created_at', '>=', $todayStart)
            ->where('orgUser.created_at', '<=', $todayEnd)
            ->orderBy('orgUser.created_at', 'desc')
            ->get();
    }

    /**
     * Get members created on a specific date
     */
    public static function membersCreatedOnDate($date)
    {
        // Convert date to timestamp range since created_at is stored as Unix timestamp
        $startOfDay = strtotime($date . ' 00:00:00');
        $endOfDay = strtotime($date . ' 23:59:59');
        
        return self::build()
            ->where('orgUser.created_at', '>=', $startOfDay)
            ->where('orgUser.created_at', '<=', $endOfDay)
            ->orderBy('orgUser.created_at', 'desc')
            ->get();
    }

    /**
     * Get paginated members created on a specific date
     */
    public static function membersCreatedOnDatePaginated($date, $perPage = 10)
    {
        // Convert date to timestamp range since created_at is stored as Unix timestamp
        $startOfDay = strtotime($date . ' 00:00:00');
        $endOfDay = strtotime($date . ' 23:59:59');
        
        $query = self::build();
        
        // If build() returns an empty query (no org_id), it will return empty paginated results
        return $query
            ->where('orgUser.created_at', '>=', $startOfDay)
            ->where('orgUser.created_at', '<=', $endOfDay)
            ->orderBy('orgUser.created_at', 'desc')
            ->paginate($perPage);
    }
}
# Active Members Analysis - wodworx-core to FOH Port

## Overview

This document provides a comprehensive analysis of how active members are listed in the wodworx-core project's `App\Filament\Resources\OrgUserActiveResource\Pages\ListOrgUsers`. This analysis will serve as the foundation for implementing a similar active members listing in the FOH project.

## 🎯 Definition of Active Member

According to the wodworx-core implementation, an **active member** is defined as an `OrgUser` who has at least one `OrgUserPlan` (membership) with the following criteria:

- **Status = 2** (Active status from `OrgUserPlanStatus::Active`)
- **isDeleted = false** (not soft deleted)
- **isArchived = false** (user is not archived)

## 📊 Core Database Schema

### OrgUser Table
The main user/member table with these key fields:
```sql
orgUser (
  id, uuid, number, fullName, org_id, user_id,
  isOwner, isAdmin, isStaff, isOnRoster, isCustomer,
  isDeleted, isArchived, phoneCountry, phoneNumber, 
  email, gender, dob, active_at, ...
)
```

### OrgUserPlan Table  
The membership/plan table with these key fields:
```sql
orgUserPlan (
  id, uuid, orgUser_id, orgPlan_id, name, 
  status, startDate, endDate, startDateLoc, endDateLoc,
  isDeleted, isCanceled, ...
)
```

### Status Constants
From `App\Enums\OrgUserPlanStatus`:
```php
enum OrgUserPlanStatus: int
{
    case None = 0;
    case Upcoming = 1;
    case Active = 2;        // ← This is what determines "active"
    case Hold = 3;
    case Canceled = 4;
    case Deleted = 5;
    case ExpiredLimit = 98;
    case Expired = 99;
}
```

## 🔍 Active Member Query Logic

### The Challenge: Duplicate Prevention
The main challenge is that users can have multiple active memberships simultaneously. For example, a user might have:
- "1 Month Limited Membership" (Jul 5 - Aug 4, 2025)
- "12 class PT" (Jul 5 - Aug 4, 2025)  
- "GX - 3 Sessions" (Jul 5 - Jul 12, 2025)

Without proper handling, this user would appear 3 times in the active members list.

### Solution: MySQL 5.7 Compatible Deduplication

Since MySQL 5.7 doesn't support window functions (ROW_NUMBER() OVER), the core system uses a multi-level MAX() aggregation approach to ensure exactly one record per user:

1. **GROUP BY orgUser_id** - gets one record per user
2. **MAX(startDate)** - gets the latest start date for each user
3. **MAX(endDate)** - among records with the same start date, gets the latest end date
4. **MAX(id)** - as final tie-breaker, gets the highest ID

This ensures deterministic results and prevents duplicates while showing the user's most relevant active membership (latest start, longest duration, highest ID).

### Complete Query Structure

```sql
SELECT 
    orgUser.id, orgUser.uuid, orgUser.number, orgUser.fullName,
    orgUser.org_id, orgUser.isOwner, orgUser.isAdmin, orgUser.isStaff,
    orgUser.isOnRoster, orgUser.isCustomer, orgUser.isDeleted, 
    orgUser.isArchived, orgUser.phoneCountry, orgUser.phoneNumber,
    CONCAT('+(', orgUser.phoneCountry, ') ', orgUser.phoneNumber) AS fullPhoneNumber,
    orgUser.email, orgUser.gender, orgUser.dob,
    -- Age calculation in days
    CASE WHEN orgUser.dob IS NOT NULL THEN
        CASE WHEN DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d') <= CURDATE() THEN
          DATEDIFF(DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d'), CURDATE())
        ELSE
          DATEDIFF(CURDATE(), DATE_FORMAT(CONCAT(YEAR(CURDATE()) - 1, '-', DATE_FORMAT(orgUser.dob, '%m-%d')), '%Y-%m-%d'))
        END
      ELSE NULL END AS dobDays,
    -- Profile image path
    IF(LENGTH(orgUser.portraitFileName) > 0, orgUser.portraitFilePath, orgUser.photoFilePath) AS photoFilePath,
    orgUser.active_at,
    -- Latest plan data from JOIN - GUARANTEED ONE RECORD PER USER
    latest_plan.id as plan_id,
    latest_plan.name as plan_name, 
    latest_plan.status as plan_status,
    latest_plan.endDate as Last_User_Plan_End_Date,
    -- Days until expiration
    DATEDIFF(latest_plan.endDate, CURDATE()) AS expiresIn,
    -- Days since expiration (for expired plans)
    CASE WHEN latest_plan.endDate < CURDATE() THEN DATEDIFF(CURDATE(), latest_plan.endDate) ELSE NULL END AS inactiveDays
FROM orgUser
LEFT JOIN user ON orgUser.user_id = user.id
-- CRITICAL: This subquery ensures exactly ONE record per orgUser_id
-- Uses MySQL 5.7 compatible MAX() aggregation instead of window functions
LEFT JOIN (
    SELECT oup1.orgUser_id, oup1.id, oup1.name, oup1.status, oup1.endDate, oup1.startDate
    FROM orgUserPlan oup1
    INNER JOIN (
        SELECT orgUser_id, MAX(startDate) as max_start_date, MAX(endDate) as max_end_date, MAX(id) as max_id
        FROM orgUserPlan 
        WHERE status IN (2) AND isDeleted != true
        GROUP BY orgUser_id
    ) oup2 ON oup1.orgUser_id = oup2.orgUser_id 
        AND oup1.startDate = oup2.max_start_date 
        AND oup1.endDate = oup2.max_end_date
        AND oup1.id = oup2.max_id
    WHERE oup1.status IN (2) AND oup1.isDeleted != true
) as latest_plan ON orgUser.id = latest_plan.orgUser_id
WHERE orgUser.isArchived = false
  AND latest_plan.id IS NOT NULL
  AND latest_plan.status = 2
ORDER BY latest_plan.endDate ASC
```

## 🎨 FOH Card Grid Layout Design

### Card Structure (ID Card Metaphor)
Each member is displayed as an individual card with the following sections:

1. **Header Section**
   - Large avatar (profile photo or initials)
   - Member full name (truncated at 20 chars)
   - Member number (with # prefix, monospace font)
   - Status badge (Active/Inactive, color-coded green/zinc)

2. **Contact Information Section**
   - Email address with envelope icon
   - Phone number (formatted internationally) with phone icon
   - Section separated by border for visual clarity

3. **Membership Plan Section**
   - Credit card icon with "Membership Plan" label
   - Plan name (or "No active membership" if none)
   - Start and end dates (formatted as "M j, Y")

4. **Expiration Information Section**
   - Calendar icon with "Expires" label
   - Days until expiration (color-coded):
     - **Red** (≤7 days): "Today", "Tomorrow", or "In X days"
     - **Yellow** (8-30 days): "In X days"
     - **Green** (>30 days): "In X days"
     - **Red** (expired): "X days ago"

### Layout Configuration
- **Responsive Grid**:
  - Mobile (1 column)
  - Tablet (2 columns)
  - Desktop (3 columns)
  - Large screens (4 columns)
- **Gap**: 1rem between cards

### Key Features
- **Search**: Full name, email, phone, and member number search functionality
- **Filters**: Status filter (All/Active/Inactive), per-page selector (10/25/50/100)
- **Infinite Scroll**: Auto-loads more members as user scrolls down
- **Click Action**: Click anywhere on card to navigate to member profile
- **Hover Effects**: 
  - Card shadow increases and border changes to blue
  - Member name transitions to blue color
- **Scroll to Top**: Floating button appears after scrolling 300px

## 🔧 Implementation Components for FOH

### 1. Models Required

#### OrgUser Model Enhancements
```php
class OrgUser extends BaseWWModel
{
    // Relationship to active memberships
    public function activeMemberships()
    {
        return $this->hasMany(OrgUserPlan::class, 'orgUser_id')
                    ->where('status', 2)
                    ->where('isDeleted', false);
    }
    
    // Get the latest active membership
    public function latestActiveMembership()
    {
        return $this->activeMemberships()
                    ->orderBy('startDate', 'desc')
                    ->orderBy('endDate', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
    }
    
    // Check if user has any active memberships
    public function hasActiveMembership(): bool
    {
        return $this->activeMemberships()->exists();
    }
}
```

#### OrgUserPlan Model Enhancements
```php
class OrgUserPlan extends BaseWWModel
{
    // Status constants (matching core system)
    const STATUS_NONE = 0;
    const STATUS_UPCOMING = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_HOLD = 3;
    const STATUS_CANCELED = 4;
    const STATUS_DELETED = 5;
    const STATUS_EXPIRED_LIMIT = 98;
    const STATUS_EXPIRED = 99;
    
    // Active scope
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('isDeleted', false);
    }
}
```

### 2. Query Builder for Active Members

```php
class ActiveMembersQuery
{
    public static function build()
    {
        return DB::table('orgUser')
            ->select([
                'orgUser.id',
                'orgUser.uuid', 
                'orgUser.number',
                'orgUser.fullName',
                'orgUser.email',
                'orgUser.phoneCountry',
                'orgUser.phoneNumber',
                DB::raw("CONCAT('+(', orgUser.phoneCountry, ') ', orgUser.phoneNumber) AS fullPhoneNumber"),
                'orgUser.gender',
                'orgUser.dob',
                'latest_plan.id as plan_id',
                'latest_plan.name as plan_name',
                'latest_plan.endDate as plan_end_date',
                DB::raw("DATEDIFF(latest_plan.endDate, CURDATE()) AS expires_in_days")
            ])
            ->leftJoin(DB::raw('(
                SELECT oup1.orgUser_id, oup1.id, oup1.name, oup1.status, oup1.endDate, oup1.startDate
                FROM orgUserPlan oup1
                INNER JOIN (
                    SELECT orgUser_id, MAX(startDate) as max_start_date, MAX(endDate) as max_end_date, MAX(id) as max_id
                    FROM orgUserPlan 
                    WHERE status = 2 AND isDeleted != true
                    GROUP BY orgUser_id
                ) oup2 ON oup1.orgUser_id = oup2.orgUser_id 
                    AND oup1.startDate = oup2.max_start_date 
                    AND oup1.endDate = oup2.max_end_date
                    AND oup1.id = oup2.max_id
                WHERE oup1.status = 2 AND oup1.isDeleted != true
            ) as latest_plan'), 'orgUser.id', '=', 'latest_plan.orgUser_id')
            ->where('orgUser.isArchived', false)
            ->whereNotNull('latest_plan.id')
            ->orderBy('latest_plan.endDate', 'asc');
    }
}
```

### 3. Livewire Component for FOH

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class ActiveMembersList extends Component
{
    use WithPagination;
    
    public $search = '';
    
    public function render()
    {
        $activeMembers = ActiveMembersQuery::build()
            ->when($this->search, function ($query) {
                $query->where('orgUser.fullName', 'like', '%' . $this->search . '%')
                      ->orWhere('orgUser.email', 'like', '%' . $this->search . '%')
                      ->orWhere('orgUser.phoneNumber', 'like', '%' . $this->search . '%');
            })
            ->paginate(25);
            
        return view('livewire.active-members-list', [
            'activeMembers' => $activeMembers
        ]);
    }
}
```

## 🚨 Critical Implementation Notes

### Database Considerations
1. **MySQL Version**: The query is designed for MySQL 5.7 compatibility
2. **Indexes Required**: Ensure proper indexing on:
   - `orgUserPlan(orgUser_id, status, isDeleted)`
   - `orgUser(isArchived)`
   - `orgUserPlan(startDate, endDate, id)`

### Query Performance
1. The subquery approach ensures exactly one record per user
2. The MAX() aggregation is deterministic and prevents race conditions  
3. Always test with users who have multiple active memberships
4. Monitor query performance with large datasets

### Status Values
- **Critical**: Status value `2` means "Active" in the core system
- This maps to `OrgUserPlanStatus::Active = 2`
- FOH project should use the same status values for consistency

## 🔄 Integration with FOH Project

### Shared Database Tables
- Uses the same `orgUser` and `orgUserPlan` tables
- Maintains data consistency with the core system
- No additional tables required for basic active member listing

### Multi-Tenancy
- All queries automatically filtered by `org_id` through TenantScope
- Each organization sees only their active members
- Maintains proper data isolation

### Future Enhancements
1. **Export Functionality**: Add CSV/Excel export of active members
2. **Advanced Filters**: Filter by plan type, expiration date range
3. **Bulk Actions**: Send notifications to active members
4. **Member Details**: Quick view modal with membership details

## 📝 Next Steps for FOH Implementation

1. **Update Models**: Enhance OrgUser and OrgUserPlan models with active member methods
2. **Create Query Builder**: Implement the ActiveMembersQuery class
3. **Build Livewire Component**: Create the active members list component
4. **Design UI**: Create responsive card-based grid layout for active members (✅ **COMPLETED**)
5. **Add Navigation**: Integrate into the FOH navigation structure
6. **Testing**: Test with users having multiple active memberships
7. **Performance**: Optimize query performance and add proper indexes

## ✅ Implementation Status

### Completed Features
- **Card-based Grid Layout**: Converted from table to responsive card grid (ID card metaphor)
- **Responsive Design**: 1-4 columns based on screen size
- **Infinite Scroll**: Implemented with Alpine.js for seamless loading
- **Visual Hierarchy**: Icons, borders, and color-coding for better UX
- **Search & Filters**: Full-text search with status and pagination filters
- **Hover States**: Enhanced interactivity with shadow and color transitions

### UI/UX Benefits
- **Distinct from other screens**: Members screen now has unique visual identity separate from Memberships and Holds tables
- **ID Card metaphor**: Cards resemble member ID cards, making the purpose immediately clear
- **Better visual scanning**: Photos and names are more prominent in card layout
- **Improved mobile experience**: Cards stack naturally on smaller screens
- **Human-centric design**: Focus on people rather than data rows

This analysis provides the complete foundation for replicating the wodworx-core active members functionality in the FOH project while maintaining consistency and performance.
# Membership Holds Feature Documentation

## Overview

The Membership Holds feature allows gym staff to temporarily pause member memberships for specified periods. This feature is essential for member retention, enabling gyms to accommodate members who need to take breaks due to vacations, injuries, medical reasons, or other life circumstances without losing their membership entirely.

## Purpose & Business Value

### Why Membership Holds Matter

**Member Retention**: Holds prevent member churn by providing flexibility during temporary absences rather than forcing cancellations.

**Revenue Protection**: Members remain committed to the gym and typically return after holds expire, maintaining long-term revenue streams.

**Operational Efficiency**: Staff can manage holds efficiently through both individual and bulk operations, reducing administrative overhead.

**Member Satisfaction**: Provides flexibility and demonstrates that the gym values member circumstances, improving overall satisfaction and loyalty.

## Key Concepts

### Hold Status Lifecycle

A membership hold progresses through the following statuses:

- **None (0)**: No hold exists
- **Upcoming (1)**: Hold is scheduled to start in the future
- **Active (2)**: Hold is currently in effect; membership is paused
- **Canceled (98)**: Hold was canceled before completion
- **Expired (99)**: Hold has ended; membership is resumed

### Hold Duration & Limits

Organizations can configure limits for membership holds:

- **Hold Count Limit**: Maximum number of holds allowed per membership
- **Hold Days Limit**: Maximum cumulative days a membership can be held
- **These limits are enforced during hold creation and modification**

### Membership Status During Holds

When a hold becomes active:
- Membership status changes to "Hold" (3)
- Member cannot check in or use gym facilities
- Membership end date is automatically extended by the hold duration
- All membership access is suspended

When a hold ends (naturally or manually):
- Membership status returns to "Active" (2)
- Member regains full access
- Remaining membership days continue from where they left off

### Bulk Hold Groups

Bulk holds create multiple hold records that are grouped together using a standardized naming convention:
```
{unique_id}-hold-{start_date}-{end_date}
```

This grouping allows staff to:
- Track which holds were created together
- End entire groups of holds simultaneously
- View bulk hold history and management

## User Actions

FOH staff can perform the following operations based on their assigned permissions:

### 1. View All Holds

**Route**: `/subscriptions/holds`

**Description**: Access a comprehensive list of all membership holds across the organization.

**Features**:
- Search holds by member name or email
- Filter by status (All, Active, Upcoming, Expired, Canceled)
- Sort by multiple columns (member name, dates, status, duration)
- Paginated display (15 holds per page)
- Click any row to view detailed information
- Real-time status badges with color coding

**Permission Required**: `view memberships`

---

### 2. View Individual Hold Details

**Route**: `/subscriptions/holds/{holdId}`

**Description**: View complete details about a specific hold.

**Information Displayed**:
- Member information (name, photo, email, phone)
- Membership details (plan name, type, dates)
- Hold overview card with status badge
- Hold start and end dates (formatted for timezone)
- Hold duration in days
- Creator information (who created the hold)
- Hold reason/notes
- All related notes and history
- Available actions based on hold status

**Permission Required**: `view memberships`

---

### 3. Create Single Hold

**Route**: From membership detail page (`/subscriptions/{id}`)

**Description**: Pause an individual member's membership for a specified period.

**Form Fields**:
- **Hold Start Date**: Future date when hold should begin (date picker)
- **Hold End Date**: Date when hold should end (must be after start date)
- **Reason**: Optional explanation for the hold (textarea, up to 500 characters)
- **Auto Resume**: Checkbox to automatically reactivate membership when hold ends
- **Send Email Notification**: Toggle to notify member via email
- **Send Push Notification**: Toggle to send mobile app notification

**Validation**:
- Start date must be in the future
- End date must be after start date
- No overlapping holds allowed
- Hold count limit not exceeded
- Hold days limit not exceeded
- Membership must be active or upcoming
- Holds must be enabled for the plan
- Membership must be modifiable

**Permission Required**: `hold memberships`

---

### 4. Create Bulk Hold by Plan

**Route**: From holds listing page (`/subscriptions/holds`)

**Description**: Hold all members of a specific gym plan simultaneously.

**Workflow**:
1. Click "Create Bulk Hold" button
2. Select "Specific Plan" option
3. Choose plan from dropdown (shows all active plans)
4. View real-time count of affected memberships
5. Set start and end dates
6. Optionally add reason and notification preferences
7. Preview validation to see conflicts (optional)
8. Submit to dispatch background job

**Features**:
- Real-time affected membership count
- Preview validation showing which members can/cannot be held
- Detailed conflict detection and reporting
- Background processing for large operations
- Progress indication and completion notification

**Permission Required**: `hold memberships`

---

### 5. Create Bulk Hold by Selected Users

**Route**: From holds listing page (`/subscriptions/holds`)

**Description**: Search for and select specific members to hold their memberships together.

**Workflow**:
1. Click "Create Bulk Hold" button
2. Select "Specific Users" option
3. Type in search box (minimum 2 characters)
4. View matching users with avatars and membership info
5. Check boxes to select users (multi-select)
6. View real-time count of selected members
7. Set dates and options
8. Submit to process selected holds

**Search Features**:
- Real-time search as you type
- Searches by member name and email
- Shows member avatars for easy identification
- Displays current membership status
- Limited to 50 results for performance

**Permission Required**: `hold memberships`

---

### 6. Create Bulk Hold for All Active Members

**Route**: From holds listing page (`/subscriptions/holds`)

**Description**: Hold all active memberships in the entire organization with one action.

**Workflow**:
1. Click "Create Bulk Hold" button
2. Select "All Users" option
3. View total count of active memberships
4. Set date range for the hold
5. Add optional reason
6. Configure notification preferences
7. Submit to process organization-wide hold

**Use Cases**:
- Gym closures for renovations
- Holiday shutdowns
- Emergency closures (natural disasters, pandemics)
- Facility maintenance periods

**Warning**: This is a powerful operation that affects all members. Confirmation is required before processing.

**Permission Required**: `hold memberships`

---

### 7. Modify Hold

**Route**: From hold detail page

**Description**: Edit the dates of an existing hold based on its current status.

**Modification Rules**:

**For Upcoming Holds**:
- Can modify both start date and end date
- Start date must remain in the future
- End date must be after start date
- All standard validation applies

**For Active Holds**:
- Can only modify end date (extend or shorten)
- Cannot modify start date (already started)
- New end date must be after today
- Can extend or reduce duration

**For Canceled/Expired Holds**:
- Cannot be modified
- Must create a new hold instead

**Form Fields**:
- Hold Start Date (disabled for active holds)
- Hold End Date (always editable for active/upcoming)
- Modification reason (optional note)

**Permission Required**: `modify hold`

---

### 8. End Hold Early

**Route**: From hold detail page or membership view

**Description**: Terminate an active or upcoming hold before its scheduled end date.

**Workflow**:
1. View hold details
2. Click "End Hold" button
3. Enter optional reason for ending early
4. Confirm action
5. System processes end operation

**What Happens**:
- Hold status changes to "Expired"
- Membership is immediately reactivated
- Membership end date is extended by the actual hold duration used
- End note is added to hold record
- System note is added to membership notes
- Member regains immediate access to gym

**Use Cases**:
- Member returns early from planned absence
- Medical clearance received sooner than expected
- Member requests early reactivation

**Permission Required**: `end hold`

---

### 9. Cancel Hold

**Route**: From hold detail page or membership view

**Description**: Cancel an upcoming or active hold completely, as if it never happened.

**Workflow**:
1. View hold details
2. Click "Cancel Hold" button
3. Enter optional cancellation reason
4. Confirm cancellation
5. System processes cancellation

**What Happens**:
- Hold is marked as canceled (`isCanceled = true`)
- Hold status changes to "Canceled"
- Membership is immediately reactivated
- Membership end date is extended by hold duration
- Cancellation note is added
- Hold remains in system for historical tracking but is inactive

**Difference from End Hold**:
- Cancel: Treats hold as invalid; full duration added back
- End: Recognizes hold was valid; only unused portion added back

**Permission Required**: `cancel hold`

---

### 10. End Bulk Hold

**Route**: From holds listing page (`/subscriptions/holds`)

**Description**: End an entire group of holds that were created together in a bulk operation.

**Workflow**:
1. Click "End Bulk Hold" button
2. View dropdown of existing bulk hold groups
3. Select group (shows name, hold count, date range)
4. View affected holds count
5. Enter optional end reason
6. Submit to dispatch background job
7. All holds in group are ended simultaneously

**Group Selection Display**:
- Group name (with timestamp)
- Number of active holds in group
- Original date range
- Only shows groups with active/upcoming holds

**Permission Required**: `end hold`

---

### 11. Preview Hold Validation

**Route**: Available in bulk hold creation modal

**Description**: Before creating bulk holds, preview detailed validation results to identify potential issues.

**Workflow**:
1. Configure bulk hold (select users, dates, options)
2. Click "Preview Validation" button
3. View detailed validation results table

**Information Shown**:
- Member name and photo
- Membership plan name
- Current membership status
- Can hold? (Yes/No with color coding)
- Specific reason if cannot hold
- Conflict category
- Existing hold details (if applicable)
- Overlapping hold information

**Conflict Categories**:
- Active Hold Conflict: Member already has an active hold
- Overlapping Hold: Proposed dates overlap with existing hold
- Hold Limit Exceeded: Member has reached hold count limit
- Days Limit Exceeded: Would exceed allowed hold days
- Inactive Membership: Membership is not active
- Hold Not Enabled: Plan doesn't allow holds
- Not Modifiable: Membership cannot be modified

**Benefits**:
- Identify issues before creating holds
- Understand why certain members can't be held
- Make informed decisions about date ranges
- Avoid failed hold creation attempts

**Permission Required**: `hold memberships`

---

### 12. View Conflicted Memberships

**Route**: `/subscriptions/conflicted`

**Description**: View a list of members who should have been affected by bulk holds but weren't due to conflicts or validation failures.

**Purpose**:
- Quality assurance for bulk operations
- Identify members needing individual attention
- Troubleshoot bulk hold issues
- Ensure all intended members are properly held

**Information Displayed**:
- Member details
- Membership information
- Reason for exclusion
- Suggested actions
- Links to member profiles

**Use Cases**:
- After bulk hold operations
- Auditing hold coverage
- Following up on failed holds
- Ensuring comprehensive hold application

**Permission Required**: `view memberships`

---

### 13. View Hold History on Member Profile

**Route**: From member profile page

**Description**: View complete hold history for a specific member across all their memberships.

**Information Displayed**:
- All past holds with dates and durations
- Current active hold (if any)
- Upcoming scheduled holds
- Hold status for each record
- Creator information
- Hold reasons and notes
- Cancellation/end information

**Timeline View**:
- Chronological display of all holds
- Visual indicators for status
- Duration calculations
- Total days held (lifetime)

**Benefits**:
- Complete member hold history
- Identify patterns of use
- Make informed decisions about new holds
- Member support and customer service

**Permission Required**: `view memberships`

---

## Business Rules & Validation

### Hold Creation Requirements

1. **Membership Status**: Must be Active (2) or Upcoming (1)
2. **Plan Configuration**: Holds must be enabled (`isHoldEnabled = true`)
3. **Modifiability**: Membership must be modifiable (`can_be_modified = true`)
4. **Date Requirements**: Start date must be future; end date after start date
5. **No Overlaps**: Cannot have overlapping holds on same membership
6. **Count Limit**: Cannot exceed plan's hold count limit
7. **Days Limit**: Cannot exceed plan's cumulative days limit

### Automatic Membership Extension

When a hold is created or ends, the system automatically extends the membership end date to ensure members receive their full membership value:

- **Hold Created**: End date extended by hold duration
- **Hold Ends Naturally**: No adjustment needed (already extended)
- **Hold Ended Early**: Extension adjusted to actual hold days used
- **Hold Canceled**: Full hold duration added back to membership

### Hold Limits Calculation

The system tracks:
- **holdCount**: Number of holds created on membership
- **holdDays**: Total cumulative days membership has been held

Validation compares:
- `holdCount < holdLimitCount` (from plan)
- `holdDays + new_duration <= holdLimitDays` (from plan)

## Permission Requirements

| Action | Permission | Notes |
|--------|-----------|-------|
| View holds list | `view memberships` | Base permission for viewing |
| View hold details | `view memberships` | Required for detail pages |
| Create single hold | `hold memberships` | Individual hold creation |
| Create bulk hold | `hold memberships` | All bulk hold types |
| Modify hold dates | `modify hold` | Edit existing holds |
| End hold early | `end hold` | Terminate before end date |
| Cancel hold | `cancel hold` | Invalidate hold completely |
| End bulk hold | `end hold` | End groups of holds |

### Permission Inheritance

The default **Admin** role receives all hold-related permissions automatically and they cannot be removed (protected).

The **Sales** role typically includes:
- `hold memberships`
- `modify hold`
- `end hold`
- `cancel hold`

The **Reception** role typically includes:
- `hold memberships` (basic hold creation only)

## Technical Architecture

### Components Overview

**Livewire Components**:
- `HoldMembership`: Single hold creation form
- `BulkCreateHold`: Bulk hold creation interface
- `BulkEndHold`: Bulk hold ending interface
- `HoldDetail`: Individual hold detail view
- `HoldOverview`: Hold information card
- `MembershipHolds`: Holds listing page
- `ModifyHold`: Hold modification form
- `EndHold`: End hold confirmation
- `CancelHold`: Cancel hold confirmation
- `ConflictedMemberships`: Conflict tracking

**Services**:
- `OrgUserPlanHoldService`: Core business logic
- `NotesService`: Hold-related notes
- `NotificationsHelper`: Email/push notifications

**Jobs**:
- `BulkHoldJob`: Background bulk hold creation
- `BulkEndHoldJob`: Background bulk hold ending
- `SendHoldNotificationJob`: Notification delivery

**Models**:
- `OrgUserPlanHold`: Hold record model
- `OrgUserPlan`: Membership model
- `OrgUser`: Member model

## Integration Points

### With Membership System
- Automatic status updates (Active ↔ Hold)
- End date extensions
- Modifiability checks
- Plan configuration validation

### With Notification System
- Email notifications to members
- Push notifications to mobile app
- SMS notifications (future enhancement)
- Staff notifications for bulk operations

### With Notes System
- Automatic note creation on hold events
- Hold reasons stored as notes
- Modification history tracking
- Cancellation/end reason logging

### With RBAC System
- Permission checks for all actions
- Role-based button visibility
- Operation authorization
- Audit logging

## Related Documentation

- [Multi-Step Wizard Implementation](../multi-step-wizard-implementation-guide.md) - Related membership flow patterns
- [RBAC System](../../rbac/README.md) - Permission system details
- [Bulk Hold Feature](../../bulk-hold-feature.md) - Technical bulk operations documentation (legacy location)
- [Hold System Documentation](../../../HOLD_SYSTEM_DOCUMENTATION.md) - Complete technical reference (legacy location)

## Future Enhancements

### Planned Features
1. **Hold Templates**: Pre-configured hold scenarios for common situations
2. **Automated Holds**: System-triggered holds based on rules (e.g., payment failures)
3. **Hold Analytics**: Reporting on hold usage patterns and trends
4. **SMS Notifications**: Text message notifications for hold events
5. **Flexible Hold Scheduling**: Recurring holds, partial-week holds
6. **Member Self-Service**: Allow members to request holds through member portal
7. **Hold Approvals**: Workflow for hold approval by managers
8. **Prorated Holds**: Calculate partial refunds for paid-in-advance memberships

### Considerations for Future Development
- Integration with payment processing for refund calculations
- Mobile app integration for member-initiated holds
- Advanced reporting and analytics dashboard
- Automated communication sequences for hold lifecycle
- Integration with class/appointment scheduling systems

---

**Document Version**: 1.0  
**Last Updated**: October 12, 2025  
**Maintained By**: FOH Development Team


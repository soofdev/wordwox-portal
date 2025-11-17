# Membership Holds Listing Page

## Overview

**Route**: `/subscriptions/holds`  
**Component**: `MembershipHolds` (Livewire)  
**View**: `resources/views/livewire/subscriptions/membership-holds.blade.php`

The Membership Holds listing page is the central hub for managing all membership holds across the organization. It provides a comprehensive table view of all holds (active, upcoming, expired, and canceled) with powerful search, filtering, and sorting capabilities.

## Page Layout

### Header Section
- **Page Title**: "Membership Holds"
- **Description**: "View all membership holds including active, upcoming, and expired holds"
- **Bulk Action Buttons**: Located in the top-right corner (permission-dependent)

### Search & Filter Bar
- **Search Input**: Full-width search field with magnifying glass icon
- **Status Filter Dropdown**: Filter by hold status (positioned on the right)

### Data Table
- **Responsive Layout**: Adapts to different screen sizes
- **Sortable Columns**: Click column headers to sort data
- **Clickable Rows**: Click any row to open hold details in a new tab
- **Status Badges**: Color-coded status indicators
- **Pagination Controls**: Bottom navigation with page numbers

## User Actions

### 1. Search for Holds

**Action**: Type in the search field to find specific holds.

**Search Criteria**:
- Member full name
- Member email address
- Member phone number
- Plan name

**Behavior**:
- **Live Search**: Results update automatically as you type
- **Debounce**: 300ms delay prevents excessive queries
- **Pagination Reset**: Returns to page 1 when search changes
- **Case-Insensitive**: Matches regardless of letter case
- **Partial Matching**: Finds results containing the search term

**Example Searches**:
- "John" - Finds all members named John
- "john@" - Finds members with emails containing "john@"
- "+971" - Finds members with UAE phone numbers
- "Unlimited" - Finds holds on "Unlimited" plan memberships

**Empty Search**:
- Clear the search field to show all holds again

---

### 2. Filter by Hold Status

**Action**: Use the status filter dropdown to view holds of a specific status.

**Available Filters**:
- **All Statuses** (default): Shows all holds regardless of status
- **Upcoming**: Holds scheduled to start in the future
- **Active**: Holds currently in effect (membership is paused)
- **Expired**: Holds that have ended
- **Canceled**: Holds that were canceled before completion

**Behavior**:
- **Live Filtering**: Table updates immediately when status is selected
- **Pagination Reset**: Returns to page 1 when filter changes
- **Combination with Search**: Works together with search to narrow results
- **Color-Coded Badges**: Each status has a distinct color in the table

**Status Badge Colors**:
- **Upcoming**: Blue badge
- **Active**: Green badge
- **Expired**: Gray badge
- **Canceled**: Red badge

**Use Cases**:
- View only active holds to see which members are currently paused
- Check upcoming holds to prepare for future membership pauses
- Review expired holds for historical tracking
- Identify canceled holds for auditing purposes

---

### 3. Sort Table Columns

**Action**: Click on any sortable column header to sort the data.

**Sortable Columns**:
1. **Member** - Sort by member name (alphabetically)
2. **Plan** - Sort by plan name (alphabetically)
3. **Start Date** - Sort by hold start date (chronologically)
4. **End Date** - Sort by hold end date (chronologically)
5. **Duration** - Sort by hold duration in days (numerically)

**Sort Behavior**:
- **First Click**: Sorts ascending (A-Z, earliest to latest, smallest to largest)
- **Second Click**: Reverses to descending (Z-A, latest to earliest, largest to smallest)
- **Third Click**: Returns to ascending
- **Visual Indicator**: Chevron icon shows current sort direction
- **Single Column Sort**: Only one column can be sorted at a time
- **Persistent**: Sort preference maintained in URL query string

**Visual Indicators**:
- **Chevron Up (↑)**: Ascending sort
- **Chevron Down (↓)**: Descending sort (rotated chevron)
- **Active Column**: Shows chevron icon
- **Inactive Columns**: No chevron displayed

**Default Sort**:
- Field: `created_at` (when hold was created)
- Direction: `desc` (newest first)

---

### 4. Navigate Through Pages

**Action**: Use pagination controls to browse through multiple pages of holds.

**Pagination Controls**:
- **Previous Button**: Go to the previous page (disabled on first page)
- **Page Numbers**: Jump directly to a specific page
- **Next Button**: Go to the next page (disabled on last page)

**Page Display Logic**:
- Shows up to 5 page numbers at a time
- Current page highlighted with primary color
- First and last pages always visible
- Ellipsis (...) indicates skipped page numbers

**Pagination Info**:
- Displays: "Showing X to Y of Z results"
- Updates dynamically based on current page
- Located on the left side of pagination controls

**Items Per Page**: 15 holds per page (hardcoded)

**Page Navigation Examples**:
```
Page 1 of 10: [1] 2 3 4 5 ... 10
Page 5 of 10: 1 ... 3 4 [5] 6 7 ... 10
Page 10 of 10: 1 ... 6 7 8 9 [10]
```

---

### 5. View Hold Details

**Action**: Click on any row in the table to view complete hold details.

**What Happens**:
- Opens hold detail page in a **new browser tab**
- Route: `/subscriptions/holds/{holdId}`
- Original listing page remains open
- Allows viewing multiple holds simultaneously

**Clickable Area**:
- Entire row is clickable
- Hover effect indicates clickability (background color changes)
- Cursor changes to pointer on hover

**Information Displayed in Row**:
- **Member Avatar**: Visual identification with initials
- **Member Name**: Full name of the member
- **Member Email**: Contact email address
- **Plan Name**: Membership plan name
- **Start Date**: When hold begins (formatted: "Jan 1, 2025")
- **End Date**: When hold ends (formatted: "Jan 7, 2025")
- **Duration**: Total days (displayed as number)
- **Status Badge**: Current hold status with color coding

---

### 6. Create Bulk Hold

**Action**: Click the "Create Bulk Hold" button to open the bulk hold creation modal.

**Button Appearance**:
- **Color**: Green background (bg-green-600)
- **Icon**: Pause icon
- **Text**: "Create Bulk Hold"
- **Location**: Top-right of page header

**Visibility**:
- Only shown if user has `hold memberships` permission
- Button is hidden for users without permission

**What Happens**:
- Opens modal overlay on same page
- Dispatches `load-create-hold-data` event
- Loads available plans and member data
- Modal name: `bulk-create-hold-modal`

**Modal Features**:
- Select hold type (Specific Plan / Specific Users / All Users)
- Choose date range for holds
- Configure notification preferences
- Preview validation before creating
- Background processing for large operations

**Related Component**: `BulkCreateHold` (Livewire)

---

### 7. End Bulk Hold

**Action**: Click the "End Bulk Hold" button to open the bulk hold ending modal.

**Button Appearance**:
- **Color**: Red background (bg-red-600)
- **Icon**: Play icon
- **Text**: "End Bulk Hold"
- **Location**: Top-right of page header (next to Create Bulk Hold)

**Visibility**:
- Only shown if user has `end hold` permission
- Button is hidden for users without permission

**What Happens**:
- Opens modal overlay on same page
- Dispatches `load-end-hold-data` event
- Loads existing bulk hold groups
- Modal name: `bulk-end-hold-modal`

**Modal Features**:
- Dropdown showing existing bulk hold groups
- Group information (name, count, date range)
- Only shows groups with active/upcoming holds
- Optional end reason field
- Background processing for bulk operations

**Related Component**: `BulkEndHold` (Livewire)

---

### 8. Refresh Table Data

**Action**: Table automatically refreshes when data changes.

**Automatic Refresh Triggers**:
- Hold is created (single or bulk)
- Hold is modified
- Hold is ended
- Hold is canceled
- Status filter changes
- Search query changes
- Sort column changes
- Page navigation

**Manual Refresh**:
- User can refresh browser page
- All filters and search preserved in URL
- Returns to same page state

**Real-time Updates**:
- Livewire wire:model.live updates
- No page reload required
- Smooth transitions
- Loading indicators during data fetch

---

### 9. View Empty States

**Action**: System displays appropriate empty state messages when no holds exist.

**Empty State Scenarios**:

**No Holds at All**:
- Icon: Large pause icon (gray, 16x16 size)
- Heading: "No holds found"
- Message: "There are no membership holds at this time"
- Displayed when: No holds exist in the organization

**No Search Results**:
- Icon: Document text icon (gray, 12x12 size)
- Heading: "No holds found"
- Message: "No holds match your search criteria"
- Displayed when: Search/filter returns no results

**Empty State Design**:
- Centered in table
- Card-style container
- Clear iconography
- Helpful messaging
- Encourages action or adjustment

---

### 10. Use URL Query Parameters

**Action**: Share or bookmark filtered/searched views using URL parameters.

**Supported Query Parameters**:
- `?search=john` - Pre-fills search field
- `?statusFilter=2` - Pre-selects status filter (2 = Active)
- `?sortField=member` - Sets sort column
- `?sortDirection=asc` - Sets sort direction
- `?page=3` - Opens specific page

**Combined Parameters Example**:
```
/subscriptions/holds?search=unlimited&statusFilter=2&sortField=start_date&sortDirection=desc&page=1
```

**Benefits**:
- **Bookmarkable**: Save frequently used filters
- **Shareable**: Send links to colleagues with specific views
- **Browser Navigation**: Back/forward buttons work correctly
- **Persistent State**: Page state maintained across sessions

**URL Updates**:
- Automatically updates when filters change
- No page reload required
- Browser history maintained

---

## Visual Design Elements

### Table Row Hover Effect
- **Default State**: White/transparent background
- **Hover State**: Light zinc background (zinc-50 in light mode)
- **Transition**: Smooth color transition
- **Cursor**: Pointer cursor indicates clickability

### Status Badge Design
- **Shape**: Rounded rectangle (pill shape)
- **Size**: Small (sm)
- **Colors**:
  - Upcoming: Blue (indigo)
  - Active: Green
  - Expired: Gray (zinc)
  - Canceled: Red
- **Typography**: Bold text, uppercase

### Member Avatar
- **Size**: Extra large (xl)
- **Content**: Initials derived from member name
- **Colors**: Automatically generated based on name
- **Shape**: Circular
- **Fallback**: Shows initials if no photo

### Responsive Behavior
- **Desktop**: Full table layout with all columns
- **Tablet**: Maintains table structure, adjusted spacing
- **Mobile**: Responsive card-based layout (Flux UI handles this)

---

## Data Display

### Hold Duration Calculation
- Displayed as whole number of days
- Formula: `round(diffInDays(startDate, endDate))`
- Examples: "7", "14", "30"
- No decimal places

### Date Formatting
- Format: "Mon DD, YYYY" (e.g., "Jan 15, 2025")
- Timezone: Converted to user's organization timezone
- Consistent across all date displays

### Member Information
- **Primary**: Full name in bold
- **Secondary**: Email address in smaller, muted text
- **Avatar**: Shows member initials or photo

### Plan Information
- **Display**: Plan name only
- **Fallback**: "N/A" if plan not found
- **Hover**: Name slightly changes color

---

## Permission Requirements

### View Holds List
**Permission**: `view memberships`

**Access Level**:
- Can see the page
- Can view all holds data
- Can search and filter
- Can click to view details

**Without Permission**:
- Page is inaccessible
- Redirected or shown error message

---

### Create Bulk Hold Button
**Permission**: `hold memberships`

**With Permission**:
- Button is visible in header
- Can open bulk create modal
- Can configure and create bulk holds

**Without Permission**:
- Button is completely hidden
- No placeholder or disabled state
- Section collapses if no bulk buttons visible

---

### End Bulk Hold Button
**Permission**: `end hold`

**With Permission**:
- Button is visible in header
- Can open bulk end modal
- Can select and end bulk hold groups

**Without Permission**:
- Button is completely hidden
- No placeholder or disabled state

---

## Technical Implementation Notes

### Component Architecture
- **Livewire Component**: `MembershipHolds`
- **With Pagination**: Uses Laravel's pagination trait
- **Real-time Updates**: Wire:model.live for instant feedback
- **Event Listeners**: Listens for hold creation/modification events

### Data Source Strategy
The component intelligently chooses data source:

1. **Primary**: `orgUserPlanHold` table (if exists)
   - Full hold information
   - All statuses available
   - Proper status tracking
   - Cancellation support

2. **Fallback**: `orgUserPlan` membership status
   - Limited to active holds only
   - No upcoming/expired distinction
   - Basic hold information from notes
   - Used when hold table doesn't exist

### Query Performance
- **Eager Loading**: Loads relationships upfront
- **Indexed Columns**: Sorts use indexed fields
- **Pagination**: Limits results to 15 per page
- **Search Optimization**: Uses database indexes
- **Conditional Queries**: Only applies active filters

### State Management
- **Query String**: Stores filter/search state in URL
- **Livewire Properties**: Manages component state
- **Pagination**: Laravel pagination system
- **Events**: Dispatches events for modal interactions

---

## User Experience Considerations

### Performance
- **Debounced Search**: 300ms delay prevents excessive queries
- **Pagination**: Keeps page loads fast with limited results
- **Conditional Loading**: Only loads data when needed
- **Optimized Queries**: Eager loading prevents N+1 problems

### Accessibility
- **Keyboard Navigation**: Tab through all interactive elements
- **Screen Reader Support**: Semantic HTML and ARIA labels
- **Clear Labels**: Descriptive button and field labels
- **Status Indicators**: Color plus text for status (not color-only)

### Mobile Responsiveness
- **Flexible Layout**: Adapts to screen width
- **Touch Targets**: Large enough for finger taps
- **Readable Text**: Appropriate font sizes
- **Scrollable Table**: Horizontal scroll on narrow screens

### User Feedback
- **Loading States**: Shows when data is loading
- **Empty States**: Clear messaging when no results
- **Hover Effects**: Visual feedback on interactive elements
- **Sort Indicators**: Shows current sort state clearly

---

## Common User Workflows

### Workflow 1: Find a Specific Member's Hold
1. Enter member name in search field
2. Wait for results to filter automatically
3. Locate member in filtered results
4. Click row to view hold details

### Workflow 2: Review All Active Holds
1. Open holds listing page
2. Select "Active" from status filter dropdown
3. Review list of currently paused memberships
4. Click specific hold for more details

### Workflow 3: Create Holds for a Plan
1. Click "Create Bulk Hold" button
2. Select "Specific Plan" option
3. Choose plan from dropdown
4. Configure dates and options
5. Submit to create holds

### Workflow 4: Monitor Hold History
1. View all statuses (default view)
2. Sort by end date (descending) to see recent completions
3. Click expired holds to review history
4. Use search to find specific member's hold history

### Workflow 5: End a Bulk Hold Group
1. Click "End Bulk Hold" button
2. Select bulk hold group from dropdown
3. Review affected holds count
4. Enter optional reason
5. Confirm to end all holds in group

---

## Related Documentation

- [README](./README.md) - Overview of all holds features
- [Bulk Hold Operations](./bulk-hold-operations.md) - Detailed bulk operations guide
- [Hold Detail Page](./hold-detail-page.md) - Individual hold view
- [Hold Creation](./hold-creation.md) - Creating single holds

---

**Document Version**: 1.0  
**Last Updated**: October 13, 2025  
**Page Component**: `app/Livewire/Subscriptions/MembershipHolds.php`  
**View Template**: `resources/views/livewire/subscriptions/membership-holds.blade.php`


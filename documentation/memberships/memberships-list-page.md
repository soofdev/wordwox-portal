# Memberships List Page

## Overview

**Route**: `/subscriptions`  
**Component**: `MembershipTable` (Livewire)  
**View**: `resources/views/livewire/subscriptions/membership-table.blade.php`

The Memberships List page is an operational dashboard for monitoring and managing all active membership subscriptions across the organization. It features an enhanced data table design with embedded visualizations, color-coded urgency indicators, and comprehensive filtering capabilities to help staff quickly identify memberships that need attention.

## Purpose & Design Philosophy

Unlike the "All Customers" card grid (which focuses on people), the Memberships List is designed for **operational monitoring** with emphasis on:
- **Expiration tracking** - Visual indicators for urgent renewals
- **Session quota management** - Circular progress displays for usage monitoring  
- **Duration visualization** - Timeline-based date range display
- **Metric-focused layout** - Large, scannable numbers for quick assessment
- **Color-coded urgency** - Row backgrounds signal priority levels

## Page Layout

### Header Section
- **Page Title**: "Memberships"
- **Description**: "Manage and monitor all membership subscriptions"
- **Stats Cards**: Key metrics displayed above the table (if implemented)

### Search & Filter Bar
- **Search Input**: Full-width search field with magnifying glass icon (autocomplete disabled)
- **Filter Dropdowns**: Horizontal row of filter selects
  - Status Filter (Active by default)
  - Type Filter
  - Plan Filter
  - Sort Menu
- **Clear Button**: Appears when any filter is active

### Enhanced Data Table
- **Infinite Scroll**: Automatically loads more memberships as you scroll
- **Color-Coded Rows**: Background colors indicate urgency/status
- **Embedded Visualizations**: Progress rings, timelines, and badges
- **Responsive Layout**: Adapts to different screen sizes
- **RTL Support**: Fully supports right-to-left languages
- **Clickable Rows**: Click any row to view membership details

## Custom Components

### 1. Date Range Timeline Component
**File**: `resources/views/components/date-range-timeline.blade.php`

**Purpose**: Displays membership start and end dates in a visual timeline format with color-coded urgency indicators.

**Features**:
- Green dot for start date, color-coded dot for end date
- Urgency colors: Blue (healthy), Yellow (warning), Orange (urgent), Red (expired)
- Visual arrow connector between dates
- Three size variants: `sm`, `md`, `lg`
- Calendar icon for context
- "START" and "END" labels for clarity

**Usage**:
```blade
<x-date-range-timeline 
    start-date="Sep 02, 2025"
    end-date="Nov 03, 2025"
    urgency-status="warning"
    size="md"
/>
```

**Props**:
- `startDate` (required): Formatted start date string
- `endDate` (required): Formatted end date string  
- `urgencyStatus` (optional): `healthy` | `warning` | `urgent` | `expired` (default: `healthy`)
- `size` (optional): `sm` | `md` | `lg` (default: `md`)

**Design Rationale**:
- Combines two columns (start/end) into one visual unit
- Color-coding end date provides instant urgency feedback
- Reusable across other date range displays in the app

---

### 2. Session Usage Indicator Component
**File**: `resources/views/components/session-usage-indicator.blade.php`

**Purpose**: Displays session consumption with a circular progress ring for limited plans or an infinity symbol for unlimited plans.

**Features**:
- Circular SVG progress ring with percentage display
- Color-coded progress: Green (<50%), Yellow (50-79%), Red (≥80%)
- Consumed vs. remaining session counts
- Infinity symbol for unlimited plans
- Three size variants: `sm`, `md`, `lg`
- Fully responsive and dark mode compatible

**Usage**:
```blade
<x-session-usage-indicator 
    :consumed="8"
    :remaining="12"
    :total="20"
    :percentage="40"
    size="md"
/>

<!-- For unlimited -->
<x-session-usage-indicator 
    :consumed="5"
    total="unlimited"
    size="md"
/>
```

**Props**:
- `consumed` (required): Number of sessions used
- `remaining` (optional): Number of sessions remaining
- `total` (required): Total sessions or `'unlimited'`
- `percentage` (optional): Usage percentage (0-100)
- `size` (optional): `sm` | `md` | `lg` (default: `md`)

**Design Rationale**:
- Visual progress ring provides instant understanding of quota status
- Large, bold numbers emphasize key metrics
- Color-coding alerts staff to members nearing quota limits
- Reusable for other quota/progress displays

---

## User Actions

### 1. Search for Memberships

**Action**: Type in the search field to find specific memberships.

**Search Criteria**:
- Member full name
- Member email address  
- Membership plan name

**Behavior**:
- **Live Search**: Results update automatically as you type
- **Debounce**: 300ms delay prevents excessive queries
- **Case-Insensitive**: Matches regardless of letter case
- **Partial Matching**: Finds results containing the search term
- **No Autocomplete**: Browser autocomplete disabled for better UX
- **Infinite Scroll Reset**: Returns to top when search changes

**Example Searches**:
- "John Doe" - Finds member by name
- "john@example.com" - Finds member by email
- "Premium" - Finds all "Premium" plan memberships

---

### 2. Filter by Status

**Action**: Use the status filter dropdown to view memberships of a specific status.

**Default Filter**: **Active** (shows only active memberships by default)

**Available Filters**:
- **All Statuses**: Shows all memberships
- **Active** (default): Currently active memberships
- **Upcoming**: Memberships scheduled to start in the future
- **On Hold**: Paused/frozen memberships
- **Canceled**: Canceled memberships
- **Expired**: Memberships past their end date
- **Expired (Limit)**: Memberships expired due to session limit

**Behavior**:
- **Live Filtering**: Table updates immediately when status is selected
- **Infinite Scroll Reset**: Returns to top when filter changes
- **Combination with Other Filters**: Works with plan, type, and search
- **URL Bookmarkable**: Status saved in URL parameter `?status=2`

**Status Values**:
- Active = `2`
- On Hold = `3`
- Canceled = `4`
- Expired (Limit) = `98`
- Expired = `99`

---

### 3. Filter by Type

**Action**: Filter memberships by plan type (Session-based, Open Gym, Programs).

**Available Types**:
- **All Types**: Shows all membership types
- **Session-based**: Limited session memberships
- **Open Gym**: Gym access memberships
- **Programs**: Program-based memberships

**Behavior**:
- **Live Filtering**: Instant update on selection
- **Combines with Other Filters**: Works alongside status and plan filters
- **URL Parameter**: `?type=value`

---

### 4. Filter by Plan

**Action**: Filter memberships by specific membership plan.

**Available Plans**:
- **All Plans**: Shows all memberships
- **Dynamic List**: Populated from organization's active plans
- **Alphabetically Sorted**: Plans sorted A-Z for easy finding

**Behavior**:
- **Live Filtering**: Table updates immediately
- **Organization-Specific**: Only shows plans from current organization
- **Excludes Deleted Plans**: Only active plans displayed
- **URL Parameter**: `?plan={planId}`

**Use Cases**:
- View all members on a specific plan
- Monitor plan-specific usage patterns
- Track plan popularity and retention

---

### 5. Sort Memberships

**Action**: Use the sort dropdown to change membership ordering.

**Sort Options**:
1. **Expiring Soonest** (default) - Closest end dates first (excludes unlimited)
2. **Expiring Latest** - Furthest end dates first (excludes unlimited)
3. **Newest First** - Most recently started memberships
4. **Oldest First** - Earliest started memberships
5. **Name A-Z** - Alphabetical by membership name
6. **Name Z-A** - Reverse alphabetical
7. **Recently Created** - Latest created in system

**Important Behavior**:
- **Expiration Sorts Filter Unlimited**: "Expiring Soonest/Latest" automatically excludes memberships with null end dates (unlimited plans)
- **Other Sorts Show All**: Name and start date sorts include unlimited memberships
- **URL Bookmarkable**: Sort saved as `?sort=endDate&dir=asc`
- **Infinite Scroll Compatible**: List resets when sort changes

**Default Sort**:
- **Field**: `endDate`
- **Direction**: `asc` (ascending - soonest expiring first)
- **Rationale**: Most operationally useful - shows urgent renewals first

---

### 6. Clear All Filters

**Action**: Click the "Clear" button to reset all filters and search to defaults.

**What Gets Reset**:
- Search field → empty
- Status filter → Active (default, not cleared to empty)
- Type filter → All Types
- Plan filter → All Plans
- Sort → Remains unchanged (intentional)

**Button Appearance**:
- **Visibility**: Only shown when search or filters are active
- **Icon**: X-mark icon
- **Style**: Ghost variant (subtle)
- **Location**: Right side of filter bar

---

### 7. Infinite Scroll Loading

**Action**: Scroll to the bottom of the list to automatically load more memberships.

**Behavior**:
- **Auto-Load**: Triggers when scrolling near bottom of page
- **Load More**: Loads 25 memberships at a time
- **Loading Message**: Shows "Loading more memberships..." (translated)
- **End Message**: Shows "You've reached the end of the list" when complete
- **Smooth Experience**: No pagination buttons needed
- **Performance**: Only loads data as needed

**Technical Implementation**:
- Uses Alpine.js `x-intersect` directive
- Observer-based scroll detection
- Livewire `loadMore()` method
- Maintains scroll position after loading

---

### 8. View Membership Details

**Action**: Click on any row in the table to view complete membership details.

**Clickable Area**: Entire row is clickable

**Visual Feedback**:
- **Hover Effect**: Row background changes on hover
- **Cursor**: Changes to pointer to indicate clickability
- **Transition**: Smooth color transition

**Navigation**: Opens membership detail page (same tab)

---

## Enhanced Table Design

### Color-Coded Row Backgrounds

**Purpose**: Provide instant visual feedback about membership status and urgency.

**Color Coding System**:

| Status | Light Mode | Dark Mode | Priority |
|--------|-----------|-----------|----------|
| **Expired** | White (`bg-white`) | Standard | Neutral (completed) |
| **Urgent** (≤7 days left) | Light Red (`bg-red-50`) | Dark Red (`dark:bg-red-900/10`) | High Priority |
| **Warning** (8-30 days left) | Light Yellow (`bg-yellow-50`) | Dark Yellow (`dark:bg-yellow-900/10`) | Medium Priority |
| **Low Quota** (<20% sessions left) | Light Orange (`bg-orange-50`) | Dark Orange (`dark:bg-orange-900/10`) | Attention Needed |
| **Healthy** (>30 days, >20% quota) | Standard | Standard | No Action Needed |

**Design Rationale**:
- Subtle backgrounds don't overwhelm but provide clear visual hierarchy
- Color-coded rows enable "scanning" the list for problems
- Expired memberships have neutral (white) background as they're completed
- System prioritizes renewal urgency and quota depletion

---

### Column Structure

**Columns (Left to Right)**:

1. **Membership Name** (20% width, left-padded)
   - Membership/plan name (wraps if too long)
   - Plan type badge below name (icon + text)
   - Click-through to membership details

2. **Member** (15% width)
   - Member profile photo (medium size)
   - Member full name (truncated with ellipsis)
   - Vertically centered alignment

3. **Duration** (Visual timeline)
   - Date range timeline component
   - Start and end dates with visual connector
   - Color-coded end date based on urgency

4. **Days Left** (Large metric)
   - Big, bold number for days remaining
   - Text label below ("days left" or "Expired")
   - No icon (clean, minimal)

5. **Session Usage** (Circular progress)
   - Session usage indicator component
   - Progress ring with percentage
   - Consumed/Remaining/Total display
   - Infinity symbol for unlimited

6. **Status** (Badge only)
   - Status badge with color coding
   - No additional icon
   - Clean, focused design

**Column Design Principles**:
- **Left-padded First Column**: RTL-aware padding for breathing room
- **Responsive Widths**: Max-widths prevent column overflow
- **Text Wrapping**: Membership names wrap to fit
- **Vertical Centering**: Consistent alignment across cells
- **Icon Placement**: Plan icons inside badges, not adjacent
- **Minimal Redundancy**: Removed duplicate icons for cleaner look

---

## Visual Design Elements

### Typography & Spacing
- **Large Metrics**: Bold, large font sizes for key numbers (days left, percentages)
- **Secondary Text**: Smaller, muted color for labels and context
- **Consistent Padding**: RTL-aware padding on first column (`ltr:pl-6 rtl:pr-6`)
- **Line Height**: Tight leading for wrapped text (`leading-tight`)

### Color System
- **Urgency Colors**: Red (urgent), Yellow/Orange (warning), Green (healthy), Blue (healthy)
- **Status Badge Colors**: Match status type (active=green, expired=gray, canceled=red, etc.)
- **Progress Ring Colors**: Green (<50%), Yellow (50-79%), Red (≥80%)
- **Neutral Backgrounds**: White for expired (light mode), standard for healthy

### Interactive Elements
- **Row Hover**: Subtle background change, pointer cursor
- **Link Hover**: Color change on member/plan name links
- **Smooth Transitions**: All color changes animated
- **Touch-Friendly**: Large clickable areas for mobile

### Accessibility
- **Color + Text**: Never rely on color alone (status has text, urgency has days count)
- **RTL Support**: All spacing and icons adapt to text direction
- **Screen Readers**: Semantic HTML, proper aria labels
- **Keyboard Navigation**: Tab through all interactive elements

---

## Data Display

### Name Capitalization
- **Auto-Capitalized**: Member full names automatically capitalize first letter of each word
- **Model Accessor**: Handled by `getFullNameAttribute()` in `OrgUser` model
- **Example**: "john doe" → "John Doe"

### Date Formatting
- **Format**: "Mon DD, YYYY" (e.g., "Sep 02, 2025")
- **Timezone**: Converted to organization timezone
- **Consistency**: Same format across all date displays

### Days Left Calculation
- **Formula**: Days between today and end date
- **Expired**: Shows "Expired" text instead of negative number
- **Null End Date**: Not displayed for unlimited plans
- **Urgency Levels**:
  - Urgent: ≤7 days
  - Warning: 8-30 days  
  - Healthy: >30 days

### Session Usage Display
- **Percentage**: Rounded to nearest whole number (no decimals)
- **Consumed**: Red text (sessions used)
- **Remaining**: Green text (sessions left)
- **Total**: Displayed below ratio
- **Unlimited**: Infinity symbol (∞) with consumed count only

### Plan Type Badges
- **Session-based**: Calendar icon
- **Open Gym**: Home icon
- **Program**: Bookmark icon
- **Spacing**: Icon + text with gap inside badge
- **New Line**: Badge always on separate line from plan name

---

## Performance & Technical Details

### Infinite Scroll Implementation
- **Initial Load**: 25 memberships
- **Per Load**: 25 additional memberships
- **Scroll Detection**: Alpine.js `x-intersect` on sentinel element
- **Loading State**: Shows translated loading message
- **End Detection**: Stops loading when no more results

### Query Optimization
- **Eager Loading**: Loads `orgUser`, `user`, and `orgPlan` relationships upfront
- **Indexed Searches**: Uses database indexes for name/email searches
- **Conditional Queries**: Only applies active filters
- **Pagination**: Offset/limit instead of traditional pagination
- **Default Filters**: Active status filter reduces initial query size

### State Management
- **URL Query String**: All filters bookmarkable in URL
- **Livewire Properties**: Component state management
- **Debounced Search**: 300ms delay on search input
- **Reset on Filter**: Scroll position and offset reset when filters change
- **Event Dispatching**: `scroll-list-updated` event triggers JS reinitialization

### Default Configuration
- **Default Status**: Active (status=2)
- **Default Sort**: Expiring Soonest (endDate ascending)
- **Items Per Load**: 25 memberships
- **Search Debounce**: 300ms
- **Autocomplete**: Disabled on search field

---

## Permission Requirements

### View Memberships List
**Permission**: `view memberships`

**Access Level**:
- Can see the page and all data
- Can search, filter, and sort
- Can click to view membership details

**Without Permission**:
- Page returns 403 Forbidden error
- Error message: "You do not have permission to view memberships."

---

## User Experience Considerations

### Operational Focus
- **Default Active Filter**: Shows most relevant memberships immediately
- **Expiring Soonest Sort**: Prioritizes urgent renewals
- **Color-Coded Urgency**: Enables quick visual scanning
- **Large Metrics**: Key numbers easy to read at a glance

### Performance
- **Infinite Scroll**: Smooth, no page reloads
- **Debounced Search**: Reduces server load
- **Optimized Queries**: Fast response times
- **Progressive Loading**: Only loads visible data

### Mobile Responsiveness
- **Responsive Columns**: Adapt to screen width
- **Touch-Friendly**: Large tap targets
- **Readable Text**: Appropriate font sizes
- **Horizontal Scroll**: Table scrolls horizontally on small screens

### Multilingual Support
- **Fully Translated**: All text translated (EN/AR)
- **RTL Support**: Layout mirrors for Arabic
- **Date Localization**: Dates formatted per locale
- **Number Formatting**: Respects locale conventions

---

## Common User Workflows

### Workflow 1: Monitor Expiring Memberships
1. Open memberships list (defaults to Active + Expiring Soonest)
2. Scan red/yellow highlighted rows for urgent renewals
3. Check "Days Left" column for specific timelines
4. Click urgent memberships to process renewals

### Workflow 2: Check Plan-Specific Memberships
1. Select specific plan from "Plan" filter dropdown
2. Review all members on that plan
3. Check session usage across plan members
4. Identify patterns or issues with specific plans

### Workflow 3: Find a Member's Membership
1. Type member name or email in search field
2. Wait for live search results
3. Locate member in filtered results
4. Click row to view full membership details

### Workflow 4: Monitor Session Quotas
1. View Active memberships (default)
2. Scan circular progress indicators for red (high usage)
3. Check consumed/remaining ratios
4. Proactively contact members nearing limits

### Workflow 5: Review Membership Types
1. Select specific type from Type filter (e.g., "Session-based")
2. Review all memberships of that type
3. Compare session usage patterns
4. Make operational decisions based on type trends

---

## Related Documentation

- [Membership Creation](./FOH-membership-creation-analysis.md) - Creating new memberships
- [Membership Holds Listing](./membership-holds/holds-listing-page.md) - Managing membership holds
- [Membership Holds README](./membership-holds/README.md) - Overview of holds features
- [Bulk Hold Operations](./membership-holds/bulk-hold-feature.md) - Bulk hold operations
- [All Customers Card Grid](../customers/customer-list/active-members-analysis.md) - People-focused view
- [Infinite Scroll Implementation](../ui-ux-standards/infinite-scroll-implementation.md) - Infinite scroll pattern
- [Table Listing Standards](../ui-ux-standards/table-listing-standards.md) - Table design standards

---

## Custom Components Reference

### Components Created for This Page

1. **Date Range Timeline**
   - Path: `resources/views/components/date-range-timeline.blade.php`
   - Purpose: Visual date range display with urgency color-coding
   - Reusable: Yes (can be used on membership details, reports, etc.)

2. **Session Usage Indicator**
   - Path: `resources/views/components/session-usage-indicator.blade.php`
   - Purpose: Circular progress display for session quota tracking
   - Reusable: Yes (can be used on member profiles, dashboards, etc.)

### Component Design Principles

**Reusability**: Both components are standalone, documented, and accept clear props
**Size Variants**: Both support `sm`, `md`, `lg` for different contexts  
**Dark Mode**: Both fully support dark mode styling
**RTL Support**: Both adapt properly to right-to-left layouts
**Accessibility**: Both use semantic HTML and proper ARIA attributes
**Documentation**: Both include inline documentation and usage examples

---

## Design Distinctions

### Memberships List vs. All Customers

These two pages are intentionally designed to be distinct:

| Feature | All Customers (Card Grid) | Memberships List (Table) |
|---------|--------------------------|--------------------------|
| **Layout** | Card grid (2-4 columns) | Enhanced data table |
| **Focus** | People (ID card metaphor) | Operations (metrics) |
| **Key Info** | Contact info, status | Expiration, quota, duration |
| **Visualizations** | Minimal | Circular progress, timelines |
| **Color Coding** | Badge colors only | Row backgrounds + badges |
| **Urgency** | Not emphasized | Primary focus |
| **Use Case** | Browse members | Monitor operations |
| **Scan Pattern** | Individual cards | Horizontal rows |

**Design Rationale**: Different views serve different purposes - cards for people-first browsing, table for metric-first monitoring.

---

## Translation Keys

### English (`lang/en/subscriptions.php`)
```php
'Session Usage' => 'Session Usage',
'Loading more memberships...' => 'Loading more memberships...',
'You\'ve reached the end of the list' => 'You\'ve reached the end of the list',
'day' => 'day',
'days' => 'days',
'Sort By' => 'Sort By',
'Expiring Soonest' => 'Expiring Soonest',
'Expiring Latest' => 'Expiring Latest',
'Newest First' => 'Newest First',
'Oldest First' => 'Oldest First',
'Name A-Z' => 'Name A-Z',
'Name Z-A' => 'Name Z-A',
'Recently Created' => 'Recently Created',
'All Plans' => 'All Plans',
```

### Arabic (`lang/ar/subscriptions.php`)
```php
'Session Usage' => 'استخدام الجلسات',
'Loading more memberships...' => 'جاري تحميل المزيد من العضويات...',
'You\'ve reached the end of the list' => 'لقد وصلت إلى نهاية القائمة',
'day' => 'يوم',
'days' => 'أيام',
'Sort By' => 'الترتيب حسب',
'Expiring Soonest' => 'الأقرب انتهاءً',
'Expiring Latest' => 'الأبعد انتهاءً',
'Newest First' => 'الأحدث أولاً',
'Oldest First' => 'الأقدم أولاً',
'Name A-Z' => 'الاسم أ-ي',
'Name Z-A' => 'الاسم ي-أ',
'Recently Created' => 'المضافة حديثاً',
'All Plans' => 'جميع الخطط',
```

---

## Future Enhancement Opportunities

### Potential Additions
- **Export Functionality**: Export filtered membership list to CSV/Excel
- **Bulk Actions**: Select multiple memberships for bulk operations
- **Advanced Filters**: Filter by date ranges, member attributes, custom fields
- **Stats Cards**: Summary cards above table (total, expiring soon, etc.)
- **Quick Actions**: Inline buttons for common tasks (renew, hold, etc.)
- **Column Customization**: Allow users to show/hide columns
- **Saved Views**: Save favorite filter combinations
- **Activity Timeline**: Show recent membership changes inline

### Performance Optimizations
- **Virtual Scrolling**: Render only visible rows for very large lists
- **Redis Caching**: Cache frequently accessed membership data
- **Elasticsearch**: Implement for advanced full-text search
- **Background Jobs**: Pre-calculate urgency metrics for faster queries

---

**Document Version**: 1.0  
**Last Updated**: October 16, 2025  
**Component Path**: `app/Livewire/Subscriptions/MembershipTable.php`  
**View Path**: `resources/views/livewire/subscriptions/membership-table.blade.php`  
**Custom Components**:
- `resources/views/components/date-range-timeline.blade.php`
- `resources/views/components/session-usage-indicator.blade.php`


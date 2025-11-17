# Table Listing UI/UX Standards

## Overview
This document defines the standard UI/UX principles, design requirements, and patterns for displaying table listings in the wodworx-foh application. All tables must follow these standards to ensure consistency, accessibility, and optimal user experience.

**For code examples and implementation details**, see [Table Listing Implementation Guide](./table-listing-implementation.md).

**Reference Implementation:** `MembershipHolds` component at `/resources/views/livewire/subscriptions/membership-holds.blade.php`

---

## 🎯 Core Design Principles

### 1. **Mobile-First Approach**
- All table listings must be optimized for touch interactions
- Minimum 48px touch targets for mobile elements
- Responsive design that adapts gracefully from mobile to desktop
- Touch-optimized scrolling
- Always use infinite scrolling (no traditional pagination)

### 2. **Progressive Enhancement**
- Basic functionality works without JavaScript
- Enhanced features (infinite scroll, real-time search) layer on top
- Graceful degradation for older browsers

### 3. **Performance-Optimized**
- Debounced search (300ms) to reduce server requests
- Throttled scroll events (60fps) for smooth performance
- Efficient data loading with offset/limit queries

### 4. **Accessibility-First**
- Semantic HTML for all navigation
- Keyboard navigation support
- Screen reader compatible
- ARIA labels where appropriate

---

## 📱 Standard Layout Structure

1. **Page Header** - Title and dynamic subtitle with counts
2. **Search & Filters Bar** - Horizontal layout with responsive stacking
3. **Data Table** - Main content with Flux UI components
4. **Loading States** - Progress indicators
5. **Empty States** - No data scenarios
6. **Floating Actions** - Scroll-to-top button

> See [Implementation Guide - Page Structure](./table-listing-implementation.md#page-structure) for code examples.

---

## 🔍 Page Header Requirements

### Title Structure
- Use `flux:heading` with `size="xl"` for page title
- Include `flux:subheading` for context information

### Dynamic Subtitle Standards
- **Default State**: Show total counts with active/inactive breakdown
- **Search/Filter Active**: Show filtered results count and criteria
- **Always use** `number_format()` for numbers > 999
- Update dynamically when filters change

---

## 🔎 Search & Filter Bar Requirements

### Search Input (MANDATORY)
- **Debouncing**: 300ms delay using `wire:model.live.debounce.300ms`
- **Icon**: Always include magnifying glass icon
- **Placeholder**: Descriptive text listing all searchable fields
- **Layout**: Full width on mobile, flex-1 on desktop

### Filter Controls
- **Status Filter**: Dropdown with "All", "Active", "Inactive" options
- **Per Page**: Optional dropdown (10, 25, 50, 100) - default 25
- **Clear Filters**: Show only when filters are active

### URL State Management (MANDATORY)
- All search and filter values must persist in URL
- Use `protected $queryString` in Livewire components
- Enables bookmarking and sharing filtered views

> See [Implementation Guide - Page Structure](./table-listing-implementation.md#page-structure) for complete examples.

---

## 📊 Data Table Standards

### Flux UI Components (MANDATORY)

**STRICT REQUIREMENT**: All tables MUST use Flux UI table components as per [Flux UI Documentation](https://fluxui.dev/docs/table).

**Required Structure**:
- `<flux:table>` - Main container
- `<flux:table.columns>` - Column headers container
- `<flux:table.column>` - Individual column header
- `<flux:table.rows>` - Rows container
- `<flux:table.row>` - Individual row
- `<flux:table.cell>` - Individual cell

> See [Implementation Guide - Flux Table Components](./table-listing-implementation.md#flux-table-components) for code examples.

---

## 🔗 Semantic Links (MANDATORY)

**CRITICAL REQUIREMENT**: Table rows must NEVER be clickable. Use semantic HTML `<a>` tags for ALL navigation.

### Required Links in Every Table

#### Universal Link Component (MANDATORY)

**STRICT REQUIREMENT**: All table links MUST use the `<x-link>` component.

**Component**: `resources/views/components/link.blade.php`

**Styling**: 
- Blue text (`text-blue-600`), darker blue on hover (`hover:text-blue-800`)
- No underline by default, underline appears on hover
- Full dark mode support
- Smooth transitions

**Usage**: Replace all `<a>` tags with `<x-link>`:
```blade
<x-link :href="route('members.profile', $userId)" class="font-medium">
    {{ $userName }}
</x-link>
```

**Benefits**:
- ✅ Single source of truth for link styling
- ✅ Consistent appearance across entire application
- ✅ Easy to update styling globally
- ✅ Clean, readable Blade templates

---

#### 1. Actions Column with View Link (MANDATORY)
- Must be the LAST column in every table
- Must contain a "View" link using `<x-link>` component
- **Navigation**: Same tab (NO `target="_blank"`)

#### 2. Member/User Name Links (MANDATORY)
- Any member or user name MUST link to their profile page using `<x-link>`
- **Route**: `members.profile` with user ID
- **Fallback**: Show as plain text if ID is missing

#### 3. Plan/Membership Name Links (MANDATORY)
- Any plan or membership name MUST link to detail page using `<x-link>`
- **Route**: `subscriptions.show` with membership ID

### Why Semantic Links Matter

✅ **Accessibility**: Screen readers, keyboard navigation  
✅ **SEO**: Search engines can crawl links  
✅ **UX**: URL preview in status bar  
✅ **Browser Features**: Right-click menu, Cmd/Ctrl+Click, middle-click  
✅ **Bookmarkable**: Users can copy/share links  
✅ **Performance**: No JavaScript overhead

### What is FORBIDDEN

❌ **Clickable rows** with `onclick` or `wire:click`  
❌ **cursor-pointer** class on rows  
❌ **target="_blank"** on any table links  
❌ **Non-semantic** click handlers  
❌ **Missing Actions column** or View link

> See [Implementation Guide - Semantic Links](./table-listing-implementation.md#semantic-links) for complete code examples.

---

## ↕️ Column Sorting Standards

### Sortable Columns Requirements
- Add `sortable` attribute and `wire:click="sortBy('field_name')"`
- Visual indicator: Chevron icon showing current sort direction
- **Sort Behavior**: First click ascending, second click descending, continues toggling
- **Single Column**: Only one column sortable at a time
- **Default Sort**: Define sensible default (usually `created_at desc` or `name asc`)

### Which Columns Should Be Sortable?
- **Always**: Name/title, dates, numeric values, status
- **Usually**: Contact info, plan names, amounts
- **Rarely**: Complex calculated fields, badges
- **Never**: Avatars, icons, action buttons

### SQL Ambiguity Prevention (CRITICAL)

**MANDATORY RULE**: ALWAYS use explicit `table.column` notation in ALL sorting queries.

**Why**: Search filters may add WHERE clauses that reference the same tables as your ORDER BY, causing "Column 'X' is ambiguous" errors.

**Required Practices**:
1. Always prefix main table columns: `mainTable.columnName`
2. Use `selectRaw()` for relationship columns with full qualification
3. Test sorting WITH and WITHOUT filters active
4. Check Laravel Debugbar for SQL ambiguity warnings

> See [Implementation Guide - Column Sorting](./table-listing-implementation.md#column-sorting) for complete implementation details.

---

## ↔️ Row Interaction Standards

### Rows Must NOT Be Clickable (STRICT RULE)

**What is ALLOWED**:
- Hover effects: `hover:!bg-zinc-50 dark:hover:!bg-white/5`
- Group hover for coordinated animations
- Transition effects for visual feedback

**What is FORBIDDEN**:
- ❌ `cursor-pointer` class on rows
- ❌ `onclick` handlers
- ❌ `wire:click` on rows
- ❌ JavaScript navigation on row click

**Why Rows Must NOT Be Clickable**:
- ❌ No keyboard navigation support
- ❌ No URL preview before clicking
- ❌ Can't right-click, Cmd+Click, or middle-click
- ❌ Poor accessibility for screen readers
- ❌ Can't copy links or bookmark rows
- ❌ SEO impact - search engines can't crawl

---

## ♾️ Infinite Scroll Requirements

### Implementation Standards
- **Backend**: Use offset/limit queries (not Laravel pagination)
- **Component**: Track `$loadedItems`, `$hasMoreItems`, `$isLoading`
- **Frontend**: Use Alpine.js with `x-intersect` directive
- **Trigger**: Load 100px before bottom of list
- **Loading Indicator**: Show during data fetch
- **End Message**: Display "End of list" when no more items

### When Search/Filter Changes
- Reset to initial state
- Clear loaded items
- Load first page
- Reset scroll position

> **📖 Complete Technical Guide**: See [Infinite Scroll Implementation Guide](./infinite-scroll-implementation.md) for step-by-step implementation with code examples, troubleshooting, and best practices.

---

## 🔄 Loading States

### Requirements
- **Position**: Centered below table
- **Content**: Spinner + descriptive text
- **Animation**: Smooth fade in/out (300ms)
- **Control**: Show/hide via JavaScript, not Livewire
- **Text Patterns**: "Loading more [items]...", "Searching...", "Filtering..."

---

## 🚫 Empty States

### Three Required Scenarios

1. **No Data**: "No [items] yet" + explanation + optional create button
2. **No Search Results**: "No [items] found" + search criteria + clear button
3. **No Filter Results**: "No [items] match filters" + clear filters button

### Design Standards
- Centered content with icon
- `flux:heading` for title
- `flux:text variant="muted"` for description
- Action button when appropriate
- Icon that matches context (document, magnifying glass, filter)

> See [Implementation Guide - Empty States](./table-listing-implementation.md#empty-states) for code examples.

---

## 📱 Mobile Optimizations

### Touch Targets
- **Minimum**: 48px x 48px for ALL interactive elements
- **Table Rows**: 72px minimum height on mobile
- **Buttons**: 56px minimum

### Responsive Breakpoints
- **Mobile**: ≤ 640px (sm breakpoint)
- **Tablet**: 641px - 768px
- **Desktop**: ≥ 769px

### Required CSS
- Touch-friendly row heights
- `-webkit-overflow-scrolling: touch` for smooth scrolling
- Smooth scroll behavior globally

> See [Implementation Guide - Mobile Optimizations](./table-listing-implementation.md#mobile-optimizations) for CSS.

---

## 🔝 Scroll-to-Top Button

### Standards
- **Trigger**: Show after scrolling 300px down
- **Position**: Fixed bottom-right (24px from edges)
- **Size**: 56px x 56px minimum
- **Animation**: Fade and scale in/out
- **Action**: Smooth scroll to top
- **Performance**: Throttled scroll detection (60fps)

---

## 🎨 Visual Design Standards

### Color Scheme
- **Primary**: Blue (#2563eb) for actions and highlights
- **Success**: Green for active/positive states
- **Warning**: Yellow for attention/pending
- **Danger**: Red for urgent/negative
- **Neutral**: Zinc scale for text and backgrounds

### Typography
- **Headings**: `flux:heading` with appropriate sizes
- **Body**: Default weight with hover color changes
- **Secondary**: `text-sm text-zinc-500 dark:text-zinc-400`
- **Muted**: `variant="muted"` for less important info

### Spacing
- **Page Sections**: `space-y-6` or `space-y-8` (24-32px)
- **Component Groups**: `gap-4` (16px)
- **Internal**: `gap-3` (12px) for tight groupings

### Animations
- **Transitions**: `transition-colors` for color changes
- **Duration**: 300ms for most animations
- **Easing**: `cubic-bezier(0.4, 0, 0.2, 1)` for natural feel

---

## 🔧 Technical Requirements

### Livewire Component Structure
Every table listing component must include:

**Required Properties**:
- `$search` - Search query (URL-bound)
- `$statusFilter` - Status filter (URL-bound)
- `$sortField` - Current sort field (default: 'created_at')
- `$sortDirection` - Sort direction (default: 'desc')
- `$perPage` - Items per page (default: 25)

**Required Methods**:
- `mount()` - Initialize component
- `updatedSearch()` - Reset pagination on search
- `updatedStatusFilter()` - Reset pagination on filter
- `sortBy($field)` - Handle column sorting
- `render()` - Return view with data

**For Infinite Scroll**:
- `$loadedItems` - Array of loaded items
- `$hasMoreItems` - Boolean flag
- `$isLoading` - Loading state
- `loadInitialItems()` - Load first page
- `loadMoreItems()` - Load next page

> See [Implementation Guide - Complete Component Example](./table-listing-implementation.md#complete-component-example) for full code.

---

## 📋 Implementation Checklist

### ✅ Page Structure
- [ ] Page header with dynamic subtitle
- [ ] Search bar with debounced input (300ms)
- [ ] Filter controls with URL state
- [ ] Clear filters button (conditional)
- [ ] Responsive layout (mobile-first)

### ✅ Table Implementation
- [ ] Flux UI table components used
- [ ] Table structure: `<flux:table>`, `<flux:table.columns>`, `<flux:table.rows>`
- [ ] **Actions column as LAST column with "View" link**
- [ ] **Member/user names are clickable links to profiles**
- [ ] **Plan/membership names are clickable links to details**
- [ ] All links use semantic `<a href>` tags (NO onclick, NO wire:click)
- [ ] Links open in same tab (NO target="_blank")
- [ ] Link fallback for missing IDs
- [ ] Sortable column headers with visual indicators
- [ ] Sort state management (sortField, sortDirection)
- [ ] URL persistence for sort preferences
- [ ] **Explicit table.column notation in ALL ORDER BY clauses**
- [ ] selectRaw() used for qualified columns in relationship sorts
- [ ] Tested sorting with search + filters active (no SQL ambiguity errors)
- [ ] **Rows are NOT clickable** (no onclick, no cursor-pointer)
- [ ] Hover effects for visual feedback
- [ ] Group hover for coordinated animations
- [ ] Proper cell content hierarchy
- [ ] Status badges with semantic colors

### ✅ Infinite Scroll
- [ ] Backend service with offset/limit queries
- [ ] Component properties for state management
- [ ] JavaScript Intersection Observer
- [ ] Loading indicators and end-of-results messaging
- [ ] Proper data type conversion (stdClass to array)

### ✅ Empty States
- [ ] No data scenario
- [ ] No search results scenario
- [ ] No filter results scenario
- [ ] Appropriate actions for each state

### ✅ Mobile Optimization
- [ ] Touch-friendly targets (48px minimum)
- [ ] Responsive breakpoints
- [ ] Smooth scrolling optimizations
- [ ] Mobile-specific CSS adjustments

### ✅ Scroll-to-Top Button
- [ ] Fixed positioning with proper z-index
- [ ] Show/hide based on scroll position (300px trigger)
- [ ] Smooth animations and transitions
- [ ] Mobile-optimized sizing (56px)
- [ ] Accessibility features (ARIA labels, focus states)

### ✅ Performance
- [ ] Debounced search inputs
- [ ] Throttled scroll events
- [ ] Efficient database queries
- [ ] Proper loading state management

---

## 🚀 Future Enhancements

### Potential Improvements
- **Virtual Scrolling**: For extremely large datasets (1000+ items)
- **Multi-Column Sorting**: Sort by multiple columns simultaneously
- **Advanced Filters**: Date ranges, multi-select options, compound filters
- **Bulk Actions**: Select multiple items for batch operations
- **Export Functionality**: CSV/Excel export with current filters
- **Saved Searches**: Bookmark common filter combinations
- **Column Customization**: Show/hide columns, reorder, resize

### Accessibility Enhancements
- **Screen Reader Support**: Enhanced ARIA labels and descriptions
- **Keyboard Navigation**: Full keyboard accessibility
- **High Contrast Mode**: Support for accessibility themes
- **Focus Management**: Proper focus handling during dynamic updates

---

## 📚 Related Documentation

- **[Table Listing Implementation Guide](./table-listing-implementation.md)** - Complete code examples and implementation details
- [Active Members Analysis](../customers/customer-listing/active-customers-analysis.md) - Use case analysis
- [Multi-Tenancy Analysis](../authentication/multi-tenancy-analysis.md) - Multi-tenant considerations
- [Flux UI Documentation](https://fluxui.dev/docs/table) - Official Flux table documentation

---

**Document Version**: 2.0  
**Last Updated**: October 13, 2025  
**Maintained By**: FOH Development Team

*This document establishes the standards and principles for all table listing pages in wodworx-foh. For implementation details and code examples, refer to the Table Listing Implementation Guide. Any deviations from these standards must be documented and justified.*

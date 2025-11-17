# Table Listing Implementation Guide

## Overview
This document captures key implementation decisions and gotchas for building table listing pages according to the [Table Listing UI/UX Standards](./table-listing-standards.md).

**Reference Implementation:** `MembershipHolds` component at `/resources/views/livewire/subscriptions/membership-holds.blade.php`

---

## Key Implementation Decisions

### 1. Why Semantic Links Instead of Clickable Rows

**Decision**: Use `<a href>` tags for navigation, NOT `onclick` on rows.

**Reasons**:
- Accessibility: Screen readers and keyboard navigation require semantic HTML
- SEO: Search engines can't crawl JavaScript click handlers
- UX: Users expect to see URLs in status bar, right-click for options, Cmd+Click for new tabs
- Performance: No JavaScript execution overhead

**Critical**: This is non-negotiable. Clickable rows were causing accessibility issues and poor user experience.

---

### 2. SQL Ambiguity Prevention in Sorting

**Problem**: When search filters reference the same tables as ORDER BY clauses, MySQL throws "Column 'X' is ambiguous" errors.

**Solution**: ALWAYS use explicit `table.column` notation in ALL sorting queries.

```php
// ✅ CORRECT
$query->orderBy('orgUserPlanHold.created_at', $this->sortDirection);

// ❌ WRONG - Will fail when search is active
$query->orderBy('created_at', $this->sortDirection);
```

**Why This Happens**:
- Search adds WHERE clauses with table joins
- ORDER BY then references ambiguous columns
- Error only appears with specific filter combinations
- Hard to debug in production

**For Relationship Sorts**: Use `selectRaw()` with explicit table prefix:

```php
$query->orderBy(\App\Models\OrgPlan::selectRaw('orgPlan.name')
    ->join('orgUserPlan', 'orgPlan.id', '=', 'orgUserPlan.orgPlan_id')
    ->whereColumn('orgUserPlan.id', 'orgUserPlanHold.orgUserPlan_id'), 
    $this->sortDirection);
```

**Testing Requirement**: Always test sorting WITH and WITHOUT search/filters active.

---

### 3. URL State Persistence

**Decision**: All filter and search state must persist in URL query parameters.

**Why**:
- Enables bookmarking and sharing filtered views
- Browser back/forward buttons work correctly
- Better user experience when navigating away and returning

**Implementation**: Use Livewire's `$queryString` property:

```php
protected $queryString = [
    'search' => ['except' => ''],
    'statusFilter' => ['except' => ''],
    'sortField' => ['except' => 'created_at'],
    'sortDirection' => ['except' => 'desc'],
];
```

---

### 4. Universal Link Component (`<x-link>`)

**Decision**: Use a reusable Blade component for all links instead of repeating Tailwind classes.

**Component Location**: `resources/views/components/link.blade.php`

**Why This Approach**:
- **DRY Principle**: Single source of truth for link styling
- **Maintainability**: Change styling in one place, affects entire app
- **Clean Templates**: No repeated long class strings in Blade files
- **Type Safety**: Can add validation and prop types
- **Consistency**: Impossible to have inconsistent link styling

**Component Features**:
1. **Consistent Styling**:
   - Blue by default (`text-blue-600`), darker blue on hover (`hover:text-blue-800`)
   - No underline by default, underline appears on hover (`hover:underline`)
   - Full dark mode support (`dark:text-blue-400`, `dark:hover:text-blue-300`)
   - Smooth transitions

2. **Flexible Usage**:
   - Required prop: `href` (defaults to `#`)
   - Optional prop: `class` for additional styling (e.g., `font-medium`, `text-sm`)
   - All other HTML attributes passed through via `$attributes`

3. **Same Tab Navigation**:
   - Default behavior (no `target="_blank"`)
   - Users can Cmd+Click or right-click to open in new tab

**Usage Examples**:
```blade
<!-- Member name link -->
<x-link :href="route('members.profile', $userId)" class="font-medium">
    {{ $userName }}
</x-link>

<!-- Plan name link -->
<x-link :href="route('subscriptions.show', $planId)" class="font-medium">
    {{ $planName }}
</x-link>

<!-- View action link -->
<x-link :href="route('subscriptions.holds.detail', $holdId)" class="font-medium text-sm">
    {{ __('subscriptions.View') }}
</x-link>
```

**Migration**: Replace all `<a>` tags in tables with `<x-link>` and remove the repeated Tailwind classes.

---

### 5. Fallback Strategy for Missing Data

**Decision**: Always handle missing IDs gracefully.

**Pattern**:
```php
@if($orgUserId)
    <a href="{{ route('members.profile', $orgUserId) }}">
        {{ $memberName }}
    </a>
@else
    <div class="font-medium">
        {{ $memberName }}
    </div>
@endif
```

**Why**: Prevents broken links and 404 errors. Shows content as plain text when ID is missing.

---

### 6. Debouncing and Performance

**Decision**: 300ms debounce on search inputs.

**Why 300ms**:
- Balance between responsiveness and server load
- Prevents query on every keystroke
- Feels instant to users while reducing requests by ~80%

**Implementation**: Use Livewire's built-in debounce:
```php
wire:model.live.debounce.300ms="search"
```

**Note**: Don't use shorter delays (causes too many requests) or longer delays (feels sluggish).

---

### 7. Pagination vs Infinite Scroll

**Decision**: Use Laravel pagination (NOT custom infinite scroll implementation).

**Why**:
- Simpler implementation
- Better performance for large datasets
- Built-in page number support
- Easier to maintain

**Note**: Previous documentation mentioned infinite scroll, but production implementation uses pagination for simplicity and performance.

---

### 8. Actions Column Placement

**Decision**: Actions column must ALWAYS be the last column.

**Why**:
- Consistent across all tables
- Users know where to find actions
- Doesn't interrupt data flow
- Mobile responsive (can be hidden if needed)

**Critical**: Update colspan in empty states when adding Actions column (e.g., from 7 to 8).

---

### 9. Row Hover Effects

**Decision**: Use hover effects on rows even though rows aren't clickable.

**Classes**: `hover:!bg-zinc-50 dark:hover:!bg-white/5 transition-colors`

**Why**:
- Provides visual feedback
- Helps users track which row they're reading
- `!important` overrides Flux UI defaults
- Subtle background change, not aggressive

**Don't Add**: `cursor-pointer` class (misleading - rows aren't clickable).

---

### 10. Empty State Colspan Calculation

**Gotcha**: Empty state must span ALL columns including Actions.

**How to Calculate**:
1. Count your data columns
2. Add 1 for Actions column
3. Use that number for colspan

**Example**:
```php
<!-- 6 data columns + 1 Actions = 7 total -->
<flux:table.cell colspan="7" class="text-center py-12">
    <!-- Empty state content -->
</flux:table.cell>
```

**Common Bug**: Forgetting to update colspan when adding Actions column causes layout issues.

---

## Common Gotchas

### 1. Flux UI Component Names
- Use `<flux:table>` not `<x-flux::table>`
- Component nesting: table → columns/rows → column/row → cell
- Always use `wire:key` on rows for Livewire

### 2. Dark Mode Support
- Always include dark mode variants: `dark:text-white`, `dark:hover:text-blue-400`
- Test in both light and dark modes
- Use Zinc scale for neutral colors (works in both modes)

### 3. Translation Keys
- Wrap all user-facing text in `__()` helper
- Use descriptive keys: `__('subscriptions.View')` not `__('view')`
- Check both English and Arabic translations

### 4. Query Performance
- Eager load relationships in `with()` to avoid N+1 queries
- Use `select()` to limit columns when possible
- Add database indexes on sortable/searchable columns

### 5. Livewire Pagination Reset
- Always call `$this->resetPage()` when search/filter changes
- Otherwise users see empty results on page 2+ after filtering

---

## Testing Checklist

### Critical Tests
- [ ] Sort by each column WITH search active (tests SQL ambiguity)
- [ ] All links show URL in status bar on hover
- [ ] Right-click menu works on all links
- [ ] Empty state colspan matches column count
- [ ] Dark mode renders correctly

### Accessibility Tests
- [ ] Tab through all links with keyboard
- [ ] Screen reader announces links properly
- [ ] No clickable rows (rows shouldn't be in tab order)

### Browser Compatibility
- [ ] Test in Chrome, Safari, Firefox
- [ ] Cmd/Ctrl+Click opens in new tab
- [ ] Middle-click opens in new tab
- [ ] Status bar shows URL on hover

---

## Performance Considerations

### Database Queries
- **Problem**: N+1 queries when loading relationships
- **Solution**: Use `with()` for eager loading
- **Example**: `->with(['orgUser', 'orgUserPlan.orgPlan'])`

### Search Performance
- **Problem**: LIKE queries can be slow on large tables
- **Solution**: Add database indexes on searchable columns
- **Consider**: Full-text search for large datasets

### Sorting Performance
- **Problem**: Subqueries in ORDER BY can be slow
- **Solution**: Add indexes on foreign keys
- **Monitor**: Use Laravel Debugbar to check query times

---

## Migration Notes

### Converting Clickable Rows to Semantic Links

If updating an existing table with clickable rows:

1. Remove `onclick` attribute from `<flux:table.row>`
2. Remove `cursor-pointer` class from rows
3. Add Actions column header (last position)
4. Add Actions cell with View link in each row
5. Wrap member names in `<a>` tags
6. Wrap plan names in `<a>` tags
7. Update empty state colspan
8. Test all links work correctly

**Commit Example**: See commit `187de2b` for MembershipHolds conversion.

---

## Related Documentation

- [Table Listing UI/UX Standards](./table-listing-standards.md) - Design principles and requirements
- [Flux UI Documentation](https://fluxui.dev/docs/table) - Official Flux table documentation
- **Reference Implementation**: `/resources/views/livewire/subscriptions/membership-holds.blade.php`

---

**Document Version**: 2.0  
**Last Updated**: October 13, 2025  
**Maintained By**: FOH Development Team

*This guide focuses on key implementation decisions and common gotchas. For complete code examples, refer to the MembershipHolds component implementation.*

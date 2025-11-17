# Infinite Scroll Technical Implementation Guide

## Overview

This document provides the technical implementation details for infinite scroll functionality in the wodworx-foh application using Livewire 3 and Alpine.js. All table listing pages must use infinite scroll instead of traditional pagination to provide a seamless mobile-first user experience.

**Reference Implementation**: `ActiveMembersList` component at `/app/Livewire/ActiveMembersList.php`

**Related Documents**: 
- [Table Listing UI/UX Standards](./table-listing-standards.md)
- [Table Listing Implementation Guide](./table-listing-implementation.md)

---

## ⚡ Key Optimizations (Production-Ready)

Our implementation includes several performance optimizations based on [Alpine.js best practices](https://alpinejs.dev/plugins/intersect):

### 1. **Preloading with `.margin` Modifier** 🚀
```blade
<div x-intersect.margin.100px="onScrollTrigger()"></div>
```
- Starts loading 100px BEFORE trigger enters viewport
- Makes infinite scroll feel instant and seamless
- Users never see a "loading" state

### 2. **Reactive Loading Indicator with `x-show`** 🎨
```blade
<div x-show="isLoading" x-transition>
```
- Automatically shows/hides based on Alpine state
- Smooth fade transitions
- No manual DOM manipulation needed

### 3. **Alpine's `$wire` Magic Property** 🪄
```javascript
this.$wire.loadMoreMembers()
```
- Direct Livewire 3 integration
- Cleaner than `@this` or `Livewire.find()`
- Always works correctly

### 4. **Proper State Management** 🔄
- Loading state in Alpine (instant UI feedback)
- Data state in Livewire (source of truth)
- Event-driven reinitialization on filter changes

---

## 🎯 Why Infinite Scroll?

### Benefits:
- ✅ **Mobile-First**: Natural touch scrolling experience
- ✅ **Performance**: Load data in smaller batches as needed
- ✅ **UX**: No page jumps or loading states between pages
- ✅ **Engagement**: Users stay in flow state while browsing
- ✅ **Modern**: Expected behavior in contemporary web apps

### When NOT to Use:
- ❌ Tables with precise navigation needs (e.g., legal documents with exact page references)
- ❌ Data that requires footer calculations across all records
- ❌ Use cases where users need to jump to specific page numbers

---

## 🏗️ Architecture Overview

### Technology Stack:
- **Livewire 3**: Backend data fetching and state management
- **Alpine.js**: Frontend reactivity and scroll detection
- **Intersection Observer API**: Via Alpine's `x-intersect` directive
- **Offset/Limit Queries**: Efficient database pagination

### Data Flow:
```
User Scrolls → Alpine detects trigger → Calls Livewire method → 
Fetches data (offset/limit) → Merges with existing → Renders new rows → 
Re-observes trigger element
```

---

## 📋 Implementation Checklist

### Backend (Livewire Component):
- [ ] Track loaded items in array property
- [ ] Track "has more" state
- [ ] Track loading state
- [ ] Implement offset/limit queries (not Laravel pagination)
- [ ] Reset state on filter/search changes
- [ ] Dispatch events when data resets

### Frontend (Blade View):
- [ ] Wrap in Alpine component with `x-data`
- [ ] Add `x-intersect` to scroll trigger element
- [ ] Display loaded items array
- [ ] Show loading indicator
- [ ] Show "end of results" message
- [ ] Include scroll-to-top button

---

## 💻 Step-by-Step Implementation

### Step 1: Backend - Livewire Component Properties

Add these properties to your Livewire component:

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;

class YourTableComponent extends Component
{
    // URL-bound search and filters
    #[Url(as: 'q')]
    public $search = '';
    
    #[Url(as: 'status')]
    public $statusFilter = '';
    
    // Infinite scroll state
    public $perPage = 25;              // Batch size
    public $loadedItems = [];          // Array of loaded items
    public $hasMoreItems = true;       // Flag to show/hide trigger
    public $isLoading = false;         // Loading state
    
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];
}
```

**Critical Notes**:
- Use `$loadedItems` as an **array**, not a collection
- Use `#[Url]` attributes for search/filters (Livewire 3 syntax)
- Keep `$perPage` reasonable (25-50 items)
- Don't use Laravel's `WithPagination` trait

---

### Step 2: Backend - Initial Load

```php
public function mount(?string $search = null, ?string $status = null)
{
    // Authorization check
    if (!optional(auth()->user()->orgUser)?->safeHasPermissionTo('view items')) {
        abort(403);
    }
    
    // Accept URL parameters
    if ($search) {
        $this->search = $search;
    }
    if ($status) {
        $this->statusFilter = $status;
    }
    
    // Load initial batch
    $this->loadInitialItems();
}

public function loadInitialItems()
{
    $newItems = YourService::getItems(
        search: $this->search,
        limit: $this->perPage,
        offset: 0,
        statusFilter: $this->statusFilter
    );
    
    // Convert to array if needed (stdClass → array)
    $this->loadedItems = $newItems->map(function ($item) {
        return (array) $item;
    })->toArray();
    
    // Check if there are more items
    $this->hasMoreItems = $newItems->count() >= $this->perPage;
}
```

---

### Step 3: Backend - Load More Method

```php
public function loadMoreItems()
{
    // Prevent duplicate loading
    if (!$this->hasMoreItems || $this->isLoading) {
        return;
    }
    
    $this->isLoading = true;
    
    // Calculate offset based on already loaded items
    $offset = count($this->loadedItems);
    
    // Fetch next batch
    $newItems = YourService::getItems(
        search: $this->search,
        limit: $this->perPage,
        offset: $offset,
        statusFilter: $this->statusFilter
    );
    
    // Check if we've reached the end
    if ($newItems->count() < $this->perPage) {
        $this->hasMoreItems = false;
    }
    
    // Convert and merge with existing items
    $newItemsArray = $newItems->map(function ($item) {
        return (array) $item;
    })->toArray();
    
    $this->loadedItems = array_merge($this->loadedItems, $newItemsArray);
    
    $this->isLoading = false;
}
```

**Why array_merge?**
- Livewire efficiently tracks array changes
- Prevents full re-render of existing items
- Maintains scroll position

---

### Step 4: Backend - Reset on Filter Changes

```php
public function updatedSearch()
{
    $this->resetItems();
}

public function updatedStatusFilter()
{
    $this->resetItems();
}

public function resetItems()
{
    $this->loadedItems = [];
    $this->hasMoreItems = true;
    $this->loadInitialItems();
    
    // Optional: Dispatch event for frontend to reinitialize
    $this->dispatch('items-updated');
}

public function clearFilters()
{
    $this->search = '';
    $this->statusFilter = '';
    $this->resetItems();
}
```

---

### Step 5: Backend - Service Layer (Offset/Limit Query)

**CRITICAL**: Use offset/limit queries, NOT `paginate()`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class YourService
{
    public static function getItems(
        ?string $search = null,
        int $limit = 25,
        int $offset = 0,
        ?string $statusFilter = null
    ): Collection {
        $query = DB::table('your_table')
            ->select([
                'id',
                'name',
                'email',
                // ... other fields
            ])
            ->where('org_id', auth()->user()->orgUser->org_id);
        
        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        
        // Use offset/limit instead of paginate()
        return $query
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
    
    // Helper methods for counts
    public static function count(?string $search = null, ?string $statusFilter = null): int
    {
        $query = DB::table('your_table')
            ->where('org_id', auth()->user()->orgUser->org_id);
            
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        
        return $query->count();
    }
}
```

---

### Step 6: Frontend - Blade View Structure

```blade
<div class="space-y-6" x-data="infiniteScroll()" x-init="init()">
    <!-- Page Header -->
    <div>
        <flux:heading size="xl">{{ __('Your Items') }}</flux:heading>
        <flux:subheading>
            @if($search || $statusFilter)
                Showing {{ number_format(count($loadedItems)) }} filtered items
            @else
                {{ number_format(count($loadedItems)) }} total items
            @endif
        </flux:subheading>
    </div>

    <!-- Search and Filters -->
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search..." 
                icon="magnifying-glass" 
            />
        </div>

        <div class="flex items-center gap-4">
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </flux:select>

            @if($search || $statusFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                    Clear
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Table with Loaded Items -->
    @if(count($loadedItems) > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <!-- More columns -->
            </flux:table.columns>

            <flux:table.rows>
                @foreach($loadedItems as $item)
                    <flux:table.row :key="$item['id']">
                        <flux:table.cell>{{ $item['name'] }}</flux:table.cell>
                        <flux:table.cell>{{ $item['email'] }}</flux:table.cell>
                        <!-- More cells -->
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <!-- Loading Indicator (Alpine reactive) -->
        <div x-show="isLoading" x-transition class="flex justify-center py-8">
            <div class="flex items-center gap-3 text-zinc-600 dark:text-zinc-400">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading more items...</span>
            </div>
        </div>

        <!-- End of Results -->
        @if(!$hasMoreItems && count($loadedItems) > 0)
            <div class="text-center py-8">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    You've reached the end of the list
                </div>
            </div>
        @endif

        <!-- Infinite Scroll Trigger with preload margin (CRITICAL) -->
        <div id="scroll-trigger" class="h-4" x-intersect.margin.100px="onScrollTrigger()"></div>

    @else
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon.users class="w-12 h-12 text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No items found</flux:heading>
            <flux:subheading>
                @if($search || $statusFilter)
                    No items match your search criteria.
                @else
                    No items have been created yet.
                @endif
            </flux:subheading>
            @if($search || $statusFilter)
                <flux:button wire:click="clearFilters" variant="primary" class="mt-4">
                    Clear filters
                </flux:button>
            @endif
        </div>
    @endif

    <!-- Scroll to Top Button -->
    <button 
        id="scroll-to-top" 
        class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition-all duration-300 ease-in-out transform scale-0 opacity-0 z-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" 
        aria-label="Scroll to top" 
        title="Back to top"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>
</div>

<script>
    function infiniteScroll() {
        return {
            isLoading: false,
            
            init() {
                // Initialize scroll to top button
                this.initScrollToTop();
            },
            
            onScrollTrigger() {
                // Using Alpine's x-intersect, this fires when scroll trigger is visible
                if (!this.isLoading && this.$wire.hasMoreItems) {
                    this.loadMoreItems();
                }
            },
            
            loadMoreItems() {
                if (this.isLoading || !this.$wire.hasMoreItems) return;
                
                this.isLoading = true;
                
                this.$wire.loadMoreItems().then(() => {
                    this.isLoading = false;
                }).catch((error) => {
                    console.error('Error loading more items:', error);
                    this.isLoading = false;
                });
            },
            
            initScrollToTop() {
                const scrollToTopButton = document.getElementById('scroll-to-top');
                let scrollTimeout;
                
                const handleScroll = () => {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > 300) {
                        scrollToTopButton.classList.remove('scale-0', 'opacity-0');
                        scrollToTopButton.classList.add('scale-100', 'opacity-100');
                    } else {
                        scrollToTopButton.classList.remove('scale-100', 'opacity-100');
                        scrollToTopButton.classList.add('scale-0', 'opacity-0');
                    }
                };
                
                const throttledScroll = () => {
                    if (scrollTimeout) {
                        clearTimeout(scrollTimeout);
                    }
                    scrollTimeout = setTimeout(handleScroll, 16); // ~60fps
                };
                
                window.addEventListener('scroll', throttledScroll, { passive: true });
                
                if (scrollToTopButton) {
                    scrollToTopButton.addEventListener('click', function() {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        this.classList.add('scale-90');
                        setTimeout(() => {
                            this.classList.remove('scale-90');
                        }, 150);
                    });
                }
            }
        }
    }
</script>
```

---

## 🔑 Key Technical Concepts

### 1. Alpine.js Integration with Livewire

**Why Alpine?**
- Already included in Livewire 3 (no extra dependencies)
- Perfect for UI interactivity
- `$wire` magic property provides direct access to Livewire component

**Key Alpine Features Used**:
- `x-data`: Define Alpine component
- `x-init`: Initialize on mount
- `x-intersect`: Detect when element enters viewport (replaces manual Intersection Observer)
- `$wire`: Access Livewire properties and methods

### 2. The `x-intersect` Directive with `.margin` Modifier

```html
<div id="scroll-trigger" class="h-4" x-intersect.margin.100px="onScrollTrigger()"></div>
```

**How it works**:
1. Alpine watches this element
2. The `.margin.100px` modifier triggers 100px BEFORE element enters viewport (preloading)
3. When triggered, `onScrollTrigger()` is called
4. We check if we should load more and call Livewire method
5. After new items load, the trigger element moves down (still attached to DOM)
6. When user scrolls to it again, it triggers again

**The `.margin` Modifier** (Performance Optimization):
- Starts loading content BEFORE user reaches the trigger
- Makes infinite scroll feel instant and seamless
- Values: `100px` (default), `200px` (aggressive), `50px` (conservative)
- From [Alpine.js docs](https://alpinejs.dev/plugins/intersect#margin): "Positive values expand the boundary beyond the viewport"

**Why it's better than manual Intersection Observer**:
- ✅ Automatic cleanup on component destruction
- ✅ Works with Livewire's morphing
- ✅ Less code to maintain
- ✅ No need to manually track observer instances
- ✅ Built-in preloading with `.margin` modifier

### 3. State Management

**Frontend State (Alpine)**:
- `isLoading`: Prevents duplicate requests
- Managed in JavaScript for instant UI feedback

**Backend State (Livewire)**:
- `$loadedItems`: Source of truth for data
- `$hasMoreItems`: Controls trigger visibility
- Synced via `$wire` magic property

### 4. Data Type Conversion

**Critical**: Convert stdClass to array:

```php
$this->loadedItems = $newItems->map(function ($item) {
    return (array) $item;  // Convert stdClass → array
})->toArray();
```

**Why?**
- Blade `@foreach` works better with arrays
- Livewire serialization is more efficient
- Prevents hydration issues

---

## ⚠️ Common Issues and Solutions

### Issue 1: Items Load Multiple Times

**Symptom**: Same items appear multiple times when scrolling

**Cause**: No duplicate prevention in loading logic

**Solution**: Check `isLoading` flag before loading:
```javascript
if (this.isLoading || !this.$wire.hasMoreItems) return;
this.isLoading = true;
```

---

### Issue 2: Scroll Trigger Not Firing After Filter Change

**Symptom**: Infinite scroll stops working after search/filter

**Cause**: Scroll trigger element gets removed from DOM

**Solution**: Make sure trigger element is ALWAYS present when `hasMoreItems` is true:
```blade
@if(!$hasMoreItems && count($loadedItems) > 0)
    <div>End of results</div>
@endif

<!-- Trigger OUTSIDE the @if -->
<div id="scroll-trigger" class="h-4" x-intersect="onScrollTrigger()"></div>
```

---

### Issue 3: Livewire Component Not Found

**Symptom**: Console error: "Cannot read properties of undefined"

**Cause**: Trying to use `Livewire.find()` instead of `$wire`

**Solution**: Always use `$wire` in Alpine components:
```javascript
// ❌ Wrong
const component = Livewire.find('some-id');
component.call('method');

// ✅ Correct
this.$wire.method();
```

---

### Issue 4: Scroll Position Jumps After Loading

**Symptom**: Page jumps when new items load

**Cause**: Height calculations during render

**Solution**: 
1. Keep loading indicator consistent size
2. Use `array_merge` (not array replace)
3. Ensure scroll trigger has fixed height (`h-4`)

---

### Issue 5: Slow Performance with Large Lists

**Symptom**: Page becomes sluggish after 100+ items

**Solutions**:
- Keep `$perPage` at 25-50 (not 100+)
- Consider virtual scrolling for 500+ items
- Optimize database queries with proper indexes
- Use `select()` to limit columns fetched

---

## 🎯 Best Practices

### 1. Batch Size (`perPage`)
- **Mobile**: 25 items
- **Desktop**: 25-50 items
- **Never**: 100+ items (hurts performance)

### 2. Loading Indicator
- **Use Alpine's `x-show`**: `<div x-show="isLoading" x-transition>`
- Benefits: Automatic reactivity, smooth transitions, no manual DOM manipulation
- Show spinner + descriptive text
- Position: centered below table

### 3. Preload with `.margin` Modifier
- **Always use**: `x-intersect.margin.100px` (or higher)
- Starts loading BEFORE user reaches trigger
- Makes scrolling feel instant and seamless
- Adjust margin based on network speed expectations

### 4. End of Results Message
- Show only when: `!$hasMoreItems && count($loadedItems) > 0`
- Clear messaging: "You've reached the end"
- Center aligned, muted text color

### 5. Scroll to Top Button
- Appear after 300px scroll
- Fixed position: bottom-right
- Smooth scroll animation
- Touch-friendly size (56px minimum)

### 6. Error Handling
```javascript
this.$wire.loadMoreItems().catch((error) => {
    console.error('Error loading:', error);
    this.isLoading = false;
    this.hideLoadingIndicator();
    // Optionally show error message to user
});
```

### 7. URL State Persistence
Always use `#[Url]` attributes or `$queryString` for:
- Search queries
- Filter selections
- This allows bookmarking and sharing filtered views

---

## 📊 Performance Considerations

### Database Optimization
```php
// ✅ Good: Use indexes
$query->where('org_id', $orgId)  // Indexed
      ->where('status', $status)  // Indexed
      ->orderBy('created_at', 'desc')  // Indexed
      ->offset($offset)
      ->limit($limit);
```

### Query Efficiency
```php
// ✅ Select only needed columns
->select(['id', 'name', 'email', 'created_at'])

// ❌ Don't select all
->select('*')
```

### Livewire Optimization
```php
// ✅ Convert to array (efficient)
$this->loadedItems = $items->map(fn($i) => (array)$i)->toArray();

// ❌ Don't store collections (inefficient serialization)
$this->loadedItems = $items;  // Bad!
```

---

## 🧪 Testing Checklist

### Functional Tests:
- [ ] Initial load shows first batch
- [ ] Scrolling loads more items
- [ ] Search resets and shows filtered results
- [ ] Filter change resets and shows filtered results
- [ ] Clear filters button works
- [ ] End of results shows when no more items
- [ ] Scroll to top button appears/disappears correctly
- [ ] Loading indicator shows during fetch

### Edge Cases:
- [ ] Empty state when no items exist
- [ ] No results when search has no matches
- [ ] Last batch has fewer than `perPage` items
- [ ] Rapid scroll doesn't cause duplicate loads
- [ ] Works with slow network connections
- [ ] Mobile touch scrolling works smoothly

### Performance Tests:
- [ ] Page loads in < 2 seconds
- [ ] Scroll is smooth at 60fps
- [ ] Memory doesn't grow excessively after 100+ items
- [ ] Database queries remain fast (< 100ms)

---

## 🔄 Migration from Pagination

### Converting Existing Table:

**1. Update Component Class:**
```php
// Remove
use Livewire\WithPagination;

// Add
public $loadedItems = [];
public $hasMoreItems = true;
public $isLoading = false;
```

**2. Replace `paginate()` with `offset/limit`:**
```php
// Old
$items = Model::query()->paginate($perPage);

// New
$items = Model::query()->offset($offset)->limit($perPage)->get();
```

**3. Update Blade View:**
```blade
<!-- Old -->
@foreach($items as $item)

<!-- New -->
@foreach($loadedItems as $item)
```

**4. Replace Pagination Links:**
```blade
<!-- Remove -->
{{ $items->links() }}

<!-- Add -->
<div id="scroll-trigger" class="h-4" x-intersect="onScrollTrigger()"></div>
```

---

## 📚 Related Documentation

- [Table Listing UI/UX Standards](./table-listing-standards.md) - High-level requirements
- [Table Listing Implementation Guide](./table-listing-implementation.md) - General table patterns
- [Livewire 3 Documentation](https://livewire.laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/directives/intersect)
- [Flux UI Documentation](https://fluxui.dev/docs/table)

---

**Document Version**: 1.0  
**Last Updated**: October 13, 2025  
**Reference Implementation**: `/app/Livewire/ActiveMembersList.php`

*This document provides the technical implementation details for infinite scroll. All table listing pages in wodworx-foh must follow this pattern for consistency and optimal user experience.*


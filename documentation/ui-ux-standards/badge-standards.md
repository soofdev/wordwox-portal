# Badge Standards - Flux UI Implementation

## Overview

This document defines the standardized approach for using badges across the wodworx-foh application using Flux UI components. Badges are used to highlight status, categories, and other important information with consistent visual styling.

## 🎨 Badge Color System

### Status-Based Colors
We use a consistent color mapping for membership and status indicators:

| Status | Color | Flux UI Color | Use Case |
|--------|-------|---------------|----------|
| Active/Good | `green` | `green` | Active memberships, positive status |
| Warning/Expiring | `yellow` | `yellow` | Memberships expiring within 30 days |
| Critical/Expired | `red` | `red` | Expired memberships, urgent attention needed |
| Inactive/Neutral | `zinc` | `zinc` | Inactive status, no active plan |

### Backend Color Logic
The backend provides color strings that directly map to Flux UI colors:

```php
// Example from MemberProfile.php
public function getExpirationStatus()
{
    if ($days <= 7) {
        return ['status' => 'expiring-soon', 'color' => 'red', 'text' => 'Expires in ' . $remainingDuration];
    }
    if ($days <= 30) {
        return ['status' => 'expiring', 'color' => 'yellow', 'text' => 'Expires in ' . $remainingDuration];
    }
    return ['status' => 'active', 'color' => 'green', 'text' => 'Active (' . $remainingDuration . ' remaining)'];
}
```

## 🛠 Implementation Patterns

### 1. Dynamic Color Badges
For status badges that change color based on backend data:

```blade
@php
    $badgeColor = match($expirationStatus['color']) {
        'green' => 'green',
        'yellow' => 'yellow', 
        'red' => 'red',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" color="{{ $badgeColor }}" inset="top bottom">
    {{ $expirationStatus['text'] }}
</flux:badge>
```

**Key Points:**
- Use `color="{{ $badgeColor }}"` (not `:color="'{{ $badgeColor }}'")
- Include `inset="top bottom"` for proper spacing when used inline with text
- Use `size="sm"` for consistent sizing across the application

### 2. Static Color Badges
For badges with fixed colors:

```blade
<flux:badge color="green" size="sm" inset="top bottom">
    Active
</flux:badge>
```

### 3. Member Number Badges
For member numbers and IDs, use the outline variant:

```blade
<flux:badge variant="outline" size="sm">
    #{{ $member->number }}
</flux:badge>
```

## 📐 Sizing Standards

### Size Guidelines
- **`sm`**: Default size for most status badges and inline usage
- **Default** (no size prop): Use sparingly for prominent status indicators
- **`lg`**: Reserved for primary call-to-action badges or headers

```blade
<!-- Standard size for most cases -->
<flux:badge size="sm" color="green">Active</flux:badge>

<!-- Larger for emphasis -->
<flux:badge size="lg" color="red">Urgent</flux:badge>
```

## 🎯 Spacing and Layout

### Inset Usage
Use `inset="top bottom"` when badges are displayed inline with text to prevent spacing issues:

```blade
<flux:heading>
    Page Title <flux:badge color="lime" inset="top bottom">New</flux:badge>
</flux:heading>
```

### Grid and List Layouts
In tables and lists, badges should be consistent:

```blade
<flux:table.cell>
    <flux:badge size="sm" color="{{ $member['membership_status'] === 'active' ? 'green' : 'zinc' }}" inset="top bottom">
        {{ $member['membership_status'] === 'active' ? 'Active' : 'Inactive' }}
    </flux:badge>
</flux:table.cell>
```

## 🔧 Common Patterns

### 1. Membership Status Pattern
Used in member profiles and member lists:

```blade
@php
    $statusColor = match($member->status) {
        'active' => 'green',
        'expiring' => 'yellow',
        'expired' => 'red',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" color="{{ $statusColor }}" inset="top bottom">
    {{ $member->statusLabel }}
</flux:badge>
```

### 2. Conditional Badge Pattern
For optional badges that may not always display:

```blade
@if($member->number)
    <flux:badge variant="outline" size="sm">
        #{{ $member->number }}
    </flux:badge>
@endif
```

### 3. Multiple Badge Groups
When displaying multiple badges together:

```blade
<div class="flex flex-wrap items-center gap-2">
    @if($member->number)
        <flux:badge variant="outline" size="sm">
            #{{ $member->number }}
        </flux:badge>
    @endif
    
    <flux:badge size="sm" color="{{ $badgeColor }}" inset="top bottom">
        {{ $expirationStatus['text'] }}
    </flux:badge>
</div>
```

## ⚠️ Common Mistakes to Avoid

### 1. Incorrect Color Syntax
❌ **Wrong:**
```blade
<flux:badge :color="'{{ $badgeColor }}'" inset="top bottom">
```

✅ **Correct:**
```blade
<flux:badge color="{{ $badgeColor }}" inset="top bottom">
```

### 2. Missing Inset for Inline Usage
❌ **Wrong:** (causes spacing issues)
```blade
<flux:heading>
    Title <flux:badge color="green">New</flux:badge>
</flux:heading>
```

✅ **Correct:**
```blade
<flux:heading>
    Title <flux:badge color="green" inset="top bottom">New</flux:badge>
</flux:heading>
```

### 3. Inconsistent Sizing
❌ **Wrong:** (mixed sizes without purpose)
```blade
<flux:badge color="green">Active</flux:badge>
<flux:badge size="sm" color="yellow">Expiring</flux:badge>
```

✅ **Correct:** (consistent sizing)
```blade
<flux:badge size="sm" color="green">Active</flux:badge>
<flux:badge size="sm" color="yellow">Expiring</flux:badge>
```

## 🎨 Available Flux UI Badge Colors

According to the [Flux UI Badge documentation](https://fluxui.dev/components/badge):

**Standard Colors:**
- `zinc` (default gray)
- `red`
- `orange` 
- `amber`
- `yellow`
- `lime`
- `green`
- `emerald`
- `teal`
- `cyan`
- `sky`
- `blue`
- `indigo`
- `violet`
- `purple`
- `fuchsia`
- `pink`
- `rose`

**Our Application Uses:**
- `green` - Active/positive status
- `yellow` - Warning/expiring status  
- `red` - Critical/expired status
- `zinc` - Neutral/inactive status

## 📝 Implementation Checklist

When implementing badges, ensure:

- [ ] Color syntax is correct: `color="{{ $variable }}"` not `:color="'{{ $variable }}'"`
- [ ] Consistent sizing (`size="sm"` for most cases)
- [ ] Proper inset usage (`inset="top bottom"` for inline badges)
- [ ] Color mapping follows the established status system
- [ ] Backend provides the correct color strings
- [ ] Responsive behavior is maintained

## 🔗 Related Components

- **Tables**: Use badges in table cells for status columns
- **Cards**: Include status badges in card headers
- **Lists**: Display badges alongside list items
- **Headers**: Use badges for page-level status indicators

## 📚 References

- [Flux UI Badge Component Documentation](https://fluxui.dev/components/badge)
- Member Profile Implementation: `resources/views/livewire/member-profile.blade.php`
- Members List Implementation: `resources/views/livewire/active-members-list.blade.php`
- Backend Logic: `app/Livewire/MemberProfile.php`

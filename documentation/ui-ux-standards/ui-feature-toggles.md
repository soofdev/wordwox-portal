# UI Feature Toggles

## Overview
Environment-based configuration to control the visibility of UI features and menu items.

## Configuration

All UI feature toggles are configured in `config/app.php` under the `ui_features` array.

```php
'ui_features' => [
    'show_subscriptions_menu' => env('SHOW_SUBSCRIPTIONS_MENU', true),
],
```

## Available Toggles

### Subscriptions Menu
**Environment Variable**: `SHOW_SUBSCRIPTIONS_MENU`  
**Default**: `true`  
**Controls**: Subscriptions navigation group and all its menu items (Membership, Membership Holds)

## Usage

Add to your `.env` file:

```env
# Hide the Subscriptions menu
SHOW_SUBSCRIPTIONS_MENU=false

# Show the Subscriptions menu (default)
SHOW_SUBSCRIPTIONS_MENU=true
```

After changing `.env`, clear config cache:
```bash
php artisan config:clear
```

## Implementation

The sidebar checks both the config toggle AND user permissions:

```php
@php
$showSubscriptionsMenu = config('app.ui_features.show_subscriptions_menu', true);
$hasSubscriptionPermissions = /* permission checks */;
@endphp

@if($showSubscriptionsMenu && $hasSubscriptionPermissions)
    {{-- Subscriptions menu items --}}
@endif
```

## Adding New Toggles

1. Add to `config/app.php`:
   ```php
   'ui_features' => [
       'show_subscriptions_menu' => env('SHOW_SUBSCRIPTIONS_MENU', true),
       'show_new_feature' => env('SHOW_NEW_FEATURE', false),
   ],
   ```

2. Use in templates:
   ```blade
   @if(config('app.ui_features.show_new_feature'))
       {{-- Feature UI --}}
   @endif
   ```

## Notes

- Toggles control UI visibility only, not security
- Always combine with permission checks
- Clear config cache after `.env` changes
- Use descriptive environment variable names

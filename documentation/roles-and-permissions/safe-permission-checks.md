# Safe Permission Checks

This document explains how to safely check permissions in the FOH system to prevent `PermissionDoesNotExist` exceptions.

## The Problem

When using Spatie Permission package, calling `hasPermissionTo()` or Laravel's `can()` method will throw a `PermissionDoesNotExist` exception if the permission hasn't been created in the database yet. This commonly happens:

- During development when permissions haven't been seeded
- After database resets
- When adding new permissions that haven't been deployed yet
- In fresh installations

## The Solution: Safe Permission Checks

### Use `safeHasPermissionTo()` Instead

❌ **Don't do this:**
```php
// These will throw exceptions if permissions don't exist
$user->hasPermissionTo('create members')
auth()->user()->can('create members')
```

✅ **Do this instead:**
```php
// This will safely return false if permission doesn't exist
optional(auth()->user()->orgUser)?->safeHasPermissionTo('create members')
```

### In Blade Templates

❌ **Don't do this:**
```blade
@can('create members')
    <!-- This will throw exception -->
@endcan

@if(auth()->user()->orgUser->hasPermissionTo('create members'))
    <!-- This will also throw exception -->
@endif
```

✅ **Do this instead:**
```blade
@if(optional(auth()->user()->orgUser)?->safeHasPermissionTo('create members'))
    <!-- Safe permission check -->
@endif
```

### In Livewire Components

❌ **Don't do this:**
```php
public function mount()
{
    if (!auth()->user()->can('create members')) {
        // Will throw exception
        return redirect()->route('dashboard');
    }
}
```

✅ **Do this instead:**
```php
public function mount()
{
    if (!optional(auth()->user()->orgUser)?->safeHasPermissionTo('create members')) {
        // Safe check - returns false if permission doesn't exist
        return redirect()->route('dashboard');
    }
}
```

## How `safeHasPermissionTo()` Works

The method is implemented in the `OrgUser` model:

```php
public function safeHasPermissionTo(string $permission): bool
{
    try {
        return $this->hasPermissionTo($permission);
    } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
        // Log the missing permission for debugging
        \Illuminate\Support\Facades\Log::warning('Permission not found in database', [
            'permission' => $permission,
            'user_id' => $this->id,
            'org_id' => $this->org_id,
            'message' => 'Consider running: php artisan foh:create-permissions'
        ]);
        
        return false;
    }
}
```

## Benefits

1. **No Exceptions**: Your application won't crash if permissions aren't seeded
2. **Graceful Degradation**: Features are simply hidden if permissions don't exist
3. **Development Friendly**: Works even in fresh development environments
4. **Debugging Support**: Logs missing permissions for easy identification
5. **Production Safe**: Handles permission deployment edge cases gracefully

## Middleware Protection

The system also includes `HandlePermissionExceptions` middleware as a fallback to catch any remaining unsafe permission checks:

```php
// In bootstrap/app.php
\App\Http\Middleware\HandlePermissionExceptions::class,
```

This middleware:
- Catches `PermissionDoesNotExist` exceptions
- Logs the missing permission details
- Shows a friendly error page instead of a 500 error
- Provides guidance on how to fix the issue

## Best Practices

1. **Always use `safeHasPermissionTo()`** for permission checks in views and controllers
2. **Use `optional()` helper** to safely access the orgUser relationship
3. **Role checks are safe** - `hasRole()` doesn't throw exceptions like `hasPermissionTo()`
4. **Test without permissions** - Regularly test your application with no permissions seeded
5. **Monitor logs** - Check for "Permission not found" warnings in your logs

## Quick Fix Command

If you encounter permission errors, run:

```bash
# Create all default permissions and roles
php artisan foh:create-permissions

# Or reset and recreate everything
php artisan foh:reset-permissions --force --seed
```

## Related Files

- **Safe Method**: `app/Models/OrgUser.php` - `safeHasPermissionTo()` method
- **Middleware**: `app/Http/Middleware/HandlePermissionExceptions.php`
- **Commands**: `app/Console/Commands/CreateFohPermissions.php`
- **Examples**: See any Livewire component for proper usage patterns

By following these patterns, your FOH application will be robust and handle permission-related edge cases gracefully.

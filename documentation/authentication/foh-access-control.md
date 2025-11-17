# FOH Access Control System

## Overview

The Front of House (FOH) access control system ensures that only authorized users can access the FOH interface. This system implements a comprehensive security model using middleware-based validation, multi-tenant organization support, and automatic user routing.

## Architecture

### Core Components

1. **EnsureFohAccess Middleware** - Global request validation
2. **User Model Extensions** - FOH access checking methods
3. **Clean Layout** - Sidebar-free organization selection interface
4. **OrgUser Controller** - Organization switching logic
5. **Login Component** - Simplified authentication flow

## Access Control Logic

### Three-Tier Access Validation

The system implements a three-step validation process:

#### 1. Current Organization FOH Access
```php
if ($user->orgUser && $user->orgUser->isFohUser) {
    // User has FOH access in current organization
    // → Allow access to dashboard and all FOH features
}
```

#### 2. Alternative Organization FOH Access
```php
if ($user->hasAnyFohAccess()) {
    // User has FOH access in other organizations
    // → Redirect to organization selection screen
}
```

#### 3. No FOH Access
```php
// User has no FOH access in any organization
// → Logout user and redirect to login with error message
```

## Implementation Details

### EnsureFohAccess Middleware

**Location**: `app/Http/Middleware/EnsureFohAccess.php`

**Purpose**: Validates FOH access on every web request

**Key Features**:
- Runs on all web routes except excluded paths
- Checks current user's orgUser FOH status
- Handles automatic redirects based on access level
- Logs out users without any FOH access

**Excluded Routes**:
- `login` - Login page
- `logout` - Logout functionality
- `org-user.select` - Organization selection
- `org-user.set` - Organization switching
- `public.signature.*` - Public signature routes
- `public/signature/*` - Public signature URLs

**Registration**: Added to `bootstrap/app.php` as web middleware

```php
$middleware->web(append: [
    \App\Http\Middleware\EnsureFohAccess::class,
    \App\Http\Middleware\SetTenantTimezone::class,
]);
```

### User Model Extensions

**Location**: `app/Models/User.php`

**New Methods**:

#### hasAnyFohAccess()
```php
public function hasAnyFohAccess(): bool
{
    return $this->orgUsers()->withoutGlobalScopes()->where('isFohUser', true)->exists();
}
```
- Checks if user has FOH access in any organization
- Uses `withoutGlobalScopes()` to bypass tenant filtering
- Returns boolean result

#### fohOrgUsers()
```php
public function fohOrgUsers()
{
    return $this->orgUsers()->where('isFohUser', true);
}
```
- Returns collection of orgUsers with FOH access
- Used for organization selection display

### Organization Selection Interface

**Layout**: `resources/views/components/layouts/clean.blade.php`

**Features**:
- Clean interface without sidebar
- Minimal header with logo and user info
- Logout functionality always available
- Logo links to dashboard only if user has current FOH access

**View**: `resources/views/org-user/select.blade.php`

**Features**:
- Uses clean layout for focused experience
- Displays only organizations with FOH access
- Search functionality for large organization lists
- Role badges showing user permissions

### OrgUser Controller

**Location**: `app/Http/Controllers/OrgUserController.php`

**Methods**:

#### select()
- Displays organization selection screen
- Filters to show only FOH-enabled organizations
- Orders organizations alphabetically

#### set($id)
- Switches user to selected organization
- Validates FOH access before switching
- Updates user's `orgUser_id` field
- Redirects to dashboard after successful switch

### Login Component Simplification

**Location**: `app/Livewire/Auth/Login.php`

**Changes**:
- Removed complex FOH validation logic
- Simplified to basic authentication
- Lets middleware handle access control
- Cleaner, more maintainable code

## Security Features

### Global Protection
- **Every Request Validated**: Middleware runs on all web routes
- **No Bypass Routes**: Unauthorized users cannot access any FOH features
- **Automatic Enforcement**: No manual security checks required in components

### Multi-Tenant Security
- **Tenant Scope Bypass**: Uses `withoutGlobalScopes()` for proper FOH checking
- **Organization Isolation**: Users can only switch to orgs where they have FOH access
- **Session Security**: Invalid users are logged out immediately

### User Experience
- **Smart Redirects**: Users automatically routed to appropriate screens
- **Clear Messaging**: Informative error messages for access denial
- **Seamless Switching**: Easy organization selection for multi-org users

## Database Schema

### Key Fields

#### user table
- `orgUser_id` - Current active organization user record

#### orgUser table
- `isFohUser` (boolean) - FOH access flag
- `user_id` - Reference to user record
- `org_id` - Reference to organization

### Relationships
```php
// User model
public function orgUser() // Current active org user
public function orgUsers() // All org users for this user

// OrgUser model
public function user() // User record
public function org() // Organization record
```

## Flow Diagrams

### Authentication Flow
```
Login → Credentials Valid? → Middleware Check → FOH Access?
                ↓                              ↓
            Login Error                   Current Org FOH?
                                              ↓
                                         Dashboard Access
                                              ↓
                                        Other Org FOH?
                                              ↓
                                        Org Selection
                                              ↓
                                         No FOH Access
                                              ↓
                                        Logout + Error
```

### Request Flow
```
Any Request → Middleware → Authenticated? → FOH Check → Allow/Redirect/Deny
```

## Configuration

### Middleware Registration
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\EnsureFohAccess::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
})
```

### Route Protection
All web routes are automatically protected except those explicitly excluded in the middleware's `shouldSkipFohCheck()` method.

## Error Handling

### Access Denied
- User logged out automatically
- Redirected to login page
- Error message: "You do not have permission to access the Front of House interface. Please contact your administrator to get access."

### Invalid Organization Switch
- HTTP 403 error
- Message: "You do not have FOH access to this organization."

## Testing Scenarios

### Test Cases

1. **User with FOH in current org**
   - Should access dashboard directly
   - Logo should link to dashboard

2. **User with FOH in other orgs only**
   - Should be redirected to org selection
   - Should see only FOH-enabled orgs
   - Logo should not link to dashboard

3. **User with no FOH access**
   - Should be logged out
   - Should see access denied message

4. **Organization switching**
   - Should validate FOH access
   - Should update user's orgUser_id
   - Should redirect to dashboard

## Maintenance

### Adding New Protected Routes
New routes are automatically protected by the middleware. No additional configuration needed.

### Excluding Routes from FOH Check
Add route patterns to the `shouldSkipFohCheck()` method in `EnsureFohAccess` middleware.

### Debugging Access Issues
- Check user's `orgUser_id` value
- Verify `isFohUser` flag on orgUser records
- Review middleware execution in logs
- Confirm global scopes aren't interfering

## Performance Considerations

- Middleware runs on every request - minimal performance impact
- Database queries optimized with proper indexing
- Global scope bypass only used when necessary
- Efficient organization filtering in selection screen

## Future Enhancements

### Potential Improvements
1. **Role-based FOH Access** - More granular permissions
2. **Audit Logging** - Track organization switches and access attempts
3. **Session Management** - Enhanced security for multi-org users
4. **API Protection** - Extend FOH validation to API routes
5. **Admin Override** - Temporary access grants for support

### Migration Considerations
- Existing users need `isFohUser` flag set appropriately
- Consider data migration scripts for bulk updates
- Test with existing user sessions

# Admin Role Protection Features

## Overview

This document outlines the admin role protection features implemented in the FOH (Front of House) roles and permissions system. These features ensure that admin users cannot accidentally lock themselves out of the system and maintain proper access control.

## Features Implemented

### 1. Admin Self-Removal Protection

**Feature**: Prevents admin users from removing themselves from the Admin role.

**Implementation**:
- Location: `app/Livewire/Setup/Roles/EditRole.php` - `removeUser()` method
- Logic: Checks if the current user is trying to remove themselves from the Admin role
- Error Message: Shows a clear message explaining why the action is blocked

**Code Example**:
```php
// Prevent Admin role users from removing themselves from the Admin role
// This prevents admins from locking themselves out
if ($this->role->name === 'Admin' && 
    auth()->user()->orgUser && 
    auth()->user()->orgUser->id == $orgUserId) {
    session()->flash('error', __('roles.admin_cannot_remove_self_from_admin_role'));
    return;
}
```

**UI Behavior**: 
- The remove button is hidden for the current admin user when viewing Admin role users
- Shows "Cannot remove self" message instead of remove button

### 2. Admin-Only Access Control

**Feature**: Restricts access to roles and permissions management to Admin role users only.

**Implementation**:
- Location: Multiple components and views
- Changed from permission-based (`manage roles`) to role-based (`Admin` role) access control
- Components updated:
  - `app/Livewire/Setup/Roles/EditRole.php`
  - `app/Livewire/Setup/Roles/RolesManagement.php`
  - `app/Livewire/Setup/Roles/CreateRole.php`
  - `resources/views/components/settings/layout.blade.php`

**Code Example**:
```php
// Permission gate: only users with Admin role can access
if (!optional(auth()->user()->orgUser)?->hasRole('Admin')) {
    session()->flash('error', __('gym.Access Denied'));
    return $this->redirect(route('dashboard'), navigate: true);
}
```

**UI Behavior**:
- "Roles & Permissions" menu item only shows for Admin role users
- Non-admin users are redirected to dashboard if they try to access role management URLs

### 3. Admin Role Permission Protection

**Feature**: Admin role permissions cannot be modified and are always enabled.

**Implementation**:
- Location: `app/Livewire/Setup/Roles/EditRole.php` - `togglePermission()` method
- Logic: Blocks any attempts to modify Admin role permissions
- UI: Permission switches are disabled for Admin role

**Code Example**:
```php
// Prevent modification of Admin role permissions
// Admin role should always have all permissions and cannot be modified
if ($this->role->name === 'Admin') {
    session()->flash('error', __('roles.admin_role_permissions_cannot_be_modified'));
    return;
}
```

**UI Behavior**:
- Permission switches are visually disabled for Admin role
- Shows informational message explaining Admin role permissions are protected
- All permissions remain checked and cannot be unchecked

## Technical Details

### Database Structure

The system uses Spatie Permission package with custom models:
- `FohRole` - Custom role model using `foh_roles` table
- `FohPermission` - Custom permission model using `foh_permissions` table
- Roles are assigned to `OrgUser` models (not `User` models)

### Role Hierarchy

1. **Admin Role**
   - Has ALL permissions automatically (enforced by seeder)
   - Cannot have permissions modified
   - Users cannot remove themselves from this role
   - Only Admin role users can access role management

2. **Sales Role**
   - Has most permissions except admin-specific ones
   - Permissions can be modified by Admin users

3. **Reception Role**
   - Has basic permissions for front desk operations
   - Permissions can be modified by Admin users

### Seeder Configuration

The `FohPermissionSeeder` ensures:
- Admin role always gets ALL permissions: `$adminRole->syncPermissions(FohPermission::all())`
- This is critical for admin protection features to work correctly

## Language Support

New language strings added to `lang/en/roles.php`:

```php
// Admin Role Protection
'admin_cannot_remove_self_from_admin_role' => 'Admin users cannot remove themselves from the Admin role to prevent being locked out.',
'admin_role_permissions_cannot_be_modified' => 'Admin role permissions cannot be modified. Admin role always has all permissions.',
'admin_role_permissions_info' => 'Admin role has all permissions enabled and cannot be modified.',
'Cannot remove self' => 'Cannot remove self',
```

## Security Considerations

1. **Lockout Prevention**: Admin users cannot accidentally remove their own access
2. **Access Control**: Only Admin role users can manage roles and permissions
3. **Permission Integrity**: Admin role permissions cannot be accidentally disabled
4. **Multi-tenancy**: All protections work within organization boundaries

## Testing Scenarios

To test these features:

1. **Admin Self-Removal Protection**:
   - Login as Admin user
   - Navigate to Admin role in role management
   - Verify you cannot remove yourself from the role

2. **Access Control**:
   - Login as non-Admin user (Sales/Reception)
   - Verify "Roles & Permissions" menu item is hidden
   - Try to access role management URLs directly - should redirect to dashboard

3. **Permission Protection**:
   - Login as Admin user
   - Navigate to Admin role permissions tab
   - Verify all permission switches are disabled
   - Try to toggle permissions - should show error message

## Future Enhancements

Potential improvements to consider:

1. **Multiple Admin Protection**: Prevent removing the last Admin user
2. **Audit Logging**: Log all role and permission changes
3. **Role Templates**: Predefined role templates for common setups
4. **Bulk Operations**: Assign roles to multiple users at once

## Related Documentation

- [FOH Access Control](../authentication/foh-access-control.md)
- [Multi-tenancy Analysis](../authentication/multi-tenancy-analysis.md)

# FOH Permissions Management Commands

This document describes the Laravel Artisan commands available for managing the FOH (Front of House) roles and permissions system.

## Available Commands

### `foh:create-permissions`
Creates FOH permissions, roles, and user assignments.

**Usage:**
```bash
php artisan foh:create-permissions [options]
```

**Options:**
- `--org-id=ID` - Target specific organization ID (if empty, processes all orgs)
- `--force` - Force recreate existing permissions and roles
- `--permissions-only` - Only create permissions, skip roles
- `--roles-only` - Only create roles, skip permissions  
- `--no-assignments` - Skip assigning admin users to Admin role
- `--detailed` - Show detailed information about what is being created

**Examples:**
```bash
# Create everything (permissions, roles, assignments)
php artisan foh:create-permissions

# Force recreate everything
php artisan foh:create-permissions --force

# Only create permissions
php artisan foh:create-permissions --permissions-only

# Only create roles (requires permissions to exist)
php artisan foh:create-permissions --roles-only

# Create without assigning users to Admin role
php artisan foh:create-permissions --no-assignments

# Show detailed information about what's being created
php artisan foh:create-permissions --detailed

# Force recreate with detailed output
php artisan foh:create-permissions --force --detailed

# Target specific organization
php artisan foh:create-permissions --org-id=1

# Target specific organization with detailed output
php artisan foh:create-permissions --org-id=1 --detailed

# Assign admin users only for specific organization
php artisan foh:create-permissions --org-id=1 --roles-only
```

### `foh:reset-permissions`
Completely resets the FOH permissions system by deleting all roles, permissions, and assignments.

**Usage:**
```bash
php artisan foh:reset-permissions [options]
```

**Options:**
- `--force` - Force the operation without confirmation
- `--seed` - Automatically run the create-permissions command after reset

**Examples:**
```bash
# Reset with confirmation prompt
php artisan foh:reset-permissions

# Reset without confirmation
php artisan foh:reset-permissions --force

# Reset and immediately recreate
php artisan foh:reset-permissions --force --seed
```

## Organization Filtering

The `--org-id` option allows you to target specific organizations when assigning admin users to roles. This is particularly useful in multi-tenant environments.

### How Organization Filtering Works:

**Without `--org-id` (Default):**
- Processes all organizations
- Assigns Admin role to all admin/owner users across all orgs
- Shows statistics for all organizations

**With `--org-id=123`:**
- Validates that organization ID exists
- Only processes admin/owner users from the specified organization
- Shows organization-specific statistics
- Permissions and roles are still global (shared across all orgs)

### Use Cases:

1. **Initial Setup**: Use without `--org-id` to set up all organizations
2. **New Organization**: Use with `--org-id` when adding a new organization
3. **Specific Fixes**: Use with `--org-id` to fix admin assignments for one org
4. **Testing**: Use with `--org-id` to test changes on a single organization

### Example Output:

```bash
# Single organization
🚀 FOH Permissions Creation Tool
🏢 Target Organization: Acme Gym (ID: 1)

👤 Assigning Admin role to organization admins in org 1...
   ✓ Assigned Admin role to 2 of 2 admin users

📊 Summary:
   • Total Permissions: 23
   • Total Roles: 3
     - Admin: 23 permissions, 2 users (in org 1)
     - Sales: 17 permissions, 0 users (in org 1)
     - Reception: 7 permissions, 0 users (in org 1)

# All organizations
🚀 FOH Permissions Creation Tool
🌐 Processing all organizations (5 total)

👤 Assigning Admin role to organization admins across all orgs...
   ✓ Assigned Admin role to 8 of 12 admin users

📊 Summary:
   • Total Permissions: 23
   • Total Roles: 3
     - Admin: 23 permissions, 8 users (all orgs)
     - Sales: 17 permissions, 15 users (all orgs)
     - Reception: 7 permissions, 23 users (all orgs)
```

## Detailed Output Mode

The `--detailed` option provides comprehensive information about what the command is creating, updating, or skipping:

### What `--detailed` Shows:

**For Permissions:**
- Individual permission creation with database IDs
- Existing permissions being deleted (with `--force`)
- Skipped permissions with reasons
- Summary lists of created and skipped permissions

**For Roles:**
- Role creation with database IDs
- Existing roles being deleted (with `--force`)
- Complete permission lists for each role
- Current permissions for existing roles (when skipped)
- Missing permissions warnings

**For User Assignments:**
- Found admin users with their details (ID, email, current roles)
- Individual user role assignments
- Skipped assignments with reasons
- Search criteria when no users found

### Example Detailed Output:
```bash
🔐 Creating FOH permissions...
   ✅ Created: 'create members' (ID: 139)
   ✅ Created: 'view members' (ID: 140)
   # ... more permissions

📋 Created Permissions:
   • create members
   • view members
   # ... complete list

🎭 Creating FOH roles...
   🆕 Created role: 'Admin' (ID: 12)

🔑 Admin Role Permissions (ALL):
   • create members
   • view members
   # ... complete list

👤 Assigning Admin role to organization admins...
👥 Found Admin Users:
   • Laith Zraikat (ID: 1, Email: laith@example.com)
     Current roles: none
   ✅ Assigned Admin role to: Laith Zraikat
```

## Default Permissions Structure

### Permissions (23 total)
The system creates the following permissions grouped by category:

**Member Management:**
- `create members`
- `view members` 
- `view member profile`
- `edit members`
- `delete members`

**Membership Management:**
- `create memberships`
- `view memberships`
- `edit memberships`
- `modify membership dates`
- `modify membership limits`
- `upcharge memberships`
- `cancel memberships`
- `transfer memberships`
- `hold memberships`
- `upgrade memberships`

**Check-in Management:**
- `check in members`
- `view check ins`

**Settings & Admin:**
- `manage settings`
- `view reports`
- `access dashboard`
- `select gym`
- `manage roles`
- `manage org terms`

### Default Roles

**Admin Role:**
- Gets ALL permissions automatically
- Cannot have permissions modified (protected)
- Users cannot remove themselves from this role

**Sales Role:**
- 17 permissions (all member/membership operations + check-ins)
- No admin/settings permissions
- Permissions can be customized

**Reception Role:**
- 7 basic permissions (view members, check-ins, basic operations)
- Permissions can be customized

## Integration with Seeder

The original `FohPermissionSeeder` now delegates to the `foh:create-permissions` command to avoid code duplication:

```bash
# Both of these do the same thing
php artisan db:seed --class=FohPermissionSeeder
php artisan foh:create-permissions --force
```

## Common Workflows

### Fresh Installation
```bash
php artisan foh:create-permissions
```

### Development Reset
```bash
php artisan foh:reset-permissions --force --seed
```

### Adding New Permissions
1. Add the permission to the `$permissions` array in `CreateFohPermissions.php`
2. Add it to the appropriate role definitions
3. Run: `php artisan foh:create-permissions --force`

### Troubleshooting
```bash
# Check current state without making changes
php artisan foh:reset-permissions
# (Cancel when prompted to see current state)

# Clear permission cache manually
php artisan cache:clear
```

## Technical Notes

- Commands use database transactions for safety
- Spatie Permission cache is automatically cleared
- Commands are organization-aware (multi-tenant safe)
- All output is colored and formatted for readability
- Error handling includes rollback on failure

## Related Files

- **Command Files:**
  - `app/Console/Commands/CreateFohPermissions.php`
  - `app/Console/Commands/ResetFohPermissions.php`
- **Seeder:** `database/seeders/FohPermissionSeeder.php`
- **Models:** `app/Models/FohRole.php`, `app/Models/FohPermission.php`

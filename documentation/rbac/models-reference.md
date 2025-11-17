# RBAC Models Reference

## Model Relationships Overview

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ RbacCategory│    │   RbacTask  │    │  RbacRole   │
│             │    │             │    │             │
│ - id        │    │ - id        │    │ - id        │
│ - uuid      │◄───┤ - uuid      │    │ - uuid      │
│ - name      │    │ - name      │    │ - name      │
│ - slug      │    │ - slug      │    │ - slug      │
│ - isActive  │    │ - module    │    │ - org_id    │
└─────────────┘    │ - isActive  │    │ - module    │
                   └─────────────┘    │ - isActive  │
                           │          └─────────────┘
                           │                  │
                   ┌───────▼──────────────────▼───────┐
                   │         RbacRoleTask            │
                   │ - rbacRole_id                   │
                   │ - rbacTask_id                   │
                   │ - isActive                      │
                   └─────────────────────────────────┘
                                      │
                   ┌──────────────────▼───────────────┐
                   │         RbacRoleUser            │
                   │ - rbacRole_id                   │
                   │ - orgUser_id                    │
                   │ - org_id                        │
                   │ - isDeleted                     │
                   └─────────────────────────────────┘
                                      │
                   ┌──────────────────▼───────────────┐
                   │           OrgUser               │
                   │ - rbacTasks() relationship      │
                   └─────────────────────────────────┘
```

## Model Details

### RbacCategory
**Purpose**: Groups related tasks together for better organization and management.

**Fields**:
- `id` (Primary Key)
- `uuid` (Unique identifier for external references)
- `name` (Display name)
- `slug` (URL-friendly identifier)
- `description` (Optional description)
- `isActive` (Boolean flag for enabling/disabling)

**Relationships**:
- `tasks()` - Has many RbacTask

**Example Categories**:
- Member Management
- Membership Operations
- Reporting
- System Administration

### RbacTask
**Purpose**: Defines specific permissions/capabilities in the system.

**Fields**:
- `id` (Primary Key)
- `uuid` (Unique identifier)
- `name` (Display name)
- `slug` (Unique identifier for permission checks)
- `description` (Detailed description)
- `module` (Scope: 'admin', 'foh', or null for global)
- `rbacCategory_id` (Foreign key to category)
- `isActive` (Boolean flag)

**Relationships**:
- `category()` - Belongs to RbacCategory
- `roles()` - Belongs to many RbacRole through RbacRoleTask

**Example Tasks**:
- `member_create` (Create new members)
- `member_update` (Edit member information)
- `membership_cancel` (Cancel memberships)
- `reports_view` (View reports)

### RbacRole
**Purpose**: Organization-specific roles that group multiple tasks.

**Fields**:
- `id` (Primary Key)
- `uuid` (Unique identifier)
- `name` (Display name)
- `slug` (Unique identifier within organization)
- `description` (Role description)
- `org_id` (Organization scope)
- `module` (Module scope: 'admin', 'foh', or null)
- `isActive` (Boolean flag)

**Relationships**:
- `org()` - Belongs to Org
- `tasks()` - Belongs to many RbacTask through RbacRoleTask
- `users()` - Belongs to many OrgUser through RbacRoleUser

**Example Roles**:
- Front Desk Staff
- Manager
- Owner
- Trainer

### RbacRoleTask (Pivot Model)
**Purpose**: Manages the many-to-many relationship between roles and tasks.

**Fields**:
- `id` (Primary Key)
- `uuid` (Unique identifier)
- `rbacRole_id` (Foreign key to role)
- `rbacTask_id` (Foreign key to task)
- `isActive` (Boolean flag for enabling/disabling specific task assignments)

**Key Features**:
- Allows granular control over task assignments
- Tasks can be temporarily disabled for specific roles
- Maintains audit trail of task assignments

### RbacRoleUser (Pivot Model)
**Purpose**: Manages the many-to-many relationship between users and roles with organization context.

**Fields**:
- `id` (Primary Key)
- `uuid` (Unique identifier)
- `rbacRole_id` (Foreign key to role)
- `orgUser_id` (Foreign key to user)
- `org_id` (Organization context for multi-tenancy)
- `isDeleted` (Soft deletion flag)

**Key Features**:
- Ensures organization isolation
- Supports soft deletion for audit trails
- Allows role restoration
- Multi-tenant aware

## OrgUser Integration

### rbacTasks() Relationship
The `OrgUser` model includes a sophisticated relationship that returns all unique tasks a user has across their active roles:

```php
public function rbacTasks()
{
    return $this->hasManyThrough(
        RbacTask::class,
        RbacRoleUser::class,
        'orgUser_id',     // Foreign key on rbacRoleUser table
        'id',             // Foreign key on rbacTask table
        'id',             // Local key on orgUser table
        'rbacTask_id'     // Local key on rbacRoleUser table (through rbacRoleTask)
    )
    ->join('rbacRoleTask', function ($join) {
        $join->on('rbacTask.id', '=', 'rbacRoleTask.rbacTask_id')
             ->where('rbacRoleTask.isActive', true);
    })
    ->join('rbacRole', function ($join) {
        $join->on('rbacRoleUser.rbacRole_id', '=', 'rbacRole.id')
             ->where('rbacRole.isActive', true);
    })
    ->where('rbacRoleUser.isDeleted', false)
    ->where('rbacRoleUser.org_id', $this->org_id)
    ->distinct();
}
```

**Filtering Logic**:
1. Only active roles (`rbacRole.isActive = true`)
2. Only active task assignments (`rbacRoleTask.isActive = true`)
3. Only non-deleted role assignments (`rbacRoleUser.isDeleted = false`)
4. Organization scoping (`rbacRoleUser.org_id = user's org_id`)
5. Distinct results to avoid duplicates

## Usage Examples

### Basic Permission Check
```php
$rbacService = new RbacService();
$canCreateMembers = $rbacService->hasTask($orgUser, 'member_create');
```

### Get User's Tasks
```php
// All tasks
$allTasks = $orgUser->rbacTasks;

// FOH module tasks only
$fohTasks = $orgUser->rbacTasks()->where('module', 'foh')->get();

// Tasks in specific category
$memberTasks = $orgUser->rbacTasks()
    ->whereHas('category', function($query) {
        $query->where('slug', 'member-management');
    })->get();
```

### Check Multiple Tasks
```php
$requiredTasks = ['member_create', 'member_update', 'member_delete'];
$userTaskSlugs = $orgUser->rbacTasks->pluck('slug')->toArray();
$hasAllTasks = empty(array_diff($requiredTasks, $userTaskSlugs));
```

## Multi-Tenancy Considerations

1. **Organization Isolation**: All role assignments are scoped to specific organizations
2. **Cross-Organization Prevention**: Users cannot access tasks from other organizations
3. **Module Scoping**: Tasks and roles can be limited to specific application modules
4. **Tenant-Aware Queries**: All relationships include organization filtering

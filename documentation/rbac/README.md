# RBAC System Documentation

## Overview
This directory contains documentation for the custom Role-Based Access Control (RBAC) system being implemented to replace the Spatie Laravel Permission package in the FOH application.

## Documentation Files

### [Implementation Status](implementation-status.md)
Current progress and status of the RBAC system implementation, including:
- Completed components
- Database schema status
- Key features implemented
- Usage examples
- Next implementation phases

### [Models Reference](models-reference.md)
Comprehensive reference for all RBAC models, including:
- Model relationships diagram
- Detailed field descriptions
- Relationship explanations
- Usage examples
- Multi-tenancy considerations

## Quick Start

### Current Capabilities
The RBAC system currently supports:

1. **Permission Checking**:
   ```php
   $rbacService = new RbacService();
   $hasPermission = $rbacService->hasTask($orgUser, 'member_create');
   ```

2. **Getting User Tasks**:
   ```php
   $userTasks = $orgUser->rbacTasks;
   $fohTasks = $orgUser->rbacTasks()->where('module', 'foh')->get();
   ```

### Key Concepts

- **Tasks**: Individual permissions (e.g., 'member_create', 'reports_view')
- **Roles**: Collections of tasks assigned to users (e.g., 'Manager', 'Front Desk')
- **Categories**: Logical groupings of related tasks
- **Modules**: Application areas ('admin', 'foh', or null for global)
- **Organizations**: Multi-tenant isolation boundary

### Architecture Highlights

- **Multi-Tenant**: All permissions are organization-scoped
- **Module-Aware**: Permissions can be limited to specific application modules
- **UUID Support**: External references use UUIDs for security
- **Soft Deletion**: Role assignments support soft deletion for audit trails
- **Active/Inactive States**: Granular control without permanent deletion

## Implementation Status

### ✅ Phase 1: Foundation (Completed)
- RBAC models created
- Database relationships established
- Basic permission checking service
- OrgUser integration

### 🚧 Phase 2: Service Layer (Planned)
- Caching implementation
- Bulk operations
- Role management methods
- Permission inheritance

### 📋 Phase 3: Laravel Integration (Planned)
- Gate integration
- Blade directives
- Middleware
- Exception handling

### 📋 Phase 4: Migration Tools (Planned)
- Spatie migration commands
- Data seeding
- Backward compatibility

### 📋 Phase 5: Admin Interface (Planned)
- Role management UI
- Permission assignment
- User management
- Auditing tools

## Contributing

When working on the RBAC system:

1. **Follow Multi-Tenancy**: Always include organization scoping
2. **Respect Module Boundaries**: Consider module-specific permissions
3. **Maintain Backward Compatibility**: Support existing permission checks during transition
4. **Document Changes**: Update this documentation when adding features
5. **Test Thoroughly**: Ensure organization isolation works correctly

## Related Documentation

- [Roles and Permissions (Legacy)](../roles-and-permissions/) - Current Spatie implementation
- [Authentication](../authentication/) - User authentication system
- [Multi-Tenancy Analysis](../authentication/multi-tenancy-analysis.md) - Organization isolation patterns

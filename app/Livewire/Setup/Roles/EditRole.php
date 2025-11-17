<?php

namespace App\Livewire\Setup\Roles;

use Livewire\Component;
use App\Models\RbacRole;
use App\Models\RbacTask;
use App\Models\RbacCategory;
use App\Models\RbacRoleTask;
use App\Models\User;
use App\Models\OrgUser;
use Flux;
use Livewire\Attributes\On;

/**
 * Edit Role Component
 *
 * Dedicated page for editing a specific role and managing its permissions
 */
class EditRole extends Component
{
    public RbacRole $role;
    public $permissions;
    public $rolePermissions = [];

    // Form properties
    public $roleName = '';

    // OrgUser assignment properties
    public $availableUsers = [];
    public $selectedOrgUserId = null;
    public $assignedOrgUsers = [];
    public $searchResults = [];
    public $search = '';

    protected $rules = [
        'roleName' => 'required|string|max:255',
        'selectedOrgUserId' => 'required',
    ];

    protected $messages = [
        'roleName.required' => 'Role name is required.',
        'roleName.max' => 'Role name cannot exceed 255 characters.',
        'selectedOrgUserId.required' => 'Please select a user to assign to this role.',
    ];

    public function mount($id)
    {
        // Permission gate: only users with Admin role can access
        $rbacService = app(\App\Services\RbacService::class);
        $orgUser = auth()->user()->orgUser;
        if (!$orgUser || !$rbacService->hasRole($orgUser, 'Admin')) {
            session()->flash('error', __('gym.Access Denied'));
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->role = RbacRole::with(['activeTasks', 'activeUsers'])->findOrFail($id);
        $this->roleName = $this->role->name;

        $this->loadData();
    }

    public function loadData()
    {
        $this->permissions = $this->getGroupedPermissions();
        $this->rolePermissions = $this->role->activeTasks->pluck('id')->toArray();
        $this->loadUsers();
    }

    /**
     * Load assigned orgUsers only (for initial page load)
     * Available orgUsers will be loaded dynamically based on search
     */
    public function loadUsers()
    {
        // Get orgUsers assigned to this role via RBAC system
        $this->assignedOrgUsers = $this->role->activeUsers()
            ->where('orgUser.isActive', 1)  // Active users - specify table name
            ->whereNull('orgUser.deleted_at') // Not soft-deleted - specify table name
            ->with('user')  // Load the related User model
            ->get();

        // Initialize empty collections for dynamic loading
        $this->availableUsers = collect();
        $this->searchResults = collect();
    }

    /**
     * Handle real-time search when user types in search input
     * Following the same pattern as CheckInModal
     */
    public function updatedSearch()
    {
        if ($this->search) {
            try {
                // Get assigned orgUser IDs to exclude them from search
                $assignedOrgUserIds = $this->assignedOrgUsers->pluck('id')->toArray();

                // Tenant scope handles org filtering automatically
                $this->searchResults = OrgUser::where(function($q) {
                        $q->where('fullName', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phoneNumber', 'like', "%{$this->search}%");
                    })
                    ->where('isActive', 1)  // Active users
                    ->where('isFohUser', 1) // FOH users only
                    ->whereNull('deleted_at') // Not soft-deleted
                    ->whereNotIn('id', $assignedOrgUserIds)  // Exclude already assigned
                    ->with('user')  // Load the related User model
                    ->limit(4)  // Limit results for performance
                    ->get();

                // Results loaded successfully
            } catch (\Exception $e) {
                // Swallow errors silently but keep UX responsive
                $this->searchResults = collect();
            }
        } else {
            $this->searchResults = collect();
        }
    }

    /**
     * Handle user selection (listens to global Livewire event from teleported modal)
     */
    #[On('select-user')]
    public function selectUser($orgUserId)
    {
        $this->selectedOrgUserId = $orgUserId;
    }

    public function updatedSelectedOrgUserId()
    {
        // no-op
    }

    /**
     * Get permissions grouped by categories from database
     */
    private function getGroupedPermissions()
    {
        // Get RBAC categories with their tasks for foh module
        $categories = RbacCategory::where('module', 'foh')
                                  ->with(['tasks' => function ($query) {
                                      $query->where('module', 'foh')->orderBy('name');
                                  }])
                                  ->orderBy('name')
                                  ->get();

        $groups = [];

        foreach ($categories as $category) {
            if ($category->tasks->count() > 0) {
                $groups[$category->name] = $category->tasks;
            }
        }

        // Add uncategorized tasks if any exist
        $uncategorized = RbacTask::where('module', 'foh')
                                ->whereNull('rbacCategory_id')
                                ->orderBy('name')
                                ->get();
        if ($uncategorized->count() > 0) {
            $groups['Uncategorized'] = $uncategorized;
        }

        return $groups;
    }

    public function togglePermission($taskId)
    {
        // Prevent modification of Admin role tasks
        // Admin role should always have all tasks and cannot be modified
        if ($this->role->name === 'Admin') {
            session()->flash('error', __('Admin role tasks cannot be modified. Admin role should always have all permissions.'));
            return;
        }

        $task = RbacTask::find($taskId);

        if (in_array($taskId, $this->rolePermissions)) {
            // Remove task from role
            RbacRoleTask::where('rbacRole_id', $this->role->id)
                        ->where('rbacTask_id', $taskId)
                        ->where('module', $this->role->module)  // Use the role's module
                        ->update(['isActive' => false]);
            $this->rolePermissions = array_diff($this->rolePermissions, [$taskId]);
            session()->flash('success', 'Task removed successfully!');
        } else {
            // Add task to role
            RbacRoleTask::updateOrCreate(
                [
                    'rbacRole_id' => $this->role->id,
                    'rbacTask_id' => $taskId,
                    'module' => $this->role->module  // Use the role's module
                ],
                [
                    'uuid' => \Str::uuid(),
                    'isActive' => true
                ]
            );
            $this->rolePermissions[] = $taskId;
            session()->flash('success', 'Task added successfully!');
        }
    }

    /**
     * Prepare and reset state when opening the Add User modal
     */
    public function prepareAddUser()
    {
        $this->resetAddUserState();
    }

    /**
     * Clear modal-related fields so every open starts fresh
     */
    private function resetAddUserState(): void
    {
        $this->selectedOrgUserId = null;
        $this->search = '';
        $this->searchResults = collect();
        $this->resetErrorBag();
    }


    /**
     * Assign selected orgUser to the role
     */
    public function assignUser()
    {
        // assign user to role

        // Simple validation first
        if (!$this->selectedOrgUserId) {
            $this->addError('selectedOrgUserId', __('gym.Please select a user to assign to this role'));
            return;
        }

        try {
            // Validate that the selected orgUser exists as a FOH user in the current organization
            // Tenant scope handles org filtering automatically
            $orgUser = OrgUser::where('id', $this->selectedOrgUserId)
                ->where('isActive', 1)  // Active user
                ->where('isFohUser', 1) // FOH user only
                ->whereNull('deleted_at') // Not soft-deleted
                ->with('user')
                ->first();

            if (!$orgUser) {
                $this->addError('selectedOrgUserId', __('gym.The selected user is not a valid FOH user in this organization'));
                return;
            }

            // Check if orgUser is already assigned to this role
            $rbacService = app(\App\Services\RbacService::class);
            if ($rbacService->hasRole($orgUser, $this->role->name)) {
                $this->addError('selectedOrgUserId', __('gym.This user is already assigned to this role'));
                return;
            }

            // Enforce one-role policy: prevent assigning if user already has any role
            $userRoles = $rbacService->getUserRoles($orgUser);
            if (!empty($userRoles)) {
                // Show a toast error that includes the existing role name and block assignment
                $existingRoleName = $userRoles[0] ?? 'Unknown';
                session()->flash('error', __('roles.user_already_assigned_with_role', ['role' => $existingRoleName]));
                $this->addError('selectedOrgUserId', __('roles.user_already_assigned_with_role', ['role' => $existingRoleName]));
                return;
            }

            // Use RBAC service to assign role
            $rbacService->assignRole($orgUser, $this->role->name);

            $this->loadUsers(); // Refresh user lists
            $this->resetAddUserState(); // Clear modal state

            // Ensure transaction is committed before UI refresh
            usleep(50000);

            // Refresh parent component
            $this->dispatch('refresh-user-list');

            // Close the modal using Flux's PHP method (same as membership modals)
            Flux::modal('add-user-modal')->close();

            session()->flash('success', __('gym.User assigned to role successfully'));
        } catch (\Exception $e) {
            $this->addError('selectedOrgUserId', __('gym.Failed to assign user') . ': ' . $e->getMessage());
        }
    }

    /**
     * Remove orgUser from the role
     */
    public function removeUser($orgUserId)
    {
        try {
            $orgUser = OrgUser::findOrFail($orgUserId);

            // Prevent Admin role users from removing themselves from the Admin role
            // This prevents admins from locking themselves out
            if ($this->role->name === 'Admin' &&
                auth()->user()->orgUser &&
                auth()->user()->orgUser->id == $orgUserId) {
                session()->flash('error', __('roles.admin_cannot_remove_self_from_admin_role'));
                return;
            }

            // Use RBAC service to remove role
            $rbacService = app(\App\Services\RbacService::class);
            $rbacService->removeRole($orgUser, $this->role->name);

            $this->loadUsers(); // Refresh user lists

            session()->flash('success', 'User removed from role successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove user: ' . $e->getMessage());
        }
    }

    /**
     * Real-time validation for role name
     */
    public function updatedRoleName()
    {
        $this->validateOnly('roleName', [
            'roleName' => 'required|string|max:255|unique:foh_roles,name,' . $this->role->id,
        ], [
            'roleName.required' => __('roles.role_name_required'),
            'roleName.max' => __('roles.role_name_max_length'),
            'roleName.unique' => __('roles.role_name_unique'),
        ]);
    }


    public function updateRole()
    {
        try {
            // Validate with dynamic rules for unique check
            $this->validate([
                'roleName' => 'required|string|max:255|unique:foh_roles,name,' . $this->role->id,
            ], [
                'roleName.required' => __('roles.role_name_required'),
                'roleName.max' => __('roles.role_name_max_length'),
                'roleName.unique' => __('roles.role_name_unique'),
            ]);

            // Update the role name
            $this->role->update([
                'name' => $this->roleName,
            ]);

            // Show success message and stay on the same page
            $this->dispatch('refresh-user-list');
            session()->flash('success', __('roles.role_updated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-throw validation exceptions to show errors in UI
        } catch (\Exception $e) {
            session()->flash('error', __('roles.failed_to_update_role') . ': ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.setup.roles.edit-role')
            ->layout('components.layouts.app');

    }
}


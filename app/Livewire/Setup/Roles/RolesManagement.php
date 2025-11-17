<?php

namespace App\Livewire\Setup\Roles;

use Livewire\Component;
use App\Models\RbacRole;
use App\Models\RbacTask;
use App\Models\RbacCategory;
use App\Models\RbacRoleTask;

/**
 * RBAC Roles Management Component
 *
 * Manages FOH RBAC roles and tasks in the setup section
 * Only manages roles and tasks for the 'foh' module
 */
class RolesManagement extends Component
{
    public $roles;
    public $tasks;
    public $categories;
    public $selectedRole = null;
    public $roleTasks = [];

    // Form properties for creating roles
    public $showCreateModal = false;
    public $roleName = '';
    public $roleDescription = '';

    protected $rules = [
        'roleName' => 'required|string|max:255',
        'roleDescription' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'roleName.required' => 'Role name is required.',
        'roleName.max' => 'Role name cannot exceed 255 characters.',
        'roleDescription.max' => 'Description cannot exceed 500 characters.',
    ];

    public function mount()
    {
        // Permission gate: only users with Admin role can access
        $rbacService = app(\App\Services\RbacService::class);
        $orgUser = auth()->user()->orgUser;
        if (!$orgUser || !$rbacService->hasRole($orgUser, 'Admin')) {
            session()->flash('error', __('gym.Access Denied'));
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->loadData();
    }

    public function loadData()
    {
        $orgId = auth()->user()->orgUser->org_id;

        // Load RBAC roles for this organization (foh module only)
        $this->roles = RbacRole::where('org_id', $orgId)
                              ->where('module', 'foh')
                              ->where('isActive', true)
                              ->with(['activeTasks', 'activeUsers'])
                              ->orderBy('name')
                              ->get();

        // Load RBAC tasks for foh module, grouped by categories
        $this->categories = RbacCategory::where('module', 'foh')
                                       ->whereHas('tasks', function ($query) {
                                           $query->where('module', 'foh');
                                       })
                                       ->with(['tasks' => function ($query) {
                                           $query->where('module', 'foh')->orderBy('name');
                                       }])
                                       ->orderBy('name')
                                       ->get();

        // Load all foh tasks for easier access
        $this->tasks = RbacTask::where('module', 'foh')->orderBy('name')->get();

        if ($this->selectedRole) {
            $this->loadRoleTasks();
        }
    }

    public function selectRole($roleId)
    {
        $this->selectedRole = RbacRole::find($roleId);
        $this->loadRoleTasks();
    }

    public function loadRoleTasks()
    {
        if ($this->selectedRole) {
            $this->roleTasks = $this->selectedRole->activeTasks->pluck('id')->toArray();
        }
    }

    public function toggleTask($taskId)
    {
        if (!$this->selectedRole) {
            return;
        }

        // Prevent modification of Admin role tasks
        if ($this->selectedRole->name === 'Admin') {
            session()->flash('error', __('Admin role tasks cannot be modified. Admin role should always have all permissions.'));
            return;
        }

        $task = RbacTask::find($taskId);

        if (in_array($taskId, $this->roleTasks)) {
            // Remove task from role
            RbacRoleTask::where('rbacRole_id', $this->selectedRole->id)
                        ->where('rbacTask_id', $taskId)
                        ->update(['isActive' => false]);
            $this->roleTasks = array_diff($this->roleTasks, [$taskId]);
        } else {
            // Add task to role
            RbacRoleTask::updateOrCreate(
                [
                    'rbacRole_id' => $this->selectedRole->id,
                    'rbacTask_id' => $taskId
                ],
                [
                    'uuid' => \Str::uuid(),
                    'isActive' => true
                ]
            );
            $this->roleTasks[] = $taskId;
        }

        session()->flash('success', 'Task assignment updated successfully!');
    }

    public function openCreateModal()
    {
        $this->reset(['roleName', 'roleDescription']);
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset(['roleName', 'roleDescription']);
        $this->resetErrorBag();
    }

    public function createRole()
    {
        try {
            $this->validate();

            $orgId = auth()->user()->orgUser->org_id;

            // Check if role name already exists for this organization
            $existingRole = RbacRole::where('name', $this->roleName)
                                   ->where('org_id', $orgId)
                                   ->where('module', 'foh')
                                   ->first();

            if ($existingRole) {
                $this->addError('roleName', 'This role name already exists in your organization.');
                return;
            }

            $role = RbacRole::create([
                'uuid' => \Str::uuid(),
                'name' => $this->roleName,
                'slug' => \Str::slug($this->roleName),
                'org_id' => $orgId,
                'module' => 'foh',
                'isActive' => true
            ]);

            $this->loadData();
            $this->closeCreateModal();
            session()->flash('success', 'Role created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-throw validation exceptions to show errors in UI
        } catch (\Exception $e) {
            \Log::error('Failed to create RBAC role', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.setup.roles.roles-management')
            ->layout('components.layouts.app');
    }
}

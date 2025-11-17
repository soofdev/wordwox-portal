<?php

namespace App\Livewire\Setup\Roles;

use Livewire\Component;
use App\Models\RbacRole;
use App\Models\RbacTask;
use App\Models\RbacCategory;
use App\Models\RbacRoleTask;

/**
 * Create RBAC Role Component
 *
 * Dedicated page for creating a new RBAC role and setting its tasks
 * Only manages roles and tasks for the 'foh' module
 */
class CreateRole extends Component
{
    public $categories;
    public $selectedTasks = [];

    // Form properties
    public $roleName = '';

    protected $rules = [
        'roleName' => 'required|string|max:255',
        'selectedTasks' => 'array',
    ];

    protected $messages = [
        'roleName.required' => 'Role name is required.',
        'roleName.max' => 'Role name cannot exceed 255 characters.',
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

        $this->loadCategories();
    }

    public function loadCategories()
    {
        // Load RBAC categories with their tasks for the foh module
        $this->categories = RbacCategory::where('module', 'foh')
                                       ->whereHas('tasks', function ($query) {
                                           $query->where('module', 'foh');
                                       })
                                       ->with(['tasks' => function ($query) {
                                           $query->where('module', 'foh')->orderBy('name');
                                       }])
                                       ->orderBy('name')
                                       ->get();
    }

    public function toggleTask($taskId)
    {
        if (in_array($taskId, $this->selectedTasks)) {
            $this->selectedTasks = array_diff($this->selectedTasks, [$taskId]);
        } else {
            $this->selectedTasks[] = $taskId;
        }
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

            // Create the RBAC role
            $role = RbacRole::create([
                'uuid' => \Str::uuid(),
                'name' => $this->roleName,
                'slug' => \Str::slug($this->roleName),
                'org_id' => $orgId,
                'module' => 'foh',
                'isActive' => true
            ]);

            // Assign selected tasks to the role
            if (!empty($this->selectedTasks)) {
                foreach ($this->selectedTasks as $taskId) {
                    RbacRoleTask::create([
                        'uuid' => \Str::uuid(),
                        'rbacRole_id' => $role->id,
                        'rbacTask_id' => $taskId,
                        'isActive' => true
                    ]);
                }
            }

            session()->flash('success', 'Role created successfully with ' . count($this->selectedTasks) . ' tasks assigned!');

            // Redirect back to roles list
            return $this->redirect(route('setup.roles'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-throw validation exceptions to show errors in UI
        } catch (\Exception $e) {
            \Log::error('Failed to create RBAC role', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.setup.roles.create-role')
            ->layout('components.layouts.app');
    }
}

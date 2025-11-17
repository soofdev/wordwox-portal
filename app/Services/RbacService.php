<?php

namespace App\Services;

use App\Models\OrgUser;
use App\Models\RbacRole;
use App\Models\RbacRoleUser;
use App\Models\RbacTask;

class RbacService
{
    /**
     * Check if the given OrgUser has the specified task
     *
     * @param OrgUser $orgUser
     * @param string $taskSlug
     * @return bool
     */
    public function hasTask(OrgUser $orgUser, string $taskSlug): bool
    {
        return $orgUser->rbacTasks()->where('slug', $taskSlug)->exists();
    }

    /**
     * Check if the given OrgUser has any of the specified tasks
     *
     * @param OrgUser $orgUser
     * @param array $taskSlugs
     * @return bool
     */
    public function hasAnyTask(OrgUser $orgUser, array $taskSlugs): bool
    {
        return $orgUser->rbacTasks()->whereIn('slug', $taskSlugs)->exists();
    }

    /**
     * Check if the given OrgUser has all of the specified tasks
     *
     * @param OrgUser $orgUser
     * @param array $taskSlugs
     * @return bool
     */
    public function hasAllTasks(OrgUser $orgUser, array $taskSlugs): bool
    {
        $userTaskSlugs = $orgUser->rbacTasks()->pluck('slug')->toArray();
        return empty(array_diff($taskSlugs, $userTaskSlugs));
    }

    /**
     * Check if the given OrgUser has the specified role
     *
     * @param OrgUser $orgUser
     * @param string $roleName
     * @return bool
     */
    public function hasRole(OrgUser $orgUser, string $roleName): bool
    {
        return RbacRoleUser::where('orgUser_id', $orgUser->id)
                          ->where('org_id', $orgUser->org_id)
                          ->where('module', 'foh')  // Direct module filter
                          ->where('isDeleted', false)
                          ->whereHas('role', function ($query) use ($roleName) {
                              $query->where('name', $roleName)
                                    ->where('module', 'foh')  // Filter by FOH module
                                    ->where('isActive', true);
                          })
                          ->exists();
    }

    /**
     * Assign a role to the given OrgUser
     *
     * @param OrgUser $orgUser
     * @param string $roleName
     * @return bool
     */
    public function assignRole(OrgUser $orgUser, string $roleName): bool
    {
        try {
            // Find the role for this organization
            $role = RbacRole::where('name', $roleName)
                           ->where('org_id', $orgUser->org_id)
                           ->where('module', 'foh')
                           ->where('isActive', true)
                           ->first();

            if (!$role) {
                \Log::warning('RBAC role not found for assignment', [
                    'role_name' => $roleName,
                    'org_id' => $orgUser->org_id,
                    'user_id' => $orgUser->id
                ]);
                return false;
            }

            // Check if already assigned
            $existingAssignment = RbacRoleUser::where('rbacRole_id', $role->id)
                                            ->where('orgUser_id', $orgUser->id)
                                            ->where('org_id', $orgUser->org_id)
                                            ->where('module', 'foh')  // Direct module filter
                                            ->first();

            if ($existingAssignment) {
                if ($existingAssignment->isDeleted) {
                    // Reactivate the assignment
                    $existingAssignment->update(['isDeleted' => false]);
                    return true;
                }
                // Already assigned and active
                return true;
            }

            // Create new assignment
            RbacRoleUser::create([
                'uuid' => \Str::uuid(),
                'rbacRole_id' => $role->id,
                'orgUser_id' => $orgUser->id,
                'org_id' => $orgUser->org_id,
                'module' => $role->module,  // Use the role's module instead of hardcoding
                'isDeleted' => false
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Failed to assign RBAC role', [
                'role_name' => $roleName,
                'user_id' => $orgUser->id,
                'org_id' => $orgUser->org_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove a role from the given OrgUser
     *
     * @param OrgUser $orgUser
     * @param string $roleName
     * @return bool
     */
    public function removeRole(OrgUser $orgUser, string $roleName): bool
    {
        try {
            // Find the role assignment
            $assignment = RbacRoleUser::where('orgUser_id', $orgUser->id)
                                    ->where('org_id', $orgUser->org_id)
                                    ->where('module', 'foh')  // Direct module filter
                                    ->where('isDeleted', false)
                                    ->whereHas('role', function ($query) use ($roleName) {
                                        $query->where('name', $roleName)
                                              ->where('module', 'foh')  // Filter by FOH module
                                              ->where('isActive', true);
                                    })
                                    ->first();

            if ($assignment) {
                $assignment->update(['isDeleted' => true]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Failed to remove RBAC role', [
                'role_name' => $roleName,
                'user_id' => $orgUser->id,
                'org_id' => $orgUser->org_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all role names for the given OrgUser
     *
     * @param OrgUser $orgUser
     * @return array
     */
    public function getUserRoles(OrgUser $orgUser): array
    {
        return RbacRole::whereHas('roleUsers', function ($query) use ($orgUser) {
                          $query->where('orgUser_id', $orgUser->id)
                                ->where('org_id', $orgUser->org_id)
                                ->where('module', 'foh')  // Direct module filter
                                ->where('isDeleted', false);
                      })
                      ->where('module', 'foh')  // Filter by FOH module
                      ->where('isActive', true)
                      ->pluck('name')
                      ->toArray();
    }

    /**
     * Get all available roles for an organization
     *
     * @param int $orgId
     * @return array
     */
    public function getAvailableRoles(int $orgId): array
    {
        return RbacRole::where('org_id', $orgId)
                      ->where('module', 'foh')
                      ->where('isActive', true)
                      ->pluck('name', 'id')
                      ->toArray();
    }

    /**
     * Check if user can access FOH system (has any FOH role)
     *
     * @param OrgUser $orgUser
     * @return bool
     */
    public function canAccessFoh(OrgUser $orgUser): bool
    {
        return RbacRoleUser::where('orgUser_id', $orgUser->id)
                          ->where('org_id', $orgUser->org_id)
                          ->where('isDeleted', false)
                          ->whereHas('role', function ($query) {
                              $query->where('module', 'foh')
                                    ->where('isActive', true);
                          })
                          ->exists();
    }
}

<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OrgTerms as OrgTermsModel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;


/**
 * Organization Terms Management Component
 * 
 * Manages organization-specific terms of service templates with:
 * - CRUD operations for terms
 * - Version control and activation management
 * - Rich text editing capabilities
 * - Template variable support
 */
#[Layout('components.layouts.app')]
#[Title('Organization Terms')]
class OrgTerms extends Component
{
    use WithPagination;
    // Terms collection and form properties
    public $terms = [];
    public $title = '';
    public $content = '';
    public $version = '1.0';
    public $effective_date = '';
    public $is_active = true;
    
    // UI state management
    public $editingId = null;
    public $showForm = false;
    public $showDeleteConfirm = false;
    public $deleteId = null;
    
    // Search and filtering
    public $search = '';
    public $filterActive = 'all'; // all, active, inactive
    public $showDeleted = false; // Show soft deleted terms
    public $showDeletedModal = false; // Show deleted terms in modal
    public $showForceDeleteConfirm = false; // Show permanent delete confirmation
    public $forceDeleteId = null; // ID of term to permanently delete

    /**
     * Component initialization
     */
    public function mount($id = null)
    {
        // Permission gate: manage org terms
        if (!optional(auth()->user()->orgUser)?->safeHasPermissionTo('manage org terms')) {
            session()->flash('error', __('gym.Access Denied'));
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->loadTerms();
        $this->effective_date = now()->format('Y-m-d');
        
        // Handle route parameters
        $currentRoute = request()->route()->getName();
        
        if ($currentRoute === 'settings.org-terms.create') {
            $this->showForm = true;
            $this->editingId = null;
            $this->content = $this->getDefaultTermsContent();
            // Emit event to update editor with default content
            $this->dispatch('contentUpdated', $this->content);
        } elseif ($currentRoute === 'settings.org-terms.edit' && $id) {
            $this->showForm = true;
            $this->edit($id);
        }
    }



    /**
     * Render the component view
     */
    public function render()
    {
        $filteredTerms = $this->getFilteredTermsPaginated();
        
        return view('livewire.settings.org-terms', [
            'filteredTerms' => $filteredTerms,
            'hasTerms' => $this->terms->count() > 0,
        ]);
    }

    /**
     * Load terms for the current organization
     */
    public function loadTerms()
    {
        $orgId = auth()->user()->orgUser->org_id ?? null;

        if ($orgId) {
            $query = OrgTermsModel::where('org_id', $orgId);
            
            // Include soft deleted terms if requested
            if ($this->showDeleted) {
                $query->withTrashed();
            }
            
            $this->terms = $query
                ->orderBy('effective_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $this->terms = collect();
        }
    }

    /**
     * Get filtered terms based on search and filter criteria
     */
    /**
     * Get paginated and filtered terms
     */
    public function getFilteredTermsPaginated()
    {
        $orgId = auth()->user()->orgUser->org_id ?? null;
        
        if (!$orgId) {
            return collect();
        }

        $query = OrgTermsModel::where('org_id', $orgId);
        
        // Include soft deleted terms if requested
        if ($this->showDeleted) {
            $query->withTrashed();
        }

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('version', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        // Apply active filter
        if ($this->filterActive === 'active') {
            $query->where('is_active', true);
        } elseif ($this->filterActive === 'inactive') {
            $query->where('is_active', false);
        }

        // Order by created date (newest first)
        $query->orderBy('created_at', 'desc');

        return $query->paginate(10);
    }

    /**
     * Get filtered terms (for non-paginated use)
     */
    public function getFilteredTerms()
    {
        $terms = $this->terms;

        // Apply search filter
        if (!empty($this->search)) {
            $terms = $terms->filter(function ($term) {
                return str_contains(strtolower($term->title), strtolower($this->search)) ||
                       str_contains(strtolower($term->version), strtolower($this->search));
            });
        }

        // Apply active filter
        if ($this->filterActive === 'active') {
            $terms = $terms->where('is_active', true);
        } elseif ($this->filterActive === 'inactive') {
            $terms = $terms->where('is_active', false);
        }

        return $terms;
    }

    /**
     * Create a new terms record
     */
    public function create()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'version' => 'required|string|max:10',
            'effective_date' => 'required|date|after_or_equal:today',
            'is_active' => 'boolean',
        ], [
            'content.min' => 'Terms content must be at least 50 characters long.',
            'effective_date.after_or_equal' => 'Effective date must be today or in the future.',
        ]);

        $orgId = auth()->user()->orgUser->org_id ?? null;

        if (!$orgId) {
            session()->flash('error', 'Organization not found.');
            return;
        }

        // If setting as active, deactivate all other terms
        if ($this->is_active) {
            OrgTermsModel::where('org_id', $orgId)->update(['is_active' => false]);
        }

        OrgTermsModel::create([
            'title' => $this->title,
            'content' => $this->content,
            'version' => $this->version,
            'effective_date' => $this->effective_date,
            'is_active' => $this->is_active,
            'org_id' => $orgId,
        ]);

        $this->resetForm();
        $this->loadTerms();

        session()->flash('success', 'Terms created successfully.');
        return $this->redirect(route('settings.org-terms'), navigate: true);
    }

    /**
     * Edit an existing terms record
     */
    public function edit($id)
    {
        $term = OrgTermsModel::findOrFail($id);

        // Verify the term belongs to the current organization
        $orgId = auth()->user()->orgUser->org_id ?? null;
        if ($term->org_id !== $orgId) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        // If we're already on the edit route, just populate the form
        $currentRoute = request()->route()->getName();
        if ($currentRoute === 'settings.org-terms.edit') {
            $this->editingId = $term->id;
            $this->title = $term->title;
            $this->content = $term->content;
            $this->version = $term->version;
            $this->effective_date = $term->effective_date->format('Y-m-d');
            $this->is_active = $term->is_active;

            // Emit event to update CKEditor content
            $this->dispatch('contentUpdated', $term->content);

            $this->showForm = true;
        } else {
            // Navigate to the edit route
            return $this->redirect(route('settings.org-terms.edit', $id), navigate: true);
        }
    }

    /**
     * Update an existing terms record
     */
    public function update()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'version' => 'required|string|max:10',
            'effective_date' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'content.min' => 'Terms content must be at least 50 characters long.',
        ]);

        $orgId = auth()->user()->orgUser->org_id ?? null;
        $term = OrgTermsModel::findOrFail($this->editingId);

        // Verify ownership
        if ($term->org_id !== $orgId) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        // If setting as active, deactivate all other terms
        if ($this->is_active) {
            OrgTermsModel::where('org_id', $orgId)
                ->where('id', '!=', $this->editingId)
                ->update(['is_active' => false]);
        }

        $term->update([
            'title' => $this->title,
            'content' => $this->content,
            'version' => $this->version,
            'effective_date' => $this->effective_date,
            'is_active' => $this->is_active,
        ]);

        $this->resetForm();
        $this->loadTerms();
        $this->showForm = false;

        session()->flash('success', 'Terms updated successfully.');
    }

    /**
     * Show delete confirmation dialog
     */
    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    /**
     * Cancel delete operation
     */
    public function cancelDelete()
    {
        $this->deleteId = null;
        $this->showDeleteConfirm = false;
    }

    /**
     * Delete a terms record
     */
    public function delete()
    {
        if (!$this->deleteId) {
            session()->flash('error', 'An error occurred while deleting terms. Please try again.');
            $this->cancelDelete();
            return;
        }

        try {
            $term = OrgTermsModel::findOrFail($this->deleteId);
            
            // Verify ownership
            $orgId = auth()->user()->orgUser->org_id ?? null;
            if (!$orgId || $term->org_id !== $orgId) {
                $this->cancelDelete();
                session()->flash('error', 'Unauthorized access.');
                return;
            }

            $termTitle = $term->title; // Store title before deletion
            $wasActive = $term->is_active; // Store if it was active
            
            // If this term is active, set it to inactive when soft deleting
            // This prevents having an active deleted term
            if ($term->is_active) {
                $term->is_active = false;
                $term->save();
            }
            
            // Perform the deletion
            $deleted = $term->delete();
            
            if ($deleted) {
                // Reset state first
                $this->cancelDelete();
                $this->loadTerms();
                
                // If we're in form mode, return to table view
                if ($this->showForm) {
                    $this->showForm = false;
                    $this->resetForm();
                }
                
                // Then show success message
                $message = "Terms '{$termTitle}' moved to deleted items successfully. You can restore it from the deleted items view.";
                if ($wasActive) {
                    $message .= " Note: The term was deactivated since active terms cannot be deleted.";
                }
                session()->flash('success', $message);
                
                // Redirect to table view
                return $this->redirect(route('settings.org-terms'), navigate: true);
            } else {
                $this->cancelDelete();
                session()->flash('error', 'Failed to delete terms. Please try again.');
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->cancelDelete();
            session()->flash('error', 'Terms not found.');
        } catch (\Exception $e) {
            $this->cancelDelete();
            session()->flash('error', 'An error occurred while deleting terms. Please try again.');
            \Log::error('Error deleting terms: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status of a terms record
     */
    public function toggleActive($id)
    {
        try {
            // Get fresh term data from database
            $term = OrgTermsModel::findOrFail($id);
            $orgId = auth()->user()->orgUser->org_id ?? null;

            // Verify ownership
            if ($term->org_id !== $orgId) {
                session()->flash('error', 'Unauthorized access.');
                return;
            }

            // Get current status from database
            $currentStatus = $term->is_active;

            if (!$currentStatus) {
                // Badge shows "Inactive" → Switch OFF → Change to Active
                // First deactivate all other terms (their switches will turn OFF, badges become "Inactive")
                OrgTermsModel::where('org_id', $orgId)
                    ->where('id', '!=', $id)
                    ->update(['is_active' => false]);
                
                // Then activate this term (switch turns ON, badge becomes "Active")
                $term->update(['is_active' => true]);
                session()->flash('success', 'Terms activated: Switch is ON ✓ Badge shows "Active" ✓');
            } else {
                // Badge shows "Active" → Switch ON → Change to Inactive
                // Deactivate this term (switch turns OFF, badge becomes "Inactive")
                $term->update(['is_active' => false]);
                session()->flash('success', 'Terms deactivated: Switch is OFF ✓ Badge shows "Inactive" ✓');
            }

            // Force reload of terms data to ensure UI sync
            $this->loadTerms();
            
            // Reset pagination to ensure fresh data
            $this->resetPage();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while updating the term status.');
            \Log::error('Error toggling term active status: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate an existing terms record
     */
    public function duplicate($id)
    {
        $term = OrgTermsModel::findOrFail($id);
        $orgId = auth()->user()->orgUser->org_id ?? null;

        // Verify ownership
        if ($term->org_id !== $orgId) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        // Create new version number
        $newVersion = $this->generateNextVersion($term->version);

        OrgTermsModel::create([
            'title' => $term->title . ' (Copy)',
            'content' => $term->content,
            'version' => $newVersion,
            'effective_date' => now()->format('Y-m-d'),
            'is_active' => false, // Duplicates are inactive by default
            'org_id' => $orgId,
        ]);

        $this->loadTerms();
        session()->flash('success', 'Terms duplicated successfully.');
    }

    /**
     * Generate next version number
     */
    private function generateNextVersion($currentVersion)
    {
        // Simple version increment logic
        if (preg_match('/^(\d+)\.(\d+)$/', $currentVersion, $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            return $major . '.' . ($minor + 1);
        }
        
        return '1.0';
    }

    /**
     * Reset form fields
     */
    public function resetForm()
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = $this->getDefaultTermsContent();
        $this->version = '1.0';
        $this->effective_date = now()->format('Y-m-d');
        $this->is_active = true;
        
        // Emit event to update editor with default content
        $this->dispatch('contentUpdated', $this->content);
    }

    /**
     * Get default terms content from template file (only if no terms exist)
     */
    private function getDefaultTermsContent(): string
    {
        $orgId = auth()->user()->orgUser->org_id ?? null;
        
        // Check if organization already has terms
        if ($orgId) {
            $existingTermsCount = OrgTermsModel::where('org_id', $orgId)->count();
            
            // If terms already exist, return empty content
            if ($existingTermsCount > 0) {
                return '';
            }
        }
        
        // Only load template if no terms exist
        $templatePath = storage_path('app/templates/default-gym-terms.txt');
        
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
        
        // Fallback content if template file doesn't exist and no terms exist
        return "# Terms of Service\n\n**Organization:** {org_name}\n**Version:** {version}\n**Effective Date:** {effective_date}\n\n## 1. Membership Agreement\n\nBy signing this agreement, you agree to become a member of {org_name} and abide by all rules and regulations.\n\n## 2. Liability Waiver\n\nMembers exercise at their own risk. {org_name} is not responsible for injuries sustained on the premises.\n\n---\n\n*This agreement is effective as of {effective_date}.*";
    }

    /**
     * Toggle form visibility
     */
    public function toggleForm()
    {
        $this->resetForm();
        return $this->redirect(route('settings.org-terms.create'), navigate: true);
    }

    /**
     * Cancel form editing
     */
    public function cancelForm()
    {
        $this->resetForm();
        return $this->redirect(route('settings.org-terms'), navigate: true);
    }

    /**
     * Clear search
     */
    public function clearSearch()
    {
        $this->search = '';
    }

    /**
     * Updated search property
     */
    public function updatedSearch()
    {
        // Reset pagination when search changes
        $this->resetPage();
    }

    /**
     * Updated filter property
     */
    public function updatedFilterActive()
    {
        // Reset pagination when filter changes
        $this->resetPage();
    }

    /**
     * Toggle showing deleted terms
     */
    public function toggleShowDeleted()
    {
        $this->showDeleted = !$this->showDeleted;
        $this->loadTerms();
    }

    /**
     * Show deleted terms in modal
     */
    public function showDeletedTermsModal()
    {
        $this->showDeletedModal = true;
    }

    /**
     * Close deleted terms modal
     */
    public function closeDeletedModal()
    {
        $this->showDeletedModal = false;
        $this->cancelForceDelete(); // Also cancel any pending force delete
    }

    /**
     * Show force delete confirmation
     */
    public function confirmForceDelete($id)
    {
        $this->forceDeleteId = $id;
        $this->showForceDeleteConfirm = true;
    }

    /**
     * Cancel force delete operation
     */
    public function cancelForceDelete()
    {
        $this->forceDeleteId = null;
        $this->showForceDeleteConfirm = false;
    }

    /**
     * Get only deleted terms
     */
    public function getDeletedTerms()
    {
        $orgId = auth()->user()->orgUser->org_id ?? null;

        if ($orgId) {
            return OrgTermsModel::where('org_id', $orgId)
                ->onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->get();
        }
        
        return collect();
    }

    /**
     * Restore a soft deleted terms record
     */
    public function restore($id)
    {
        try {
            $term = OrgTermsModel::withTrashed()->findOrFail($id);
            
            // Verify ownership
            $orgId = auth()->user()->orgUser->org_id ?? null;
            if (!$orgId || $term->org_id !== $orgId) {
                session()->flash('error', 'Unauthorized access.');
                return;
            }

            if ($term->trashed()) {
                // Restore the term (keeps the inactive status set during deletion)
                // User can manually activate it later if needed
                $term->restore();
                $this->loadTerms();
                $this->closeDeletedModal(); // Close the modal after restore
                session()->flash('success', "Terms '{$term->title}' restored successfully. Note: The term is currently inactive and can be activated if needed.");
            } else {
                session()->flash('error', 'Terms is not deleted.');
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', 'Terms not found.');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while restoring terms. Please try again.');
            \Log::error('Error restoring terms: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a terms record
     */
    public function forceDelete()
    {
        if (!$this->forceDeleteId) {
            session()->flash('error', 'An error occurred while permanently deleting terms. Please try again.');
            $this->cancelForceDelete();
            return;
        }

        try {
            $term = OrgTermsModel::withTrashed()->findOrFail($this->forceDeleteId);
            
            // Verify ownership
            $orgId = auth()->user()->orgUser->org_id ?? null;
            if (!$orgId || $term->org_id !== $orgId) {
                session()->flash('error', 'Unauthorized access.');
                return;
            }

            $termTitle = $term->title;
            $term->forceDelete(); // Permanently delete
            
            $this->loadTerms();
            $this->cancelForceDelete(); // Reset confirmation state
            session()->flash('success', "Terms '{$termTitle}' permanently deleted.");
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', 'Terms not found.');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while permanently deleting terms. Please try again.');
            \Log::error('Error force deleting terms: ' . $e->getMessage());
        }
    }

    /**
     * Go to previous page
     */
    public function goToPreviousPage()
    {
        $this->previousPage();
    }

    /**
     * Go to next page
     */
    public function goToNextPage()
    {
        $this->nextPage();
    }

    /**
     * Go to specific page
     */
    public function gotoPage($page)
    {
        $this->setPage($page);
    }
}

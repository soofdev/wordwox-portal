<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Template Manager Component
 * 
 * Allows bulk template management for CMS pages
 * - Filter pages by status, type, template
 * - Select multiple pages
 * - Apply template to selected pages
 * - Apply template to all filtered pages
 */
class TemplateManager extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $templateFilter = 'all';
    public $sortBy = 'updated_at';
    public $sortDirection = 'desc';
    public $selectedPages = [];
    public $selectAll = false;
    public $selectedTemplate = 'modern';
    public $orgId;
    public $portalId;

    public function mount()
    {
        $user = auth()->user();
        $this->orgId = $user && $user->orgUser ? $user->orgUser->org_id : 8;
        $this->portalId = 1;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedPages = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->selectedPages = [];
        $this->selectAll = false;
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
        $this->selectedPages = [];
        $this->selectAll = false;
    }

    public function updatedTemplateFilter()
    {
        $this->resetPage();
        $this->selectedPages = [];
        $this->selectAll = false;
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
        $this->selectedPages = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPages = $this->getFilteredPageIds();
        } else {
            $this->selectedPages = [];
        }
    }

    public function updatedSelectedPages()
    {
        $filteredIds = $this->getFilteredPageIds();
        $this->selectAll = count($this->selectedPages) === count($filteredIds) && count($filteredIds) > 0;
    }

    /**
     * Get IDs of all pages matching current filters
     */
    private function getFilteredPageIds()
    {
        $query = $this->buildQuery();
        return $query->pluck('id')->toArray();
    }

    /**
     * Build query based on current filters
     */
    private function buildQuery()
    {
        $query = CmsPage::where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->templateFilter !== 'all') {
            $query->where('template', $this->templateFilter);
        }

        return $query;
    }

    /**
     * Apply template to selected pages
     */
    public function applyTemplateToSelected()
    {
        if (empty($this->selectedPages)) {
            session()->flash('error', 'Please select at least one page.');
            return;
        }

        $count = CmsPage::where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId)
            ->whereIn('id', $this->selectedPages)
            ->update(['template' => $this->selectedTemplate]);

        $this->selectedPages = [];
        $this->selectAll = false;
        
        session()->flash('message', "Template '{$this->getTemplateName($this->selectedTemplate)}' applied to {$count} page(s).");
    }

    /**
     * Apply template to all filtered pages
     */
    public function applyTemplateToAll()
    {
        $query = $this->buildQuery();
        $count = $query->update(['template' => $this->selectedTemplate]);

        $this->selectedPages = [];
        $this->selectAll = false;
        
        session()->flash('message', "Template '{$this->getTemplateName($this->selectedTemplate)}' applied to {$count} page(s).");
    }

    /**
     * Get available templates
     */
    public function getTemplatesProperty()
    {
        return [
            'modern' => ['name' => 'Modern', 'icon' => '🚀', 'description' => 'Futuristic Glass Design'],
            'classic' => ['name' => 'Classic', 'icon' => '🏛️', 'description' => 'Elegant Traditional Design'],
            'meditative' => ['name' => 'Meditative', 'icon' => '🧘‍♀️', 'description' => 'Zen Wellness Design'],
        ];
    }

    /**
     * Get template name
     */
    public function getTemplateName($template)
    {
        $templates = $this->getTemplatesProperty();
        return $templates[$template]['name'] ?? ucfirst($template);
    }

    public function render()
    {
        $query = $this->buildQuery();
        $pages = $query->orderBy($this->sortBy, $this->sortDirection)->paginate(10);

        // Update selectAll based on current selection
        $filteredIds = $this->getFilteredPageIds();
        $this->selectAll = count($this->selectedPages) === count($filteredIds) && count($filteredIds) > 0;

        return view('livewire.template-manager', compact('pages'));
    }
}


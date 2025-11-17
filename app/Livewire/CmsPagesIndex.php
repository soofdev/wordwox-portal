<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;
use Livewire\WithPagination;

class CmsPagesIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $sortBy = 'updated_at';
    public $sortDirection = 'desc';
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
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
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
    }

    public function deletePage($pageId)
    {
        $page = CmsPage::where('id', $pageId)
            ->where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId)
            ->first();

        if ($page) {
            $page->delete();
            session()->flash('message', 'Page deleted successfully.');
        }
    }

    public function duplicatePage($pageId)
    {
        $page = CmsPage::where('id', $pageId)
            ->where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId)
            ->with('sections')
            ->first();

        if ($page) {
            $newPage = $page->replicate();
            $newPage->title = $page->title . ' (Copy)';
            $newPage->slug = $page->slug . '-copy-' . time();
            $newPage->status = 'draft';
            $newPage->is_homepage = false;
            $newPage->save();

            // Duplicate sections
            foreach ($page->sections as $section) {
                $newSection = $section->replicate();
                $newSection->cms_page_id = $newPage->id;
                $newSection->save();
            }

            session()->flash('message', 'Page duplicated successfully.');
        }
    }

    public function render()
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

        $pages = $query->orderBy($this->sortBy, $this->sortDirection)->paginate(10);

        return view('livewire.cms-pages-index', compact('pages'));
    }
}

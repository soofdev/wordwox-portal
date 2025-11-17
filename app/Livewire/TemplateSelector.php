<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;

class TemplateSelector extends Component
{
    public $pageId;
    public $currentTemplate = 'default';
    public $showSelector = false;

    public function mount($pageId = null, $currentTemplate = 'default')
    {
        $this->pageId = $pageId;
        $this->currentTemplate = $currentTemplate;
    }

    public function toggleSelector()
    {
        $this->showSelector = !$this->showSelector;
    }

    public function selectTemplate($template)
    {
        $this->currentTemplate = $template;
        
        if ($this->pageId) {
            $page = CmsPage::find($this->pageId);
            if ($page) {
                $page->update(['template' => $template]);
                $this->dispatch('template-changed', template: $template);
                session()->flash('message', 'Template changed to: ' . $this->getTemplateName($template));
            }
        }
        
        $this->showSelector = false;
    }

    public function getTemplateName($template)
    {
        $templates = [
            'modern' => '🚀 Modern Template',
            'classic' => '🏛️ Classic Template',
            'meditative' => '🧘‍♀️ Meditative Template',
        ];

        return $templates[$template] ?? 'Unknown Template';
    }

    public function getTemplateIcon($template)
    {
        $icons = [
            'modern' => '🚀',
            'classic' => '🏛️',
            'meditative' => '🧘‍♀️',
        ];

        return $icons[$template] ?? '🚀';
    }

    public function render()
    {
        $templates = [
            'modern' => ['name' => 'Modern', 'description' => 'Futuristic Glass'],
            'classic' => ['name' => 'Classic', 'description' => 'Elegant Traditional'],
            'meditative' => ['name' => 'Meditative', 'description' => 'Zen Wellness'],
        ];

        return view('livewire.template-selector', compact('templates'));
    }
}

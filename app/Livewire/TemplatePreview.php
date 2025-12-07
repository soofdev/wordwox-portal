<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Models\TemplateThemeColor;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

class TemplatePreview extends Component
{
    public $selectedTemplate = 'default';
    public $previewPage = null;
    public $availableTemplates = [];
    public $successMessage = '';
    public $errorMessage = '';
    
    // Theme color customization (for fitness template)
    public $showColorCustomization = false;
    public $themeColors = [];

    public function mount()
    {
        // Get a sample page for preview (preferably homepage)
        $this->previewPage = CmsPage::where('org_id', env('CMS_DEFAULT_ORG_ID', 8))
            ->where('status', 'published')
            ->where('is_homepage', true)
            ->with(['sections' => function($query) {
                $query->where('is_active', true)
                      ->where('is_visible', true)
                      ->orderBy('sort_order', 'asc');
            }])
            ->first();

        // If no homepage, get any published page
        if (!$this->previewPage) {
            $this->previewPage = CmsPage::where('org_id', env('CMS_DEFAULT_ORG_ID', 8))
                ->where('status', 'published')
                ->with(['sections' => function($query) {
                    $query->where('is_active', true)
                          ->where('is_visible', true)
                          ->orderBy('sort_order', 'asc');
                }])
                ->first();
        }

        $this->loadAvailableTemplates();
        
        // Set selected template - use page template if it exists in available templates, otherwise use first available
        if ($this->previewPage && $this->previewPage->template && isset($this->availableTemplates[$this->previewPage->template])) {
            $this->selectedTemplate = $this->previewPage->template;
        } elseif (!empty($this->availableTemplates)) {
            $this->selectedTemplate = array_key_first($this->availableTemplates);
        } else {
            $this->selectedTemplate = '';
        }
        
        // Load theme colors if fitness template is selected
        $this->loadThemeColors();
    }
    
    public function loadThemeColors()
    {
        if ($this->selectedTemplate === 'fitness') {
            $orgId = Auth::user()->orgUser->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
            $themeColor = TemplateThemeColor::getOrCreateForOrg($orgId, 'fitness');
            
            $this->themeColors = [
                'primary_color' => $themeColor->primary_color,
                'secondary_color' => $themeColor->secondary_color,
                'text_base' => $themeColor->text_base,
                'text_light' => $themeColor->text_light,
                'text_footer' => $themeColor->text_footer,
                'bg_white' => $themeColor->bg_white,
                'bg_packages' => $themeColor->bg_packages,
                'bg_coaches' => $themeColor->bg_coaches,
                'bg_footer' => $themeColor->bg_footer,
                'bg_navbar' => $themeColor->bg_navbar,
                'text_navbar' => $themeColor->text_navbar,
            ];
        } else {
            $this->themeColors = [];
        }
    }

    public function loadAvailableTemplates()
    {
        $this->availableTemplates = [
            'fitness' => [
                'name' => 'Fitness Template',
                'description' => 'Modern Gym & Fitness Design',
                'icon' => '💪',
                'preview_color' => 'bg-gradient-to-r from-red-100 to-orange-100',
                'features' => ['Gym layout', 'Bootstrap 5', 'Responsive design', 'Multiple page types']
            ],
        ];
    }

    public function selectTemplate($template)
    {
        $this->selectedTemplate = $template;
        $this->loadThemeColors();
        $this->showColorCustomization = ($template === 'fitness');
    }
    
    public function toggleColorCustomization()
    {
        $this->showColorCustomization = !$this->showColorCustomization;
    }
    
    public function saveThemeColors()
    {
        try {
            if ($this->selectedTemplate !== 'fitness') {
                $this->errorMessage = 'Theme colors can only be customized for the Fitness template.';
                return;
            }
            
            $orgId = Auth::user()->orgUser->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
            $themeColor = TemplateThemeColor::getOrCreateForOrg($orgId, 'fitness');
            
            $themeColor->update([
                'primary_color' => $this->themeColors['primary_color'] ?? '#4285F4',
                'secondary_color' => $this->themeColors['secondary_color'] ?? '#e03e2d',
                'text_base' => $this->themeColors['text_base'] ?? '#212529',
                'text_light' => $this->themeColors['text_light'] ?? '#ffffff',
                'text_footer' => $this->themeColors['text_footer'] ?? '#6c757d',
                'bg_white' => $this->themeColors['bg_white'] ?? '#ffffff',
                'bg_packages' => $this->themeColors['bg_packages'] ?? '#f5f5f5',
                'bg_coaches' => $this->themeColors['bg_coaches'] ?? '#f5f5f5',
                'bg_footer' => $this->themeColors['bg_footer'] ?? '#f5f5f5',
                'bg_navbar' => $this->themeColors['bg_navbar'] ?? '#212529',
                'text_navbar' => $this->themeColors['text_navbar'] ?? '#ffffff',
            ]);
            
            $this->successMessage = 'Theme colors saved successfully!';
            Flux::toast(
                variant: 'success',
                heading: 'Colors Saved',
                text: 'Your theme colors have been updated successfully.'
            );
            
        } catch (\Exception $e) {
            Log::error('Error saving theme colors: ' . $e->getMessage());
            $this->errorMessage = 'Error saving theme colors: ' . $e->getMessage();
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: $this->errorMessage
            );
        }
    }
    
    public function resetThemeColors()
    {
        $defaults = TemplateThemeColor::getDefaults();
        $this->themeColors = $defaults;
    }

    public function applyTemplate()
    {
        try {
            if (!$this->selectedTemplate || !isset($this->availableTemplates[$this->selectedTemplate])) {
                $this->errorMessage = 'Please select a valid template first.';
                return;
            }

            $orgId = env('CMS_DEFAULT_ORG_ID', 8);
            $portalId = env('CMS_DEFAULT_PORTAL_ID', 1);

            // Log the attempt
            Log::info('Attempting to apply template to all organization pages', [
                'org_id' => $orgId,
                'portal_id' => $portalId,
                'selected_template' => $this->selectedTemplate,
            ]);

            // Update template for all pages in the organization
            $updatedCount = CmsPage::where('org_id', $orgId)
                ->update(['template' => $this->selectedTemplate]);
            
            if ($updatedCount > 0) {
                Log::info('Template applied successfully to all pages', [
                    'pages_updated' => $updatedCount,
                    'template' => $this->selectedTemplate
                ]);
                
                $this->successMessage = 'Template "' . $this->availableTemplates[$this->selectedTemplate]['name'] . 
                    '" applied successfully to all ' . $updatedCount . ' page(s) in your organization!';
                
                // Show success toast notification
                Flux::toast(
                    variant: 'success',
                    heading: 'Template Applied',
                    text: $this->successMessage
                );
            } else {
                Log::warning('No pages found to update template for');
                $this->errorMessage = 'No pages found to apply template to. Please create some pages first.';
                Flux::toast(
                    variant: 'danger',
                    heading: 'No Pages Found',
                    text: $this->errorMessage
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Error applying template to all pages: ' . $e->getMessage());
            $this->errorMessage = 'Error applying template: ' . $e->getMessage();
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: $this->errorMessage
            );
        }
    }

    public function previewTemplate($template)
    {
        // Open preview in new tab
        $url = $this->previewPage ? 
            ('/' . ($this->previewPage->slug === 'home' ? '' : $this->previewPage->slug) . '?preview_template=' . $template) :
            '/?preview_template=' . $template;
            
        // Dispatch event with URL as first parameter for Livewire 3 compatibility
        $this->dispatch('open-preview', $url);
    }

    public function render()
    {
        return view('livewire.template-preview');
    }
}

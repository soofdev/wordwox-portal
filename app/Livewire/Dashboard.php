<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
    public $orgName;
    public $orgId;
    public $portalId;
    public $currentTemplate;
    public $currentTemplateName;

    public function mount()
    {
        $user = Auth::user();
        
        Log::info('🏠 DASHBOARD COMPONENT MOUNTED', [
            'user_id' => $user->id,
            'user_name' => $user->fullName,
            'email' => $user->email,
            'current_orgUser_id' => $user->orgUser_id,
            'timestamp' => now()->toDateTimeString()
        ]);

        if ($user->orgUser) {
            Log::info('🏢 Dashboard - Current Organization Details', [
                'orgUser_id' => $user->orgUser->id,
                'org_id' => $user->orgUser->org_id,
                'org_name' => $user->orgUser->org->name ?? 'Unknown',
                'isFohUser' => $user->orgUser->isFohUser,
                'has_foh_access' => (bool)$user->orgUser->isFohUser
            ]);
        }

        Log::info('✅ DASHBOARD SUCCESSFULLY LOADED - User has reached the dashboard');
        
        // Get organization and portal info
        $this->orgId = $user->orgUser->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
        $this->portalId = env('CMS_DEFAULT_PORTAL_ID', 1);
        $this->orgName = $user->orgUser->org->name ?? 'Unknown Organization';

        // Get current template from any published page
        $this->currentTemplate = CmsPage::where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId)
            ->where('status', 'published')
            ->whereNotNull('template')
            ->value('template') ?? env('CMS_DEFAULT_THEME', 'modern');

        $templateNames = [
            'modern' => '🚀 Modern',
            'classic' => '🏛️ Classic', 
            'meditative' => '🧘‍♀️ Meditative',
            'fitness' => '💪 Fitness'
        ];
        $this->currentTemplateName = $templateNames[$this->currentTemplate] ?? '🚀 Modern';
    }

    public function render()
    {
        Log::info('🎨 Dashboard render() called');
        return view('livewire.dashboard');
    }
}

<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
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
    }

    public function render()
    {
        Log::info('🎨 Dashboard render() called');
        return view('livewire.dashboard');
    }
}

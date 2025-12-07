<?php

namespace App\Livewire\Customer;

use App\Models\OrgPlan;
use App\Models\OrgUserPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PaymentSuccess extends Component
{
    public $plan = null;
    public $ref = null;
    public $navigationPages;
    public $error = null;

    public function mount($ref = null)
    {
        $this->ref = $ref ?? request()->query('ref');
        
        // Check for flash error message from callback
        $this->error = session('error');
        
        // Load navigation pages
        $this->loadNavigationPages();
        
        // Log all request data for debugging
        Log::info('PaymentSuccess: Mounted', [
            'ref' => $this->ref,
            'error' => $this->error,
            'query_params' => request()->query(),
            'session_error' => session('error'),
            'all_session_keys' => array_keys(session()->all()),
        ]);
        
        // Parse reference to get plan UUID
        if ($this->ref) {
            $refParts = explode('_', $this->ref);
            if (count($refParts) >= 2 && $refParts[0] === 'plan') {
                $planUuid = $refParts[1];
                
                // Load plan details
                try {
                    $this->plan = OrgPlan::where('uuid', $planUuid)->first();
                    
                    if ($this->plan) {
                        Log::info('PaymentSuccess: Plan loaded', [
                            'plan_uuid' => $planUuid,
                            'plan_id' => $this->plan->id,
                            'plan_name' => $this->plan->name,
                        ]);
                    } else {
                        Log::warning('PaymentSuccess: Plan not found', [
                            'plan_uuid' => $planUuid,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('PaymentSuccess: Error loading plan', [
                        'plan_uuid' => $planUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // If no plan and no error, try to find recently created membership for logged-in user
        if (!$this->plan && !$this->error && Auth::check() && Auth::user()->orgUser) {
            $this->tryFindRecentMembership();
        }
    }
    
    /**
     * Try to find recently created membership for the logged-in user
     */
    protected function tryFindRecentMembership()
    {
        try {
            $orgUser = Auth::user()->orgUser;
            
            // Look for membership created in the last 5 minutes
            $recentMembership = OrgUserPlan::where('orgUser_id', $orgUser->id)
                ->where('created_at', '>=', now()->subMinutes(5)->timestamp)
                ->where('status', OrgUserPlan::STATUS_ACTIVE)
                ->where('isCanceled', false)
                ->where('isDeleted', false)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($recentMembership) {
                $this->plan = $recentMembership->orgPlan;
                $this->ref = 'plan_' . $this->plan->uuid . '_' . $orgUser->id;
                
                Log::info('PaymentSuccess: Found recent membership', [
                    'membership_id' => $recentMembership->id,
                    'plan_id' => $this->plan->id,
                    'plan_uuid' => $this->plan->uuid,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PaymentSuccess: Error finding recent membership', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load navigation pages for navbar
     */
    protected function loadNavigationPages()
    {
        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
        // Cache navigation pages for 1 hour to improve performance
        $this->navigationPages = \Illuminate\Support\Facades\Cache::remember(
            "navigation_pages_{$orgId}",
            3600,
            function () use ($orgId) {
                return \App\Models\CmsPage::where('org_id', $orgId)
                    ->where('status', 'published')
                    ->where('show_in_navigation', true)
                    ->orderBy('sort_order')
                    ->get();
            }
        );
    }

    public function render()
    {
        return view('livewire.customer.payment-success', [
            'navigationPages' => $this->navigationPages ?? collect(),
            'error' => $this->error,
        ])->layout('components.layouts.templates.fitness', [
            'navigationPages' => $this->navigationPages ?? collect(),
        ]);
    }
}


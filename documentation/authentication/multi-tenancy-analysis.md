# Multi-Tenancy Analysis - wodworx-core to FOH Port

## Overview

This document analyzes the multi-tenancy implementation in wodworx-core and provides a roadmap for porting it to the FOH interface. The core system uses organization-based tenancy with automatic data isolation through Laravel's global scopes.

## 🏗️ Multi-Tenancy Architecture in wodworx-core

### Core Concept
- **Organization-Based Tenancy**: Uses `org_id` as the tenant identifier
- **Shared Database**: Single database with logical separation per organization
- **Automatic Isolation**: Global scopes ensure tenant data isolation
- **User-Tenant Bridge**: Users can belong to multiple organizations via `orgUser` records

### Database Schema Structure

#### Core Tables
```sql
-- Organization table (tenant root)
org (id, name, timezone, settings...)

-- User authentication table  
user (id, email, password_hash, orgUser_id, org_id, ...)

-- Bridge table (user-organization relationship with roles)
orgUser (id, user_id, org_id, fullName, isAdmin, isStaff, isCustomer, ...)
```

#### User-Tenant Relationship
```php
// User model relationships
class User extends Authenticatable {
    // Current active organization user record
    public function orgUser()
    {
        return $this->belongsTo(BaseOrgUser::class, 'orgUser_id', 'id');
    }
    
    // All organizations this user has access to
    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'user_id', 'id');
    }
}
```

#### Tenant Column Pattern
Every tenant-aware table includes:
- `org_id` (integer, indexed) - Foreign key to `org.id`
- Foreign key constraint: `->foreign('org_id')->references('id')->on('org')->onDelete('cascade')`
- Index for performance: `$table->index(['org_id'])`

## 🔧 Implementation Components

### 1. TenantScope (Global Query Scope)
```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $table = $model->getTable();
            $builder->where($table . '.org_id', Auth::user()->orgUser->org_id);
        }
    }
}
```

### 2. Tenantable Trait
```php
<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Tenantable
{
    protected static function bootTenantable()
    {
        // Apply global scope to all queries
        static::addGlobalScope(new TenantScope);

        // Auto-set org_id when creating records
        static::creating(function (Model $model) {
            if (!$model->org_id && Auth::check()) {
                $model->org_id = Auth::user()->orgUser->org_id;
            }
        });
    }

    // Scope to bypass tenant filtering when needed
    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
```

### 3. BaseWWModel (Legacy Yii2 Compatible)
```php
abstract class BaseWWModel extends Model
{
    use SoftDeletes;
    protected $dateFormat = 'U'; // Unix timestamp for Yii2 compatibility
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            // Auto-set org_id and uuid
            if (empty($model->org_id) && auth()->check() && auth()->user()->orgUser) {
                $model->org_id = auth()->user()->orgUser->org_id;
            }
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }
    
    // Standard org relationship
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'id');
    }
}
```

### 4. Tenant-Specific Middleware

#### SetTenantTimezone Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $org = auth()->user()->orgUser->org;
            
            if ($org && $org->timezone) {
                config(['app.timezone' => $org->timezone]);
                date_default_timezone_set($org->timezone);
            }
        } catch (\Exception $e) {
            // Fallback to default timezone
            config(['app.timezone' => config('app.timezone', 'UTC')]);
            date_default_timezone_set(config('app.timezone', 'UTC'));
        }

        return $next($request);
    }
}
```

## 🔄 Tenant Context & Switching

### Current Tenant Access Pattern
```php
// Throughout the application
$orgId = auth()->user()->orgUser->org_id;
$currentOrg = auth()->user()->orgUser->org;

// Manual tenant filtering (when needed)
$records = SomeModel::where('org_id', auth()->user()->orgUser->org_id)->get();

// Bypassing tenant scope (admin functions)
$allRecords = SomeModel::allTenants()->get();
```

### Tenant Switching Mechanism
Based on the actual implementation in wodworx-core:

#### **Routes**:
```php
// Gym selection routes
Route::get('org-user/select', [OrgUserController::class, 'select'])
    ->name('org-user.select')
    ->middleware(['auth']);
Route::get('org-user/set/{id}', [OrgUserController::class, 'set'])
    ->name('org-user.set')
    ->middleware(['auth']);
```

#### **Controller Implementation**:
```php
// OrgUserController.php
public function select()
{
    $user = Auth::user();
    $orgUsers = OrgUser::where('orgUser.user_id', $user->id)
        ->where(function ($query) {
            $query->where('isStaff', true)
                ->orWhere('isAdmin', true)
                ->orWhere('isOwner', true);
        })
        ->withoutGlobalScopes()
        ->with(['org'])
        ->join('org', 'orgUser.org_id', '=', 'org.id')
        ->orderBy('org.name')
        ->select('orgUser.*')
        ->get();
    
    return view('org-user.select', compact('orgUsers'));
}

public function set($id)
{
    // Set the user.orgUser_id to the given id 
    $user = Auth::user();
    $user->orgUser_id = $id;
    $user->save();

    // Log this action in LogAction 
    $this->logService->orgUserSelect($user->id, $id, 'User switched to orgUser #' . $id);

    // redirect to dashboard 
    return redirect()->route('dashboard');
}
```

#### **UI Integration**:
- **Navigation Dropdown**: "Select Gym..." link in user dropdown menu
- **Selection Page**: Dedicated page with searchable list of organizations
- **Visual Elements**: Organization logos, names, and search functionality

## 🎯 FOH Implementation Requirements

### Essential Components to Port

#### 1. Core Multi-Tenancy Files
```
app/
├── Scopes/
│   └── TenantScope.php                 ✅ Copy exactly
├── Traits/
│   └── Tenantable.php                  ✅ Copy exactly  
├── Http/Middleware/
│   └── SetTenantTimezone.php           ✅ Copy exactly
└── Models/
    ├── BaseWWModel.php                 ✅ Copy with modifications
    └── OrgUser.php                     ✅ Create simplified version
```

#### 2. Database Requirements
- ✅ Existing `user` table has `orgUser_id` and `org_id` columns
- ✅ Existing `orgUser` table with org relationships
- ✅ Existing `org` table with organization data
- ✅ All tenant-aware tables have `org_id` columns

#### 3. User Model Updates
```php
// Update existing FOH User model
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user';
    protected $dateFormat = 'U';

    protected $fillable = [
        'fullName', 'email', 'password_hash', 'orgUser_id', 'org_id',
        'phoneCountry', 'phoneNumber', // ... other fields
    ];

    // Add tenant relationships
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id', 'id');
    }

    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'user_id', 'id');
    }
}
```

#### 4. OrgUser Model for FOH
```php
<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgUser extends BaseWWModel
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'orgUser';
    protected $dateFormat = 'U';

    protected $fillable = [
        'org_id', 'user_id', 'fullName', 'email', 'phoneNumber', 'phoneCountry',
        'isCustomer', 'isStaff', 'isAdmin', // ... other fields
    ];

    // Relationships
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

#### 5. Middleware Registration
```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
})
```

### Tenant Switching UI Components

#### FOH Controller Implementation
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrgUser;
use Illuminate\Support\Facades\Auth;

class OrgUserController extends Controller
{
    public function select()
    {
        $user = Auth::user();
        $orgUsers = OrgUser::where('orgUser.user_id', $user->id)
            ->where(function ($query) {
                $query->where('isStaff', true)
                    ->orWhere('isAdmin', true)
                    ->orWhere('isOwner', true);
            })
            ->withoutGlobalScopes()
            ->with(['org'])
            ->join('org', 'orgUser.org_id', '=', 'org.id')
            ->orderBy('org.name')
            ->select('orgUser.*')
            ->get();
        
        return view('org-user.select', compact('orgUsers'));
    }

    public function set($id)
    {
        $user = Auth::user();
        $user->orgUser_id = $id;
        $user->save();

        // Optional: Log the action
        // $this->logService->orgUserSelect($user->id, $id, 'User switched to orgUser #' . $id);

        return redirect()->route('dashboard');
    }
}
```

#### Organization Selector Livewire Component (Alternative)
```php
<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OrgUser;

class OrganizationSelector extends Component
{
    public $availableOrgs = [];
    public $currentOrg;

    public function mount()
    {
        $this->loadAvailableOrganizations();
        $this->currentOrg = Auth::user()->orgUser;
    }

    public function loadAvailableOrganizations()
    {
        $user = Auth::user();
        $this->availableOrgs = OrgUser::where('orgUser.user_id', $user->id)
            ->where(function ($query) {
                $query->where('isStaff', true)
                    ->orWhere('isAdmin', true)
                    ->orWhere('isOwner', true);
            })
            ->withoutGlobalScopes()
            ->with(['org'])
            ->join('org', 'orgUser.org_id', '=', 'org.id')
            ->orderBy('org.name')
            ->select('orgUser.*')
            ->get()
            ->map(function ($orgUser) {
                return [
                    'id' => $orgUser->id,
                    'org_id' => $orgUser->org_id,
                    'org_name' => $orgUser->org->name,
                    'org_logo' => $orgUser->org->logoFilePath,
                    'role' => $this->getUserRole($orgUser)
                ];
            });
    }

    public function switchOrganization($orgUserId)
    {
        $user = Auth::user();
        $user->orgUser_id = $orgUserId;
        $user->save();
        
        // Redirect to refresh tenant context
        return redirect()->route('dashboard');
    }

    private function getUserRole($orgUser)
    {
        if ($orgUser->isOwner || $orgUser->isAdmin) return 'Admin';
        if ($orgUser->isStaff) return 'Staff';
        return 'Customer';
    }

    public function render()
    {
        return view('livewire.components.organization-selector');
    }
}
```

#### Organization Selection Page Template
```blade
<!-- resources/views/org-user/select.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Select Organization') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Select Your Gym</h3>

                    <!-- Search Box -->
                    <div class="mb-4">
                        <input type="text" id="org-search" placeholder="Search organizations..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <ul class="space-y-4" id="org-list">
                        @foreach ($orgUsers as $orgUser)
                        <li class="org-item">
                            <a href="{{ route('org-user.set', ['id' => $orgUser->id]) }}" 
                               class="flex items-center gap-4 p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border border-transparent hover:border-gray-200 dark:hover:border-gray-600">
                                @if($orgUser->org && $orgUser->org->logoFilePath)
                                <img src="{{ Storage::disk('s3')->url($orgUser->org->logoFilePath) }}" 
                                     alt="{{ $orgUser->org->name }}" 
                                     class="w-12 h-12 object-contain rounded-lg">
                                @else
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                @endif
                                <span class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100 org-name">
                                    {{ ($orgUser->org ? $orgUser->org->name : 'N/A') }}
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <!-- No Results Message -->
                    <div id="no-results" class="hidden py-4 text-center text-gray-500 dark:text-gray-400">
                        No organizations found matching your search.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('org-search');
            const orgItems = document.querySelectorAll('.org-item');
            const noResults = document.getElementById('no-results');

            function filterOrganizations() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                orgItems.forEach(item => {
                    const orgName = item.querySelector('.org-name').textContent.toLowerCase().trim();

                    if (orgName.includes(searchTerm)) {
                        item.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                if (visibleCount === 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }

            searchInput.addEventListener('input', filterOrganizations);
        });
    </script>
</x-app-layout>
```

#### Navigation Integration Template
```blade
<!-- Add to navigation dropdown -->
<x-dropdown-link :href="route('org-user.select')">
    {{ __('Select Gym...') }}
</x-dropdown-link>
```

## 📋 Implementation Checklist for FOH

### Phase 1: Core Multi-Tenancy Setup
- [ ] Copy `TenantScope` class to `app/Scopes/TenantScope.php`
- [ ] Copy `Tenantable` trait to `app/Traits/Tenantable.php`  
- [ ] Copy `SetTenantTimezone` middleware to `app/Http/Middleware/SetTenantTimezone.php`
- [ ] Create `BaseWWModel` in `app/Models/BaseWWModel.php`
- [ ] Update existing `User` model with tenant relationships
- [ ] Create `OrgUser` model with tenant functionality
- [ ] Register middleware in `bootstrap/app.php`

### Phase 2: Organization Selection & Switching
- [ ] Create `OrgUserController` with `select()` and `set()` methods
- [ ] Create organization selection page view `org-user/select.blade.php`
- [ ] Add routes for `org-user/select` and `org-user/set/{id}`
- [ ] Add "Select Gym..." link to navigation dropdown
- [ ] Test tenant switching functionality
- [ ] Optional: Create Livewire component for inline switching

### Phase 3: Tenant-Aware Models
- [ ] Update any existing models to use `Tenantable` trait
- [ ] Add `org_id` to model `$fillable` arrays
- [ ] Add `org()` relationships to tenant-aware models
- [ ] Test automatic tenant scoping

### Phase 4: Member Creation Integration
- [ ] Ensure member creation respects tenant context
- [ ] Update OrgUser creation to use current tenant
- [ ] Test member creation across different organizations
- [ ] Verify Yii2 job dispatching works per tenant

## 🔗 Key Integration Points

### With wodworx-core
- Shares same database tables (`user`, `orgUser`, `org`)
- Uses identical tenant scoping logic
- Maintains data consistency across applications
- Supports same user authentication flow

### With FOH Functionality  
- Member creation automatically scoped to current organization
- Digital signatures linked to correct tenant
- Terms of use per organization
- Staff access limited to their organization's members

## 🚨 Important Considerations

### Security
- Always validate user has access to target organization before switching
- Ensure tenant isolation is maintained in all queries
- Test that users cannot access other organizations' data

### Performance
- Index `org_id` columns for fast filtering
- Cache current organization data to avoid repeated queries
- Consider eager loading organization relationships

### User Experience
- Clear indication of current organization context
- Smooth switching between organizations
- Maintain session state during organization switches

## 📝 Next Steps

1. **Implement Core Multi-Tenancy** (Phase 1)
2. **Add Organization Switching UI** (Phase 2) 
3. **Update Existing Models** (Phase 3)
4. **Test Member Creation Flow** (Phase 4)
5. **Verify Tenant Isolation** (Testing)

This multi-tenancy implementation will ensure the FOH interface properly isolates data per organization while allowing staff members who work for multiple gyms to switch contexts seamlessly.

---

*This analysis was created on 2025-08-10 to guide the implementation of multi-tenancy in the wodworx-foh project.*
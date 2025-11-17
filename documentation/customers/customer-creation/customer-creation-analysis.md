# Member Creation Analysis - wodworx-core to FOH

## Overview

This document analyzes the member creation functionality in the wodworx-core project's Filament admin panel to understand how to implement a simplified version for the Front of House (FOH) interface.

## 📋 User Creation Flow in wodworx-core

### 1. User Type Selection (`SelectUserType.php`)
Users first select what type of member to create:
- **Customer** (regular gym member)
- **Staff Member** (gym employee) 
- **Teacher/Coach** (instructor)

### 2. Main Creation Form (`CreateOrgUser.php`)
- Dynamic form based on selected user type
- Multiple sections with conditional fields
- Extensive validation and business rules

## 📝 Form Structure & Fields

### Personal Information Section
```php
// Required fields
'fullName' => 'required|unique per org'
'gender' => 'conditionally required based on org settings'
'dob' => 'conditionally required based on org settings'
'nationalID' => 'optional'
```

### Login Information Section
```php
// Login method selection
'addMemberInviteOption' => [
    'LoginByPhone' => 'default',
    'LoginByEmail' => 'alternative'
]

// Contact details
'phoneCountry' => 'country code dropdown'
'phoneNumber' => 'required for phone login, unique per org'
'email' => 'required for email login, unique per org'
```

### Emergency Contact Section (hidden for staff/teachers)
```php
'emergencyFullName' => 'emergency contact name'
'emergencyRelation' => 'Parent|Spouse|Sibling|Child|Friend|Other'
'emergencyPhoneNumber' => 'emergency contact phone'
'emergencyEmail' => 'emergency contact email'
```

### Required Forms Section (hidden for staff/teachers)
```php
// Dynamic checkbox list of organization forms
// Required forms are pre-selected
// Forms have descriptions/messages
```

## 🏗️ Data Model (OrgUser)

### Key Database Fields
```php
// Identity & Basic Info
protected $fillable = [
    'fullName', 'email', 'phoneNumber', 'phoneCountry',
    'gender', 'dob', 'nationalID', 'address',
    
    // User Type Flags (boolean integers)
    'isCustomer', 'isStaff', 'isOnRoster', 'isOwner', 'isAdmin',
    'isGuest', 'isKiosk', 'isPosUser',
    
    // Emergency Contact
    'emergencyFullName', 'emergencyPhoneNumber', 
    'emergencyEmail', 'emergencyRelation',
    
    // System Fields
    'org_id', 'user_id', 'status', 'uuid', 'token', 'token_sms',
    'created_by', 'deleted_by',
    
    // Additional fields...
    'hashId', 'code', 'number', 'pin', 'photoFileName',
    'isFoundationRequired', 'isFoundationCompleted',
    'pipeline_id', 'pipeline_stage_id'
];
```

### User Type Logic
```php
// Customer (regular member)
'isCustomer' => true

// Staff Member  
'isStaff' => true, 'isCustomer' => true

// Teacher/Coach
'isOnRoster' => true, 'isCustomer' => true
```

## 🔧 Creation Process

### 1. Data Mutation (`mutateFormDataBeforeCreate`)
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    switch ($this->userType) {
        case 'teacher':
            $data['isOnRoster'] = true;
            $data['isCustomer'] = true;
            break;
        case 'staff':
            $data['isStaff'] = true;
            $data['isCustomer'] = true;
            break;
        case 'customer':
            $data['isCustomer'] = true;
            break;
    }
    return $data;
}
```

### 2. Model Events (in OrgUser model)
```php
static::creating(function ($model) {
    // Auto-set org_id from authenticated user
    if (empty($model->org_id) && auth()->check()) {
        $model->org_id = auth()->user()->orgUser->org_id;
    }
    
    // Set who created this user
    if (empty($model->created_by) && auth()->check()) {
        $model->created_by = auth()->user()->orgUser->id;
    }
    
    // Generate tokens
    if (empty($model->token)) {
        $model->generateToken();
    }
    if (empty($model->token_sms)) {
        $model->generateTokenSMS();
    }
    
    $model->uuid = Str::uuid();
    $model->status = OrgUserStatus::None->value;
});

static::created(function ($model) {
    // Dispatch Yii2 queue job for post-processing
    $dispatcher = new Yii2QueueDispatcher();
    $dispatcher->dispatch('common\jobs\user\OrgUserCreateCompleteJob', ['id' => $model->id]);
});
```

### 3. Post-Creation (`afterCreate`)
```php
protected function afterCreate(): void
{
    $orgUser = $this->record;
    
    // Handle form assignments using Laravel queue job
    if (!empty($this->data['forms'])) {
        ProcessOrgUserFormAssignments::dispatch($orgUser->id, $this->data['forms']);
    }
}
```

## 🔄 Async Processing (Yii2 Integration)

### Yii2QueueDispatcher Service
```php
class Yii2QueueDispatcher
{
    public function dispatch(string $jobClass, array $jobData = [])
    {
        // Serialize job in Yii2 format
        $serializedJob = sprintf(
            'O:%d:"%s":%d:{%s}',
            strlen($jobClass),
            $jobClass,
            count((array)$job),
            // ... serialization logic
        );

        // Insert into queue table for Yii2 worker
        DB::connection($this->connection)->table('queue')->insert([
            'channel' => 'default',
            'job' => $serializedJob,
            'pushed_at' => $now->timestamp,
            'ttr' => 30
        ]);
    }
}
```

### Async Jobs Dispatched
- `OrgUserCreateCompleteJob` - Post-creation processing
- `OrgUserUpdateCompleteJob` - Update processing  
- `OrgUserDeleteCompleteJob` - Deletion processing
- `ProcessOrgUserFormAssignments` - Form assignment processing

## ✅ Validation & Business Rules

### Uniqueness Rules
```php
// Custom validation rules used
new UniqueOrgUserFullName(auth()->user()->orgUser->org_id)
new UniqueOrgUserPhone(auth()->user()->orgUser->org_id, $phoneCountry)
new UniqueOrgUserEmail(auth()->user()->orgUser->org_id, $phoneCountry)
new PhoneNumberRule($phoneCountry)
```

### Conditional Requirements
- Gender/DOB requirements based on `OrgSettingsInput` table
- Phone vs Email login based on user selection
- Emergency contact section only for customers
- Forms only assigned to customers (not staff/teachers)

### Organization Settings Integration
```php
// Check organization-specific requirements
protected function isGenderRequired(): bool
{
    $orgSettings = $this->getOrgSettings();
    return $orgSettings && $orgSettings->orgUserGenderRequired === 1;
}

protected function isDOBRequired(): bool  
{
    $orgSettings = $this->getOrgSettings();
    return $orgSettings && $orgSettings->orgUserDOBRequired === 1;
}
```

## 🎯 FOH Implementation Strategy

### Essential Components for FOH
1. **Simplified Member Creation Form**:
   - Full name (required, unique validation)
   - Phone number with country code (required)
   - Email (optional for phone login)
   - Digital signature capture area

2. **Terms of Use Integration**:
   - Display terms text from database/config
   - Require agreement checkbox
   - Capture digital signature with finger/stylus

3. **User Creation Logic**:
   - Create OrgUser record with `isCustomer = true`
   - Generate required tokens and UUID
   - Dispatch Yii2 queue job for post-processing

4. **Validation System**:
   - Unique name/phone per organization
   - Phone number format validation
   - Required field validation

### Simplified FOH Flow
```
1. Single Form (no user type selection - always customer)
   ↓
2. Essential Fields Only: 
   - fullName, phoneNumber, phoneCountry, email
   ↓  
3. Terms & Signature:
   - Display terms text
   - Capture digital signature
   ↓
4. Auto-Submit:
   - Create OrgUser record
   - Dispatch Yii2 async job
   - Show success message
```

### Key Differences from Core Admin
| Feature | Core Admin | FOH Interface |
|---------|------------|---------------|
| User Types | Customer/Staff/Teacher selection | Always Customer |
| Form Sections | 4 sections with many fields | 1 section with essential fields |
| Emergency Contact | Full section | Not needed for FOH |
| Forms Assignment | Complex checkbox system | Not needed for FOH |
| Validation | Extensive conditional rules | Basic required + unique |
| Signature | Not implemented | Digital signature required |
| Terms of Use | Not in creation flow | Required before creation |

## 📋 Technical Requirements for FOH

### Database Tables Needed
- `orgUser` - Main member records (already exists)
- `user` - Authentication records (already exists)  
- `queue` - Yii2 async job queue (already exists)
- Terms of use storage (config or database)

### Services to Implement
- `Yii2QueueDispatcher` - Copy from core project
- Digital signature capture component
- Terms of use display component
- Member creation service

### Validation Rules to Implement
- Unique full name per organization
- Unique phone number per organization
- Phone number format validation
- Required field validation

### Models to Create/Update
- Copy relevant parts of OrgUser model
- Implement User/OrgUser relationship
- Add digital signature storage fields if needed

## 🔗 Integration Points

### With wodworx-core
- Shares same database tables
- Uses same async job processing
- Follows same validation rules
- Maintains data consistency

### With Yii2 Backend  
- Dispatches jobs to shared queue table
- Jobs processed by existing Yii2 workers
- Maintains existing business logic flow

## 📝 Next Steps

1. **Create OrgUser model** in FOH project
2. **Implement Yii2QueueDispatcher service**
3. **Build member creation Livewire component**
4. **Add digital signature capture**
5. **Implement terms of use display**
6. **Add validation rules**
7. **Test integration with existing systems**

---

*This analysis was created on 2025-08-10 to guide the implementation of member creation functionality in the wodworx-foh project.*
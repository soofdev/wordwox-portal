# Bulk Hold Feature Documentation

## Overview

The Bulk Hold feature allows gym administrators to efficiently manage membership holds for multiple members simultaneously. This feature supports three main scenarios: holding all members of a specific plan, holding selected individual users, or holding all active memberships in the organization.

## Table of Contents

1. [Feature Architecture](#feature-architecture)
2. [User Interface Components](#user-interface-components)
3. [Backend Components](#backend-components)
4. [Database Structure](#database-structure)
5. [Workflow Scenarios](#workflow-scenarios)
6. [Validation System](#validation-system)
7. [Permission System](#permission-system)
8. [Background Processing](#background-processing)
9. [Testing & Debugging](#testing--debugging)
10. [Troubleshooting](#troubleshooting)

## Feature Architecture

### High-Level Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend UI   │────│  Livewire        │────│  Background     │
│   (Blade Views) │    │  Components      │    │  Jobs           │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  Hold Service    │
                       │  & Validation    │
                       └──────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  Database        │
                       │  (orgUserPlanHold)│
                       └──────────────────┘
```

### Component Hierarchy

```
BulkCreateHold (Livewire Component)
├── Plan Selection Dropdown
├── User Search & Selection
├── Date Range Selection
├── Validation Preview
└── Background Job Dispatch

BulkEndHold (Livewire Component)
├── Group Selection Dropdown
├── End Reason Input
└── Background Job Dispatch

Background Jobs
├── BulkHoldJob (Create holds)
└── BulkEndHoldJob (End holds)
```

## User Interface Components

### 1. Bulk Hold Buttons

**Location**: `/subscriptions/holds` page header

**Buttons**:
- **Create Bulk Hold** (Green button with pause icon)
- **End Bulk Hold** (Red button with play icon)

**Visibility**: Only shown if user has appropriate permissions

### 2. Create Bulk Hold Modal

#### Hold Type Selection
- **Specific Plan**: Hold all members of a selected plan
- **Specific Users**: Hold individually selected users
- **All Users**: Hold all active memberships in organization

#### Plan Selection (for "Specific Plan")
- Dropdown with all active plans in the organization
- Shows plan name and type
- Real-time count of affected memberships

#### User Search & Selection (for "Specific Users")
- **Search Input**: Type 2+ characters to search users
- **Real-time Results**: Shows matching users with avatars
- **Multi-select**: Checkbox selection for multiple users
- **Search Criteria**: Name and email matching

#### Date Selection
- **Start Date**: Cannot be in the past
- **End Date**: Must be after start date
- **Real-time Validation**: Auto-validates as you type

#### Advanced Features
- **Preview Validation**: Shows which users can/cannot be held
- **Conflict Detection**: Identifies overlapping holds
- **Notification Options**: Email and push notifications
- **Reason Field**: Optional hold reason

### 3. End Bulk Hold Modal

#### Group Selection
- Dropdown with existing bulk hold groups
- Shows group name, hold count, and date range
- Only shows active/upcoming holds

#### End Reason
- Optional textarea for ending reason
- Maximum 500 characters

## Backend Components

### 1. BulkCreateHold Component

**File**: `app/Livewire/Subscriptions/BulkCreateHold.php`

**Key Methods**:
- `mount()`: Initialize component with default values
- `loadAvailablePlans()`: Load active plans for the organization
- `loadAvailableUsers()`: Search users based on input
- `updateAffectedUsersCount()`: Calculate affected memberships
- `createBulkHold()`: Validate and dispatch background job
- `previewValidation()`: Show validation results before execution

**Properties**:
```php
public $holdType = 'plan'; // 'plan', 'all_users', 'specific_users'
public $selectedPlanId = null;
public $selectedUserIds = [];
public $holdStartDate;
public $holdEndDate;
public $holdReason = '';
public $notifyEmail = false;
public $notifyPush = false;
public $availablePlans = [];
public $availableUsers = [];
public $userSearch = '';
public $affectedUsersCount = 0;
public $validationResults = [];
```

### 2. BulkEndHold Component

**File**: `app/Livewire/Subscriptions/BulkEndHold.php`

**Key Methods**:
- `mount()`: Initialize component
- `loadAvailableGroups()`: Load existing bulk hold groups
- `updateAffectedHoldsCount()`: Calculate holds to be ended
- `endBulkHold()`: Validate and dispatch background job

**Properties**:
```php
public $endType = 'plan';
public $selectedPlanId = null;
public $selectedUserIds = [];
public $selectedGroupName = null;
public $endReason = '';
public $availableGroups = [];
public $affectedHoldsCount = 0;
```

### 3. Background Jobs

#### BulkHoldJob
**File**: `app/Jobs/BulkHoldJob.php`

**Purpose**: Process bulk hold creation in background

**Parameters**:
- `$orgId`: Organization ID
- `$planId`: Specific plan ID (null for all users)
- `$data`: Hold data (dates, reason, notifications)
- `$createdBy`: User ID who created the hold

#### BulkEndHoldJob
**File**: `app/Jobs/BulkEndHoldJob.php`

**Purpose**: Process bulk hold ending in background

**Parameters**:
- `$orgId`: Organization ID
- `$planId`: Specific plan ID (null for all users)
- `$data`: End data (reason, ended by)
- `$endedBy`: User ID who ended the hold

## Database Structure

### orgUserPlanHold Table

**Primary Table**: Stores individual hold records

**Key Columns**:
- `id`: Primary key
- `org_id`: Organization ID
- `orgUser_id`: User ID
- `orgUserPlan_id`: Membership ID
- `startDateTime`: Hold start date
- `endDateTime`: Hold end date
- `groupName`: Bulk hold group identifier
- `status`: Hold status (Active, Upcoming, Expired, Canceled)
- `isCanceled`: Boolean flag for canceled holds
- `note`: Hold reason/notes
- `notifyEmail`: Email notification flag
- `notifyPush`: Push notification flag

### Group Name Format

Bulk holds use a standardized group name format:
```
{auto_id}-hold-{start_date}-{end_date}
```

Example: `1735567890123-hold-2025-10-01-2025-10-07`

## Workflow Scenarios

### Scenario 1: Hold All Members of a Specific Plan

1. **User Action**: Select "Specific Plan" → Choose plan from dropdown
2. **System Response**: Shows count of affected memberships
3. **User Action**: Set start/end dates
4. **System Response**: Real-time validation
5. **User Action**: Click "Preview Validation" (optional)
6. **System Response**: Shows detailed validation results
7. **User Action**: Click "Create Bulk Hold"
8. **System Response**: Dispatches BulkHoldJob with plan filter
9. **Background Processing**: Creates holds for all plan members
10. **Completion**: Shows success message and refreshes page

### Scenario 2: Hold Selected Individual Users

1. **User Action**: Select "Specific Users"
2. **User Action**: Type in search box (2+ characters)
3. **System Response**: Shows matching users with avatars
4. **User Action**: Check users to hold
5. **System Response**: Updates affected count
6. **User Action**: Set dates and submit
7. **System Response**: Dispatches BulkHoldJob with user filter
8. **Background Processing**: Creates holds for selected users only

### Scenario 3: Hold All Active Memberships

1. **User Action**: Select "All Users"
2. **System Response**: Shows total active membership count
3. **User Action**: Set dates and submit
4. **System Response**: Dispatches BulkHoldJob without filters
5. **Background Processing**: Creates holds for all active memberships

### Scenario 4: End Bulk Hold

1. **User Action**: Click "End Bulk Hold"
2. **System Response**: Shows modal with group dropdown
3. **User Action**: Select bulk hold group
4. **System Response**: Shows group details and hold count
5. **User Action**: Enter end reason and submit
6. **System Response**: Dispatches BulkEndHoldJob
7. **Background Processing**: Ends all holds in the group

## Validation System

### Real-time Validation

The system performs validation at multiple levels:

#### 1. Form Validation
- **Required Fields**: Hold type, dates, plan/user selection
- **Date Validation**: Start date cannot be in past, end date must be after start
- **User Selection**: At least one user must be selected for specific users

#### 2. Business Logic Validation
- **Active Membership Check**: Only active/upcoming memberships can be held
- **Existing Hold Check**: Prevents overlapping holds
- **Permission Check**: User must have 'hold memberships' permission

#### 3. Preview Validation
- **Conflict Detection**: Identifies users with existing holds
- **Overlap Analysis**: Shows overlapping hold periods
- **Detailed Results**: Provides specific reasons for each conflict

### Validation Results Structure

```php
[
    'membership_id' => 123,
    'member_name' => 'John Doe',
    'plan_name' => 'Monthly Unlimited',
    'status' => 'active',
    'can_hold' => false,
    'reason' => 'Active hold exists from 2025-09-15 to 2025-10-15',
    'scenario' => 'active_hold_exists',
    'existing_holds' => [...],
    'overlapping_holds' => [...],
    'conflict_category' => 'Active Hold Conflict'
]
```

## Permission System

### Required Permissions

#### Create Bulk Hold
- `hold memberships`: Required to create holds

#### End Bulk Hold
- `end hold`: Required to end holds

#### View Holds
- `view memberships`: Required to see holds page

### Permission Checks

```php
// In Blade template
@if(optional(auth()->user()->orgUser)?->safeHasPermissionTo('hold memberships'))
    <flux:modal.trigger name="bulk-create-hold-modal">
        <flux:button>Create Bulk Hold</flux:button>
    </flux:modal.trigger>
@endif
```

## Background Processing

### Job Queue Configuration

The system uses Laravel's job queue system for background processing:

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'),
```

### Job Processing Flow

1. **Job Dispatch**: Component dispatches job with parameters
2. **Queue Storage**: Job stored in database queue
3. **Worker Processing**: Queue worker picks up job
4. **Hold Creation**: Job creates individual hold records
5. **Notification Sending**: Sends email/push notifications if enabled
6. **Completion**: Job marks as completed

### Error Handling

- **Validation Errors**: Shown as toast notifications
- **Job Failures**: Logged to Laravel logs
- **Retry Logic**: Failed jobs can be retried
- **User Feedback**: Success/error messages via toast notifications

## Testing & Debugging

### Debug Logging

The system includes comprehensive logging:

```php
\Log::info('BulkCreateHold: Component mounted', [
    'holdStartDate' => $this->holdStartDate,
    'holdEndDate' => $this->holdEndDate,
    'holdType' => $this->holdType,
    'user_id' => auth()->id(),
    'orgUser_id' => auth()->user()->orgUser?->id
]);
```

### Test Script

A comprehensive test script is available: `test-bulk-hold-functionality.php`

**Test Coverage**:
- Database structure validation
- Organization data verification
- User search functionality
- Plan selection testing
- Date validation
- Permission simulation
- Component state testing
- Translation support

### Running Tests

```bash
# Run comprehensive test
php test-bulk-hold-functionality.php

# Monitor logs
tail -f storage/logs/laravel.log

# Check queue status
php artisan queue:work --verbose
```

## Troubleshooting

### Common Issues

#### 1. Bulk Hold Buttons Not Visible
**Cause**: Buttons are commented out or user lacks permissions
**Solution**: 
- Check `resources/views/livewire/subscriptions/membership-holds.blade.php`
- Verify user has 'hold memberships' or 'end hold' permissions

#### 2. User Search Not Working
**Cause**: Search term too short or no active memberships
**Solution**:
- Ensure search term is 2+ characters
- Check for active memberships in organization
- Verify database connection

#### 3. No Plans Available
**Cause**: No active plans in organization
**Solution**:
- Check `orgPlan` table for active plans
- Verify `isActive` flag is set to true

#### 4. Validation Errors
**Cause**: Invalid dates or conflicting holds
**Solution**:
- Check date format and logic
- Review existing holds for conflicts
- Use preview validation to identify issues

#### 5. Jobs Not Processing
**Cause**: Queue worker not running
**Solution**:
```bash
# Start queue worker
php artisan queue:work

# Check queue status
php artisan queue:monitor
```

### Debug Commands

```bash
# Check hold table structure
php artisan tinker --execute="Schema::getColumnListing('orgUserPlanHold')"

# Count active holds
php artisan tinker --execute="App\Models\OrgUserPlanHold::where('isCanceled', false)->count()"

# Check queue jobs
php artisan queue:monitor

# Clear failed jobs
php artisan queue:flush
```

### Performance Considerations

#### Large Organizations
- **User Search Limit**: Limited to 50 users per search
- **Background Processing**: Large operations use job queues
- **Pagination**: Hold lists are paginated (15 per page)

#### Database Optimization
- **Indexes**: Ensure proper indexes on frequently queried columns
- **Query Optimization**: Use eager loading for relationships
- **Batch Processing**: Jobs process holds in batches

## API Reference

### Component Methods

#### BulkCreateHold

```php
// Load available plans
public function loadAvailablePlans()

// Search users
public function loadAvailableUsers()

// Update affected count
public function updateAffectedUsersCount()

// Create bulk hold
public function createBulkHold()

// Preview validation
public function previewValidation()

// Reset form
public function resetForm()
```

#### BulkEndHold

```php
// Load available groups
public function loadAvailableGroups()

// Update affected count
public function updateAffectedHoldsCount()

// End bulk hold
public function endBulkHold()

// Reset form
public function resetForm()
```

### Job Classes

#### BulkHoldJob

```php
// Constructor
public function __construct($orgId, $planId, $data, $createdBy)

// Handle job
public function handle()

// Validate membership
private function canHoldMembership($membership)
```

#### BulkEndHoldJob

```php
// Constructor
public function __construct($orgId, $planId, $data, $endedBy)

// Handle job
public function handle()
```

## Conclusion

The Bulk Hold feature provides a comprehensive solution for managing membership holds at scale. With its intuitive interface, robust validation system, and background processing capabilities, it enables gym administrators to efficiently handle bulk operations while maintaining data integrity and user experience.

The system is designed to be:
- **Scalable**: Handles large organizations with thousands of members
- **Reliable**: Comprehensive validation and error handling
- **User-friendly**: Intuitive interface with real-time feedback
- **Maintainable**: Well-structured code with extensive logging
- **Extensible**: Easy to add new features and scenarios

For additional support or feature requests, please refer to the development team or create an issue in the project repository.


# Notification System Documentation

## Overview

The notification system in your application supports both **template-based notifications** (using the `notificationTemplate` table) and **simplified direct notifications** (using Laravel's Mail system). Here's how it works:

## Architecture

### 1. Database Structure

**Table: `notificationTemplate`**
- `id` - Primary key
- `uuid` - Unique identifier
- `slug` - Template identifier (e.g., 'hold_created', 'hold_ended')
- `org_id` - Organization ID (NULL for global templates)
- `name` - Template display name
- `emailSubject` - Email subject line with placeholders
- `emailBodyHtml` - HTML email body with placeholders
- `emailBodyText` - Plain text email body with placeholders
- `pushHeadline` - Push notification headline
- `pushSubtitle` - Push notification subtitle
- `pushBody` - Push notification body
- `smsBody` - SMS message body
- `placeholder` - JSON array of available placeholders
- `created_at`, `updated_at`, `deleted_at` - Timestamps

### 2. Core Components

#### A. NotificationTemplate Model (`app/Models/NotificationTemplate.php`)
- Maps to `notificationTemplate` table
- Handles template CRUD operations
- Provides placeholder validation
- Converts HTML to plain text automatically
- Includes default hold notification templates

#### B. NotificationsHelper Service (`app/Services/NotificationsHelper.php`)
- **Template-based notification system**
- Supports email, SMS, and push notifications
- Handles placeholder replacement
- Dispatches jobs for actual sending
- Falls back from org-specific to global templates

#### C. SendHoldNotificationJob (`app/Jobs/SendHoldNotificationJob.php`)
- **Simplified notification system** (currently used)
- Uses `HoldNotificationMail` for emails
- Logs push notifications (can be extended for real push service)
- Handles hold-specific notifications (created, ended, cancelled, modified)

#### D. HoldNotificationMail (`app/Mail/HoldNotificationMail.php`)
- Laravel Mailable for hold notifications
- Supports different notification types
- Uses Blade templates for email rendering

## How It Works

### 1. Bulk Hold Creation Process

```php
// 1. User creates bulk hold via BulkCreateHold Livewire component
$holdData = [
    'startDate' => '2025-12-01',
    'endDate' => '2025-12-05',
    'reason' => 'Vacation hold',
    'notifyEmail' => true,  // Enable email notifications
    'notifyPush' => true,   // Enable push notifications
    'groupName' => 'vacation-hold-2025'
];

// 2. BulkHoldJob processes memberships
BulkHoldJob::dispatch($orgId, $planId, $holdData, $createdBy);

// 3. For each successful hold creation:
OrgUserPlanHoldService->createHold($membership, $holdData);

// 4. Notification job is dispatched
SendHoldNotificationJob::dispatch($hold->id, 'created', $notifyEmail, $notifyPush);
```

### 2. Notification Job Execution

```php
// SendHoldNotificationJob handles the notification
public function handle(): void
{
    // 1. Find the hold record
    $hold = OrgUserPlanHold::find($this->holdId);
    
    // 2. Get membership and user details
    $membership = $hold->orgUserPlan;
    $orgUser = $membership->orgUser;
    
    // 3. Send email notification (if enabled)
    if ($this->sendEmail && $orgUser->email) {
        $this->sendEmailNotification($hold, $membership, $orgUser, $org);
    }
    
    // 4. Send push notification (if enabled)
    if ($this->sendPush) {
        $this->sendPushNotification($hold, $membership, $orgUser, $org);
    }
}
```

### 3. Email Notification Process

```php
private function sendEmailNotification($hold, $membership, $orgUser, $org): void
{
    $emailData = [
        'member_name' => $orgUser->fullName,
        'plan_name' => $membership->orgPlan->name,
        'start_date' => $hold->startDateTime->format('M d, Y'),
        'end_date' => $hold->endDateTime->format('M d, Y'),
        'notification_type' => $this->notificationType,
        'hold_note' => $hold->note ?? '',
        'org_name' => $org->name ?? 'Your Gym'
    ];

    // Send using HoldNotificationMail
    Mail::to($orgUser->email)->send(new HoldNotificationMail($emailData));
}
```

### 4. Push Notification Process

```php
private function sendPushNotification($hold, $membership, $orgUser, $org): void
{
    // Currently logs the notification
    // Can be extended to integrate with push notification services
    Log::info('SendHoldNotificationJob: Push notification logged', [
        'hold_id' => $this->holdId,
        'user_id' => $orgUser->id,
        'message' => $this->getPushNotificationMessage($hold, $membership, $orgUser, $org)
    ]);
}
```

## Available Notification Types

### 1. Hold Created
- **Email Subject**: "Your [PLAN NAME] Membership Has Been Put On Hold"
- **Content**: Confirms hold creation with dates and reason
- **Push Message**: "Your [PLAN NAME] has been put on hold from [START DATE] to [END DATE]."

### 2. Hold Ended
- **Email Subject**: "Your [PLAN NAME] Hold Has Been Ended"
- **Content**: Confirms hold has ended and membership is active
- **Push Message**: "Your [PLAN NAME] hold has been ended. Your membership is now active."

### 3. Hold Cancelled
- **Email Subject**: "Your [PLAN NAME] Hold Has Been Cancelled"
- **Content**: Confirms hold cancellation
- **Push Message**: "Your [PLAN NAME] hold from [START DATE] to [END DATE] has been cancelled."

### 4. Hold Modified
- **Email Subject**: "Your [PLAN NAME] Hold Dates Have Been Updated"
- **Content**: Confirms hold date changes
- **Push Message**: "Your [PLAN NAME] hold dates have been updated to [START DATE] - [END DATE]."

## Testing Results

### ✅ **Bulk Hold Creation**: Working correctly
- Successfully creates holds for eligible memberships
- Handles validation and conflict detection
- Skips memberships with existing holds or conflicts

### ✅ **Email Notifications**: Working correctly
- Emails are sent using `HoldNotificationMail`
- Template placeholders are replaced with actual data
- Logs show: `SendHoldNotificationJob: Email sent successfully`

### ✅ **Push Notifications**: Working correctly (logged)
- Push notifications are prepared and logged
- Ready for integration with push notification services
- Logs show: `SendHoldNotificationJob: Push notification logged`

### ✅ **Template System**: Available but not currently used
- `notificationTemplate` table is properly configured
- `NotificationsHelper` service is available for template-based notifications
- Default hold templates can be created automatically

## Usage Examples

### 1. Creating Default Templates

```php
// Create default hold notification templates for an organization
$templates = NotificationTemplate::createDefaultHoldTemplates($orgId);
```

### 2. Using Template-Based Notifications

```php
// Use NotificationsHelper for template-based notifications
$helper = new NotificationsHelper($org);
$helper->sendEmail('user@example.com', $placeholders, 'hold_created');
$helper->sendPushNotification($userId, $placeholders, 'hold_created');
```

### 3. Using Simplified Notifications (Current)

```php
// Direct notification job dispatch
SendHoldNotificationJob::dispatch($holdId, 'created', true, true);
```

## Configuration

### Email Configuration
- Uses Laravel's mail configuration (`config/mail.php`)
- Supports SMTP, Mailgun, etc.
- Templates stored in `resources/views/emails/`

### Push Notification Configuration
- Currently logs notifications
- Can be extended to support Firebase, OneSignal, etc.
- Configuration would go in `config/services.php`

## Future Enhancements

1. **Real Push Notifications**: Integrate with Firebase, OneSignal, or similar services
2. **SMS Notifications**: Implement SMS sending via Twilio, etc.
3. **Template Management UI**: Create admin interface for managing notification templates
4. **Notification Preferences**: Allow users to set notification preferences
5. **Delivery Tracking**: Track notification delivery status and open rates

## Troubleshooting

### Common Issues

1. **Template Not Found**: Ensure templates exist in `notificationTemplate` table
2. **Email Not Sending**: Check mail configuration and SMTP settings
3. **Missing Placeholders**: Verify placeholder names match template content
4. **Queue Issues**: Ensure queue workers are running for background jobs

### Debugging

```php
// Check logs for notification results
tail -f storage/logs/laravel.log | grep "SendHoldNotificationJob"

// Test notification system
php test-notification-system.php

// Test bulk hold creation
php test-bulk-hold-december.php
```

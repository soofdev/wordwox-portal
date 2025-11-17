# EventSubscriber Model Documentation

## Overview
The `EventSubscriber` model represents a **member's booking in a class or session**. When a member books a spot in an Event (class), an EventSubscriber record is created to track their reservation, attendance, payment, and related information.

**Location**: `/Users/macbook1993/Herd/wodworx-core/app/Models/EventSubscriber.php`

---

## Purpose
- Represents a member's booking/reservation in a specific event (class)
- Tracks booking status (confirmed, signed in, waitlisted, canceled, etc.)
- Manages attendance and no-shows
- Handles payment and invoicing for drop-in/paid classes
- Tracks quota consumption for membership plans
- Links members to events they're registered for

---

## Database Table
**Table Name**: `eventSubscriber`

### Key Fields

#### Identifiers
- `id` - Primary key
- `hashId` - Hash identifier for external reference
- `uuid` - Universal unique identifier
- `org_id` - Organization (tenant) ID

#### Relationships
- `event_id` - The event (class) being booked
- `orgUser_id` - The member booking the class
- `orgUserPlan_id` - The membership plan being used
- `orgPlan_id` - The plan template this booking is for
- `user_id` - Associated user account
- `by_orgUser_id` - Staff member who created the booking (if booked by staff)
- `program_id` - Program this booking is for
- `schedule_id` - Parent schedule (for recurring bookings)

#### Event Details (Cached)
- `event_startDateTime` - Event start time (cached for performance)
- `event_endDateTime` - Event end time (cached for performance)

#### Status & Type
- `status` - Current booking status (integer enum)
- `status_prev` - Previous status for history tracking
- `type` - Subscriber type enum (regular, waitlist, drop-in, etc.)

#### Reservation Rules
- `reservationRequiresActivePlan` - Whether active plan was required (boolean)
- `reservationCountsTowardsQuota` - Whether this counts against plan quota (boolean)
- `quotaMultiple` - Credit multiplier (e.g., 1.5 credits used)

#### Contact Information (for Drop-Ins)
- `fullName` - Full name (if not an org member)
- `email` - Email address
- `phoneCountry` - Phone country code
- `phoneNumber` - Phone number

#### Notifications & Reminders
- `eventReminderEmail` - When reminder email was sent
- `eventReminderCall` - When reminder call was made
- `teleEventLinkEmail` - Send virtual event link via email (boolean)
- `workoutEmailSent` - Workout email sent (boolean)
- `workoutPushSent` - Workout push notification sent (boolean)
- `cancellation` - Cancellation policy applied (boolean)

#### Payment & Invoicing (for Paid Classes)
- `invoiceTotal` - Total amount charged
- `invoiceTotalPaid` - Amount paid
- `invoiceCurrency` - Currency code (e.g., "USD")
- `invoiceStatus` - Payment status
- `invoiceDue` - When payment is due
- `invoiceMethod` - Payment method
- `invoiceReceipt` - Receipt reference

#### Status Flags
- `isCanceled` - Booking was canceled (boolean)
- `isDeleted` - Soft deleted (boolean)

---

## Relationships

### Belongs To (Many-to-One)
```php
// The organization
public function org(): BelongsTo

// The member who booked
public function orgUser(): BelongsTo

// The membership plan used
public function orgUserPlan(): BelongsTo

// The event (class) booked
public function event(): BelongsTo

// The program
public function program(): BelongsTo

// The schedule (for recurring)
public function schedule(): BelongsTo

// The user account
public function user(): BelongsTo

// Staff member who created booking
public function createdBy(): BelongsTo
```

### Morph Many (Polymorphic)
```php
// Notes associated with this booking
public function notes(): MorphMany
```

---

## Enums Used

### EventSubscriberStatus
Common status values (exact enum to be documented based on implementation):
- `STATUS_CONFIRMED` - Booking confirmed
- `STATUS_SIGNEDIN` - Member signed in/attended
- `STATUS_WAITLIST` - On waitlist
- `STATUS_CANCELED` - Booking canceled
- `STATUS_NOSHOW` - Member didn't show up
- `STATUS_LATE_CANCELED` - Canceled after cutoff time

The enum also provides:
```php
// Get array of active status values
EventSubscriberStatus::activeStatuses() // [STATUS_CONFIRMED, STATUS_SIGNEDIN]
```

### EventSubscriberType
- Various types like regular booking, waitlist, drop-in, etc.

---

## Key Attributes

### Display Name
```php
// Returns orgUser.fullName if available, otherwise the fullName field
$subscriber->fullName

// Useful for drop-in bookings where there may not be an orgUser
```

### Profile Photo
```php
// Returns the member's profile photo URL
$subscriber->photoFilePath

// Returns null if no member or no photo
```

---

## Common Usage Examples

### Get All Active Bookings for an Event
```php
use App\Models\EventSubscriber;
use App\Enums\EventSubscriberStatus;

$activeBookings = EventSubscriber::where('event_id', $eventId)
    ->whereIn('status', EventSubscriberStatus::activeStatuses())
    ->with('orgUser')
    ->get();
```

### Get Member's Upcoming Bookings
```php
$upcomingClasses = EventSubscriber::where('org_id', $orgId)
    ->where('orgUser_id', $orgUserId)
    ->whereIn('status', EventSubscriberStatus::activeStatuses())
    ->where('event_startDateTime', '>', now())
    ->with(['event', 'event.program', 'event.orgLocation'])
    ->orderBy('event_startDateTime')
    ->get();
```

### Get Member's Booking History
```php
$bookingHistory = EventSubscriber::where('org_id', $orgId)
    ->where('orgUser_id', $orgUserId)
    ->where('event_endDateTime', '<', now())
    ->with(['event', 'event.program'])
    ->orderBy('event_startDateTime', 'desc')
    ->paginate(20);
```

### Check if Member is Booked for Specific Event
```php
$isBooked = EventSubscriber::where('event_id', $eventId)
    ->where('orgUser_id', $orgUserId)
    ->whereIn('status', EventSubscriberStatus::activeStatuses())
    ->exists();
```

### Get No-Shows for a Date Range
```php
$noShows = EventSubscriber::where('org_id', $orgId)
    ->where('status', EventSubscriberStatus::STATUS_NOSHOW)
    ->whereBetween('event_startDateTime', [$startDate, $endDate])
    ->with(['orgUser', 'event'])
    ->get();
```

### Get Waitlisted Members for an Event
```php
$waitlist = EventSubscriber::where('event_id', $eventId)
    ->where('status', EventSubscriberStatus::STATUS_WAITLIST)
    ->orderBy('created_at') // FIFO
    ->with('orgUser')
    ->get();
```

### Count Active Bookings by Member
```php
$activeBookingsCount = EventSubscriber::where('orgUser_id', $orgUserId)
    ->whereIn('status', EventSubscriberStatus::activeStatuses())
    ->where('event_startDateTime', '>', now())
    ->count();
```

### Get Bookings that Count Toward Quota
```php
$quotaBookings = EventSubscriber::where('orgUser_id', $orgUserId)
    ->where('orgUserPlan_id', $planId)
    ->where('reservationCountsTowardsQuota', true)
    ->whereIn('status', EventSubscriberStatus::activeStatuses())
    ->sum('quotaMultiple'); // Total credits used
```

### Get Unpaid Drop-In Bookings
```php
$unpaidBookings = EventSubscriber::where('org_id', $orgId)
    ->whereNull('orgUser_id') // Drop-ins may not have orgUser
    ->where('invoiceStatus', '!=', 'paid')
    ->where('invoiceTotal', '>', 0)
    ->with('event')
    ->get();
```

---

## Booking Flow

### 1. Creating a Booking
When a member books a class:
```php
$subscriber = EventSubscriber::create([
    'org_id' => $orgId,
    'event_id' => $eventId,
    'orgUser_id' => $orgUserId,
    'orgUserPlan_id' => $planId,
    'orgPlan_id' => $orgPlanId,
    'by_orgUser_id' => auth()->user()->orgUser->id, // If booked by staff
    'status' => EventSubscriberStatus::STATUS_CONFIRMED,
    'type' => EventSubscriberType::TYPE_REGULAR,
    'event_startDateTime' => $event->startDateTime,
    'event_endDateTime' => $event->endDateTime,
    'reservationRequiresActivePlan' => $event->reservationRequiresActivePlan,
    'reservationCountsTowardsQuota' => $event->reservationCountsTowardsQuota,
    'quotaMultiple' => $event->quotaMultiple,
    // ... other fields
]);
```

### 2. Member Check-In/Sign-In
When a member arrives for class:
```php
$subscriber->update([
    'status' => EventSubscriberStatus::STATUS_SIGNEDIN,
    'status_prev' => $subscriber->status,
]);
```

### 3. Canceling a Booking
When a member cancels:
```php
$subscriber->update([
    'status' => EventSubscriberStatus::STATUS_CANCELED,
    'status_prev' => $subscriber->status,
    'isCanceled' => true,
    'cancellation' => now(), // Track when canceled
]);

// If late cancellation, may affect member's account
```

### 4. Marking No-Show
After class starts, mark absent members:
```php
$confirmedNotSignedIn = EventSubscriber::where('event_id', $eventId)
    ->where('status', EventSubscriberStatus::STATUS_CONFIRMED)
    ->get();

foreach ($confirmedNotSignedIn as $subscriber) {
    $subscriber->update([
        'status' => EventSubscriberStatus::STATUS_NOSHOW,
        'status_prev' => EventSubscriberStatus::STATUS_CONFIRMED,
    ]);
}
```

---

## Important Considerations

### Multi-Tenancy
- **Always filter by `org_id`** - The `Tenantable` trait applies `TenantScope` automatically
- Cross-org queries require explicit scope removal (rare)

### Quota Management
- Check `reservationCountsTowardsQuota` before counting against plan limits
- Use `quotaMultiple` to calculate actual credits used
- Some classes may not count (e.g., free trial classes)

### Drop-In Bookings
- Drop-ins may not have `orgUser_id` (external customers)
- Use `fullName`, `email`, `phoneNumber` fields for contact info
- Payment fields are important for drop-in tracking

### Active vs. All Bookings
- Use `EventSubscriberStatus::activeStatuses()` to filter for active bookings
- Active typically includes: Confirmed, Signed In
- Excludes: Canceled, No-Show, Waitlist (depending on implementation)

### Cached Event Data
- `event_startDateTime` and `event_endDateTime` are cached for performance
- Allows querying bookings without joining to Event table
- Update when event times change

### Status History
- Always set `status_prev` when changing status
- Useful for reporting and reverting changes
- Track the full lifecycle of a booking

### Payment Tracking
- Invoice fields only used for paid classes/drop-ins
- Members with active plans typically don't have invoice data
- `invoiceStatus` tracks payment state

### Soft Deletes
- Uses `isDeleted` flag, not Laravel's `deleted_at`
- Deleted bookings are retained for historical data
- Use `->where('isDeleted', false)` when querying

---

## Validation Rules

### Before Creating Booking
1. **Check Event Capacity**
   ```php
   $activeCount = EventSubscriber::where('event_id', $eventId)
       ->whereIn('status', EventSubscriberStatus::activeStatuses())
       ->count();
   
   if ($activeCount >= $event->capacity) {
       // Handle waitlist or reject
   }
   ```

2. **Check for Duplicate Booking**
   ```php
   $exists = EventSubscriber::where('event_id', $eventId)
       ->where('orgUser_id', $orgUserId)
       ->whereIn('status', EventSubscriberStatus::activeStatuses())
       ->exists();
   
   if ($exists) {
       throw new \Exception('Already booked');
   }
   ```

3. **Check Plan Quota**
   ```php
   if ($event->reservationCountsTowardsQuota) {
       $used = EventSubscriber::where('orgUserPlan_id', $planId)
           ->where('reservationCountsTowardsQuota', true)
           ->whereIn('status', EventSubscriberStatus::activeStatuses())
           ->sum('quotaMultiple');
       
       if ($used + $event->quotaMultiple > $plan->quota) {
           throw new \Exception('Insufficient credits');
       }
   }
   ```

4. **Check Active Plan Requirement**
   ```php
   if ($event->reservationRequiresActivePlan) {
       if (!$plan || $plan->status !== 'Active') {
           throw new \Exception('Active membership required');
       }
   }
   ```

---

## Related Models
- **Event**: The class/session being booked
- **OrgUser**: The member making the booking
- **OrgUserPlan**: The membership plan used for booking
- **EventAssignment**: Coach/trainer assigned to the event
- **Program**: Program category
- **Schedule**: Recurring schedule

---

## See Also
- [Event Model Documentation](event-model.md)
- [Class Booking System](class-booking-system.md) *(to be created)*
- [Membership Quota Management](membership-quota.md) *(to be created)*


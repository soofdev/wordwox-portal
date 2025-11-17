# Event Model Documentation

## Overview
The `Event` model represents a **class or session** in the gym management system. It is the core entity for scheduling group classes, personal training sessions, workshops, and other scheduled activities.

**Location**: `/Users/macbook1993/Herd/wodworx-core/app/Models/Event.php`

---

## Purpose
- Represents scheduled classes, personal training sessions, and other events
- Manages event timing, capacity, reservations, and waitlists
- Tracks event status (upcoming, in-progress, completed, canceled)
- Handles both in-person and virtual (teleconferencing) events
- Integrates with schedules and programs for recurring events

---

## Database Table
**Table Name**: `event`

### Key Fields

#### Basic Information
- `id` - Primary key
- `hashId` - Hash identifier for external reference
- `uuid` - Universal unique identifier
- `usid` - User-friendly short ID
- `org_id` - Organization (tenant) ID
- `orgLocation_id` - Location where event takes place
- `room_id` - Specific room for the event
- `program_id` - Associated program (e.g., "CrossFit", "Yoga")
- `schedule_id` - Parent schedule (for recurring events)
- `ptSchedule_id` - Personal training schedule ID

#### Event Details
- `type` - Event type enum (Group class, Personal Training, etc.)
- `name` - Custom event name (optional)
- `note` - Additional notes/description
- `status` - Current status enum value
- `status_prev` - Previous status for history tracking

#### Timing
- `startDateTimeLoc` - Start date/time in local timezone
- `endDateTimeLoc` - End date/time in local timezone
- `startDateTime` - Start date/time in UTC
- `endDateTime` - End date/time in UTC
- `timezone_long` - Full timezone name (e.g., "America/New_York")
- `timezone_offset` - Timezone offset in seconds

#### Capacity & Reservations
- `capacity` - Maximum number of participants
- `reservation` - Whether reservations are required (boolean)
- `reservationRequiresActivePlan` - Require active membership (boolean)
- `reservationCountsTowardsQuota` - Count against membership quota (boolean)
- `quotaMultiple` - Credit multiplier (e.g., 1.5 credits per booking)
- `waitListMode` - Waitlist behavior enum

#### Drop-In & Public Access
- `dropIn` - Allow drop-in participants (boolean)
- `dropInPublic` - Public drop-in allowed (boolean)
- `dropInPublicPayment` - Payment required for drop-in (boolean)

#### Event Management
- `skillLevel` - Required skill level enum
- `minOccupancyEnabled` - Minimum attendance requirement (boolean)
- `minOccupancyCheck` - When to check minimum occupancy
- `cancellation` - Cancellation policy (boolean)
- `subscriberNoShow` - Track member no-shows (boolean)
- `assignmentNoShow` - Track coach/trainer no-shows (boolean)
- `venue` - Venue type enum (In-Person, Virtual, Hybrid)

#### Sign-In Tracking
- `assignmentSignIn` - Require coach/trainer sign-in (boolean)
- `subscriberSignIn` - Require member sign-in (boolean)

#### Teleconferencing (Virtual Events)
- `tele_uuid` - Teleconference UUID
- `tele_id` - Teleconference provider ID
- `tele_host_id` - Host user ID
- `tele_start_url` - URL for host to start meeting
- `tele_join_url` - URL for participants to join
- `tele_password` - Meeting password
- `tele_encrypted_password` - Encrypted password
- `tele_status_code` - Provider status code
- `tele_status_message` - Provider status message
- `teleHostLinkEmail` - Send host link via email (boolean)

#### Status Flags
- `isActive` - Event is active (boolean)
- `isCanceled` - Event was canceled (boolean)
- `isDeleted` - Soft deleted (boolean)

---

## Relationships

### Has Many (One-to-Many)
```php
// All subscribers (bookings) for this event
public function eventSubscribers(): HasMany

// Only active subscribers (confirmed or signed in)
public function eventSubscribersActive(): HasMany

// All coach/trainer assignments
public function eventAssignments(): HasMany

// Only active assignments
public function eventAssignmentsActive(): HasMany
```

### Belongs To (Many-to-One)
```php
// Parent schedule (for recurring events)
public function schedule(): BelongsTo

// Associated program (e.g., "CrossFit")
public function program(): BelongsTo

// Organization (tenant)
public function org(): BelongsTo

// Location
public function orgLocation(): BelongsTo

// Room
public function room(): BelongsTo
```

### Has One (Single Record)
```php
// Get single subscriber (for PT sessions)
public function onlySubscriber()

// Get single assignment (for PT sessions)
public function onlyAssignment()
```

---

## Enums Used

### EventType
- `TYPE_PT` - Personal Training session
- `TYPE_GROUP` - Group class

### EventStatus
- `STATUS_ACTIVE` - Event is active
- `STATUS_CONDUCTED_BY_ADMIN` - Completed by admin
- `STATUS_CANCELED` - Canceled
- `STATUS_CANCELED_BY_ADMIN` - Admin canceled
- `STATUS_CANCELED_BY_SCHEDULE` - Canceled by schedule
- `STATUS_CANCELED_BY_LOW_OCCUPANCY` - Canceled due to low attendance
- `STATUS_DELETED` - Deleted
- `STATUS_DELETED_BY_ADMIN` - Admin deleted
- `STATUS_DELETED_BY_SCHEDULE` - Schedule deleted
- `STATUS_INACTIVE` - Inactive

### EventVenue
- `VENUE_IN_PERSON` - Physical location
- `VENUE_VIRTUAL` - Online/teleconference
- `VENUE_HYBRID` - Both in-person and virtual

### WaitListMode
- Various waitlist behaviors (auto-confirm, manual, etc.)

### SkillLevel
- Beginner, Intermediate, Advanced, etc.

---

## Key Computed Attributes

### Status & Timing
```php
// Has the event started?
$event->hasStarted // boolean

// Has the event ended?
$event->hasEnded // boolean

// Computed status text
$event->computedStatus // "Upcoming", "Started", "Ended", or "Canceled"
```

### Display Attributes
```php
// Short display name (varies by type)
$event->shortName // "John D & Coach Sarah" (PT) or "CrossFit - HIIT" (Group)

// Long display name with time
$event->longName // "9:00 AM - CrossFit - HIIT"

// Background color for UI display
$event->rowBackgroundColor // Hex color based on status

// Program color
$event->programColor // Program's color or default green
```

### Capacity & Bookings
```php
// Number of active subscribers
$event->subscribers_count // integer

// Percentage of capacity filled
$event->consumedPercent // 0-100
```

### Status Information
```php
// Complete status information for UI
$event->eventStatus // [
//   'label' => 'Upcoming',
//   'color' => 'warning',
//   'icon' => 'heroicon-o-clock'
// ]
```

### Event Metadata
```php
// Array of metadata for display
$event->eventMetadata // [
//   ['icon' => 'heroicon-o-calendar', 'label' => 'Date', 'value' => 'Oct 13, 2025'],
//   ['icon' => 'heroicon-o-clock', 'label' => 'Time', 'value' => '9:00 AM'],
//   ['icon' => 'heroicon-o-map-pin', 'label' => 'Location', 'value' => 'Main Gym'],
//   ['icon' => 'heroicon-o-building-office-2', 'label' => 'Room', 'value' => 'Studio A'],
//   ['icon' => 'heroicon-o-star', 'label' => 'Credits', 'value' => '1.5']
// ]
```

---

## Common Usage Examples

### Query Active Events for Today
```php
use App\Models\Event;
use App\Enums\EventStatus;
use Carbon\Carbon;

$todayEvents = Event::where('org_id', $orgId)
    ->whereDate('startDateTimeLoc', Carbon::today())
    ->where('isCanceled', false)
    ->where('isDeleted', false)
    ->orderBy('startDateTimeLoc')
    ->get();
```

### Get Events with Available Capacity
```php
$availableEvents = Event::where('org_id', $orgId)
    ->where('reservation', 1)
    ->whereRaw('capacity > (
        SELECT COUNT(*)
        FROM eventSubscriber
        WHERE eventSubscriber.event_id = event.id
        AND eventSubscriber.status IN (?, ?)
    )', [
        EventSubscriberStatus::STATUS_CONFIRMED->value,
        EventSubscriberStatus::STATUS_SIGNEDIN->value
    ])
    ->where('startDateTime', '>', now())
    ->get();
```

### Get Upcoming Group Classes for a Program
```php
use App\Enums\EventType;

$groupClasses = Event::where('org_id', $orgId)
    ->where('program_id', $programId)
    ->where('type', EventType::TYPE_GROUP)
    ->where('startDateTime', '>', now())
    ->where('isCanceled', false)
    ->with(['program', 'orgLocation', 'room', 'eventSubscribersActive'])
    ->orderBy('startDateTime')
    ->paginate(20);
```

### Check if Member is Booked
```php
$isBooked = Event::whereHas('eventSubscribersActive', function($query) use ($orgUserId) {
    $query->where('orgUser_id', $orgUserId);
})->where('id', $eventId)->exists();
```

### Get Events by Date Range
```php
$weekEvents = Event::where('org_id', $orgId)
    ->whereBetween('startDateTimeLoc', [
        Carbon::now()->startOfWeek(),
        Carbon::now()->endOfWeek()
    ])
    ->where('isCanceled', false)
    ->with(['program', 'eventSubscribersActive'])
    ->get();
```

---

## Important Considerations

### Multi-Tenancy
- **Always filter by `org_id`** - The `Tenantable` trait applies `TenantScope` automatically
- Use `withoutGlobalScope('tenant')` if you need cross-org queries (rare)

### Timezone Handling
- Store both local timezone (`startDateTimeLoc`) and UTC (`startDateTime`) versions
- Always use `startDateTimeLoc` for display to members
- Use `startDateTime` for system calculations and comparisons

### Capacity Management
- Check `capacity` before allowing bookings
- Count only `STATUS_CONFIRMED` and `STATUS_SIGNEDIN` subscribers
- Consider `waitListMode` for handling over-capacity bookings

### Status vs. Computed Status
- `status` field contains the database enum value
- `computedStatus` attribute calculates status based on time and flags
- Use `eventStatus` attribute for complete UI-ready status information

### Personal Training vs. Group Classes
- PT sessions (`TYPE_PT`) typically have:
  - One subscriber (client)
  - One assignment (trainer)
  - Lower capacity (often 1)
- Group classes (`TYPE_GROUP`) have:
  - Multiple subscribers
  - One or more assignments (instructors)
  - Higher capacity

### Soft Deletes & Cancellations
- Events use `isDeleted` flag, not Laravel's `deleted_at`
- Canceled events have `isCanceled = true`
- Query with `->where('isCanceled', false)->where('isDeleted', false)`

### Reservations & Drop-Ins
- `reservation = 1` means booking is required
- `dropIn = 1` allows walk-ins
- `dropInPublic = 1` allows non-members to book
- Check all three flags when implementing booking logic

---

## Related Models
- **EventSubscriber**: Represents a member's booking in this event
- **EventAssignment**: Represents a coach/trainer assigned to this event
- **Program**: The program category this event belongs to
- **Schedule**: The parent schedule for recurring events
- **OrgLocation**: Where the event takes place
- **Room**: Specific room within the location

---

## See Also
- [EventSubscriber Model Documentation](event-subscriber-model.md)
- [Class Booking System](class-booking-system.md) *(to be created)*
- [Schedule Management](schedule-management.md) *(to be created)*


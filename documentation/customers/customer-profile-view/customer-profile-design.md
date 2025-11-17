# FOH Member Profile Design

## Overview

This document outlines the design for a simplified member profile page optimized for Front of House (FOH) operations. Based on the analysis of the wodworx-core ViewOrgUser page, this design focuses on essential member information and quick actions relevant to front desk staff.

## 🎯 Design Goals

### Primary Objectives
- **Quick Member Identification**: Instant recognition with photo and key details
- **Essential Information Access**: Membership status, contact info, expiration dates
- **Mobile-First Design**: Optimized for tablets and phones at the front desk
- **Fast Loading**: Minimal data requirements for quick access
- **Action-Oriented**: Quick access to common FOH tasks

### User Stories
1. **Front Desk Staff**: "I need to quickly verify a member's status and contact them if needed"
2. **Manager**: "I want to see a member's membership details and recent activity at a glance"
3. **Sales Staff**: "I need to know when a member's plan expires to discuss renewals"

## 📱 Layout Design

### Mobile-First Approach
- **Single Column Layout**: Stacked sections for mobile optimization
- **Card-Based Design**: Distinct sections with clear boundaries
- **Touch-Friendly**: Large buttons and touch targets
- **Minimal Scrolling**: Most important info above the fold

### Desktop Enhancement
- **Two-Column Layout**: Sidebar with member info, main content with details
- **Expanded Actions**: More visible action buttons
- **Side-by-Side Metrics**: Better use of horizontal space

## 🏗️ Component Structure

### 1. Member Header Card
**Purpose**: Primary member identification and contact
**Layout**: Horizontal layout with photo, info, and quick actions

```
[Photo] [Name, Member #, Status] [Quick Actions]
        [Phone, Email]           [Call, Email, SMS]
        [Member Since, Plan]     [Edit, More...]
```

**Key Elements**:
- **Profile Photo**: 80x80px avatar or generated initials
- **Full Name**: Large, prominent text
- **Member Number**: Formatted as "#12345"
- **Status Badge**: Active, Expired, Suspended, etc.
- **Contact Info**: Phone and email with click-to-call/email
- **Member Since**: Join date
- **Current Plan**: Active membership name
- **Quick Actions**: Call, Email, SMS buttons

### 2. Membership Status Card
**Purpose**: Current membership information and expiration tracking
**Layout**: Prominent display with status indicators

```
Current Membership
[Plan Name]                    [Status Badge]
Valid: Jan 1, 2024 - Dec 31, 2024
Expires in: 45 days           [Renew Button]
```

**Key Elements**:
- **Plan Name**: Current active membership
- **Validity Period**: Start and end dates
- **Expiration Countdown**: Days remaining with color coding
- **Status Indicator**: Active, Expiring, Expired badges
- **Renewal Action**: Quick link to membership sales

### 3. Contact Information Card
**Purpose**: Detailed contact information and communication history
**Layout**: Clean list with action buttons

```
Contact Information
📞 +1 (555) 123-4567         [Call] [SMS]
✉️  member@email.com          [Email]
📍 123 Main St, City, State

Emergency Contact
👤 Jane Doe (Spouse)
📞 +1 (555) 987-6543         [Call]
```

**Key Elements**:
- **Primary Contact**: Phone with click-to-call
- **Email**: Click-to-email functionality
- **Address**: If available
- **Emergency Contact**: Name, relationship, phone

### 4. Quick Stats Grid
**Purpose**: Essential metrics at a glance
**Layout**: 2x2 or 4x1 grid depending on screen size

```
[Total Visits]  [Last Visit]
[    42    ]    [Yesterday]

[Revenue]       [Referrals]
[ $1,234  ]     [    3    ]
```

**Key Elements**:
- **Total Visits**: Lifetime visit count
- **Last Visit**: Most recent check-in date
- **Total Revenue**: Lifetime value
- **Referrals**: Members referred (if tracked)

### 5. Recent Activity Card
**Purpose**: Recent member activity and engagement
**Layout**: Simple list with timestamps

```
Recent Activity
✅ Checked in - Today 9:15 AM
💳 Payment received - Jan 15, 2024
📝 Note added by staff - Jan 10, 2024
🎯 Goal completed - Jan 5, 2024
```

**Key Elements**:
- **Check-ins**: Recent gym visits
- **Payments**: Recent transactions
- **Notes**: Staff notes and interactions
- **Achievements**: Goals or milestones

### 6. Quick Actions Bar
**Purpose**: Common FOH tasks
**Layout**: Horizontal button bar (mobile) or sidebar (desktop)

```
[Check In] [New Membership] [Add Note] [View History]
```

**Key Actions**:
- **Check In**: Manual check-in
- **Sell Membership**: Link to membership sales
- **Add Note**: Quick note entry
- **View Full History**: Link to detailed records
- **Print Guest Pass**: Temporary access
- **Send Welcome Email**: Onboarding

## 🎨 Visual Design System

### Color Coding
- **Green**: Active memberships, positive status
- **Yellow**: Expiring soon (7-30 days)
- **Orange**: Expiring very soon (1-7 days)
- **Red**: Expired or suspended
- **Blue**: Information and actions
- **Gray**: Neutral information

### Typography
- **Header**: 24px bold for member name
- **Subheader**: 18px semibold for section titles
- **Body**: 16px regular for content
- **Caption**: 14px for timestamps and metadata
- **Button**: 16px semibold for actions

### Spacing and Layout
- **Card Padding**: 24px for comfortable spacing
- **Section Gaps**: 16px between cards
- **Button Spacing**: 12px between action buttons
- **Grid Gaps**: 16px in stat grids

### Icons and Graphics
- **Heroicons**: Consistent icon library
- **Status Badges**: Rounded badges with appropriate colors
- **Profile Photos**: Rounded corners with fallback initials
- **Action Buttons**: Clear, recognizable icons

## 📊 Data Requirements

### Essential Data
```php
// Core member information
- fullName, number, email, phoneNumber
- status, member_since, isArchived
- photoFilePath (if available)

// Current membership
- activeMembership: name, startDate, endDate, status
- expirationDays (calculated)

// Quick stats
- totalVisits, lastVisitDate
- totalRevenue, referralCount

// Recent activity (last 5 items)
- checkIns, payments, notes, achievements
```

### Performance Optimization
- **Single Query**: Load all data in one optimized query
- **Eager Loading**: Preload necessary relationships
- **Caching**: Cache frequently accessed member data
- **Lazy Loading**: Load detailed history on demand

## 🔧 Technical Implementation

### Component Architecture
```
MemberProfile (Main Component)
├── MemberHeader
├── MembershipStatus
├── ContactInformation
├── QuickStats
├── RecentActivity
└── QuickActions
```

### Livewire Components
1. **MemberProfile**: Main container component
2. **MemberHeader**: Profile photo, name, basic info
3. **QuickActions**: Action buttons with wire:click methods
4. **MembershipStatus**: Membership info with renewal logic
5. **ContactActions**: Click-to-call/email functionality

### Routes and Navigation
```php
// Member profile route
Route::get('members/{member}', MemberProfile::class)
    ->name('members.profile')
    ->middleware(['auth', 'verified']);

// Quick access from active members card grid
// Click member card → redirect to profile
```

## 📱 Responsive Breakpoints

### Mobile (< 768px)
- Single column layout
- Stacked cards
- Full-width buttons
- Collapsed contact info

### Tablet (768px - 1024px)
- Two-column grid for stats
- Expanded contact display
- Side-by-side action buttons
- Optimized for landscape orientation

### Desktop (> 1024px)
- Sidebar + main content layout
- Expanded all sections
- Hover states for interactions
- Full action button labels

## 🚀 Implementation Phases

### Phase 1: Core Profile
- Member header with photo and basic info
- Current membership status
- Contact information with actions
- Basic navigation

### Phase 2: Enhanced Features
- Quick stats grid
- Recent activity feed
- Advanced quick actions
- Mobile optimizations

### Phase 3: Integration
- Deep linking from active members card grid
- Integration with membership sales flow
- Staff note system
- Print functionality

## 🎯 Success Metrics

### User Experience
- **Load Time**: < 2 seconds for profile display
- **Touch Targets**: Minimum 44px for mobile interactions
- **Information Hierarchy**: Clear visual hierarchy
- **Error Handling**: Graceful handling of missing data

### Business Value
- **Staff Efficiency**: Faster member lookup and service
- **Member Satisfaction**: Quick resolution of inquiries
- **Data Accuracy**: Real-time membership status
- **Revenue Opportunities**: Visible renewal prompts

## 📋 Development Checklist

### Frontend
- [ ] Responsive layout implementation
- [ ] Touch-friendly interactions
- [ ] Loading states and error handling
- [ ] Accessibility compliance
- [ ] Print-friendly styling

### Backend
- [ ] Optimized member query
- [ ] Real-time membership status
- [ ] Activity tracking
- [ ] Permission-based actions
- [ ] API endpoints for actions

### Testing
- [ ] Mobile device testing
- [ ] Various membership states
- [ ] Missing data scenarios
- [ ] Performance with large datasets
- [ ] Cross-browser compatibility

This design provides a focused, efficient member profile experience optimized for FOH operations while maintaining the essential functionality needed for excellent member service.
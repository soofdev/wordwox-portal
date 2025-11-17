# Project description and scope:

## Description:
This project is a module within a broader suite of web apps built in PHP (Laravel and Yii2) that make up one big SaaS app for gym management. This is the "Front of House" interface that gym staff will use to onboard new gym clients. The interface should be super simple and only give them access to create new member profiles (users) and sell new membership plans to existing users.

## Scope:
This project will eventually have the following functionality available to gym front of house (front desk) staff:
- Creating new customers.
- Initiating customer self registration.
- Collecting document signatures.
- Selling new memberships.
- Checking in customers into the gym.
- Booking members in classes and one-on-one appointments.
- Looking up customer information: profile details, membership history, class/appointment booking history, gym check-in in history. 
- Updating customer profile information, including capturing/uploading profile pictures. 
- Uploading customer documents such as Identification cards, cerificates, reports ...etc. 
- Assigning tasks to other staff.

## Member Creation System
The FOH system provides multiple ways to create new members:
1. **Staff Member Creation**: FOH staff can create members directly using the traditional interface with digital signature collection
2. **Customer Self-Registration**: New multi-step wizard system allowing customers to register themselves online

### Staff Member Creation
Creating a new member involves entering their full name, email, and/or phone number. The member has to also read the organization-specific terms of use and sign digitally before the user profile is created. Digital signature is collected by having the member sign with their finger on the screen.

### Customer Self-Registration
The new registration wizard allows customers to register online through:
- **Universal Organization URLs**: Permanent public links for website/marketing use
- **Staff-Generated Secure Links**: Time-limited links sent via SMS/Email
- **Multi-step Process**: Individual or family registration with comprehensive data collection
- **Mobile-Responsive**: Touch-friendly interface optimized for all devices

### Key Features Implemented (always keep this updated):
- **POS-style Interface**: Large, touch-friendly forms optimized for staff efficiency
- **Digital Signature Integration**: Canvas-based signature capture using `creagia/laravel-sign-pad`
- **Organization-Specific Terms**: Template-based terms system with variable replacement
- **Automated PDF Generation**: Professional signed agreements with TCPDF
- **S3 Cloud Storage**: Secure storage for signatures and generated documents
- **Multi-tenant Architecture**: Complete organization isolation and data security
- **Legal Compliance**: ESIGN Act and UETA compliant digital signatures
- **Admin Role Protection**: Comprehensive security features preventing admin lockout and ensuring proper access control
- **Multi-Step Membership Wizard**: Advanced 5-step membership creation with mobile-first design, real-time calculations, permission-based discounts, and persistent sidebar summary
- **Complete RBAC System**: Production-ready Role-Based Access Control system deployed across all 89 organizations with 6 categories, 24 tasks, 267 roles, and 344 user assignments
- **UI Feature Toggles**: Environment-based configuration system to control visibility of UI features and menu items for flexible deployment and feature management

### Documentation:
- **[Customer Creation Implementation](customers/customer-creation/customer-creation-implementation.md)**: Complete technical implementation details
- **[Customer Registration Wizard](customers/customer-self-registration/customer-registration-wizard.md)**: New self-registration system documentation
- **[FOH Staff Guide](customers/customer-self-registration/foh-staff-guide.md)**: How to use registration links
- **[Quick Setup Guide](customers/customer-self-registration/quick-setup-guide.md)**: Immediate testing and setup
- **[Multi-Step Wizard Implementation Guide](memberships/multi-step-wizard-implementation-guide.md)**: Comprehensive guide for building multi-step wizards with pure Laravel Livewire, featuring the MembershipWizard implementation
- **[Terms System Analysis](org-terms/terms-system-analysis.md)**: Design decisions and architecture analysis  
- **[Legal Compliance Guide](org-terms/legal-compliance-guide.md)**: Legal requirements and compliance framework
- **[Template Management Guide](org-terms/template-management-guide.md)**: Template creation and customization instructions
- **[FOH Access Control](authentication/foh-access-control.md)**: Authentication and authorization system documentation
- **[Admin Role Protection](roles-and-permissions/admin-role-protection.md)**: Admin role security features and implementation details
- **[FOH Commands](roles-and-permissions/foh-commands.md)**: Laravel Artisan commands for managing roles and permissions
- **[Safe Permission Checks](roles-and-permissions/safe-permission-checks.md)**: How to safely check permissions without exceptions
- **[RBAC System Documentation](rbac/)**: Complete Role-Based Access Control system documentation
- **[RBAC Implementation Status](rbac/implementation-status.md)**: Current status and capabilities of the production-ready RBAC system
- **[UI Feature Toggles](ui-ux-standards/ui-feature-toggles.md)**: Environment-based configuration for controlling UI feature visibility

## Technical Architecture

### Multi-Tenancy System
The FOH app implements organization-based multi-tenancy using:
- **TenantScope**: Automatic data filtering by organization
- **Organization Switching**: Staff can manage multiple gyms from one interface  
- **Data Isolation**: Complete separation of member data between organizations
- **FOH Access Control**: Middleware-based security ensuring only authorized users can access the interface

### Integration Points
- **wodworx-core**: Admin dashboard and core business logic
- **Yii2 Backend**: Legacy async processing and queue jobs
- **S3 Storage**: Cloud storage for signatures and documents
- **TCPDF**: Professional PDF generation for legal agreements

## Important Notes: 

**Database Schema**: There will be no migrations created at any point during the development of this FOH app. We will just be using the existing schema.

**Exception**: The `org_terms` table was added to support organization-specific terms of service templates. 

The admin dashboard for this app was built using Laravel 11 and Filament 3 and is located in a spearate project located:
/Users/vagabond/Herd/wodworx-apps/wodworx-core/

Login and authentication will use the existing "user" table. You can find details on how to implement the login in the "/Users/vagabond/Herd/wodworx-apps/wodworx-core/" project. 

The async back end processes were built using Yii2 php framework because it was part of a legacy system. We still dispatch some yii2 queue jobs after creating a new user and after a new membership plan is sold to a member. The async processes are in a spearate project located:
/Users/vagabond/Documents/sites/wod-worx/

Technical notes:

Don't yse Flux for any new componenets unless explicitly instructed. Keep the existing flux components used in the sidebar intact. 
# Customer Registration Consent System

## Overview

The Customer Registration Consent System provides a flexible, organization-specific consent management solution integrated into the self-registration wizard. The system allows organizations to define custom consent types and collect granular consent records from customers during the registration process, including parent consent for children in family registrations.

## Features

### Core Functionality
- **Organization-Specific Consents**: Each organization can define their own consent types
- **Two-Table Architecture**: Clean separation between consent definitions and user consent records
- **Family Consent Management**: Parent/guardian consent collection for children
- **Audit Trail**: Complete tracking of consent with IP address, timestamp, and user agent
- **Flexible Configuration**: Active/inactive and required/optional consent toggles per organization

### Registration Integration
- **Step-Based Integration**: Consents presented as dedicated step before review/confirmation
- **Real-Time Validation**: Required consents must be checked to proceed
- **Session Linking**: All consents from single registration session are linked together
- **Multi-Member Support**: Handles individual and family registration scenarios

## Database Architecture

### Tables

#### `org_consents`
Stores organization-specific consent type definitions.

```sql
CREATE TABLE org_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    org_id BIGINT UNSIGNED NOT NULL,
    consent_name VARCHAR(255) NOT NULL,        -- Display name: 'Terms of Service'
    consent_key VARCHAR(100) NOT NULL,         -- Code reference: 'terms'
    description TEXT,                          -- Full consent description
    is_active BOOLEAN DEFAULT TRUE,            -- Can be toggled on/off per org
    is_required BOOLEAN DEFAULT FALSE,         -- Must be checked to proceed
    display_order INT DEFAULT 0,               -- Order to show consents
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (org_id) REFERENCES org(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_consent_key (org_id, consent_key),
    INDEX idx_org_active (org_id, is_active),
    INDEX idx_org_required (org_id, is_required)
);
```

#### `org_user_consents`
Stores individual user consent records with complete audit trail.

```sql
CREATE TABLE org_user_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    org_user_id BIGINT UNSIGNED NOT NULL,           -- User the consent is about
    org_consent_id BIGINT UNSIGNED NOT NULL,        -- Which consent type
    consented BOOLEAN NOT NULL,                     -- TRUE/FALSE
    consented_at TIMESTAMP NOT NULL,                -- When consent was given
    ip_address VARCHAR(45),                         -- IP address of consenter
    user_agent TEXT,                                -- Browser/device information
    consented_by_org_user_id BIGINT UNSIGNED NULL,  -- Parent ID for children
    registration_session_id VARCHAR(36),            -- Links all consents from same session
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (org_user_id) REFERENCES orgUser(id) ON DELETE CASCADE,
    FOREIGN KEY (org_consent_id) REFERENCES org_consents(id) ON DELETE CASCADE,
    FOREIGN KEY (consented_by_org_user_id) REFERENCES orgUser(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_consent (org_user_id, org_consent_id),
    INDEX idx_user_consents (org_user_id),
    INDEX idx_consent_type (org_consent_id),
    INDEX idx_consenter (consented_by_org_user_id),
    INDEX idx_session (registration_session_id)
);
```

## Default Consent Types

Each organization receives these default consent types upon creation (all set to `active=true`, `required=false`):

1. **Terms of Service** (`terms`)
   - "I agree to the Terms of Service and membership rules."

2. **Privacy Policy** (`privacy`)
   - "I agree to the Privacy Policy and data handling practices."

3. **Liability Waiver** (`liability`)
   - "I understand and accept the risks associated with physical activities."

4. **Participation Consent** (`participation`)
   - "I consent (as parent/guardian) for my child to participate in gym activities."

5. **Email Marketing** (`marketing_email`)
   - "I consent to receiving promotional emails and updates."

6. **SMS Marketing** (`marketing_sms`)
   - "I consent to receiving promotional text messages."

7. **Media Consent** (`media`)
   - "I consent to photos/videos being taken and used for promotional purposes."

## Registration Flow Integration

### Updated Step Structure

#### Individual Registration (4 steps)
- Step 0: Registration type selection
- Step 1: Primary user information
- Step 2: Emergency contact information
- **Step 5: Consents & Agreements** ← NEW
- Step 6: Review and submit

#### Family Registration (6 steps)
- Step 0: Registration type selection
- Step 1: Primary user information
- Step 2: Spouse information (optional)
- Step 3: Child 1 information (optional)
- Step 4: Child 2 information (optional)
- **Step 5: Consents & Agreements** ← NEW
- Step 6: Review and submit

### Consent Collection Logic

#### Individual Registration
- User provides consent for themselves
- All active consents for the organization are presented
- Required consents must be checked to proceed

#### Family Registration
- **Primary Member**: Consents for themselves
- **Spouse**: Consents for themselves (if spouse information provided)
- **Children**: Primary member (parent) provides consent for all children

### UI Presentation

Consents are presented in a dedicated step with clear visual distinction:

- **Required consents**: Marked with red asterisk (*), must be checked
- **Optional consents**: Can be left unchecked
- **Family sections**: Visually separated sections for each family member
- **Parent consent**: Clearly labeled when parent is providing consent for children

## Technical Implementation

### Models

#### `OrgConsent` Model
- Manages organization-specific consent definitions
- Provides scopes for active/required consents
- Handles UUID generation and soft deletes

#### `OrgUserConsent` Model
- Stores individual consent records
- Tracks consent giver (parent for children)
- Maintains complete audit trail

#### `ConsentService`
- Centralized business logic for consent management
- Handles consent retrieval and storage
- Provides helper methods for consent checking

### Integration Points

#### CustomerRegistrationWizard
- New consent step (Step 5) before review
- Dynamic consent loading based on organization
- Validation of required consents
- Family member consent collection

#### CustomerRegistrationService
- Consent storage during registration completion
- Session linking for audit trail
- Parent-child consent relationship handling

## Security & Compliance

### Audit Trail
- **IP Address**: Captured for each consent action
- **User Agent**: Browser/device information stored
- **Timestamp**: Exact time of consent with timezone
- **Session Linking**: All consents from single registration linked
- **Parent Tracking**: Clear record of who provided consent for children

### Data Protection
- **Soft Deletes**: Consent history preserved for legal compliance
- **UUIDs**: External reference capability without exposing internal IDs
- **Foreign Key Constraints**: Data integrity maintained
- **Unique Constraints**: Prevents duplicate consent records

### Legal Compliance
- **Granular Consent**: Individual consent per type per user
- **Withdrawal Capability**: Architecture supports future consent withdrawal
- **Parent Authority**: Proper tracking of parental consent for minors
- **Version Control**: Ready for future consent versioning requirements

## Configuration

### Organization Setup
1. **Default Consents**: Automatically created for each organization
2. **Activation**: Organizations can activate/deactivate consent types
3. **Requirements**: Organizations can mark consents as required/optional
4. **Ordering**: Display order can be customized per organization

### Customization Options
- **Consent Names**: Organization-specific titles
- **Descriptions**: Custom consent text per organization
- **Active Status**: Enable/disable specific consent types
- **Required Status**: Mark consents as mandatory or optional
- **Display Order**: Control presentation sequence

## Future Enhancements

### Phase 1: Admin Interface
- Organization admin panel for consent management
- Bulk consent activation/deactivation
- Custom consent text editing
- Consent analytics and reporting

### Phase 2: Advanced Features
- Consent versioning and history
- Conditional consents based on user attributes
- Multi-language consent support
- Consent withdrawal interface

### Phase 3: Integration Expansion
- API endpoints for external consent management
- Webhook notifications for consent changes
- Advanced reporting and analytics
- Legal compliance reporting tools

## Implementation Status

### Completed
- ✅ Database schema design
- ✅ Model architecture planning
- ✅ Registration flow integration design
- ✅ Default consent definitions
- ✅ Family consent logic design

### In Progress
- 🔄 Model implementation
- 🔄 Service layer development
- 🔄 Registration wizard integration

### Pending
- ⏳ Migration creation
- ⏳ Seeder implementation
- ⏳ UI component development
- ⏳ Testing and validation
- ⏳ Admin interface (future)

## Testing Strategy

### Unit Tests
- Model relationships and scopes
- Service layer business logic
- Validation rules and constraints
- UUID generation and uniqueness

### Integration Tests
- Registration flow with consents
- Family consent collection
- Database constraint enforcement
- Audit trail accuracy

### User Acceptance Tests
- Complete registration scenarios
- Required vs optional consent handling
- Parent consent for children
- Error handling and validation messages

---

*This feature provides a robust foundation for consent management while maintaining simplicity and legal compliance. The two-table architecture ensures scalability and flexibility for future organizational needs.*

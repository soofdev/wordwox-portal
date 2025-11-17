# Customer Registration Validation Rules

## Individual Registration

### Primary User Validation

#### **Full Name**
- **Always required**
- Must have at least two parts: first and last name
- Must be unique within the organization
- Automatically trim leading and trailing spaces and double spaces

**Examples:**
- ✅ Valid: "John Smith", "Mary Jane Watson", "José María García"
- ❌ Invalid: "John" (single name), "  John   Smith  " (before trimming)
- ❌ Invalid: Empty string, only spaces
- ❌ Invalid: "John Smith" when another user already has this exact name in the org

#### **Login Method Selection**
- Login method is always required
- **Login by Email**: Email address is required
- **Login by SMS**: Phone number is required

**Examples:**
- User selects "Login by Email" → email field becomes required, phone optional
- User selects "Login by SMS" → phone field becomes required, email optional
- ❌ Invalid: No selection made → shows "Login method is required" error

#### **Phone Number**
- Must be unique when required and populated
- Must be valid international format when populated
- Uses Google libphonenumber library for validation
- Supports mobile, landline, and fixed-line-or-mobile types

**Examples:**
- ✅ Valid formats: "+1234567890", "+44 20 7946 0958", "+52 55 1234 5678"
- ❌ Invalid formats: "1234567890" (no country code), "abc123", "123"
- ❌ Invalid: "+1234567890" when another user already has this number
- ✅ Valid: Same number can be used if other user is archived (but not soft-deleted)
- ✅ Valid: Any valid number can be used if Login by Email is selected 

#### **Email Address**
- Must be unique when required and populated
- Must be valid email format when populated
- Standard Laravel email validation rules apply

**Examples:**
- ✅ Valid: "john@example.com", "mary.jane+gym@company.co.uk"
- ❌ Invalid: "notanemail", "john@", "@example.com", "john..doe@example.com"
- ❌ Invalid: "john@example.com" when another user already has this email
- ✅ Valid: Same email can be used if other user is archived (but not soft-deleted)
- ✅ Valid: Any valid email can be used if Login by SMS is selected 
---

## Family Registration

### Primary User Validation
- Same validation rules as Individual Registration

**Example Scenario:**
```
Primary User:
- Name: "Sarah Johnson" ✅
- Login Method: Email ✅
- Email: "sarah@example.com" ✅ (required, unique)
- Phone: "+1234567890" ✅ (optional, but unique if provided)
```

### Spouse Validation
- **Optional**: All spouse fields are optional
- **If spouse name is provided**: Other required fields become mandatory
- **Login Method**: Same options as primary user (Email or SMS)
- **Phone/Email**: Same uniqueness and format validation as primary user (must not be the same with the already populated primary user email/phone)
- **Uniqueness**: Must be unique across organization and family

**Example Scenarios:**

**Scenario 1: No Spouse Information**
```
Spouse:
- Name: "" (empty) ✅
- All other fields: ignored/optional ✅
```

**Scenario 2: Complete Spouse Information**
```
Spouse:
- Name: "Michael Johnson" ✅ (required when provided)
- Login Method: SMS ✅
- Phone: "+1987654321" ✅ (required, unique across org and family)
- Email: "mike@example.com" ✅ (optional, but unique if provided)
```

**Scenario 3: Invalid Spouse Setup - Same as Primary User**
```
Spouse:
- Name: "Michael Johnson" ✅
- Login Method: Email ✅
- Email: "sarah@example.com" ❌ (same as primary user - not unique within family)
- Phone: "+1234567890" ❌ (same as primary user - not unique within family)
```

**Scenario 4: Invalid Spouse Setup - Same as Organization User**
```
Existing Org User: "existing@example.com", "+1555123456"
Spouse:
- Name: "Michael Johnson" ✅
- Login Method: Email ✅
- Email: "existing@example.com" ❌ (already exists in organization)
- Phone: "+1555123456" ❌ (already exists in organization)
```

**Scenario 5: Valid Spouse with Different Login Method**
```
Primary User: Email login with "sarah@example.com", phone "+1234567890" (optional)
Spouse:
- Name: "Michael Johnson" ✅
- Login Method: SMS ✅
- Phone: "+1987654321" ✅ (unique across org and family)
- Email: "sarah@example.com" ✅ (duplicate allowed - not required for SMS login)
```

### Children Validation
- **Optional**: Up to 2 children can be added
- **If child name is provided**: Basic required fields become mandatory
- **No login credentials**: Children don't have phone/email validation for login
- **Age validation**: Must be between 0-18 years old
- **School information**: Required if child is added

**Example Scenarios:**

**Scenario 1: No Children**
```
Child 1: (all fields empty) ✅
Child 2: (all fields empty) ✅
```

**Scenario 2: One Child Added**
```
Child 1:
- Name: "Emma Johnson" ✅ (required when provided)
- Date of Birth: "2010-05-15" ✅ (age 15 - valid range)
- School: "Lincoln Middle School" ✅ (required when child added)
- Grade: "9th Grade" ✅
```

**Scenario 3: Invalid Child Information**
```
Child 1:
- Name: "Emma Johnson" ✅
- Date of Birth: "2000-05-15" ❌ (age 25 - outside 0-18 range)
- School: "" ❌ (required when child name provided)
```

---

## Validation Behavior

### **Real-time Validation**
- Validates on field blur events
- Always validate phone number and email fields regardless of login method so that we can update the form if the login method is changed
- No validation errors shown when no login method is selected

**Example Flow:**
```
1. User enters email "john@example.com" → validates format ✅
2. User enters phone "+1234567890" → validates format ✅
3. User tries to proceed without selecting login method → shows "Login method is required" ❌
4. User selects "Login by Email" → email becomes required ✅, login method error clears
5. User clears email field → shows required error ❌
6. User switches to "Login by SMS" → email error clears, phone becomes required
```

### **Login Method Change Behavior**
- **Immediate revalidation**: When login method changes, validation errors are cleared for both fields
- **Selective uniqueness validation**: Only the field required by the login method is validated for uniqueness:
  - **Email Login**: Only email uniqueness is validated, phone uniqueness is NOT validated
  - **SMS Login**: Only phone uniqueness is validated, email uniqueness is NOT validated
- **Error clearing**: All previous validation errors are cleared when switching login methods
- **Field relevance**: Uniqueness validation only applies to the field required for the selected login method

**Example Scenarios:**

**Scenario 1: Email Login with Duplicate Phone (Should Pass)**
```
Existing User: phone "+1234567890", email "existing@example.com"
New Registration:
- Login Method: Email ✅
- Email: "new@example.com" ✅ (unique - this is what matters)
- Phone: "+1234567890" ✅ (duplicate, but ignored for Email login)
Result: Registration allowed ✅
```

**Scenario 2: SMS Login with Duplicate Email (Should Pass)**
```
Existing User: phone "+1234567890", email "existing@example.com"
New Registration:
- Login Method: SMS ✅
- Phone: "+1987654321" ✅ (unique - this is what matters)
- Email: "existing@example.com" ✅ (duplicate, but ignored for SMS login)
Result: Registration allowed ✅
```

**Scenario 3: Email Login with Duplicate Email (Should Fail)**
```
Existing User: email "john@example.com"
New Registration:
- Login Method: Email ❌
- Email: "john@example.com" ❌ (duplicate and required for login)
Result: Registration blocked ❌
```

**Scenario 4: Method Switch Clears Errors**
```
1. User selects "Email Login"
2. Enters duplicate email → shows uniqueness error ❌
3. User switches to "SMS Login" → error clears immediately ✅
4. Email field no longer validated for uniqueness ✅
``` 

### **Uniqueness Scope**
- All validation is scoped to the organization level
- Includes soft-deleted users in uniqueness checks
- Excludes archived users from uniqueness checks

**Example Database States:**
```
Organization Users:
- Active User: "john@example.com" → blocks duplicates ❌
- Soft-deleted User: "jane@example.com" → blocks duplicates ❌
- Archived User: "archived@example.com" → allows duplicates ✅
```

### **Error Handling**
- User-friendly error messages
- Immediate visual feedback with field highlighting
- Step-by-step validation prevents progression with invalid data

**Example Error Messages:**
```
Login Method:
- "Login method is required"

Full Name:
- "Full name is required"
- "Full name must include first and last name"
- "This name is already registered in your organization"

Email:
- "Please enter a valid email address"
- "This email address is already registered"
- "Email address is required for email login"
- "This email address is already used by another family member"

Phone:
- "Please enter a valid phone number"
- "This phone number is already registered"
- "Phone number is required for SMS login"
- "This phone number is already used by another family member"

Children:
- "Child must be between 0 and 18 years old"
- "School information is required when adding a child"
```
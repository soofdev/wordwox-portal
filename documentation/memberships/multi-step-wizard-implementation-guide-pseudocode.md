# Multi-Step Wizard Implementation Guide

## Overview

This document provides a comprehensive guide for implementing multi-step wizard forms using a reactive component-based approach without external packages. This approach was successfully used to create a 5-step membership creation wizard and can be adapted for any multi-step form requirement.

## 🎯 **Why Pure Component-Based Approach Over Packages?**

- **✅ Full Control**: Complete control over state management and UI
- **✅ No Dependencies**: No external package dependencies to maintain
- **✅ Business Logic Integration**: Seamless integration with service classes and permissions
- **✅ Automatic Persistence**: Built-in state persistence across steps
- **✅ Mobile Optimized**: Mobile-first responsive design
- **✅ Multi-Language Support**: Built-in internationalization support
- **✅ Easy Debugging**: Clear, straightforward implementation

## 🏗️ **Architecture Overview**

### Core Concept
- **Single Component**: One reactive component manages all steps with business logic
- **Conditional Rendering**: Steps rendered based on current step number
- **State Persistence**: Automatic state persistence across steps
- **Step Validation**: Individual validation for each step with business rules
- **Progress Indicator**: Visual progress tracking with completion states
- **Service Layer Integration**: Integration with business services and permissions
- **Real-time Updates**: Live calculations and dynamic data loading

### File Structure
```
COMPONENT_DIRECTORY/
├── WizardComponent           # Main wizard component class
├── wizard-template          # Main wizard template
├── route-definition         # Route configuration
```

## 📝 **Step 1: Create the Wizard Component**

### Component Structure - Membership Wizard Example

```
CLASS MembershipWizard EXTENDS ReactiveComponent:
    // Step Management Properties
    currentStep = 1
    totalSteps = 5

    // Step 1: Customer Selection
    customerId = null
    customerSearch = ""
    customerSearchResults = []
    customerContext = null

    // Step 2: Plan Selection  
    planId = null
    startDate = null
    endDate = null
    price = 0
    selectedPlan = null

    // Step 3: Discount
    discountId = null
    discountOptions = []
    invoiceTotal = 0

    // Step 4: Payment
    paymentMethod = null
    salesRepId = null
    availablePaymentMethods = []

    // Step 5: Review
    notes = ""
    isLoading = false
    showSuccess = false
    createdMembership = null

    FUNCTION initialize():
        // Check user permissions
        IF NOT user.hasPermission('create memberships'):
            THROW UnauthorizedException
        
        // Set defaults
        startDate = getCurrentDate()
        salesRepId = getCurrentUser().id
        
        // Handle URL parameters for pre-population
        IF urlParameters.contains('customerId'):
            customerId = urlParameters.get('customerId')
            loadCustomerContext()
            currentStep = 2
            loadStepData()

    FUNCTION nextStep():
        validateCurrentStep()
        
        IF currentStep < totalSteps:
            currentStep = currentStep + 1
            loadStepData()

    FUNCTION previousStep():
        IF currentStep > 1:
            currentStep = currentStep - 1

    FUNCTION goToStep(stepNumber):
        IF stepNumber >= 1 AND stepNumber <= totalSteps:
            currentStep = stepNumber
            loadStepData()

    FUNCTION validateCurrentStep():
        SWITCH currentStep:
            CASE 1:
                VALIDATE customerId IS_NOT_NULL AND EXISTS_IN_DATABASE
            CASE 2:
                VALIDATE planId IS_NOT_NULL AND EXISTS_IN_DATABASE
                VALIDATE startDate IS_VALID_DATE
            CASE 3:
                IF discountId IS_NOT_NULL:
                    VALIDATE discountId EXISTS_IN_DATABASE
            CASE 4:
                VALIDATE paymentMethod IN availablePaymentMethods
                VALIDATE salesRepId IS_NOT_NULL AND EXISTS_IN_DATABASE

    FUNCTION loadStepData():
        SWITCH currentStep:
            CASE 2:
                IF customerId IS_NOT_NULL:
                    loadAvailablePlans()
            CASE 3:
                IF customerId IS_NOT_NULL:
                    loadDiscountOptions()
                    calculateTotal()
            CASE 4:
                loadPaymentMethods()
                calculateTotal()

    FUNCTION submitForm():
        validateCurrentStep()
        isLoading = true
        
        data = prepareSubmissionData()
        createdMembership = MembershipService.create(data, notes)
        
        showSuccess = true
        isLoading = false

    FUNCTION prepareSubmissionData():
        RETURN {
            customerId: customerId,
            planId: planId,
            startDate: startDate,
            endDate: endDate,
            discountId: discountId,
            invoiceTotal: invoiceTotal,
            paymentMethod: paymentMethod,
            soldBy: salesRepId,
            createdBy: getCurrentUser().id
        }

    FUNCTION resetWizard():
        RESET_ALL_PROPERTIES()
        currentStep = 1
        startDate = getCurrentDate()
        salesRepId = getCurrentUser().id
```

## 🔧 **Business Logic Implementation**

### Permission-Based Authorization
```
FUNCTION checkPermissions():
    IF NOT currentUser.hasPermission('create memberships'):
        THROW UnauthorizedException('Access denied')
```

### Customer Search with Live Results
```
FUNCTION onCustomerSearchChanged():
    IF searchText.length >= 2:
        customerSearchResults = DATABASE.query({
            table: 'customers',
            where: [
                organizationId = currentUser.organizationId,
                isDeleted = false,
                (fullName CONTAINS searchText OR 
                 email CONTAINS searchText OR 
                 phoneNumber CONTAINS searchText)
            ],
            limit: 10
        })
        
        // Transform results for display
        customerSearchResults = customerSearchResults.map(customer => {
            id: customer.id,
            displayText: customer.fullName + " - " + (customer.email OR customer.phone),
            fullName: customer.fullName,
            email: customer.email,
            phone: customer.phone
        })
    ELSE:
        customerSearchResults = []
```

### Customer Context Loading
```
FUNCTION loadCustomerContext():
    IF customerId IS NULL:
        customerContext = null
        RETURN
    
    customer = DATABASE.findById('customers', customerId)
    IF customer IS NULL:
        customerContext = null
        RETURN
    
    memberType = DiscountService.getMemberType(customerId)
    lastMembership = DATABASE.query({
        table: 'memberships',
        where: [customerId = customerId, isDeleted = false],
        orderBy: 'startDate DESC',
        limit: 1
    })
    
    customerContext = {
        customer: customer,
        memberType: memberType,
        memberTypeLabel: memberType == 'new_member' ? 'New Member' : 'Renewing Member',
        lastMembership: lastMembership,
        totalMemberships: DATABASE.count('memberships', [customerId = customerId, isDeleted = false])
    }
```

### Dynamic End Date Calculation
```
FUNCTION calculateEndDate():
    IF selectedPlan IS NULL OR startDate IS NULL:
        endDate = null
        RETURN
    
    cycleDuration = selectedPlan.cycleDuration OR 1
    cycleUnit = selectedPlan.cycleUnit OR 'month'
    
    SWITCH cycleUnit:
        CASE 'day':
            endDate = startDate.addDays(cycleDuration)
        CASE 'week':
            endDate = startDate.addWeeks(cycleDuration)
        CASE 'month':
            endDate = startDate.addMonths(cycleDuration)
        CASE 'year':
            endDate = startDate.addYears(cycleDuration)
        DEFAULT:
            endDate = startDate.addMonths(1)
```

### Discount System Integration
```
FUNCTION loadDiscountOptions():
    IF customerId IS NULL OR customerContext IS NULL:
        discountOptions = []
        RETURN
    
    TRY:
        staffId = currentUser.id
        memberType = customerContext.memberType
        
        discountOptions = DiscountService.getFilteredDiscountOptions(staffId, memberType)
    CATCH Exception:
        LOG_ERROR('Failed to load discount options')
        discountOptions = []

FUNCTION calculateTotal():
    basePrice = price
    discountAmount = 0
    
    IF discountId IS_NOT_NULL:
        discount = DATABASE.findById('discounts', discountId)
        IF discount IS_NOT_NULL:
            IF discount.unit == 'percent':
                discountAmount = (basePrice * discount.value) / 100
            ELSE:
                discountAmount = discount.value
    
    invoiceTotal = MAX(0, basePrice - discountAmount)
```

### Payment Method Loading with Fallback
```
FUNCTION loadPaymentMethods():
    TRY:
        organizationId = currentUser.organizationId
        availablePaymentMethods = DATABASE.query({
            table: 'payment_methods',
            where: [organizationId = organizationId OR isGlobal = true]
        }).map(method => {
            code: method.code,
            name: method.name,
            shortName: method.shortName,
            icon: method.icon,
            description: method.description,
            isGlobal: method.isGlobal
        })
    CATCH Exception:
        LOG_ERROR('Failed to load payment methods from database')
        // Fallback to default methods
        availablePaymentMethods = [
            {
                code: 'cash',
                name: 'Cash',
                icon: 'money-icon',
                description: 'Cash payment'
            },
            {
                code: 'card', 
                name: 'Card',
                icon: 'card-icon',
                description: 'Credit/Debit card'
            }
        ]
```

## 🎨 **Step 2: Create the Template Structure**

### Mobile-First Template Design

```
TEMPLATE STRUCTURE:
    MAIN_CONTAINER (full-height, flex-column):
        
        HEADER_SECTION:
            TITLE: "New Membership" 
            SUBTITLE: "Create a new membership plan for an existing member"
            CANCEL_LINK: -> redirect to dashboard
        
        IF showSuccess:
            SUCCESS_PANEL:
                MESSAGE: "Membership Created Successfully!"
                DESCRIPTION: "The membership has been created and saved."
                BUTTON: "Create Another Membership" -> resetWizard()
        
        ELSE:
            PROGRESS_INDICATOR_SECTION:
                FOR each step (1 to totalSteps):
                    STEP_INDICATOR:
                        IF step < currentStep: GREEN_CIRCLE with CHECKMARK
                        IF step == currentStep: BLUE_CIRCLE with STEP_NUMBER  
                        IF step > currentStep: GRAY_CIRCLE with STEP_NUMBER
                    STEP_LABEL:
                        CASE 1: "Customer"
                        CASE 2: "Plan"
                        CASE 3: "Discount" 
                        CASE 4: "Payment"
                        CASE 5: "Review"
            
            MOBILE_SUMMARY_SECTION (visible on mobile only):
                COLLAPSIBLE_PANEL:
                    HEADER: "Summary" + current total amount
                    CONTENT (when expanded):
                        - Customer information
                        - Plan details
                        - Price breakdown  
                        - Final total
            
            MAIN_CONTENT_GRID (responsive layout):
                
                LEFT_COLUMN (main content area):
                    CONDITIONAL_STEP_CONTENT:
                        SWITCH currentStep:
                            CASE 1: RENDER customer_selection_template
                            CASE 2: RENDER plan_selection_template
                            CASE 3: RENDER discount_selection_template
                            CASE 4: RENDER payment_selection_template
                            CASE 5: RENDER review_template
                
                RIGHT_COLUMN (persistent sidebar):
                    STICKY_SUMMARY_PANEL:
                        SUMMARY_TITLE: "Summary"
                        
                        CUSTOMER_SECTION:
                            LABEL: "Customer"
                            IF customerContext exists:
                                DISPLAY: customer.name + customer.email + memberType badge
                            ELSE:
                                DISPLAY: "--"
                        
                        PLAN_SECTION:
                            LABEL: "Plan"  
                            IF selectedPlan exists:
                                DISPLAY: plan.name + duration + dateRange
                            ELSE:
                                DISPLAY: "--"
                        
                        PRICING_SECTION:
                            LABEL: "Price"
                            IF price > 0:
                                DISPLAY: basePrice
                                IF discount applied:
                                    DISPLAY: discountAmount (negative)
                                    DISPLAY: discountName
                            ELSE:
                                DISPLAY: "--"
                        
                        IF currentStep >= 4:
                            PAYMENT_SECTION:
                                LABEL: "Payment"
                                IF paymentMethod selected:
                                    DISPLAY: paymentMethod + salesRep
                                ELSE:
                                    DISPLAY: "--"
                        
                        INVOICE_TOTAL_SECTION:
                            LABEL: "Invoice Total"
                            IF invoiceTotal > 0:
                                DISPLAY: formattedTotal (large, emphasized)
                            ELSE:
                                DISPLAY: "$0.00"
            
            STICKY_FOOTER_NAVIGATION (fixed at bottom):
                LEFT_SIDE:
                    IF currentStep > 1:
                        PREVIOUS_BUTTON -> previousStep()
                
                RIGHT_SIDE:
                    IF currentStep < totalSteps:
                        NEXT_BUTTON -> nextStep()
                    ELSE:
                        SUBMIT_BUTTON -> submitForm()
                            WITH_LOADING_STATE: "Processing..." + spinner
            
            BOTTOM_SPACER (prevent content overlap with sticky footer)

STEP_TEMPLATES:

    customer_selection_template:
        TITLE: "Step 1: Select Customer"
        
        IF customerContext exists:
            SELECTED_CUSTOMER_PANEL:
                CUSTOMER_INFO: name, email, phone
                MEMBER_TYPE_BADGE: "New Member" OR "Renewing Member"  
                MEMBERSHIP_COUNT: totalMemberships
                CLEAR_BUTTON -> clearCustomer()
        ELSE:
            SEARCH_SECTION:
                SEARCH_INPUT:
                    LABEL: "Search for a member"
                    INPUT: debounced -> onCustomerSearchChanged()
                    PLACEHOLDER: "Search by name, email, or phone..."
                
                IF customerSearchResults.length > 0:
                    RESULTS_LIST:
                        FOR each result:
                            RESULT_ITEM: name + contactInfo -> selectCustomer(id)
                ELSE IF searchText.length >= 2:
                    NO_RESULTS_MESSAGE: "No members found matching [searchText]"

    plan_selection_template:
        TITLE: "Step 2: Select Plan"
        
        PLAN_DROPDOWN:
            LABEL: "Membership Plan"
            OPTIONS: availablePlans with prices
            ON_CHANGE -> onPlanChanged()
        
        PRICE_DISPLAY (read-only):
            LABEL: "Plan Price"
            VALUE: selectedPlan.price OR "Select a plan to see price"
            NOTE: "Price is automatically taken from the selected plan"
        
        START_DATE_INPUT:
            LABEL: "Start Date"  
            INPUT: datePicker -> onStartDateChanged()
        
        IF selectedPlan AND endDate:
            END_DATE_DISPLAY (read-only):
                LABEL: "End Date"
                VALUE: calculatedEndDate
                NOTE: "End date is automatically calculated based on plan duration"
        
        IF selectedPlan:
            PLAN_DETAILS_PANEL:
                PLAN_NAME: selectedPlan.name
                DESCRIPTION: selectedPlan.description
                DURATION: selectedPlan.cycleDuration + selectedPlan.cycleUnit
                PRICE: formattedPrice

    discount_selection_template:
        TITLE: "Step 3: Apply Discount (Optional)"
        
        IF discountOptions.length > 0:
            DISCOUNT_DROPDOWN:
                LABEL: "Available Discounts"
                OPTIONS: "No discount" + discountOptions with values
                ON_CHANGE -> onDiscountChanged()
        ELSE:
            NO_DISCOUNTS_MESSAGE: contextual message based on customerType
        
        PRICE_SUMMARY_PANEL:
            BASE_PRICE_LINE: "Plan Price: " + basePrice
            IF discount selected:
                DISCOUNT_LINE: "Discount: -" + discountAmount (green)
            TOTAL_LINE: "Total: " + invoiceTotal (large, bold)

    payment_selection_template:
        TITLE: "Step 4: Payment Details"
        
        PAYMENT_METHOD_GRID:
            LABEL: "Payment Method"
            FOR each availablePaymentMethod:
                METHOD_CARD:
                    ICON: method.icon
                    NAME: method.shortName
                    SELECTED_INDICATOR: if method == selectedPaymentMethod
                    ON_CLICK -> selectPaymentMethod(method.code)
        
        SALES_REP_DROPDOWN:
            LABEL: "Sales Representative"
            OPTIONS: staffMembers
            DEFAULT: currentUser
        
        PAYMENT_SUMMARY_PANEL:
            TOTAL_AMOUNT: invoiceTotal (large, emphasized)

    review_template:
        TITLE: "Step 5: Review & Submit"
        
        REVIEW_PANELS:
            CUSTOMER_REVIEW_PANEL:
                TITLE: "Customer"
                CONTENT: customerName, email, memberType
            
            PLAN_REVIEW_PANEL:
                TITLE: "Plan"
                CONTENT: planName, dateRange, duration
            
            PAYMENT_REVIEW_PANEL:
                TITLE: "Payment"
                CONTENT: paymentMethod, totalAmount
        
        NOTES_SECTION:
            LABEL: "Additional Notes (Optional)"
            TEXTAREA -> bind to notes
        
        IF validationErrors exist:
            ERROR_DISPLAY: validationMessages
```

## 🛣️ **Step 3: Add Route**

```
ROUTE CONFIGURATION:
    PATH: '/memberships/create-wizard'
    COMPONENT: MembershipWizard
    NAME: 'memberships.create.wizard'
    MIDDLEWARE: ['auth', 'verified']
```

## 🔧 **Advanced Features**

### URL Parameter Integration
```
FUNCTION initialize():
    // Pre-populate customer if customerId parameter is provided
    IF urlParameters.contains('customerId'):
        customerId = urlParameters.get('customerId')
        loadCustomerContext()
        // Skip to step 2 since customer is already selected
        currentStep = 2
        loadStepData()
```

### Direct Step Navigation
```
FUNCTION goToStep(stepNumber):
    IF stepNumber >= 1 AND stepNumber <= totalSteps:
        currentStep = stepNumber
        loadStepData()
```

### Step-Specific Data Loading
```
FUNCTION loadStepData():
    SWITCH currentStep:
        CASE 2:
            IF customerId IS_NOT_NULL:
                loadAvailablePlans()
        CASE 3:
            IF customerId IS_NOT_NULL:
                loadDiscountOptions()
                calculateTotal()
        CASE 4:
            loadPaymentMethods()
            calculateTotal()
```

### Real-Time Updates with Event Listeners
```
FUNCTION onPlanChanged():
    IF planId IS_NOT_NULL:
        plan = DATABASE.findById('plans', planId)
        IF plan IS_NOT_NULL:
            selectedPlan = plan
            // Price is always taken from the plan, never from user input
            price = plan.price
            calculateEndDate()
            calculateTotal() // Recalculate total when plan changes

FUNCTION onStartDateChanged():
    calculateEndDate()

FUNCTION onDiscountChanged():
    calculateTotal()
```

### Multi-Language Support
```
TEMPLATE INTERNATIONALIZATION:
    // Use translation function for all text
    TITLE: TRANSLATE('New Membership')
    SUBTITLE: TRANSLATE('Create a new membership plan for an existing member')
    STEP_LABELS: TRANSLATE('Customer'), TRANSLATE('Plan'), etc.
    
    // Support RTL languages with appropriate styling
    INPUT_STYLING: include RTL text alignment when needed
```

### Computed Properties for Complex Data
```
COMPUTED_PROPERTY selectedSalesRep():
    IF salesRepId IS_NULL:
        RETURN null
    RETURN DATABASE.findById('staff', salesRepId)

COMPUTED_PROPERTY selectedDiscount():
    IF discountId IS_NULL:
        RETURN null
    RETURN DATABASE.findById('discounts', discountId)

COMPUTED_PROPERTY discountAmount():
    IF discountId IS_NULL OR price == 0:
        RETURN 0
    
    discount = selectedDiscount
    IF discount IS_NULL:
        RETURN 0
    
    RETURN discount.unit == 'percent' 
        ? (price * discount.value) / 100 
        : discount.value
```

## 🎨 **UI/UX Best Practices**

### 1. **Advanced Progress Indication**
- Visual step indicators with checkmarks for completed steps
- Color-coded status (green for completed, blue for current, gray for future)
- Step labels with descriptions
- Multi-language support

### 2. **Mobile-First Responsive Design**
- Complex grid layout that adapts from single column to 5-column layout
- Collapsible mobile summary with interactive elements
- Sticky footer navigation for mobile devices
- Touch-friendly button sizes and spacing

### 3. **Comprehensive Loading States**
- Button loading states with visual indicators
- Disabled states during processing
- Loading text changes

### 4. **Advanced Error Handling**
- Field-level validation errors
- Step-specific validation
- Success state management

### 5. **Persistent Summary Sidebar**
- Real-time updates as user progresses
- Sticky positioning for desktop
- Responsive text sizing
- Visual hierarchy with typography

### 6. **Sticky Footer Navigation**
- Fixed bottom positioning for mobile
- Responsive padding and spacing
- Context-aware button states

## ⚡ **Performance Considerations**

### 1. **Conditional Data Loading**
```
FUNCTION loadStepData():
    // Only load data when entering specific steps
    SWITCH currentStep:
        CASE 2:
            IF customerId IS_NOT_NULL:
                loadAvailablePlans()
        CASE 3:
            IF customerId IS_NOT_NULL:
                loadDiscountOptions()
                calculateTotal()
        CASE 4:
            loadPaymentMethods()
            calculateTotal()
```

### 2. **Strategic Input Binding**
```
INPUT_BINDING_STRATEGIES:
    // Use debounced binding for search inputs
    customerSearch: DEBOUNCED_BINDING(300ms) -> onCustomerSearchChanged()
    
    // Use live binding for fields that trigger calculations
    planId: LIVE_BINDING -> onPlanChanged()
    
    // Use standard binding for simple fields
    notes: STANDARD_BINDING
```

### 3. **Efficient Search Implementation**
```
FUNCTION onCustomerSearchChanged():
    IF searchText.length >= 2:
        customerSearchResults = DATABASE.query({
            table: 'customers',
            where: [
                organizationId = currentUser.organizationId,
                isDeleted = false,
                (fullName CONTAINS searchText OR 
                 email CONTAINS searchText OR 
                 phoneNumber CONTAINS searchText)
            ],
            limit: 10 // Limit results for performance
        })
        // Transform results for display efficiency
        customerSearchResults = customerSearchResults.map(transformForDisplay)
    ELSE:
        customerSearchResults = []
```

### 4. **Error Handling with Fallbacks**
```
FUNCTION loadPaymentMethods():
    TRY:
        organizationId = currentUser.organizationId
        availablePaymentMethods = DATABASE.query({
            table: 'payment_methods',
            where: [organizationId = organizationId OR isGlobal = true]
        })
    CATCH Exception:
        LOG_ERROR('Error loading payment methods: ' + exception.message)
        // Fallback to hardcoded methods if database lookup fails
        availablePaymentMethods = getDefaultPaymentMethods()
```

### 5. **Computed Properties for Expensive Operations**
```
COMPUTED_PROPERTY selectedSalesRep():
    IF salesRepId IS_NULL:
        RETURN null
    RETURN DATABASE.findById('staff', salesRepId)

COMPUTED_PROPERTY selectedDiscount():
    IF discountId IS_NULL:
        RETURN null
    RETURN DATABASE.findById('discounts', discountId)
```

## 🧪 **Testing Strategy**

### Component Testing
```
TEST_CASE wizard_navigation():
    user = CREATE_TEST_USER()
    customer = CREATE_TEST_CUSTOMER(organizationId: user.organizationId)
    
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    ASSERT wizardComponent.currentStep == 1
    
    SET wizardComponent.customerId = customer.id
    CALL wizardComponent.nextStep()
    
    ASSERT wizardComponent.currentStep == 2

TEST_CASE customer_selection_validation():
    user = CREATE_TEST_USER()
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    SET wizardComponent.customerId = null
    CALL wizardComponent.nextStep()
    
    ASSERT wizardComponent.hasErrors(['customerId'])

TEST_CASE member_search_functionality():
    user = CREATE_TEST_USER()
    customer = CREATE_TEST_CUSTOMER(
        organizationId: user.organizationId,
        fullName: 'John Doe',
        email: 'john@example.com'
    )
    
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    SET wizardComponent.customerSearch = 'John'
    ASSERT wizardComponent.customerSearchResults.count == 1
    ASSERT wizardComponent.customerSearchResults[0].fullName == 'John Doe'

TEST_CASE plan_selection_triggers_calculations():
    user = CREATE_TEST_USER()
    customer = CREATE_TEST_CUSTOMER(organizationId: user.organizationId)
    plan = CREATE_TEST_PLAN(organizationId: user.organizationId, price: 100)
    
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    SET wizardComponent.customerId = customer.id
    SET wizardComponent.planId = plan.id
    
    ASSERT wizardComponent.price == 100
    ASSERT wizardComponent.selectedPlan.id == plan.id

TEST_CASE discount_application():
    user = CREATE_TEST_USER()
    customer = CREATE_TEST_CUSTOMER(organizationId: user.organizationId)
    plan = CREATE_TEST_PLAN(price: 100)
    discount = CREATE_TEST_DISCOUNT(unit: 'percent', value: 10)
    
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    SET wizardComponent.customerId = customer.id
    SET wizardComponent.planId = plan.id
    SET wizardComponent.discountId = discount.id
    
    ASSERT wizardComponent.invoiceTotal == 90 // 100 - (100 * 0.1)

TEST_CASE membership_submission():
    user = CREATE_TEST_USER()
    customer = CREATE_TEST_CUSTOMER(organizationId: user.organizationId)
    plan = CREATE_TEST_PLAN(organizationId: user.organizationId)
    
    AUTHENTICATE_AS(user)
    
    wizardComponent = CREATE_COMPONENT(MembershipWizard)
    
    SET wizardComponent.customerId = customer.id
    SET wizardComponent.planId = plan.id
    SET wizardComponent.startDate = getCurrentDate()
    SET wizardComponent.paymentMethod = 'cash'
    SET wizardComponent.salesRepId = user.id
    SET wizardComponent.currentStep = 5
    
    CALL wizardComponent.submitForm()
    
    ASSERT wizardComponent.showSuccess == true
```

### Permission Testing
```
TEST_CASE requires_create_memberships_permission():
    user = CREATE_TEST_USER() // User without permission
    
    AUTHENTICATE_AS(user)
    
    EXPECT_EXCEPTION(UnauthorizedException):
        CREATE_COMPONENT(MembershipWizard)
```

## 🔍 **Debugging Tips**

### 1. **Add Debug Information**
```
FUNCTION debug():
    DEBUG_OUTPUT({
        currentStep: currentStep,
        customerId: customerId,
        planId: planId,
        customerContext: customerContext,
        selectedPlan: selectedPlan,
        discountOptions: discountOptions,
        availablePaymentMethods: availablePaymentMethods,
        invoiceTotal: invoiceTotal
    })
```

### 2. **Log State Changes and Errors**
```
FUNCTION nextStep():
    LOG_INFO('MembershipWizard: Moving to next step', {
        from: currentStep,
        to: currentStep + 1,
        customerId: customerId,
        planId: planId,
        invoiceTotal: invoiceTotal
    })
    
    validateCurrentStep()
    
    IF currentStep < totalSteps:
        currentStep = currentStep + 1
        loadStepData()

FUNCTION loadDiscountOptions():
    IF customerId IS NULL OR customerContext IS NULL:
        discountOptions = []
        RETURN

    TRY:
        staffId = currentUser.id
        memberType = customerContext.memberType
        
        LOG_INFO('Loading discount options', {
            staffId: staffId,
            memberType: memberType,
            customerId: customerId
        })
        
        discountOptions = DiscountService.getFilteredDiscountOptions(staffId, memberType)
    CATCH Exception:
        LOG_ERROR('Error loading discount options: ' + exception.message, {
            customerId: customerId,
            staffId: currentUser.id
        })
        discountOptions = []
```

### 3. **Browser Console Debugging**
```
DEBUGGING_INTERFACE:
    // Add debug event listeners
    ON_COMPONENT_INIT:
        LISTEN_FOR_DEBUG_EVENTS()
    
    // In component, dispatch debug events
    FUNCTION dispatchDebugInfo():
        EMIT_EVENT('debug-wizard', {
            currentStep: currentStep,
            customerContext: customerContext,
            invoiceTotal: invoiceTotal
        })
    
    // Add debug panel in development mode
    IF DEVELOPMENT_MODE:
        RENDER_DEBUG_PANEL:
            DISPLAY: "Debug: Step " + currentStep + "/" + totalSteps
            IF customerContext:
                DISPLAY: "Customer: " + customerContext.customer.name
            IF invoiceTotal > 0:
                DISPLAY: "Total: $" + formatCurrency(invoiceTotal)
```

## 📋 **Implementation Checklist**

### Core Implementation
- [x] Create reactive wizard component with 5-step management
- [x] Define business-specific form fields as component properties
- [x] Implement step-by-step validation with business rules
- [x] Create mobile-first responsive template
- [x] Add advanced progress indicator with checkmarks
- [x] Implement conditional rendering for each step
- [x] Add comprehensive loading states and error handling
- [x] Create route with proper middleware protection

### Business Logic Integration
- [x] Permission-based authorization ('create memberships')
- [x] Customer search with live results and debouncing
- [x] Customer context loading with member type detection
- [x] Dynamic plan selection with automatic price calculation
- [x] End date calculation based on plan duration and cycle
- [x] Discount system integration with permission filtering
- [x] Payment method loading with database fallbacks
- [x] Real-time total calculation with discount application
- [x] Service layer integration for membership creation

### Advanced Features
- [x] URL parameter integration for pre-population
- [x] Direct step navigation with goToStep() method
- [x] Multi-language support with translation functions
- [x] Multi-directional language support with appropriate styling
- [x] Computed properties for complex data relationships
- [x] Interactive element integration for mobile summary collapsing
- [x] Persistent sidebar summary with real-time updates
- [x] Sticky footer navigation for mobile devices

### UI/UX Features
- [x] Mobile-first responsive design (1-5 column grid)
- [x] Advanced progress indicators with visual states
- [x] Collapsible mobile summary section
- [x] Touch-friendly button sizes and spacing
- [x] Dark mode support throughout
- [x] Comprehensive loading and success states
- [x] Field-level validation error display
- [x] Graceful error handling with fallbacks

### Performance & Testing
- [x] Conditional data loading per step
- [x] Strategic input binding strategies
- [x] Efficient search with query optimization
- [x] Error handling with fallback mechanisms
- [x] Computed properties for expensive operations
- [x] Comprehensive test coverage
- [x] Permission-based access control testing

### Documentation & Deployment
- [x] Complete implementation documentation
- [x] Business logic explanation
- [x] UI/UX best practices guide
- [x] Performance considerations
- [x] Testing strategy and examples
- [x] Debugging tips and tools

## 🎯 **Benefits of This Implementation**

1. **Business-Focused**: Tailored specifically for membership creation with real business logic
2. **Permission-Aware**: Integrated with role-based access control system
3. **Mobile-Optimized**: Sophisticated responsive design with mobile-first approach
4. **Multi-Language**: Full internationalization support with multi-directional language compatibility
5. **Service Integration**: Seamless integration with business service classes
6. **Error Resilient**: Comprehensive error handling with graceful fallbacks
7. **Performance Optimized**: Strategic data loading and efficient query patterns
8. **Highly Testable**: Comprehensive test coverage with realistic business scenarios
9. **Maintainable**: Clean architecture with clear separation of concerns
10. **Extensible**: Easy to adapt for other multi-step business processes

This MembershipWizard implementation demonstrates how to build sophisticated, business-focused multi-step forms using reactive component architecture while maintaining excellent user experience across all device types.

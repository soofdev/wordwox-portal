<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Customer Registration Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for the public customer 
    | registration wizard that anonymous users access via public URLs.
    | This is separate from the staff interface translations.
    |
    */

    // === LAYOUT & COMMON ===
    'Customer Registration' => 'Customer Registration',
    'Secure Registration' => 'Secure Registration',
    'All rights reserved.' => 'All rights reserved.',
    'Secure customer registration system' => 'Secure customer registration system',
    'Choose your preferred language' => 'Choose your preferred language',

    // === LANGUAGE SWITCHER ===
    'language_switcher' => [
        'choose_language' => 'Choose your preferred language',
    ],

    // === REGISTRATION WIZARD ===
    'wizard' => [
        'title' => 'Membership Registration',
        'welcome' => 'Welcome to Wodworx',
        'welcome_org' => 'Welcome to :org_name',
        
        // Common elements
        'required_field' => 'Required field',
        'optional_field' => 'Optional',
        'continue' => 'Continue',
        'back' => 'Back',
        'next' => 'Next',
        'submit' => 'Submit Registration',
        'loading' => 'Processing...',
        'secure_notice' => 'All information is securely encrypted and protected',
        
        // Step 0: Registration Type Selection
        'type_selection' => [
            'title' => 'Choose Your Registration Type',
            'subtitle' => 'Please select the type of membership registration',
            'subtitle_org' => 'Please select your registration type to get started',
            
            'individual' => [
                'title' => 'Individual Registration',
                'description' => 'Register yourself as an individual member. Perfect for single memberships.',
                'cta' => 'Quick and simple process →',
            ],
            
            'family' => [
                'title' => 'Family Registration',
                'description' => 'Register your family including spouse and children. Comprehensive family membership setup.',
                'cta' => 'Complete family setup →',
            ],
        ],
        
        // Step Progress Indicators
        'progress' => [
            'individual_steps' => [
                'your_info' => 'Your Information',
                'your_info_desc' => 'Personal details',
                'emergency_contact' => 'Emergency Contact',
                'emergency_contact_desc' => 'Safety information',
                'review' => 'Review & Submit',
                'review_desc' => 'Confirm details',
            ],
            
            'family_steps' => [
                'your_info' => 'Your Information',
                'your_info_desc' => 'Primary member',
                'spouse_info' => 'Spouse Information',
                'spouse_info_desc' => 'Partner details',
                'child1_info' => 'Child 1 Information',
                'child1_info_desc' => 'First child',
                'child2_info' => 'Child 2 Information',
                'child2_info_desc' => 'Second child',
                'review' => 'Review & Submit',
                'review_desc' => 'Confirm all details',
            ],
        ],
        
        // Step 1: Primary Information
        'primary_info' => [
            'title' => 'Your Information',
            'subtitle' => 'Please provide your personal details for registration.',
            
            'full_name' => 'Full Name',
            'full_name_placeholder' => 'Enter your full name',
            
            'login_method' => 'Login Method',
            'login_method_email' => 'Email',
            'login_method_email_desc' => 'Log in using your email address',
            'login_method_sms' => 'SMS',
            'login_method_sms_desc' => 'Log in using text messages to your phone',
            'login_method_notice_email' => 'You will log in using your email address',
            'login_method_notice_sms' => 'SMS login is not available at this time',
            
            'phone_country' => 'Country',
            'phone_number' => 'Phone Number',
            'phone_number_placeholder' => 'Enter your phone number',
            'phone_number_note' => 'Enter phone number without country code',
            'country' => 'Country',
            
            'email' => 'Email Address',
            'email_placeholder' => 'Enter your email address',
            
            'nationality' => 'Nationality',
            'nationality_placeholder' => 'Enter your nationality',
            
            'national_id' => 'National ID',
            'national_id_placeholder' => 'Enter your national ID',
            'national_id_optional' => 'National ID (Optional)',
            
            'date_of_birth' => 'Date of Birth',
            'date_of_birth_placeholder' => 'dd/mm/yyyy',
            'date_of_birth_optional' => 'Date of Birth (Optional)',
            
            'gender' => 'Gender',
            'gender_male' => 'Male',
            'gender_female' => 'Female',
            'gender_select' => 'Select gender',
            'gender_optional' => 'Gender (Optional)',
            
            'employer' => 'Employer',
            'employer_placeholder' => 'Enter your employer name',
            'employer_optional' => 'Employer Name (Optional)',
            
            'address' => 'Address',
            'address_placeholder' => 'Enter your home address',
            'address_optional' => 'Home Address (Optional)',
        ],
        
        // Step 2: Emergency Contact (Individual)
        'emergency_contact' => [
            'title' => 'Emergency Contact Information',
            'subtitle' => 'Please provide emergency contact details (all fields are optional)',
            
            'name' => 'Emergency Contact Name',
            'name_placeholder' => 'Full name of emergency contact',
            
            'email' => 'Emergency Contact Email',
            'email_placeholder' => 'email@example.com',
            
            'phone' => 'Emergency Contact Phone',
            'phone_placeholder' => 'Phone number',
            
            'relationship' => 'Relationship to You',
            'relationship_placeholder' => 'e.g., Spouse, Parent, Sibling, Friend',
            
            'note_title' => 'Note',
            'note_text' => 'Emergency contact information is optional but recommended for safety purposes. This person will be contacted in case of an emergency during your gym activities.',
            
            'note' => 'Emergency contact information is optional but recommended for safety purposes. This person will be contacted in case of an emergency during your gym activities.',
        ],
        
        // Step 2: Spouse Information (Family)
        'spouse_info' => [
            'title' => 'Spouse Information',
            'subtitle' => 'Add your spouse to the family membership (optional).',
            
            'full_name' => 'Spouse Full Name',
            'full_name_placeholder' => 'Enter spouse\'s full name',
            
            'login_method' => 'Login Method',
            'login_method_email' => 'Email',
            'login_method_sms' => 'SMS',
            'login_method_email_desc' => 'Log in using email address',
            'login_method_sms_desc' => 'Log in using text messages to phone',
            'login_method_notice_email' => 'Spouse will log in using email address',
            
            'phone_country' => 'Country',
            'phone_number' => 'Phone Number',
            'phone_number_placeholder' => 'Enter spouse\'s phone number',
            'phone_number_note' => 'Must be different from primary member\'s phone',
            
            'email' => 'Email Address',
            'email_placeholder' => 'Enter spouse\'s email address',
            
            'nationality' => 'Nationality',
            'nationality_placeholder' => 'Select spouse\'s nationality',
            'date_of_birth' => 'Date of Birth',
            'employer' => 'Employer Name',
            'employer_placeholder' => 'Enter spouse\'s employer name',
            
            'optional_step' => 'Optional Step',
            'skip_message' => 'Leave the name field empty to skip adding a spouse to your family membership.',
            'continue_message' => 'You can continue without filling all spouse details, but the name is required if adding a spouse.',
        ],
        
        // Step 3 & 4: Child Information
        'child_info' => [
            'title' => 'Child :number Information',
            'subtitle' => 'Add your child to the family membership (optional).',
            'title_child1' => 'Child 1 Information',
            'title_child2' => 'Child 2 Information',
            'subtitle_child1' => 'Add your first child to the family membership (optional).',
            'subtitle_child2' => 'Add your second child to the family membership (optional).',
            
            'full_name' => 'Child\'s Full Name',
            'full_name_placeholder' => 'Enter child\'s full name',
            'name' => 'Child\'s Name',
            'name_placeholder' => 'Enter child\'s name',
            
            'gender' => 'Gender',
            'gender_select' => 'Select gender',
            'gender_male' => 'Male',
            'gender_female' => 'Female',
            
            'date_of_birth' => 'Date of Birth',
            
            'school_name' => 'School Name',
            'school_name_placeholder' => 'Enter school name',
            
            'school_level' => 'School Level/Grade',
            'school_level_placeholder' => 'Enter grade or school level',
            
            'activities' => 'Current Activities',
            'activities_placeholder' => 'Sports, hobbies, or activities the child participates in',
            
            'medical_conditions' => 'Medical Conditions',
            'medical_conditions_placeholder' => 'Any medical conditions we should be aware of',
            
            'allergies' => 'Allergies',
            'allergies_placeholder' => 'Food, environmental, or other allergies',
            
            'medications' => 'Current Medications',
            'medications_placeholder' => 'Any medications the child is taking',
            
            'special_needs' => 'Special Needs',
            'special_needs_placeholder' => 'Any special accommodations needed',
            
            'past_injuries' => 'Past Injuries',
            'past_injuries_placeholder' => 'Previous injuries that might affect activities',
            
            'medical_information_title' => 'Medical Information (Optional)',
            'medical_note' => 'Medical information helps us provide better care and safety for your child during activities.',
            
            'optional_step' => 'Optional Step',
            'skip_message' => 'Leave the name field empty to skip adding a child to your family membership.',
        ],
        
        // Step 5: Consents & Agreements
        'consents' => [
            'title' => 'Consents & Agreements',
            'subtitle' => 'Please review and provide consent for the following agreements to complete your registration.',
            
            'your_consents' => 'Your Consents',
            'primary_member' => 'Primary Member',
            
            'spouse_consents' => 'Spouse Consents',
            'spouse_consent_note' => 'As the primary member, you are providing consent on behalf of your spouse.',
            
            'parent_guardian_consent_for' => 'Parent/Guardian Consent for',
            'parent_guardian_note' => 'As parent/guardian, you are providing consent for your child to participate in activities and programs.',
            
            'required_consents' => 'Required Consents',
            'required_notice' => 'Fields marked with * are required to complete your registration.',
            
            'no_consents_title' => 'No Additional Consents Required',
            'no_consents_message' => 'You can proceed to review your registration information.',
        ],
        
        // Step 5/6: Review & Submit
        'review' => [
            'title' => 'Review & Submit',
            'subtitle' => 'Please review your information before submitting your registration.',
            'subtitle_individual' => 'Please review your registration details before submitting.',
            'subtitle_family' => 'Please review your family registration details before submitting.',
            
            'your_information' => 'Your Information',
            'spouse_information' => 'Spouse Information',
            'child_1_information' => 'Child 1 Information',
            'child_2_information' => 'Child 2 Information',
            'emergency_contact_information' => 'Emergency Contact Information',
            
            'primary_member' => 'Primary Member',
            'spouse' => 'Spouse',
            'child1' => 'Child 1',
            'child2' => 'Child 2',
            'emergency_contact' => 'Emergency Contact',
            
            'edit' => 'Edit',
            'not_provided' => 'Not provided',
            'optional_skipped' => 'Optional - skipped',
            
            // Field labels
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'nationality' => 'Nationality',
            'national_id' => 'National ID',
            'date_of_birth' => 'Date of Birth',
            'gender' => 'Gender',
            'gender_male' => 'Male',
            'gender_female' => 'Female',
            'employer' => 'Employer',
            'address' => 'Address',
            'school' => 'School',
            'grade' => 'Grade',
            'activities' => 'Activities',
            
            // Medical information
            'medical_information' => 'Medical Information',
            'conditions' => 'Conditions',
            'allergies' => 'Allergies',
            'medications' => 'Medications',
            'special_needs' => 'Special Needs',
            'past_injuries' => 'Past Injuries',
            
            // Terms and conditions
            'terms_and_conditions' => 'Terms and Conditions',
            'terms_intro' => 'By submitting this registration, you acknowledge that:',
            'terms_accurate' => 'All information provided is accurate and complete',
            'terms_agree' => 'You agree to Wodworx\'s terms of service and privacy policy',
            'terms_payment' => 'You understand that membership activation requires payment',
            'terms_medical' => 'Medical information will be kept confidential and used only for safety purposes',
            
            'submit_button' => 'Submit Registration',
            'submit_confirm' => 'By submitting this registration, you confirm that all information provided is accurate.',
        ],
        
        // Success Messages
        'success' => [
            'title' => 'Registration Successful!',
            'individual_message' => 'Welcome :name! Your registration has been completed successfully.',
            'family_message' => 'Family registration completed successfully!',
            'family_members_title' => 'Registered Family Members:',
            'family_member_primary' => ':name (Primary)',
            'family_member_spouse' => ':name (Spouse)',
            'family_member_child' => ':name (Child)',
            'confirmation_message' => 'You will receive a confirmation email shortly. Please visit :gym_name to complete your membership setup.',
            'confirmation_message_no_gym' => 'You will receive a confirmation email shortly. Please visit the gym to complete your membership setup.',
            'address_title' => ':gym_name Address:',
        ],
        
        // Error Messages
        'errors' => [
            'title' => 'Registration Error',
            'return_home' => 'Return to Home',
            'invalid_token' => 'Invalid or expired registration link.',
            'organization_not_found' => 'Organization not found.',
            'step_access_denied' => 'Invalid step access.',
            'validation_failed' => 'Please correct the errors below and try again.',
        ],
    ],

    // === VALIDATION MESSAGES ===
    'validation' => [
        'full_name_required' => 'Full name is required.',
        'full_name_unique' => 'A member with this name already exists.',
        'full_name_min' => 'Full name must be at least 2 characters.',
        'full_name_max' => 'Full name may not be greater than 255 characters.',
        
        'email_required' => 'Email address is required.',
        'email_format' => 'Please enter a valid email address.',
        'email_unique' => 'A member with this email already exists.',
        'email_max' => 'Email may not be greater than 255 characters.',
        
        'phone_required' => 'Phone number is required.',
        'phone_format' => 'Please enter a valid phone number.',
        'phone_unique' => 'A member with this phone number already exists.',
        'phone_different' => 'Spouse phone number must be different from primary member.',
        'phone_min' => 'Phone number must be at least 7 digits.',
        'phone_max' => 'Phone number may not be greater than 15 digits.',
        
        'login_method_required' => 'Please select a login method.',
        'login_method_invalid' => 'Invalid login method selected.',
        
        'gender_required' => 'Gender is required.',
        'gender_invalid' => 'Please select a valid gender.',
        
        'date_of_birth_required' => 'Date of birth is required.',
        'date_of_birth_format' => 'Please enter a valid date.',
        'date_of_birth_past' => 'Date of birth must be in the past.',
        
        'child_age_invalid' => 'Child must be between 0 and 18 years old.',
        'child_name_required' => 'Child name is required when adding a child.',
        'child_gender_required' => 'Child gender is required when adding a child.',
        'child_dob_required' => 'Child date of birth is required when adding a child.',
        'child_school_required' => 'School information is required when adding a child.',
        
        'national_id_max' => 'National ID may not be greater than 50 characters.',
        'employer_max' => 'Employer name may not be greater than 255 characters.',
        'address_max' => 'Address may not be greater than 500 characters.',
        'nationality_max' => 'Nationality may not be greater than 255 characters.',
        
        'emergency_email_format' => 'Please enter a valid emergency contact email.',
        'emergency_phone_format' => 'Please enter a valid emergency contact phone.',
        'emergency_relation_max' => 'Relationship may not be greater than 100 characters.',
        
        'activities_max' => 'Activities may not be greater than 500 characters.',
        'medical_conditions_max' => 'Medical conditions may not be greater than 500 characters.',
        'allergies_max' => 'Allergies may not be greater than 500 characters.',
        'medications_max' => 'Medications may not be greater than 500 characters.',
        'special_needs_max' => 'Special needs may not be greater than 500 characters.',
        'past_injuries_max' => 'Past injuries may not be greater than 500 characters.',
    ],

    // === COUNTRIES (Common ones) ===
    'countries' => [
        'select_country' => 'Select Country',
        'united_states' => 'United States',
        'jordan' => 'Jordan',
        'united_kingdom' => 'United Kingdom',
        'canada' => 'Canada',
        'australia' => 'Australia',
        'germany' => 'Germany',
        'france' => 'France',
        'spain' => 'Spain',
        'italy' => 'Italy',
        'saudi_arabia' => 'Saudi Arabia',
        'united_arab_emirates' => 'United Arab Emirates',
        'qatar' => 'Qatar',
        'kuwait' => 'Kuwait',
        'bahrain' => 'Bahrain',
        'oman' => 'Oman',
        'lebanon' => 'Lebanon',
        'syria' => 'Syria',
        'iraq' => 'Iraq',
        'egypt' => 'Egypt',
        'other' => 'Other',
    ],
];

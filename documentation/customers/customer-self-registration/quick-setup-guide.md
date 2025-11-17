# Customer Registration Wizard - Quick Setup & Testing Guide

## 🚀 Immediate Testing

### **Test Universal Registration (Org ID 8 - SuperHero CrossFit)**

**Universal URL:**
```
http://wodworx-foh.test/register/org/c6a244394b0e11e984999600000a0cbd
```

### **Test Individual Registration Flow**
1. **Open the URL above**
2. **Select "Individual Registration"**
3. **Step 1 - Your Information:**
   - Name: "John Doe Test"
   - Phone Country: Select "Jordan (+962)" or "United States (+1)"
   - Phone: "797722999" (without leading zero)
   - Email: "john.doe.test@example.com"
   - Nationality: "American" (optional)
4. **Step 2 - Emergency Contact (All Optional):**
   - Name: "Jane Doe"
   - Email: "jane.doe@example.com"
   - Phone: "797722888"
   - Relation: "Sister"
5. **Step 3 - Review & Submit:**
   - Verify all information is displayed correctly
   - Click "Submit Registration"
6. **Verify Success:**
   - Check `orgUser` table for new record
   - Confirm emergency contact fields are populated
   - Verify `org_id = 8` assignment

### **Test Family Registration Flow**
1. **Open the URL above**
2. **Select "Family Registration"**
3. **Step 1 - Primary User:**
   - Name: "Sarah Johnson"
   - Phone: Different from individual test
   - Email: Different from individual test
4. **Step 2 - Spouse (Optional):**
   - Name: "Mike Johnson"
   - Phone: Must be different from primary
   - Email: Different from primary
5. **Step 3 - Child 1 (Optional):**
   - Name: "Emma Johnson"
   - Gender: "Female"
   - DOB: "2015-05-15" (age 8-9)
   - School: "Elementary School"
   - Grade: "3rd Grade"
6. **Step 4 - Child 2 (Optional):**
   - Skip or add second child
7. **Step 5 - Review & Submit:**
   - Verify all family information
   - Click "Submit Registration"
8. **Verify Success:**
   - Check `orgUser` table for all family members
   - Verify `orgFamily` record created
   - Confirm `orgFamilyUser` relationships exist
   - Check parent/child level assignments

## 🔧 FOH Staff Interface Testing

### **Access Link Manager**
```
URL: http://wodworx-foh.test/registration-links
Login Required: Yes (FOH staff account)
```

### **Test Features**
1. **Universal URL Display:**
   - ✅ Verify organization's universal URL is shown
   - ✅ Test copy-to-clipboard functionality
   - ✅ Confirm URL format is correct

2. **Token Generation:**
   - ✅ Generate individual registration link
   - ✅ Generate family registration link
   - ✅ Test different expiration times (24h, 48h, 72h, 168h)
   - ✅ Verify token format and encryption

3. **SMS Delivery:**
   - ✅ Send test SMS with valid phone number
   - ✅ Verify message personalization works
   - ✅ Check delivery status and timing
   - ✅ Test international phone numbers

4. **Email Delivery:**
   - ✅ Send test email with valid address
   - ✅ Verify subject and body customization
   - ✅ Check email formatting and links
   - ✅ Test spam folder delivery

## 📝 Testing Any Organization

### **Get Universal URL for Any Org**
```bash
# Via Tinker
php artisan tinker
$org = \App\Models\Org::find(YOUR_ORG_ID);
echo $org->getPublicRegistrationUrl();
```

### **Generate UUID if Missing**
```bash
# Via Tinker  
php artisan tinker
$org = \App\Models\Org::find(YOUR_ORG_ID);
$org->generateUuidIfMissing();
echo "UUID: " . $org->uuid;
echo "URL: " . $org->getPublicRegistrationUrl();
```

### **Test Token Generation**
```bash
# Via Tinker
php artisan tinker
$service = app(\App\Services\CustomerRegistrationService::class);
$token = $service->generateRegistrationToken(8, 'individual', 48);
echo "Token URL: " . route('register.wizard', $token);
```

## ✅ Comprehensive Verification Checklist

### **After Individual Registration**
- [ ] New `orgUser` record created with correct `org_id`
- [ ] Emergency contact fields populated if provided
- [ ] Phone number stored in normalized format (no leading zeros)
- [ ] Phone country stored as dialing code (962, 1, etc.)
- [ ] Nationality stored as string, not ID
- [ ] `isCustomer = 1` and other flags set correctly
- [ ] `created_by` field populated if staff user logged in
- [ ] Yii2 background job queued for processing
- [ ] UUID generated for new user

### **After Family Registration**
- [ ] Primary user created successfully
- [ ] Spouse created if provided (optional)
- [ ] Children created if provided (optional)
- [ ] `orgFamily` record created with correct `org_id`
- [ ] `orgFamilyUser` records link all family members
- [ ] Parent level = 'parent' for primary and spouse
- [ ] Child level = 'child' for children
- [ ] All family members have same `org_id`
- [ ] Medical information stored for children
- [ ] School information stored correctly
- [ ] Background jobs queued for all family members

### **Validation Testing**
- [ ] **Duplicate Names**: Try registering same name twice
- [ ] **Duplicate Emails**: Try registering same email twice  
- [ ] **Duplicate Phones**: Try registering same phone twice
- [ ] **Phone Normalization**: Test "0797722333" vs "797722333"
- [ ] **International Phones**: Test different country codes
- [ ] **Spouse Phone Validation**: Ensure spouse phone differs from primary
- [ ] **Child Age Validation**: Test invalid ages (under 0, over 18)
- [ ] **School Grade Validation**: Test invalid grade values
- [ ] **Email Format**: Test invalid email formats
- [ ] **Required Fields**: Test submitting with missing required data

## 🐛 Troubleshooting & Testing

### **Common Test Scenarios**

#### **Phone Number Edge Cases**
```bash
# Test these phone number variations:
"0797722333"  # With leading zero
"797722333"   # Without leading zero  
"+962797722333" # With country code
"797-722-333" # With dashes
"797 722 333" # With spaces
"(797) 722-333" # With parentheses
```

#### **Country Code Testing**
```bash
# Test ISO to dialing code conversion:
"JO" → "962" (Jordan)
"US" → "1" (United States)  
"AE" → "971" (UAE)
"SA" → "966" (Saudi Arabia)
"GB" → "44" (United Kingdom)
```

#### **Step Navigation Testing**
```bash
# Test URL parameters:
?step=0  # Type selection
?step=1  # Primary info
?step=2  # Emergency (individual) / Spouse (family)
?step=3  # Child 1 (family only)
?step=4  # Child 2 (family only)
?step=5  # Review (family)
?step=6  # Review (individual)
```

### **Database Verification Queries**
```sql
-- Check recent registrations
SELECT id, fullName, phoneNumber, phoneCountry, email, org_id, created_at 
FROM orgUser 
WHERE org_id = 8 
ORDER BY created_at DESC 
LIMIT 10;

-- Check family relationships
SELECT f.id as family_id, fu.level, u.fullName, u.email
FROM orgFamily f
JOIN orgFamilyUser fu ON f.id = fu.orgFamily_id
JOIN orgUser u ON fu.orgUser_id = u.id
WHERE f.org_id = 8
ORDER BY f.id DESC, fu.level DESC;

-- Check emergency contacts
SELECT fullName, emergencyFullName, emergencyEmail, emergencyPhoneNumber, emergencyRelation
FROM orgUser 
WHERE org_id = 8 AND emergencyFullName IS NOT NULL
ORDER BY created_at DESC;
```

### **Error Testing**

#### **Expected Validation Errors**
- **"A member with this name already exists"** - Duplicate name test
- **"A member with this phone number already exists"** - Duplicate phone test  
- **"A member with this email already exists"** - Duplicate email test
- **"The spouse phone number must be different"** - Same phone for spouse
- **"The phone number is not valid"** - Invalid phone format
- **"The child must be between 0 and 18 years old"** - Invalid child age

#### **System Error Testing**
- **Organization not found** - Invalid UUID in URL
- **Token expired** - Use expired token link
- **Invalid step access** - Manual URL manipulation
- **Database constraint violations** - Missing required relationships

## 🔗 Quick Reference Links

### **URL Patterns**
- **Universal**: `/register/org/{orgUuid}?step={step}`
- **Token**: `/register/{token}?step={step}`
- **Staff Interface**: `/registration-links`
- **Documentation**: `/documentation/registration/`

### **Key Files for Debugging**
- **Logs**: `storage/logs/laravel.log`
- **Queue Jobs**: Monitor with `php artisan queue:work`
- **Configuration**: `config/sms.php`, `config/mail.php`
- **Routes**: `routes/web.php`

### **Artisan Commands**
```bash
# Monitor queue jobs
php artisan queue:work

# Clear application cache
php artisan cache:clear

# Generate organization UUIDs
php artisan tinker
\App\Models\Org::whereNull('uuid')->get()->each->generateUuidIfMissing();

# Check SMS configuration
php artisan tinker
config('sms');
```

## 📞 Support & Debugging

### **Log Monitoring**
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Search for registration errors
grep -i "registration" storage/logs/laravel.log

# Check SMS delivery logs
grep -i "sms" storage/logs/laravel.log
```

### **Debug Mode Setup**
```bash
# Enable debug mode in .env
APP_DEBUG=true
LOG_LEVEL=debug

# Check configuration
php artisan config:cache
php artisan route:cache
```

### **Performance Testing**
- **Load Testing**: Test with multiple simultaneous registrations
- **Mobile Testing**: Verify responsive design on various devices
- **Browser Testing**: Test on Chrome, Firefox, Safari, Edge
- **Network Testing**: Test on slow connections and mobile data

## 🎯 Success Criteria

### **Individual Registration Success**
- ✅ Complete 3-step flow in under 5 minutes
- ✅ Real-time validation provides immediate feedback
- ✅ Emergency contact information properly stored
- ✅ Phone numbers normalized and validated
- ✅ Step navigation works smoothly
- ✅ Success confirmation displayed

### **Family Registration Success**
- ✅ Complete 5-step flow for full family
- ✅ Optional spouse and children handled correctly
- ✅ Family relationships properly established
- ✅ Medical information for children stored
- ✅ All family members linked in database
- ✅ Background jobs process successfully

### **Staff Interface Success**
- ✅ Universal URL easily accessible and shareable
- ✅ Token generation works with all expiration options
- ✅ SMS delivery successful with personalization
- ✅ Email delivery with proper formatting
- ✅ Copy-to-clipboard functionality works
- ✅ Error handling provides clear feedback

### **System Integration Success**
- ✅ Multi-tenancy properly isolates organizations
- ✅ Background jobs process without errors
- ✅ Database transactions maintain consistency
- ✅ Validation prevents duplicate registrations
- ✅ Phone number handling works internationally
- ✅ Family relationships maintain referential integrity

---

**Ready to test!** 🎉

*Use this guide to systematically verify all features work correctly before deploying to production.*

*Last Updated: 2025-01-07*
*Version: 2.0 - Complete Testing Coverage*
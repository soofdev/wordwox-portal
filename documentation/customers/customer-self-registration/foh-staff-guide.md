# FOH Staff Guide - Customer Registration Links

## 📋 Overview

This guide explains how Front of House (FOH) staff can generate and send customer registration links using the registration link management system. The system supports both individual and family registrations with advanced features like emergency contacts, family relationship tracking, and real-time validation.

## 🔐 Access Requirements

- **Login Required**: Yes, you must be logged into the FOH system
- **Permissions**: Your account must have FOH access enabled
- **URL**: `/registration-links` (accessible from main navigation)

## 🌐 Two Types of Registration Links

### **1. Universal Organization URL** 
**Best for: Public sharing, marketing, website**

- ✅ **Never expires** - permanent link for ongoing use
- ✅ **Easy to remember** - clean URL structure  
- ✅ **Public sharing** - safe to post on website, social media
- ✅ **No setup required** - always available for your organization
- ✅ **Step-specific navigation** - customers can bookmark specific steps

**Format**: `/register/org/{orgUuid}?step={stepNumber}`

**When to use:**
- Adding to your gym's website footer or registration page
- Sharing on social media (Facebook, Instagram, LinkedIn)
- Including in email signatures and business cards
- Printing on marketing materials and flyers
- General public registration campaigns
- Radio/TV advertisement mentions

### **2. Secure Token Links**
**Best for: Specific customers, targeted outreach**

- 🔒 **Secure access** - unique encrypted token per link
- ⏰ **Configurable expiration** - 24 hours to 1 week (168 hours)
- 📱 **SMS/Email delivery** - send directly to customer with personalized message
- 📊 **Trackable** - know exactly which links are being used
- 🎯 **Pre-configured** - can specify individual or family registration type

**Format**: `/register/{token}?step={stepNumber}`

**When to use:**
- Following up with prospects who visited your gym
- Sending to specific customers via SMS/Email
- Time-sensitive registration campaigns or promotions
- Personalized outreach with customer names
- When you want to track registration completion rates

## 📱 How to Send Registration Links

### **Step 1: Access Link Manager**
1. Log into FOH system with your staff credentials
2. Navigate to "Registration Links" from the main menu
3. View your organization's universal URL at the top of the page

### **Step 2: Choose Your Method**

#### **Method A: Share Universal URL (Recommended for Public Use)**
1. **Copy the universal URL** displayed at the top of the page
2. **Share via any channel:**
   - **Website**: Add to registration page or footer
   - **Social Media**: Post on Facebook, Instagram stories
   - **Email**: Include in newsletters or email signatures
   - **Print**: Add to business cards, flyers, posters
   - **Verbal**: Give to customers over the phone

#### **Method B: Generate & Send Secure Link (Recommended for Individual Customers)**

**Generate Secure Link:**
1. **Choose registration type**: Individual or Family
   - **Individual**: Single person registration with emergency contact
   - **Family**: Primary member + optional spouse + up to 2 children
2. **Set expiration time**: 24-168 hours (48 hours recommended)
3. **Click "Generate Secure Link"**
4. **Copy the generated URL** for manual sharing

**Send via SMS:**
1. **Enter customer's full name** (for personalization)
2. **Enter phone number** with country code (e.g., +1 for US, +962 for Jordan)
3. **Customize message** (optional - default template provided)
4. **Click "Send SMS"** - message delivered instantly

**Send via Email:**
1. **Enter customer's full name** (for personalization)
2. **Enter email address** (double-check for accuracy)
3. **Customize subject and message** (optional - professional template provided)
4. **Click "Send Email"** - delivered within minutes

## 💬 Message Templates & Customization

### **Default SMS Message**
```
Hi [Customer Name]! Please complete your [Gym Name] membership registration: [Link]
Link expires in [X] hours.
```

### **Default Email Message**
```
Subject: [Gym Name] - Complete Your Membership Registration

Hi [Customer Name]!

Please complete your [Gym Name] membership registration by clicking the link below:

[Registration Link]

This secure link will expire in [X] hours for your security.

If you have any questions, please contact us at [Gym Phone] or reply to this email.

Best regards,
[Gym Name] Team
```

### **Customization Best Practices**
- **Keep SMS under 160 characters** to avoid splitting into multiple messages
- **Include gym name** for immediate brand recognition
- **Add urgency** for time-sensitive offers ("Limited time offer expires soon!")
- **Personalize with customer's name** to increase engagement
- **Include contact information** for questions or support
- **Use professional tone** that represents your gym well

## 🎯 Registration Process Overview

### **What Customers Experience**

#### **Individual Registration (3 Steps)**
1. **Your Information**: Name, phone, email, nationality, emergency contact
2. **Emergency Contact**: Optional contact person details
3. **Review & Submit**: Confirm information and complete registration

#### **Family Registration (5 Steps)**  
1. **Your Information**: Primary member details
2. **Spouse Information**: Optional spouse details
3. **Child 1 Information**: Optional first child with school/medical info
4. **Child 2 Information**: Optional second child with school/medical info
5. **Review & Submit**: Confirm all family information and complete

### **Advanced Features Customers Get**
- **Real-time validation**: Immediate feedback on form errors
- **Smart navigation**: Individual registrations skip unnecessary steps
- **Phone number intelligence**: Automatic country code handling
- **Progress tracking**: Visual progress indicators
- **Mobile-friendly**: Works perfectly on phones and tablets
- **Step bookmarking**: Can return to specific steps via URL

## 🎯 Best Practices for Staff

### **For Universal URLs**
- ✅ **Add to website footer** - always visible to visitors
- ✅ **Include in email signatures** - every email becomes a registration opportunity
- ✅ **Share on social media regularly** - weekly posts with registration reminders
- ✅ **Print on all materials** - business cards, flyers, posters, merchandise
- ✅ **Use in advertisements** - radio, TV, online ads
- ✅ **Train all staff** - everyone should know and share the URL

### **For Token Links**
- ✅ **Use customer's actual name** - increases personal connection
- ✅ **Set 48-hour expiration** - creates urgency without being too restrictive
- ✅ **Follow up appropriately** - one reminder after 24 hours if not completed
- ✅ **Send during business hours** - 9 AM to 8 PM for best response
- ✅ **Include clear call-to-action** - "Complete your registration now"
- ✅ **Provide support contact** - phone number or email for questions

### **SMS Best Practices**
- ✅ **Verify phone numbers** before sending to avoid delivery failures
- ✅ **Include gym name immediately** - "Hi John! CrossFit Downtown here..."
- ✅ **Keep messages concise** - get to the point quickly
- ✅ **Send during appropriate hours** - respect customer time zones
- ✅ **Use professional language** - avoid slang or overly casual tone
- ❌ **Don't spam** - maximum one follow-up message
- ❌ **Don't send late at night** - avoid 9 PM to 8 AM

### **Email Best Practices**
- ✅ **Use clear subject lines** - "Complete Your Gym Registration - Action Required"
- ✅ **Include gym branding** - logo and consistent colors if possible
- ✅ **Provide multiple contact methods** - phone, email, address
- ✅ **Set clear expectations** - "Takes 5 minutes to complete"
- ✅ **Mobile-optimize** - many customers read email on phones
- ❌ **Don't use all caps** - appears unprofessional
- ❌ **Don't overload with information** - keep focused on registration

## 📊 Tracking & Follow-up Strategy

### **Monitor Registration Success**
- **Check daily** for new member registrations in your system
- **Track completion rates** - which method (SMS vs Email) works better
- **Note abandonment points** - where customers stop in the process
- **Follow up strategically** - don't let leads go cold

### **Follow-up Timeline**
- **24 hours after sending**: Check if registration completed
- **48 hours**: Send one polite reminder if not completed
- **1 week**: Final follow-up with offer to help complete registration
- **After 1 week**: Consider in-person or phone registration assistance

### **Sample Follow-up Messages**

**24-Hour SMS Reminder:**
```
Hi [Name]! Just a friendly reminder about your [Gym] registration link sent yesterday. 
It expires in 24 hours. Need help? Call us at [Phone]. Thanks!
```

**48-Hour Email Reminder:**
```
Subject: [Gym Name] - Registration Link Expires Soon

Hi [Name],

We noticed you haven't completed your membership registration yet. Your secure link expires in a few hours.

Complete now: [Link]

Need assistance? Reply to this email or call us at [Phone].

Thanks!
[Gym] Team
```

## 🆘 Troubleshooting Guide

### **Common Customer Issues**

**"SMS not delivering"**
- **Check**: Phone number includes correct country code (+1, +962, etc.)
- **Verify**: No typos in phone number entry
- **Test**: Ask customer if they receive SMS from other businesses
- **Solution**: Try email delivery instead, or use universal URL

**"Email not delivering"**
- **Check**: Email address spelling and format
- **Ask**: Customer to check spam/junk folder
- **Try**: Different email address if customer has multiple
- **Backup**: Use SMS or provide universal URL

**"Link expired"**
- **Generate**: New link with longer expiration (72-168 hours)
- **Provide**: Universal URL as permanent alternative
- **Educate**: Customer about expiration for security reasons
- **Assist**: Offer to help complete registration over phone

**"Can't access link on mobile"**
- **Verify**: Link was copied completely (no line breaks)
- **Test**: Send via different method (email vs SMS)
- **Provide**: Universal URL as alternative
- **Check**: Customer's internet connection and browser

**"Form validation errors"**
- **Common**: Phone number format issues
- **Help**: Guide customer through correct phone format
- **Check**: Name/email/phone not already in system
- **Assist**: Complete registration manually if needed

### **Staff Troubleshooting**

**"Can't access registration link manager"**
- **Check**: You're logged in with FOH staff account
- **Verify**: Your account has proper permissions
- **Try**: Logging out and back in
- **Contact**: System administrator for permission issues

**"SMS sending fails"**
- **Check**: SMS service configuration in system
- **Verify**: Phone number format is correct
- **Monitor**: SMS provider credits/limits
- **Backup**: Use email or universal URL

**"Email sending fails"**
- **Check**: Email configuration in system
- **Verify**: Recipient email format
- **Monitor**: Email delivery logs
- **Backup**: Use SMS or universal URL

### **Getting Help**
1. **Check with gym manager** - they may have additional guidance
2. **Contact IT support** - for technical system issues
3. **Use universal URL** - always works as backup method
4. **Manual registration** - complete registration in-person as last resort

## 📈 Success Tips & Strategies

### **Increase Registration Completion Rates**
- **Timing matters**: Send links when customers are most likely to act (evenings, weekends)
- **Personalization works**: Always use customer's actual name
- **Create urgency**: Mention limited-time offers or link expiration
- **Keep it simple**: Clear instructions and minimal steps
- **Follow up once**: One polite reminder can double completion rates
- **Offer help**: "Need assistance? We're here to help!"

### **Customer Communication Excellence**
- **Be helpful**: "I'm here to make this easy for you"
- **Be patient**: Some customers need extra time or assistance
- **Be professional**: You represent your gym's brand
- **Be available**: Provide clear contact info for questions
- **Be proactive**: Follow up appropriately without being pushy

### **Conversion Optimization**
- **Track what works**: Note which messages get best response
- **Test different approaches**: Vary message tone and timing
- **Segment customers**: Different approaches for different customer types
- **Use social proof**: "Join 500+ members who've registered online"
- **Highlight benefits**: "Quick 5-minute process, no paperwork needed"

## 📞 Customer Support Scripts

### **When Sending Initial Link**
*"Hi [Name], I'm sending you a secure link to complete your gym registration online. It's quick and easy - should take about 5 minutes. The link will expire in 48 hours for security, so please complete it when convenient. If you have any questions or need help, just call us at [Phone]. Looking forward to welcoming you to [Gym Name]!"*

### **When Following Up**
*"Hi [Name], just a friendly reminder about your gym registration link I sent yesterday. It expires in a few hours, so please complete it when you have a moment. If you need a new link or have any questions, just let me know. We're excited to have you join [Gym Name]!"*

### **If Customer Has Technical Issues**
*"No problem at all! Let me send you our permanent registration link that never expires, or I'd be happy to help you complete the registration right here at the front desk. Whatever works best for you! We want to make this as easy as possible."*

### **For Family Registrations**
*"I'm sending you our family registration link. You can add your spouse and up to 2 children with all their details. It's completely secure and you can save your progress if you need to come back to it. The whole family will be set up in one easy process!"*

## 🏆 Advanced Tips for Power Users

### **Segmentation Strategies**
- **New prospects**: Use individual registration links with 48-hour expiration
- **Family inquiries**: Send family registration links with 72-hour expiration  
- **Referrals**: Mention who referred them in personalized message
- **Returning members**: Use universal URL for quick access

### **Campaign Integration**
- **Social media campaigns**: Use universal URL in posts, token links for direct messages
- **Email marketing**: Include universal URL in newsletters, token links for targeted campaigns
- **Event follow-up**: Send token links to event attendees within 24 hours
- **Referral programs**: Train members to share universal URL

### **Analytics & Optimization**
- **Track completion rates** by message type and timing
- **Monitor step abandonment** to identify problem areas
- **A/B test message templates** to improve response rates
- **Measure time-to-completion** to optimize expiration settings

---

**Remember**: The goal is to make registration as easy and convenient as possible for customers while maintaining professionalism and representing your gym excellently! 🏋️‍♂️

*Last Updated: 2025-01-07*
*Version: 2.0 - Complete Feature Implementation*
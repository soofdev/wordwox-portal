# SMS Service Setup Guide ✅

**Status**: Fully implemented and tested with BluNet provider

## Environment Variables

Add these variables to your `.env` file:

```env
# SMS Service Configuration
SMS_DEFAULT_PROVIDER=blunet
SMS_ENABLED=true

# BluNet SMS Provider Configuration
SMS_BLUNET_ENDPOINT=http://82.212.81.40:8080/websmpp/websms
SMS_BLUNET_ACCESS_KEY=*****
SMS_BLUNET_TYPE=4
SMS_BLUNET_DEFAULT_SENDER=Wodworx

# SMS Service Behavior
SMS_VALIDATE_BEFORE_SEND=true
SMS_SKIP_IN_DEV=true

# Queue Configuration for SMS
SMS_QUEUE_CONNECTION=default
SMS_QUEUE_NAME=sms
```

## Usage Examples

### Basic SMS Sending (Queued)

```php
use App\Services\SmsService;

$smsService = app(SmsService::class);

// Send SMS (queued)
$success = $smsService->send(
    to: '+962797973709',
    message: 'Hello from Wodworx!',
    orgId: 1,
    orgUserId: 10443
);
```

### Immediate SMS Sending

```php
use App\Services\SmsService;

$smsService = app(SmsService::class);

// Send SMS immediately
$result = $smsService->sendNow(
    to: '+962797973709',
    message: 'Hello from Wodworx!',
    orgId: 1,
    orgUserId: 10443
);

if ($result->isSuccess()) {
    echo "SMS sent! Cost: {$result->cost}";
} else {
    echo "SMS failed: {$result->message}";
}
```

### For Signature Requests

```php
use App\Services\SmsService;

$smsService = app(SmsService::class);

$signatureUrl = "https://wodworx-foh.test/member/{$orgUser->id}/terms-review";
$message = "Please review and sign your membership agreement: {$signatureUrl}";

$success = $smsService->send(
    to: $orgUser->fullPhone,
    message: $message,
    orgId: $orgUser->org_id,
    orgUserId: $orgUser->id,
    options: [
        'subject' => 'Membership Agreement Signature Request',
        'create_msg_item' => true, // Creates tracking record
    ]
);
```

## Queue Processing

Make sure your queue worker is running:

```bash
php artisan queue:work --queue=sms
```

## Database Tables

The SMS service uses existing database tables:

- `logSms` - SMS delivery logs
- `orgMsgItem` - Message tracking (optional)

## Provider Configuration

### BluNet Provider

- **Endpoint**: `http://82.212.81.40:8080/websmpp/websms`
- **Authentication**: Access key based
- **Cost**: 40 units per SMS (as per Yii2 implementation)
- **Sender Names**: Org-specific sender names configured in `config/sms.php`

### Country Routing

The service supports country-specific provider routing:

- **Default**: BluNet for most countries
- **Exceptions**: Twilio for US (1), Hungary (36), UAE (971), Saudi Arabia (966)
- **Blacklisted**: Pakistan (92), Bangladesh (88), Nepal (977), Austria (43), Iraq (964)

## Logging

All SMS attempts are logged to:

1. **Laravel Logs**: Detailed processing logs
2. **Database**: `logSms` table for delivery tracking
3. **Message Items**: `orgMsgItem` table for campaign tracking (optional)

## Testing

In development environment (`APP_ENV=local`), SMS sending is skipped by default but logged for debugging.

To test SMS in development, set:
```env
SMS_SKIP_IN_DEV=false
```

## SMS Enable/Disable Control

You can globally enable or disable SMS functionality using the `SMS_ENABLED` environment variable:

### Disable SMS Globally
```env
SMS_ENABLED=false
```

When SMS is disabled:
- All SMS sending attempts (both `send()` and `sendNow()`) will be skipped
- Operations return success status but no actual SMS is sent
- All attempts are logged for debugging purposes
- This setting overrides all other SMS configurations

### Enable SMS (Default)
```env
SMS_ENABLED=true
```

**Note**: This is the default setting if the environment variable is not specified.

## Error Handling

- **Invalid Phone Numbers**: Validated using libphonenumber library
- **Blacklisted Countries**: Automatically blocked
- **Provider Failures**: Logged and retried once
- **Queue Failures**: Logged to failed jobs table

## Monitoring

Monitor SMS delivery through:

1. **Queue Dashboard**: Track job processing
2. **Database Queries**: Check `logSms` for delivery status
3. **Laravel Logs**: Detailed error information
Hi {{ $orgUser->firstName }},

Welcome to {{ $gymName }}!

To complete your membership registration, we need you to review and digitally sign your membership agreement.

Please visit this secure link to review and sign your agreement:
{{ $signatureUrl }}

IMPORTANT: This link expires on {{ $expiresAt->format('M j, Y \a\t g:i A') }}
Please complete your signature within 48 hours.

What happens next:
1. Click the link above to review your membership terms
2. Read through the agreement carefully
3. Sign digitally using your mouse, finger, or stylus
4. Receive a copy of your signed agreement immediately

Your digital signature is legally binding and complies with electronic signature laws. All information is encrypted and securely stored.

If you have any questions about your membership or need assistance with the signing process, please contact us directly at the gym.

Welcome to the {{ $gymName }} family!

---
{{ $gymName }}
This is an automated message. Please do not reply to this email.
This signature request was sent securely to {{ $orgUser->email }}

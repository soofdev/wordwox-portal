Hi {{ $orgUser->firstName }},

Great news! Your membership agreement has been successfully signed and processed.

Your signed agreement is now available for download:
{{ $pdfUrl }}

IMPORTANT: Keep this signed agreement for your records. It contains all the terms and conditions of your membership with {{ $gymName }}.

Digitally signed on {{ $signedAt->format('M j, Y \a\t g:i A') }}
This document is legally binding and compliant with electronic signature laws.

What's next:
1. Download and save your signed agreement
2. Visit {{ $gymName }} to get started with your membership
3. Contact us if you have any questions about your membership
4. Keep this email for future reference

Thank you for choosing {{ $gymName }}! We're excited to be part of your fitness journey.

---
{{ $gymName }}
This is an automated message. Please do not reply to this email.
This signed agreement was sent to {{ $orgUser->email }}

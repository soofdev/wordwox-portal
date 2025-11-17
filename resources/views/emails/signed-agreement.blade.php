<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Signed Membership Agreement</title>
    <style>
        /* Reset styles */
        body,
        table,
        td,
        p,
        a,
        li,
        blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .gym-name {
            color: #a7f3d0;
            font-size: 18px;
            font-weight: 500;
            margin: 8px 0 0 0;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 20px 0;
        }

        .message {
            font-size: 16px;
            line-height: 1.7;
            color: #475569;
            margin: 0 0 30px 0;
        }

        .success-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .cta-container {
            text-align: center;
            margin: 40px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .info-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }

        .info-box h3 {
            color: #1e293b;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .info-box p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .signed-notice {
            background-color: #ecfdf5;
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            text-align: center;
        }

        .signed-notice strong {
            color: #065f46;
            font-weight: 600;
        }

        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #64748b;
            font-size: 14px;
            margin: 0 0 10px 0;
        }

        .footer a {
            color: #10b981;
            text-decoration: none;
        }

        /* Mobile responsiveness */
        @media only screen and (max-width: 600px) {
            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .content {
                padding: 30px 20px;
            }

            .cta-button {
                padding: 14px 24px;
                font-size: 16px;
            }

            .footer {
                padding: 20px;
            }
        }

    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>Agreement Signed!</h1>
            <p class="gym-name">{{ $gymName }}</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p class="greeting">Hi {{ $orgUser->firstName }},</p>

            <p class="message">
                Great news! Your membership agreement has been successfully signed and processed. 🎉
            </p>

            <p class="message">
                Your signed agreement is now available for download. This document serves as your official membership contract with {{ $gymName }}.
            </p>

            <!-- Call to Action -->
            <div class="cta-container">
                <a href="{{ $pdfUrl }}" class="cta-button">
                    📄 Download Your Agreement
                </a>
            </div>

            <!-- Important Information -->
            <div class="info-box">
                <h3>📋 Important Information</h3>
                <p>Keep this signed agreement for your records. It contains all the terms and conditions of your membership with {{ $gymName }}.</p>
            </div>

            <!-- Signed Notice -->
            <div class="signed-notice">
                <strong>✍️ Digitally signed on {{ $signedAt->format('M j, Y \a\t g:i A') }}</strong>
                <br>
                <small>This document is legally binding and compliant with electronic signature laws</small>
            </div>

            <p class="message">
                <strong>What's next?</strong>
            </p>
            <ul style="color: #475569; line-height: 1.7;">
                <li>Download and save your signed agreement</li>
                <li>Visit {{ $gymName }} to get started with your membership</li>
                <li>Contact us if you have any questions about your membership</li>
                <li>Keep this email for future reference</li>
            </ul>

            <p class="message">
                Thank you for choosing {{ $gymName }}! We're excited to be part of your fitness journey. 💪
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $gymName }}</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>If you need assistance, please contact us directly at the gym.</p>

            <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                This signed agreement was sent to {{ $orgUser->email }}
            </p>
        </div>
    </div>
</body>
</html>

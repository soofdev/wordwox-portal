<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Hold Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .hold-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .hold-details h3 {
            margin-top: 0;
            color: #495057;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .detail-value {
            color: #495057;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-created {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-ended {
            background-color: #d1edff;
            color: #0c5460;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-modified {
            background-color: #d4edda;
            color: #155724;
        }
        .note-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .footer {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $orgName }}</h1>
        <p>Membership Hold Notification</p>
    </div>

    <div class="content">
        <h2>Hello {{ $memberName }},</h2>

        @if($notificationType === 'created')
            <p>Your membership has been put on hold. Here are the details:</p>
        @elseif($notificationType === 'ended')
            <p>Your membership hold has been ended and your membership is now active again.</p>
        @elseif($notificationType === 'cancelled')
            <p>Your membership hold has been cancelled. Your membership remains active.</p>
        @elseif($notificationType === 'modified')
            <p>Your membership hold dates have been updated. Here are the new details:</p>
        @endif

        <div class="hold-details">
            <h3>Hold Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Membership Plan:</span>
                <span class="detail-value">{{ $planName }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Hold Start Date:</span>
                <span class="detail-value">{{ $startDate }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Hold End Date:</span>
                <span class="detail-value">{{ $endDate }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="status-badge status-{{ $notificationType }}">
                        @if($notificationType === 'created')
                            Hold Active
                        @elseif($notificationType === 'ended')
                            Hold Ended
                        @elseif($notificationType === 'cancelled')
                            Hold Cancelled
                        @elseif($notificationType === 'modified')
                            Hold Updated
                        @endif
                    </span>
                </span>
            </div>
        </div>

        @if($holdNote)
            <div class="note-section">
                <h4>Additional Notes:</h4>
                <p>{{ $holdNote }}</p>
            </div>
        @endif

        @if($notificationType === 'created')
            <p><strong>What this means:</strong></p>
            <ul>
                <li>Your membership is temporarily paused from {{ $startDate }} to {{ $endDate }}</li>
                <li>You will not be charged during this period</li>
                <li>Your membership will automatically resume on {{ $endDate }}</li>
                <li>Any remaining days will be added to your membership duration</li>
            </ul>
        @elseif($notificationType === 'ended')
            <p><strong>What this means:</strong></p>
            <ul>
                <li>Your membership is now active and you can use all services</li>
                <li>Regular billing will resume</li>
                <li>The hold period has been added to your membership duration</li>
            </ul>
        @elseif($notificationType === 'cancelled')
            <p><strong>What this means:</strong></p>
            <ul>
                <li>Your membership was never paused</li>
                <li>Regular billing continues as normal</li>
                <li>You can continue using all services without interruption</li>
            </ul>
        @endif

        <p>If you have any questions about your membership hold, please contact us.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from {{ $orgName }}.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777777;
            margin-top: 20px;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        a {
            color: #007bff;
        }
        h1, h2, h3 {
            color: #333333;
        }
        ul, ol {
            padding-left: 20px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($fromName)
            <div class="header">
                <h2>{{ $fromName }}</h2>
            </div>
        @endif
        
        <div class="content">
            {!! $htmlData !!}
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $orgName ?? config('app.name') }}. All rights reserved.</p>
            @if($templateId)
                <p style="font-size: 10px; color: #999;">Template ID: {{ $templateId }}</p>
            @endif
        </div>
    </div>
</body>
</html>







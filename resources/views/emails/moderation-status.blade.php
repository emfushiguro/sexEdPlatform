<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Account Update' }} - Concious Connections</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #374151;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
        }
        .header {
            background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);
            padding: 40px 32px;
            text-align: center;
        }
        .logo-card {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 16px;
        }
        .brand-name {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px;
            letter-spacing: 0.5px;
        }
        .header-divider {
            width: 40px;
            height: 2px;
            background: rgba(255, 255, 255, 0.4);
            margin: 0 auto 20px;
            border-radius: 2px;
        }
        .header-title {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 8px;
        }
        .header-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.80);
            margin: 0;
        }
        .body {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            color: #374151;
            margin-bottom: 16px;
        }
        .message {
            font-size: 15px;
            line-height: 1.7;
            color: #6b7280;
            margin-bottom: 18px;
        }
        .details-list {
            margin: 0 0 24px;
            padding-left: 18px;
        }
        .details-list li {
            margin: 7px 0;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.5;
        }
        .cta-wrapper {
            text-align: center;
            margin: 24px 0;
        }
        .cta-button {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 12px;
            letter-spacing: 0.3px;
        }
        .expiry-note {
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
            margin: 0 0 24px;
        }
        .url-fallback {
            font-size: 12px;
            color: #9ca3af;
            word-break: break-all;
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
        }
        .footer {
            background: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #f3f4f6;
        }
        .footer-links {
            margin-bottom: 12px;
        }
        .footer-links a {
            font-size: 12px;
            color: #9ca3af;
            text-decoration: none;
            margin: 0 8px;
        }
        .footer-copy {
            font-size: 12px;
            color: #d1d5db;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo-card">
                <span style="font-size: 26px; font-weight: 800; color: #730DB1; line-height: 1;">CC</span>
            </div>
            <p class="brand-name">Concious Connections</p>
            <div class="header-divider"></div>
            <h1 class="header-title">{{ $title ?? 'Account Update' }}</h1>
            @if(!empty($subtitle))
                <p class="header-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="body">
            <p class="greeting">Hello {{ $greetingName ?? 'Learner' }},</p>

            @if(!empty($intro))
                <p class="message">{{ $intro }}</p>
            @endif

            @if(!empty($details) && is_array($details))
                <ul class="details-list">
                    @foreach($details as $detail)
                        @if(!empty($detail))
                            <li>{{ $detail }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @if(!empty($actionUrl) && !empty($actionText))
                <div class="cta-wrapper">
                    <a href="{{ $actionUrl }}" class="cta-button">{{ $actionText }}</a>
                </div>

                <p class="url-fallback">
                    If the button does not work, copy and paste this link into your browser:<br>
                    <span style="color: #730DB1;">{{ $actionUrl }}</span>
                </p>
            @endif

            @if(!empty($expiryText))
                <p class="expiry-note">{{ $expiryText }}</p>
            @endif

            @if(!empty($footerNote))
                <p class="message" style="margin-bottom: 0;">{{ $footerNote }}</p>
            @endif
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
            <p class="footer-copy">&copy; {{ date('Y') }} Concious Connections. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

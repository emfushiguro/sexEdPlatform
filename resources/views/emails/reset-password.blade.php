<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password — Conscious Connections</title>
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
            margin-bottom: 28px;
        }
        .cta-wrapper {
            text-align: center;
            margin-bottom: 28px;
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
            margin-bottom: 28px;
        }
        .warning-box {
            background: #fef9f0;
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
            padding: 14px 16px;
            margin-bottom: 24px;
        }
        .warning-box p {
            font-size: 13px;
            color: #92400e;
            margin: 0;
            line-height: 1.5;
        }
        .divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 24px 0;
        }
        .tips-title {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }
        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
        }
        .tips-list li {
            font-size: 14px;
            color: #6b7280;
            padding: 5px 0 5px 24px;
            position: relative;
            line-height: 1.5;
        }
        .tips-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #A30EB2;
            font-weight: 700;
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
        {{-- Header --}}
        <div class="header">
            <div class="logo-card">
                <span style="font-size: 26px; font-weight: 800; color: #730DB1; line-height: 1;">CC</span>
            </div>
            <p class="brand-name">Conscious Connections</p>
            <div class="header-divider"></div>
            <h1 class="header-title">Password Reset</h1>
            <p class="header-subtitle">We received a request to reset your password</p>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">
                Hi, <strong>{{ $user->first_name ?? $user->name }}</strong>!
            </p>
            <p class="message">
                We received a request to reset the password for your Conscious Connections account
                linked to <strong>{{ $user->email }}</strong>. Click the button below to choose a new password.
            </p>

            <div class="cta-wrapper">
                <a href="{{ $resetUrl }}" class="cta-button">
                    Reset My Password
                </a>
            </div>

            <p class="expiry-note">⏱ This link expires in <strong>60 minutes</strong>.</p>

            <div class="warning-box">
                <p>
                    ⚠️ <strong>Didn't request this?</strong> If you did not request a password reset,
                    no action is needed — your account is safe and this email can be ignored.
                </p>
            </div>

            <hr class="divider">

            <p class="tips-title">Password Tips</p>
            <ul class="tips-list">
                <li>Use at least 8 characters</li>
                <li>Mix uppercase and lowercase letters</li>
                <li>Add numbers and special characters (e.g., @, #, !)</li>
                <li>Avoid using your name or email address</li>
            </ul>

            <div class="url-fallback">
                If the button doesn't work, copy and paste this link into your browser:<br>
                <span style="color: #730DB1;">{{ $resetUrl }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
            <p class="footer-copy">
                &copy; {{ date('Y') }} Conscious Connections. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

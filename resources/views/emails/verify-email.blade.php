<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email — Concious Connections</title>
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
        .logo-card img {
            width: 60px;
            height: 60px;
            object-fit: contain;
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
        .divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 24px 0;
        }
        .next-steps-title {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }
        .next-steps-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
        }
        .next-steps-list li {
            font-size: 14px;
            color: #6b7280;
            padding: 5px 0 5px 24px;
            position: relative;
            line-height: 1.5;
        }
        .next-steps-list li::before {
            content: '✓';
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
        .footer-links a:hover {
            color: #730DB1;
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
            <p class="brand-name">Concious Connections</p>
            <div class="header-divider"></div>
            <h1 class="header-title">Verify Your Email</h1>
            <p class="header-subtitle">One quick step to activate your account</p>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">
                Hi, <strong>{{ $user->first_name ?? $user->name }}</strong>!
            </p>
            <p class="message">
                Thanks for joining Concious Connections! We're excited to have you be part of our
                safe, age-appropriate learning community. Before you can start exploring, please
                confirm your email address by clicking the button below.
            </p>

            <div class="cta-wrapper">
                <a href="{{ $verificationUrl }}" class="cta-button">
                    Verify Email Address
                </a>
            </div>

            <p class="expiry-note">⏱ This link expires in <strong>60 minutes</strong>.</p>

            <hr class="divider">

            <p class="next-steps-title">What happens next?</p>
            <ul class="next-steps-list">
                <li>Complete your profile with a username and location</li>
                <li>Browse age-appropriate educational modules</li>
                <li>Track progress and earn achievements</li>
                <li>Take quizzes and download certificates</li>
            </ul>

            <p class="url-fallback">
                If the button doesn't work, copy and paste this link into your browser:<br>
                <span style="color: #730DB1;">{{ $verificationUrl }}</span>
            </p>

            <p style="font-size: 13px; color: #9ca3af; margin: 0;">
                If you did not create an account, you can safely ignore this email.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
            <p class="footer-copy">
                &copy; {{ date('Y') }} Concious Connections. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

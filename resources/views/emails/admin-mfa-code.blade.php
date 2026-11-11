<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Verification — Conscious Connections</title>
    <style>
        body { margin: 0; padding: 0; background: #f3f4f6; color: #374151; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .wrapper { max-width: 560px; margin: 40px auto; overflow: hidden; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); }
        .header { padding: 40px 32px; text-align: center; background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%); }
        .logo-card { display: inline-block; width: 80px; height: 80px; margin-bottom: 16px; border: 1px solid rgba(255, 255, 255, .25); border-radius: 20px; background: rgba(255, 255, 255, .18); color: #730DB1; font-size: 26px; font-weight: 800; line-height: 80px; }
        .brand { margin: 0 0 4px; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .divider { width: 40px; height: 2px; margin: 0 auto 20px; border-radius: 2px; background: rgba(255, 255, 255, .4); }
        .title { margin: 0 0 8px; color: #ffffff; font-size: 26px; }
        .subtitle { margin: 0; color: rgba(255, 255, 255, .8); font-size: 14px; }
        .body { padding: 36px 40px; }
        .greeting { margin: 0 0 16px; font-size: 16px; }
        .message { margin: 0 0 24px; color: #6b7280; font-size: 15px; line-height: 1.7; }
        .code-label { margin: 0 0 8px; color: #6b7280; text-align: center; font-size: 12px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        .code-row { margin: 0 auto 20px; text-align: center; white-space: nowrap; }
        .code-digit { display: inline-block; width: 40px; height: 40px; margin: 0 3px; border-radius: 6px; background: #ffffff; color: #1f2937; text-align: center; font-family: Consolas, Monaco, monospace; font-size: 22px; font-weight: 700; line-height: 40px; }
        .copy-button { display: table; margin: 0 auto 22px; padding: 12px 26px; border-radius: 24px; background: #730DB1; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; }
        .copy-note, .expiry { margin: 0 0 24px; color: #9ca3af; text-align: center; font-size: 13px; line-height: 1.5; }
        .warning { margin-bottom: 24px; padding: 14px 16px; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; background: #fef9f0; }
        .warning p { margin: 0; color: #92400e; font-size: 13px; line-height: 1.5; }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; background: #f9fafb; text-align: center; }
        .footer a { margin: 0 8px; color: #9ca3af; font-size: 12px; text-decoration: none; }
        .copyright { margin: 12px 0 0; color: #d1d5db; font-size: 12px; }
        @media (max-width: 600px) { .wrapper { margin: 0; border-radius: 0; } .header, .body, .footer { padding-left: 24px; padding-right: 24px; } .code-digit { width: 34px; height: 36px; margin: 0 2px; font-size: 20px; line-height: 36px; } }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo-card">CC</div>
            <p class="brand">Conscious Connections</p>
            <div class="divider"></div>
            <h1 class="title">Admin Login Verification</h1>
            <p class="subtitle">Secure access to the Admin panel</p>
        </div>

        <div class="body">
            <p class="greeting">Hi, <strong>{{ $user->first_name ?? $user->name }}</strong>!</p>
            <p class="message">Use the verification code below to finish signing in to the Conscious Connections Admin panel.</p>

            <p class="code-label">Code requested</p>
            <div class="code-row" role="img" aria-label="Verification code {{ implode(' ', str_split($code)) }}">
                @foreach(str_split($code) as $digit)
                    <span class="code-digit">{{ $digit }}</span>
                @endforeach
            </div>
            @if(filled($copyUrl ?? null))
                <a href="{{ $copyUrl }}" class="copy-button">Copy code</a>
                <p class="copy-note">Open the verification page to copy this code directly, or select the code above manually.</p>
            @else
                <span class="copy-button" aria-hidden="true">Copy code</span>
                <p class="copy-note">Select and copy the code above into the verification page already open in your browser.</p>
            @endif
            <p class="expiry">This code expires in <strong>5 minutes</strong>.</p>

            <div class="warning">
                <p><strong>Never share this code.</strong> Conscious Connections will never ask you to disclose it. If you did not request this login, ignore this email and secure your account.</p>
            </div>
        </div>

        <div class="footer">
            <div>
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
            <p class="copyright">&copy; {{ date('Y') }} Conscious Connections. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

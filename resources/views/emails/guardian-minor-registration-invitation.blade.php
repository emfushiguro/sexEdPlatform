<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian invitation - Conscious Connections</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;color:#374151;">
    <div style="max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);">
        <div style="background:#730DB1;background-image:linear-gradient(135deg,#A30EB2,#730DB1,#3B0CB1);padding:40px 32px;text-align:center;color:#fff;">
            <p style="margin:0 0 20px;font-size:22px;font-weight:700;">Conscious Connections</p>
            <h1 style="margin:0 0 8px;font-size:26px;">Your guidance is requested</h1>
            <p style="margin:0;font-size:14px;">An invitation to support a dependent</p>
        </div>
        <div style="padding:36px 40px;">
            <p style="font-size:16px;">Hello,</p>
            <p style="font-size:15px;line-height:1.7;color:#6b7280;"><strong>{{ $name }}</strong> asked for your help with registration on Conscious Connections. Your support helps them continue their learning journey.</p>
            <p style="text-align:center;margin:28px 0;"><a href="{{ $acceptUrl }}" style="display:inline-block;padding:14px 32px;background:#730DB1;color:#fff;text-decoration:none;border-radius:12px;font-weight:600;">Review the invitation</a></p>
            <p style="font-size:13px;text-align:center;color:#6b7280;">This invitation expires {{ $expiresAt?->format('M d, Y H:i T') }}.</p>
            <hr style="border:0;border-top:1px solid #f3f4f6;margin:24px 0;">
            <h2 style="font-size:15px;">What happens next?</h2>
            <p style="font-size:14px;line-height:1.6;color:#6b7280;">Sign in using an email you control, complete Guardian verification, and claim the invitation. Your email can differ from the invited address. Relationship evidence and dependent identity checks still follow. Return to this invitation if you leave the process.</p>
            <p style="font-size:13px;line-height:1.6;color:#6b7280;">If you don't recognize this request, you can ignore this email. Opening the invitation does not authorize registration.</p>
            <p style="font-size:12px;word-break:break-all;color:#6b7280;background:#f9fafb;padding:12px;border-radius:8px;">If the button doesn't work, copy this link into your browser:<br>{{ $acceptUrl }}</p>
        </div>
        <div style="background:#f9fafb;padding:24px;text-align:center;font-size:12px;color:#6b7280;">
            <a href="{{ url('/') }}" style="color:#730DB1;">Home</a> &nbsp; <a href="{{ route('privacy') }}" style="color:#730DB1;">Privacy Policy</a> &nbsp; <a href="{{ route('terms') }}" style="color:#730DB1;">Terms of Service</a>
            <p>&copy; {{ date('Y') }} Conscious Connections. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

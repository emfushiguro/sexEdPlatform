<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $status === 'approved' ? 'Application Approved' : 'Application Update' }} — Conscious Connections</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #374151; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .header { background: linear-gradient(135deg, #7e22ce 0%, #4f46e5 100%); padding: 40px 30px; text-align: center; color: #ffffff; }
        .header-logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; letter-spacing: -0.025em; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 50px; background: rgba(255, 255, 255, 0.2); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; backdrop-filter: blur(4px); }
        .body { padding: 40px 40px 30px; }
        .greeting { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 16px; }
        .message { font-size: 16px; line-height: 1.6; color: #4b5563; margin-bottom: 24px; }
        .remarks-wrapper { background-color: #f9fafb; border-left: 4px solid {{ $status === 'approved' ? '#10b981' : '#ef4444' }}; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0; }
        .remarks-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.05em; }
        .remarks-content { font-size: 15px; color: #374151; }
        .remarks-content p { margin: 0 0 10px; }
        .remarks-content p:last-child { margin-bottom: 0; }
        .remarks-content ul, .remarks-content ol { margin: 8px 0 8px 20px; }
        .cta-button { display: inline-block; padding: 14px 32px; background: {{ $status === 'approved' ? '#7e22ce' : '#4b5563' }}; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px -1px rgba(126, 34, 206, 0.2); transition: background-color 0.2s; }
        .footer { background: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb; font-size: 13px; color: #9ca3af; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-logo">Conscious Connections</div>
            <div class="status-badge">
                {{ $status === 'approved' ? 'Application Approved' : 'Application Status Update' }}
            </div>
        </div>

        <div class="body">
            <p class="greeting">Hello {{ $user->first_name }},</p>
            
            @if($status === 'approved')
                <p class="message">
                    We are thrilled to inform you that your application to become an instructor has been <strong>approved</strong>. You are now a verified educator on our platform.
                </p>
                
                @if($remarks)
                    <div class="remarks-wrapper">
                        <div class="remarks-label">Admin Note</div>
                        <div class="remarks-content">{!! $remarks !!}</div>
                    </div>
                @endif
                
                <p class="message" style="margin-bottom: 32px;">
                    You can now access your Instructor Dashboard to start creating courses, managing students, and tracking your impact.
                </p>
                
                <div style="text-align: center;">
                    <a href="{{ route('instructor.dashboard') }}" class="cta-button">Access Instructor Dashboard</a>
                </div>
            @else
                <p class="message">
                    Thank you for your interest in joining our instructor community. Our team has reviewed your application, and unfortunately, we are unable to approve it at this time.
                </p>
                
                @if($reasonLabel || $reasonNote || $remarks)
                    <div class="remarks-wrapper">
                        <div class="remarks-label">Waitlist / Rejection Reason</div>
                        @if($reasonLabel)
                            <div class="remarks-content"><strong>{{ $reasonLabel }}</strong></div>
                        @endif
                        @if($reasonNote)
                            <div class="remarks-content" style="margin-top: 8px;">{{ $reasonNote }}</div>
                        @endif
                        @if($remarks)
                            <div class="remarks-content" style="margin-top: 8px;">{!! $remarks !!}</div>
                        @endif
                    </div>
                @endif
                
                <p class="message" style="margin-bottom: 32px;">
                    Please address the feedback above before submitting a new application.
                </p>

                <div style="text-align: center;">
                    <a href="{{ route('learner.instructor.apply') }}" class="cta-button" style="background-color: #ffffff; color: #4b5563; border: 1px solid #d1d5db; box-shadow: none;">Review Application</a>
                </div>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Conscious Connections. All rights reserved.<br>
            If you have any questions, please contact our support team.
        </div>
    </div>
</body>
</html>
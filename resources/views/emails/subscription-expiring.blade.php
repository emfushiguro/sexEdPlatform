@component('mail::message')
# Your Subscription is Expiring Soon

Hi {{ $user->first_name }},

Your **{{ $planName }}** subscription will expire in **{{ $daysUntilExpiry }} days** on **{{ $expiryDate }}**.

## Don't lose access to your premium features!

To continue enjoying unlimited access to our educational content, please renew your subscription before it expires.

@component('mail::button', ['url' => $renewUrl])
Renew Subscription
@endcomponent

## What you'll lose if you don't renew:
- Access to premium modules and content
- Certificate generation
- Priority customer support
- Downloadable resources
- Progress tracking and analytics

## Questions?
If you have any questions about your subscription or need help with renewal, please don't hesitate to contact our support team.

@component('mail::button', ['url' => 'mailto:support@sexedplatform.com', 'color' => 'secondary'])
Contact Support
@endcomponent

Thank you for being a valued member of our community!

Best regards,<br>
{{ config('app.name') }} Team

---
<small>This is an automated reminder. Your subscription will automatically expire on {{ $expiryDate }} if not renewed.</small>
@endcomponent
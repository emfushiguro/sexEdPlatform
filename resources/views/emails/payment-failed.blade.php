@component('mail::message')
# Payment Failed - Action Required

Hi {{ $user->first_name }},

We were unable to process your payment for your {{ $subscription->plan }} subscription.

## Payment Details
- **Amount:** ₱{{ $amount }}
- **Plan:** {{ ucfirst($subscription->plan) }} Subscription
- **Transaction ID:** {{ $payment->transaction_id }}
- **Failure Date:** {{ $payment->created_at->format('M d, Y') }}

## What happens next?

We'll automatically retry your payment in **{{ $nextRetryDays }} days**. To avoid any service interruption:

@component('mail::button', ['url' => $updatePaymentUrl])
Update Payment Method
@endcomponent

## Keep in mind:
- Your premium access remains active during the grace period
- We'll send you reminders before each retry attempt
- After 3 failed attempts, your subscription will be cancelled

## Need help?
If you have questions about this payment failure, please contact our support team.

@component('mail::button', ['url' => 'mailto:support@sexedplatform.com', 'color' => 'secondary'])
Contact Support
@endcomponent

Thank you for being a valued member!

Best regards,<br>
{{ config('app.name') }} Team

---
<small>This is an automated message. Please do not reply to this email.</small>
@endcomponent
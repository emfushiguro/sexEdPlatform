@component('mail::message')
# Payment Received

Hi {{ $user->name }},

Your payment of **₱{{ $amount }}** has been received successfully.

**Plan:** {{ $planName }}
**Paid:** {{ $paidAt }}
**Access Until:** {{ $endDate }}

@component('mail::button', ['url' => $invoiceUrl])
View My Subscription
@endcomponent

Thank you for subscribing!

{{ config('app.name') }}
@endcomponent

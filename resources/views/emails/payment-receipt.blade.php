@component('mail::message')
# Payment Received

Hi {{ $user->name }},

Your payment of **₱{{ $amount }}** has been received successfully.

@if($isGift ?? false)
This prepaid subscription is a gift for **{{ $giftBeneficiary }}**. Payments are final and non-refundable.
@endif

**Plan:** {{ $planName }}
**Paid:** {{ $paidAt }}
**Access Until:** {{ $endDate }}

@component('mail::button', ['url' => $invoiceUrl])
{{ ($isGift ?? false) ? 'View Gift Receipt' : 'View My Subscription' }}
@endcomponent

Thank you for subscribing!

{{ config('app.name') }}
@endcomponent

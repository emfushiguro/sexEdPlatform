@component('mail::message')
# Your Subscription Has Expired

Hi {{ $user->name }},

Your **{{ $planName }}** subscription expired on **{{ $expiredAt }}**.

You have lost access to premium content. Renew now to restore your access.

@component('mail::button', ['url' => $renewUrl])
Renew Subscription
@endcomponent

{{ config('app.name') }}
@endcomponent

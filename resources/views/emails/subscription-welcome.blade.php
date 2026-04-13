@component('mail::message')
# Welcome to Premium!

Hi {{ $user->name }},

Your **{{ $planName }}** subscription is now active.

**Access Until:** {{ $endDate }}

You now have full access to all premium content on the platform.

@component('mail::button', ['url' => $dashboardUrl])
Go to Dashboard
@endcomponent

{{ config('app.name') }}
@endcomponent

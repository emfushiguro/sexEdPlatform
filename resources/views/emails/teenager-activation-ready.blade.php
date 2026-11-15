<h1>Dependent account activation is ready</h1>

<p>The guardian relationship for {{ $child->name }} was approved. The dependent can now choose a password and continue identity verification.</p>

<p><a href="{{ $activationUrl }}">Open the activation page</a></p>

<p>One-time activation code: <strong>{{ $code }}</strong></p>

<x-activation-expiry :expires-at="$expiresAt" :live="false" />
<p>Time remaining when this email was generated. Open the activation page for the current countdown. The dependent's password remains private.</p>

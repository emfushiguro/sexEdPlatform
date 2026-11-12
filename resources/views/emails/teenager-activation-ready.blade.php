<h1>Teenager account activation is ready</h1>

<p>The guardian relationship for {{ $child->name }} was approved. The Teenager can now choose a password and continue identity verification.</p>

<p><a href="{{ $activationUrl }}">Open the activation page</a></p>

<p>One-time activation code: <strong>{{ $code }}</strong></p>

<p>This code expires {{ $expiresAt?->format('M d, Y H:i T') }}. Never share the Teenager's password or identity evidence.</p>
